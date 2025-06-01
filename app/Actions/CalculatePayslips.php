<?php

namespace App\Actions;

use App\Models\demande_conge;
use App\Models\Employe;
use App\Models\fiche_de_paie;
use App\Models\Grh;
use App\Models\Presence;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use DateTime;

class PayslipCalculationException extends Exception {}
class PayslipBusinessException extends Exception {}

class CalculatePayslips
{
    /**
     * Génère une fiche de paie totalement indépendante.
     * @param string $userType 'employe' ou 'grh'
     * @param int $userId
     * @param float|null $tauxHoraireSup
     * @param float|null $montantHeuresSup
     * @param string|null $mois format 'Y-m'
     * @return fiche_de_paie
     * @throws PayslipCalculationException|PayslipBusinessException
     */
     public function run(string $userType, int $userId, ?float $tauxHoraireSup = null, ?float $montantHeuresSup = null, ?string $mois = null): fiche_de_paie
    {
        try {
            Log::info("[Payslip] Début du traitement fiche de paie pour $userType #$userId, mois $mois");

            // Validation des paramètres
            $this->validateBasicParams($userType, $userId, $tauxHoraireSup, $montantHeuresSup);

            // Récupération et validation de l'utilisateur
            $user = $this->getValidatedUser($userType, $userId);
            $shift = $user->shift ?? null;
            $salaire = floatval($user->salaire);

            // Validation du shift
            $this->validateShift($shift);

            // Vérification du format du mois
            if ($mois === null) {
                $mois = Carbon::now()->subMonth()->format('Y-m');
            }
            if (!preg_match('/^\d{4}-\d{2}$/', $mois)) {
                throw new PayslipBusinessException("Format du mois invalide (attendu: YYYY-MM)");
            }

            // Récupération des présences du mois - du 1er au dernier jour du mois
            $start = Carbon::parse($mois . '-01');
            $end = $start->copy()->endOfMonth();
            $presences = Presence::where('user_type', $userType)
                ->where('user_id', $userId)
                ->whereBetween('date', [$start, $end])
                ->where('anomalie_resolue', true)
                ->get();

            // Récupération des congés approuvés pour ce mois
            $conges = $this->getApprovedLeaves($userType, $userId, $start, $end);

            // Vérification métier absences/retards - compte les jours ouvrés du mois selon le shift
            $joursOuvres = $this->getBusinessDaysInMonth($start, $end, $shift);
            $joursPointes = $presences->pluck('date')->unique()->count();
            $joursConges = $this->calculateDaysOnLeave($conges, $start, $end, $shift);

            if ($joursPointes + $joursConges === 0) {
                throw new PayslipBusinessException("Aucune présence ni congé enregistré pour ce mois.");
            }

            // Calcul du nombre d'heures travaillées (plus granulaire par jour)
            $dureeStandard = $this->getDureeStandard($shift);
            $breakDuration = $shift && $shift->pause && $shift->duree_pause ? ($shift->duree_pause / 60.0) : 0.0;
            $heuresParJour = [];
            $heuresSup = 0;
            $totalHeures = 0;

            // Créer un tableau indexé par date pour tous les jours du mois
            $currentDate = clone $start;
            while ($currentDate->lte($end)) {
                $dateStr = $currentDate->format('Y-m-d');
                $heuresParJour[$dateStr] = 0;
                $currentDate->addDay();
            }

            // Ajouter les heures travaillées
            foreach ($presences as $presence) {
                $dateStr = $presence->date->format('Y-m-d');
                $heuresJour = $this->parseTimeToHours($presence->heures_travaillees);
                
                // Deduct break hours if worked (assuming heures_travaillees includes break if not taken)
                $heuresEffective = $heuresJour;
                if ($breakDuration > 0 && $heuresJour >= $dureeStandard + $breakDuration) {
                    $heuresEffective -= $breakDuration;
                }
                
                $heuresParJour[$dateStr] = $heuresEffective;
                
                // Calcul heures sup par jour si dépasse la durée standard
                $heuresSupJour = max(0, $heuresEffective - $dureeStandard);
                $heuresSup += $heuresSupJour;
                $totalHeures += $heuresEffective;
            }

            // Ajouter les heures daemon pour les jours de congés (comme si l'employé avait travaillé normalement)
            $totalHeures += $this->addLeaveHours($conges, $start, $end, $shift, $dureeStandard, $heuresParJour);

            // Calcul du montant des heures normales et supplémentaires
            if ($montantHeuresSup !== null) {
                $montantBase = 0.0;
                $montantSup = $montantHeuresSup;
            } else {
                $tauxSup = $tauxHoraireSup ?? ($shift && $shift->taux_horaire_sup ? floatval($shift->taux_horaire_sup) : 1.5);
                $heuresNormales = $totalHeures - $heuresSup;
                $montantBase = round($salaire * $heuresNormales, 2);
                $montantSup = round($heuresSup * $salaire * $tauxSup, 2);
            }

            // Récupération des primes et avances du mois
            $primes = $this->getPrimes($user, $mois) ?? 0.0;
            $avance = $this->getAvances($user, $mois) ?? 0.0;

            // Création de la fiche de paie avec les montants du mois
            return $this->createPayslip($userType, $userId, $mois, $montantBase, $montantSup, $heuresSup, $primes, $avance, $tauxHoraireSup);

        } catch (PayslipBusinessException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error("[Payslip] Erreur inattendue: " . $e->getMessage());
            throw new PayslipCalculationException("Une erreur est survenue lors du calcul: " . $e->getMessage());
        }
    }

    /**
     * Get preview data for the Livewire component
     * This method provides all the data needed for display without duplicating calculation logic
     */
    public function getPayslipPreviewData(string $userType, int $userId, string $mois, $record): array
    {
        $start = Carbon::parse($mois . '-01');
        $end = $start->copy()->endOfMonth();
        $shift = $record->shift ?? null;

        // Get approved leaves
        $conges = $this->getApprovedLeaves($userType, $userId, $start, $end);

        // Fetch all presences for the month
        $presences = Presence::where('user_type', $userType)
            ->where('user_id', $userId)
            ->whereBetween('date', [$start, $end])
            ->orderBy('date')
            ->get();

        // Calculate statistics
        $stats = $this->calculateStatistics($presences, $conges, $start, $end, $shift);
        
        // Generate daily details
        $dailyDetails = $this->generateDailyDetails($presences, $conges, $start, $end, $shift);

        // Calculate financial details
        $salaireBase = floatval($record->salaire);
        $heuresNormales = $stats['heures_travaillees'] - $stats['heures_sup'];
        $montantBase = round($salaireBase * $heuresNormales, 2);

        return [
            'mois' => $mois,
            'employe' => $record->name . ' ' . $record->prenom,
            'heures_theoriques' => number_format($stats['heures_theoriques'], 2, ',', ''),
            'heures_travaillees' => number_format($stats['heures_travaillees'], 2, ',', ''),
            'heures_sup' => number_format($stats['heures_sup'], 2, ',', ''),
            'retards' => $stats['retards'],
            'absences' => $stats['absences'],
            'presences' => $dailyDetails,
            'salaire_base' => number_format($salaireBase, 2, ',', ''),
            'montant_base' => number_format($montantBase, 2, ',', ''),
            'poste' => $record->poste ?? 'N/A',
            'departement' => $record->departement ? $record->departement->nom : 'N/A',
            'shift' => $shift ? $shift->nom : 'N/A',
        ];
    }

    /**
     * Format financial details for consistent output
     */
    public function formatFinancialDetails(fiche_de_paie $fiche): array
    {
        return [
            'id' => $fiche->id,
            'montant' => number_format($fiche->montant, 2, ',', ' '),
            'avance' => number_format($fiche->avance ?? 0, 2, ',', ' '),
            'prime' => number_format($fiche->prime ?? 0, 2, ',', ' '),
            'status' => $fiche->status,
            'date_generation' => $fiche->date_generation ? Carbon::parse($fiche->date_generation)->format('d/m/Y H:i') : null,
            'taux_horaire_sup' => $fiche->taux_horaire_sup ? number_format($fiche->taux_horaire_sup, 2, ',', '') : null,
            'montant_heures_sup' => $fiche->montant_heures_sup ? number_format($fiche->montant_heures_sup, 2, ',', ' ') : null,
        ];
    }

    /**
     * Determine global status for a presence row
     */
    public function determinePresenceStatus(array $presence): string
    {
        $isWeekend = $presence['is_weekend'] ?? false;
        $anomalieType = $presence['anomalie_type'] ?? null;
        $duree = floatval(str_replace(',', '.', $presence['duree'] ?? '0'));

        if ($isWeekend) {
            return $duree > 0 ? 'Présent (weekend)' : 'Weekend';
        }

        if ($duree == 0 || $anomalieType === 'absent') {
            $status = 'Absent';
            if (!($presence['anomalie_resolue'] ?? true)) {
                $status .= ' (Non résolu)';
            }
            return $status;
        }

        if ($anomalieType === 'conge') {
            return 'Congé';
        }

        if ($anomalieType) {
            $status = $this->translateAnomalyType($anomalieType);
            return $status . ($presence['anomalie_resolue'] ? ' (Résolu)' : ' (Non résolu)');
        }

        if ($presence['etat_check_in'] === 'retard') {
            return 'Retard';
        }

        return 'Présent';
    }

    /**
     * Translation helper for anomaly types
     */
    public function translateAnomalyType(?string $type): string
    {
        if (!$type) return 'Normal';
        
        return match($type) {
            'unique_pointage' => 'Pointage unique',
            'absent' => 'Absent',
            'incomplet' => 'Incomplet',
            'hors_shift' => 'Hors shift',
            'conge' => 'Congé',
            default => ucfirst($type),
        };
    }

    /**
     * Translation helper for check status
     */
    public function translateCheckStatus(?string $status): string
    {
        if (!$status) return '—';
        
        return match($status) {
            'present' => 'Présent',
            'retard' => 'Retard',
            'absent' => 'Absent',
            default => ucfirst($status),
        };
    }

    /**
     * Calculate statistics for preview
     */
    protected function calculateStatistics($presences, $conges, Carbon $start, Carbon $end, $shift): array
    {
        $dureeStandard = $this->getDureeStandard($shift);
        $joursOuvres = $this->getBusinessDaysInMonth($start, $end, $shift);
        $heuresTheoriques = $joursOuvres * $dureeStandard;
        
        $workedHours = 0.0;
        $heuresSup = 0.0;
        $retards = 0;
        $absences = 0;

        // Process presences
        foreach ($presences as $presence) {
            $duree = $this->parseTimeToHours($presence->heures_travaillees);
            $workedHours += $duree;
            
            // Calculate overtime
            $sup = max(0, $duree - $dureeStandard);
            $heuresSup += $sup;
            
            // Check for late arrival
            if ($presence->etat_check_in === 'retard') {
                $retards++;
            }
        }

        // Add leave hours
        $heuresConges = $this->calculateLeaveHours($conges, $start, $end, $shift, $dureeStandard);
        $workedHours += $heuresConges;

        // Calculate absences (working days without presence or leave)
        $joursPointes = $presences->pluck('date')->unique()->count();
        $joursConges = $this->calculateDaysOnLeave($conges, $start, $end, $shift);
        $absences = max(0, $joursOuvres - $joursPointes - $joursConges);

        return [
            'heures_theoriques' => $heuresTheoriques,
            'heures_travaillees' => $workedHours,
            'heures_sup' => $heuresSup,
            'retards' => $retards,
            'absences' => $absences,
        ];
    }

    /**
     * Generate daily details for preview
     */
    protected function generateDailyDetails($presences, $conges, Carbon $start, Carbon $end, $shift): array
    {
        $dureeStandard = $this->getDureeStandard($shift);
        $dailyDetailsByDate = [];
        
        // Create a map of date => presence
        $presencesByDate = [];
        foreach ($presences as $presence) {
            $dateKey = $presence->date->format('Y-m-d');
            $presencesByDate[$dateKey] = $presence;
        }

        // Create leave days map
        $leaveDays = [];
        foreach ($conges as $conge) {
            $debutConge = max($start, Carbon::parse($conge->date_debut));
            $finConge = min($end, Carbon::parse($conge->date_fin));
            
            $jourConge = clone $debutConge;
            while ($jourConge->lte($finConge)) {
                if ($this->isWorkingDay($jourConge, $shift)) {
                    $leaveDays[$jourConge->format('Y-m-d')] = true;
                }
                $jourConge->addDay();
            }
        }

        // Process each day of the month
        $currentDate = clone $start;
        while ($currentDate->lte($end)) {
            $dateStr = $currentDate->format('Y-m-d');
            $presence = $presencesByDate[$dateStr] ?? null;
            $isWorkingDay = $this->isWorkingDay($currentDate, $shift);
            $isWeekend = !$isWorkingDay;
            $isLeaveDay = isset($leaveDays[$dateStr]);
            
            if ($presence && $presence->heures_travaillees) {
                $duree = $this->parseTimeToHours($presence->heures_travaillees);
                
                $dailyDetailsByDate[$dateStr] = [
                    'date' => $dateStr,
                    'duree' => number_format($duree, 2, ',', ''),
                    'is_weekend' => $isWeekend,
                    'check_in' => $presence->check_in ? $presence->check_in->format('H:i:s') : null,
                    'etat_check_in' => $presence->etat_check_in,
                    'check_out' => $presence->check_out ? $presence->check_out->format('H:i:s') : null,
                    'etat_check_out' => $presence->etat_check_out,
                    'anomalie_type' => $presence->anomalie_type,
                    'anomalie_resolue' => $presence->anomalie_resolue,
                ];
            } else {
                // No presence recorded
                $duree = $isLeaveDay ? $dureeStandard : 0;
                $anomalieType = null;
                
                if ($isLeaveDay) {
                    $anomalieType = 'conge';
                } elseif ($isWorkingDay) {
                    $anomalieType = 'absent';
                }
                
                $dailyDetailsByDate[$dateStr] = [
                    'date' => $dateStr,
                    'duree' => number_format($duree, 2, ',', ''),
                    'is_weekend' => $isWeekend,
                    'check_in' => null,
                    'etat_check_in' => null,
                    'check_out' => null,
                    'etat_check_out' => null,
                    'anomalie_type' => $anomalieType,
                    'anomalie_resolue' => $isLeaveDay ? true : ($isWorkingDay ? false : null),
                ];
            }
            
            $currentDate->addDay();
        }

        // Convert to indexed array and sort by date
        $dailyDetails = array_values($dailyDetailsByDate);
        usort($dailyDetails, function($a, $b) {
            return strcmp($a['date'], $b['date']);
        });

        return $dailyDetails;
    }

    /**
     * Get approved leaves for the period
     */
    protected function getApprovedLeaves(string $userType, int $userId, Carbon $start, Carbon $end): \Illuminate\Database\Eloquent\Collection
    {
        $conges = collect();
        
        if ($userType === 'employe') {
            $conges = demande_conge::where('employe_id', $userId)
                ->whereIn('status', ['approuvee', 'approuvée', 'Approuvée', 'APPROUVEE'])
                ->where(function ($query) use ($start, $end) {
                    $query->whereBetween('date_debut', [$start, $end])
                        ->orWhereBetween('date_fin', [$start, $end])
                        ->orWhere(function ($q) use ($start, $end) {
                            $q->where('date_debut', '<', $start)
                              ->where('date_fin', '>', $end);
                        });
                })
                ->get();
        }

        Log::info("[Payslip] Found " . $conges->count() . " approved leaves for $userType #$userId");
        return $conges;
    }

    /**
     * Add leave hours to total worked hours
     */
    protected function addLeaveHours($conges, Carbon $start, Carbon $end, $shift, float $dureeStandard, array &$heuresParJour): float
    {
        $totalLeaveHours = 0;
        
        Log::info("[Payslip] Processing " . count($conges) . " approved leaves");
        foreach ($conges as $conge) {
            $debutConge = max($start, Carbon::parse($conge->date_debut));
            $finConge = min($end, Carbon::parse($conge->date_fin));
            
            Log::info("[Payslip] Processing leave ID={$conge->id}: adjusted period from {$debutConge->format('Y-m-d')} to {$finConge->format('Y-m-d')}");
            
            $jourConge = clone $debutConge;
            while ($jourConge->lte($finConge)) {
                $dateStr = $jourConge->format('Y-m-d');
                $carbonDate = $this->ensureCarbon($jourConge);
                
                // Only count working days according to shift
                if ($this->isWorkingDay($carbonDate, $shift)) {
                    // If not already counted as worked day
                    if ($heuresParJour[$dateStr] == 0) {
                        $heuresParJour[$dateStr] = $dureeStandard;
                        $totalLeaveHours += $dureeStandard;
                        Log::info("[Payslip] Leave day added: {$dateStr} = {$dureeStandard}h");
                    } else {
                        Log::info("[Payslip] Leave day ignored (already worked): {$dateStr}");
                    }
                } else {
                    Log::info("[Payslip] Leave day ignored (non-working day): {$dateStr}");
                }
                
                $jourConge->addDay();
            }
        }
        
        return $totalLeaveHours;
    }

    /**
     * Calculate leave hours for statistics
     */
    protected function calculateLeaveHours($conges, Carbon $start, Carbon $end, $shift, float $dureeStandard): float
    {
        $totalLeaveHours = 0;
        
        foreach ($conges as $conge) {
            $debutConge = max($start, Carbon::parse($conge->date_debut));
            $finConge = min($end, Carbon::parse($conge->date_fin));
            
            $jourConge = clone $debutConge;
            while ($jourConge->lte($finConge)) {
                if ($this->isWorkingDay($jourConge, $shift)) {
                    $totalLeaveHours += $dureeStandard;
               

 }
                $jourConge->addDay();
            }
        }
        
        return $totalLeaveHours;
    }

    /**
     * Create payslip record
     */
    protected function createPayslip(string $userType, int $userId, string $mois, float $montantBase, float $montantSup, float $heuresSup, float $primes, float $avance, ?float $tauxHoraireSup): fiche_de_paie
    {
        DB::beginTransaction();
        try {
            $fiche = fiche_de_paie::create([
                'user_type' => $userType,
                'user_id' => $userId,
                'mois' => $mois,
                'montant' => max(0, $montantBase + $montantSup + $primes - $avance),
                'heures_sup' => $heuresSup,
                'prime' => $primes,
                'avance' => $avance,
                'taux_horaire_sup' => $tauxHoraireSup,
                'montant_heures_sup' => $montantSup,
                'status' => 'en_attente',
                'date_generation' => now(),
            ]);
            DB::commit();
            Log::info("[Payslip] Payslip created successfully for $userType #$userId, payslip #{$fiche->id}");
            return $fiche;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw new PayslipCalculationException("Error creating payslip: " . $e->getMessage());
        }
    }

    /**
     * Valide les paramètres de base
     */
    protected function validateBasicParams(string $userType, int $userId, ?float $tauxHoraireSup, ?float $montantHeuresSup): void
    {
        if (!in_array($userType, ['employe', 'grh'])) {
            throw new PayslipBusinessException("Type utilisateur invalide");
        }
        if ($userId <= 0) {
            throw new PayslipBusinessException("ID utilisateur invalide");
        }
        if ($tauxHoraireSup !== null && $montantHeuresSup !== null) {
            throw new PayslipBusinessException("Spécifiez soit un taux heure sup, soit un montant heure sup, jamais les deux.");
        }
    }

    /**
     * Récupère et valide l'utilisateur
     */
    protected function getValidatedUser(string $userType, int $userId)
    {
        $user = $userType === 'employe'
            ? Employe::with(['shift'])->findOrFail($userId)
            : Grh::with(['shift'])->findOrFail($userId);

        if (!is_numeric($user->salaire) || floatval($user->salaire) <= 0) {
            throw new PayslipBusinessException("Salaire de base manquant ou invalide");
        }

        return $user;
    }

    /**
     * Valide les données du shift
     */
    protected function validateShift($shift): void
    {
        if (!$shift) {
            throw new PayslipBusinessException("Aucun shift n'est assigné à cet utilisateur");
        }
        
        if (!$shift->heure_debut || !$shift->heure_fin) {
            throw new PayslipBusinessException("Le shift doit avoir une heure de début et de fin");
        }
        
        // Log des heures pour le débogage
        Log::info("Validation shift: heure_debut={$shift->heure_debut}, heure_fin={$shift->heure_fin}");
        
        // Calculer la durée du shift en minutes en utilisant une méthode alternative
        $heureDebut = Carbon::parse($shift->heure_debut);
        $heureFin = Carbon::parse($shift->heure_fin);
        
        // Convertir les heures en minutes depuis minuit
        $debutMinutes = $heureDebut->hour * 60 + $heureDebut->minute;
        $finMinutes = $heureFin->hour * 60 + $heureFin->minute;
        
        // Si l'heure de fin est avant l'heure de début, on suppose que c'est le jour suivant
        if ($finMinutes < $debutMinutes) {
            $finMinutes += 24 * 60; // Ajouter 24 heures en minutes
        }
        
        // Calculer la durée en minutes
        $dureeShiftMinutes = $finMinutes - $debutMinutes;
        
        Log::info("Durée du shift calculée manuellement: {$dureeShiftMinutes} minutes");
        
        // Ne vérifier la durée de pause que si la pause est activée
        if ($shift->pause && $shift->duree_pause) {
            // Vérifier si la durée de pause est supérieure à la durée totale du shift
            if ($shift->duree_pause >= $dureeShiftMinutes) {
                Log::warning("Validation shift échouée: duree_pause={$shift->duree_pause}, dureeShiftMinutes={$dureeShiftMinutes}");
                throw new PayslipBusinessException("La durée de pause ne peut pas être supérieure à la durée totale du shift");
            }
        }
        
        // Validation des jours de travail
        if (!$shift->jours_travail) {
            throw new PayslipBusinessException("Le shift doit définir au moins un jour de travail");
        }

        // Gérer le cas où jours_travail est déjà un array ou une string JSON
        if (is_array($shift->jours_travail)) {
            $joursArray = $shift->jours_travail;
        } else {
            $joursArray = json_decode($shift->jours_travail, true);
        }

        if (!is_array($joursArray) || empty($joursArray)) {
            throw new PayslipBusinessException("Le shift doit définir au moins un jour de travail");
        }
    }

    /**
     * Récupère les primes de l'utilisateur pour le mois donné
     */
    protected function getPrimes($user, ?string $mois = null): float
    {
        $mois = $mois ?? Carbon::now()->format('Y-m');
        return (float) \App\Models\Prime::where('user_type', $user instanceof \App\Models\Employe ? 'employe' : 'grh')
            ->where('user_id', $user->id)
            ->where('mois', $mois)
            ->sum('montant');
    }

    protected function getAvances($user, ?string $mois = null): float
    {
        $mois = $mois ?? Carbon::now()->format('Y-m');
        return (float) \App\Models\Avance::where('user_type', $user instanceof \App\Models\Employe ? 'employe' : 'grh')
            ->where('user_id', $user->id)
            ->where('mois', $mois)
            ->sum('montant');
    }

    /**
     * Calcule le nombre de jours de congés dans la période
     */
    protected function calculateDaysOnLeave($conges, Carbon $start, Carbon $end, $shift): int
    {
        $joursConges = 0;
        
        foreach ($conges as $conge) {
            $debutConge = max($start, Carbon::parse($conge->date_debut));
            $finConge = min($end, Carbon::parse($conge->date_fin));
            
            $jourConge = clone $debutConge;
            while ($jourConge->lte($finConge)) {
                // Convertir DateTime en Carbon si nécessaire
                $carbonDate = $this->ensureCarbon($jourConge);
                
                if ($this->isWorkingDay($carbonDate, $shift)) {
                    $joursConges++;
                }
                
                $jourConge->addDay();
            }
        }
        
        return $joursConges;
    }

    /**
     * Vérifie si un jour est un jour ouvré selon le shift
     * @param Carbon|DateTime $date
     * @param mixed $shift
     * @return bool
     */
    protected function isWorkingDay($date, $shift): bool
    {
        // Convertir en Carbon si c'est un DateTime
        $date = $this->ensureCarbon($date);
        
        if (!$shift || !$shift->jours_travail) {
            // Fallback: lun-ven si pas de shift ou jours non définis
            return !in_array($date->format('N'), [6, 7]);
        }
        
        // Gérer le cas où jours_travail est déjà un array ou une string JSON
        if (is_array($shift->jours_travail)) {
            $joursShift = $shift->jours_travail;
        } else {
            $joursShift = json_decode($shift->jours_travail, true);
        }

        if (!is_array($joursShift)) {
            return !in_array($date->format('N'), [6, 7]);
        }
        
        // Mapping des jours français vers numéros ISO
        $jourMapping = [
            'lundi' => 1,
            'mardi' => 2,
            'mercredi' => 3,
            'jeudi' => 4,
            'vendredi' => 5,
            'samedi' => 6,
            'dimanche' => 7
        ];
        
        $numeroJour = (int) $date->format('N');
        
        foreach ($joursShift as $jour) {
            if (isset($jourMapping[strtolower($jour)]) && $jourMapping[strtolower($jour)] === $numeroJour) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Convertit un temps en format texte en heures décimales
     */
    protected function parseTimeToHours(?string $time): float
    {
        if (!$time) return 0.0;
        $parts = explode(':', $time);
        if (count($parts) !== 3) return 0.0;
        [$h, $m, $s] = array_map('intval', $parts);
        return $h + ($m / 60) + ($s / 3600);
    }

    /**
     * Calcule la durée standard de travail selon le shift
     */
    protected function getDureeStandard($shift): float
    {
        if (!$shift || !$shift->heure_debut || !$shift->heure_fin) return 8.0;
    
        $heureDebut = Carbon::parse($shift->heure_debut);
        $heureFin = Carbon::parse($shift->heure_fin);
        
        // Convertir les heures en minutes depuis minuit
        $debutMinutes = $heureDebut->hour * 60 + $heureDebut->minute;
        $finMinutes = $heureFin->hour * 60 + $heureFin->minute;
        
        // Si l'heure de fin est avant l'heure de début, on suppose que c'est le jour suivant
        if ($finMinutes < $debutMinutes) {
            $finMinutes += 24 * 60; // Ajouter 24 heures en minutes
        }
        
        // Calculer la durée en heures
        $duree = ($finMinutes - $debutMinutes) / 60.0;
        
        // Soustraire la pause seulement si l'employé est éligible ET qu'une durée est définie
        if ($shift->pause && $shift->duree_pause) {
            $duree -= ($shift->duree_pause / 60.0);
        }
        
        return max(1, $duree);
    }

    /**
     * Calcule le nombre de jours ouvrés dans le mois selon le shift
     */
    protected function getBusinessDaysInMonth(Carbon $start, Carbon $end, $shift): int
    {
        $days = 0;
        $currentDate = clone $start;
        while ($currentDate->lte($end)) {
            // Convertir DateTime en Carbon si nécessaire
            $carbonDate = $this->ensureCarbon($currentDate);
            
            if ($this->isWorkingDay($carbonDate, $shift)) {
                $days++;
            }
            
            $currentDate->addDay();
        }
        return $days;
    }
    
    /**
     * Assure qu'une date est un objet Carbon
     * @param Carbon|DateTime $date
     * @return Carbon
     */
    protected function ensureCarbon($date): Carbon
    {
        if ($date instanceof Carbon) {
            return $date;
        }
        
        if ($date instanceof DateTime) {
            return Carbon::instance($date);
        }
        
        // Si c'est une string ou autre chose, essayer de parser
        return Carbon::parse($date);
    }
}