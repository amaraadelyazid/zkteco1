<?php

namespace App\Filament\Resources\FicheDePaieResource\Pages;

use App\Filament\Resources\FicheDePaieResource;
use App\Models\Employe;
use App\Models\Grh;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use App\Actions\CalculatePayslips;
use App\Actions\PayslipCalculationException;
use App\Actions\PayslipBusinessException;
use Carbon\Carbon;
use Illuminate\Support\Facades\Response;

class CalculateFicheDePaie extends Page implements Tables\Contracts\HasTable
{
    use InteractsWithTable;

    protected static string $resource = FicheDePaieResource::class;
    protected static string $view = 'filament.resources.fiche-de-paie-resource.pages.calculate-fiche-de-paie';

    public string $activeTab = 'employes';
    public array $payslip_preview = [];

    public function mount(): void
    {
        $this->activeTab = 'employes';
        $this->resetPayslipPreview();
    }

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetTable();
        $this->resetPayslipPreview();
    }

    public function resetPayslipPreview(): void
    {
        $this->payslip_preview = [];
    }

    protected function getTableActions(): array
    {
        return [
            Tables\Actions\Action::make('calculate')
                ->label('Calculer la paie')
                ->icon('heroicon-o-calculator')
                ->form(function () {
                    return [
                        TextInput::make('mois')
                            ->label('Mois')
                            ->required()
                            ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state . '-01')->format('Y-m') : null)
                            ->dehydrateStateUsing(fn ($state) => $state ? Carbon::parse($state)->format('Y-m') : null)
                            ->placeholder('YYYY-MM')
                            ->mask('9999-99'),
                        Select::make('mode')
                            ->label('Mode de calcul')
                            ->options([
                                'taux' => 'Taux heure sup',
                                'montant' => 'Montant heure sup',
                            ])
                            ->reactive(),
                        TextInput::make('taux_heure_sup')
                            ->label('Taux heure sup')
                            ->numeric()
                            ->minValue(0.01)
                            ->suffix('x')
                            ->visible(fn ($get) => $get('mode') === 'taux')
                            ->required(fn ($get) => $get('mode') === 'taux'),
                        TextInput::make('montant_heure_sup')
                            ->label('Montant heure sup')
                            ->numeric()
                            ->minValue(0)
                            ->suffix('€')
                            ->visible(fn ($get) => $get('mode') === 'montant')
                            ->required(fn ($get) => $get('mode') === 'montant'),
                    ];
                })
                ->action(function (array $data, $record) {
                    $this->calculatePayslip($data, $record);
                })
        ];
    }

    protected function calculatePayslip(array $data, $record): void
    {
        $this->resetPayslipPreview();
        
        try {
            $userType = $this->activeTab === 'grhs' ? 'grh' : 'employe';
            $userId = $record->id;
            $mois = $data['mois'] ?? null;
            $mode = $data['mode'] ?? null;
            $tauxHoraireSup = $mode === 'taux' ? floatval($data['taux_heure_sup']) : null;
            $montantHeuresSup = $mode === 'montant' ? floatval($data['montant_heure_sup']) : null;

            $calculatePayslips = new CalculatePayslips();
            $fiche = $calculatePayslips->run(
                $userType,
                $userId,
                $tauxHoraireSup,
                $montantHeuresSup,
                $mois
            );

            $previewData = $calculatePayslips->getPayslipPreviewData(
                $userType,
                $userId,
                $mois,
                $record
            );

            $this->payslip_preview = array_merge($previewData, [
                'fiche_id' => $fiche->id,
                'fiche_details' => $calculatePayslips->formatFinancialDetails($fiche),
            ]);

            Notification::make()
                ->title('Succès')
                ->body('Fiche de paie générée avec succès')
                ->success()
                ->send();

        } catch (PayslipBusinessException $e) {
            $this->resetPayslipPreview();
            Notification::make()
                ->title('Erreur métier')
                ->body($e->getMessage())
                ->warning()
                ->send();
        } catch (PayslipCalculationException $e) {
            $this->resetPayslipPreview();
            Notification::make()
                ->title('Erreur de calcul')
                ->body($e->getMessage())
                ->danger()
                ->send();
        } catch (\Throwable $e) {
            Log::error('Unexpected error in payslip calculation: ' . $e->getMessage());
            $this->resetPayslipPreview();
            Notification::make()
                ->title('Erreur')
                ->body('Une erreur inattendue est survenue')
                ->danger()
                ->send();
        }
    }

    public function exportCsv()
    {
        $preview = $this->payslip_preview;
        if (empty($preview)) {
            Notification::make()
                ->title('Erreur')
                ->body('Aucune donnée à exporter. Veuillez d\'abord calculer la paie.')
                ->danger()
                ->send();
            return;
        }
        
        $filename = 'fiche_paie_' . str_replace(' ', '_', $preview['employe'] ?? 'employe') . '_' . ($preview['mois'] ?? date('Y-m')) . '.csv';
        
        return Response::streamDownload(function () use ($preview) {
            echo "\xEF\xBB\xBF";
            $out = fopen('php://output', 'w');
            $delimiter = ';';
            
            $writeCSV = function($handle, $data) use ($delimiter) {
                fputcsv($handle, $data, $delimiter);
            };
            
            $this->generateCsvContent($out, $writeCSV, $preview);
            
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    protected function generateCsvContent($out, $writeCSV, array $preview): void
    {
        $calculatePayslips = new CalculatePayslips();

        // Section 1: Informations générales
        $writeCSV($out, ['FICHE DE PAIE']);
        $writeCSV($out, ['Référence', $preview['fiche_id'] ?? 'N/A']);
        $writeCSV($out, ['Période', isset($preview['mois']) ? Carbon::parse($preview['mois'] . '-01')->locale('fr')->isoFormat('MMMM YYYY') : 'N/A']);
        $writeCSV($out, ['Date de génération', $preview['fiche_details']['date_generation'] ?? date('d/m/Y H:i')]);
        $writeCSV($out, ['Statut', $this->translateStatus($preview['fiche_details']['status'] ?? 'en_attente')]);
        $writeCSV($out, []);
        
        // Section 2: Informations employé
        $writeCSV($out, ['INFORMATIONS EMPLOYÉ']);
        $writeCSV($out, ['Nom et prénom', $preview['employe'] ?? 'N/A']);
        $writeCSV($out, ['Poste', $preview['poste'] ?? 'N/A']);
        $writeCSV($out, ['Département', $preview['departement'] ?? 'N/A']);
        $writeCSV($out, ['Shift', $preview['shift'] ?? 'N/A']);
        $writeCSV($out, []);
        
        // Section 3: Résumé des heures
        $writeCSV($out, ['RÉSUMÉ DES HEURES']);
        $writeCSV($out, ['Heures à travailler (théoriques)', $preview['heures_theoriques'] ?? '0,00']);
        $writeCSV($out, ['Heures travaillées', $preview['heures_travaillees'] ?? '0,00']);
        $writeCSV($out, ['Heures supplémentaires', $preview['heures_sup'] ?? '0,00']);
        $writeCSV($out, ['Retards', $preview['retards'] ?? '0']);
        $writeCSV($out, ['Absences', $preview['absences'] ?? '0']);
        $writeCSV($out, []);
        
        // Section 4: Rémunération
        $writeCSV($out, ['RÉMUNÉRATION']);
        $writeCSV($out, ['Salaire de base', $preview['salaire_base'] . ' €']);
        $writeCSV($out, ['Montant heures travaillées', $preview['montant_base'] . ' €']);
        
        if (isset($preview['fiche_details']['taux_horaire_sup']) && $preview['fiche_details']['taux_horaire_sup']) {
            $writeCSV($out, ['Taux horaire supplémentaire', $preview['fiche_details']['taux_horaire_sup'] . 'x']);
        }
        if (isset($preview['fiche_details']['montant_heures_sup']) && $preview['fiche_details']['montant_heures_sup']) {
            $writeCSV($out, ['Montant heures supplémentaires', $preview['fiche_details']['montant_heures_sup'] . ' €']);
        }
        if (isset($preview['fiche_details']['prime']) && $preview['fiche_details']['prime']) {
            $writeCSV($out, ['Prime', $preview['fiche_details']['prime'] . ' €']);
        }
        if (isset($preview['fiche_details']['avance']) && $preview['fiche_details']['avance']) {
            $writeCSV($out, ['Avances', $preview['fiche_details']['avance'] . ' €']);
        }
        $writeCSV($out, ['Montant total', $preview['fiche_details']['montant'] . ' €']);
        $writeCSV($out, []);
        
        // Section 5: Détail des présences
        $writeCSV($out, ['DÉTAIL DES PRÉSENCES']);
        $writeCSV($out, [
            'Date', 
            'Jour', 
            'Type de jour',
            'Heures travaillées', 
            'Heure d\'arrivée', 
            'Statut arrivée',
            'Heure de départ', 
            'Statut départ',
            'Type d\'anomalie', 
            'Anomalie résolue',
            'Statut global'
        ]);
        
        if (isset($preview['presences']) && is_array($preview['presences'])) {
            foreach ($preview['presences'] as $row) {
                $date = isset($row['date']) ? Carbon::parse($row['date']) : null;
                
                if ($date) {
                    $jour = $date->locale('fr')->isoFormat('dddd');
                    $date_formatted = $date->format('d/m/Y');
                    $type_jour = $row['is_weekend'] ? 'Weekend' : 'Jour ouvré';
                    $anomalie_resolue = isset($row['anomalie_resolue']) && $row['anomalie_resolue'] ? 'Oui' : 'Non';
                    
                    $writeCSV($out, [
                        $date_formatted,
                        $jour,
                        $type_jour,
                        $row['duree'] ?? '0,00',
                        $row['check_in'] ?? '—',
                        $calculatePayslips->translateCheckStatus($row['etat_check_in'] ?? null),
                        $row['check_out'] ?? '—',
                        $calculatePayslips->translateCheckStatus($row['etat_check_out'] ?? null),
                        $calculatePayslips->translateAnomalyType($row['anomalie_type'] ?? null),
                        $row['anomalie_type'] ? $anomalie_resolue : '—',
                        $calculatePayslips->determinePresenceStatus($row),
                    ]);
                }
            }
        }
    }

    public function table(\Filament\Tables\Table $table): \Filament\Tables\Table
    {
        if ($this->activeTab === 'grhs') {
            $query = Grh::query()->with(['shift:id,nom']);
            $columns = [
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('name')->label('Nom')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('prenom')->label('Prénom')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('salaire')->label('Salaire')->money('EUR')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('shift.nom')->label('Shift')->sortable()->searchable()->formatStateUsing(fn ($state) => $state ?? '—'),
            ];
        } else {
            $query = Employe::query()->with(['departement:id,nom', 'shift:id,nom']);
            $columns = [
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('name')->label('Nom')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('prenom')->label('Prénom')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('poste')->label('Poste')->sortable()->searchable()->formatStateUsing(fn ($state) => $state ?? '—'),
                Tables\Columns\TextColumn::make('salaire')->label('Salaire')->money('EUR')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('departement.nom')->label('Département')->sortable()->searchable()->formatStateUsing(fn ($state) => $state ?? '—'),
                Tables\Columns\TextColumn::make('shift.nom')->label('Shift')->sortable()->searchable()->formatStateUsing(fn ($state) => $state ?? '—'),
            ];
        }

        return $table
            ->query($query)
            ->columns($columns)
            ->actions($this->getTableActions());
    }

    protected function translateStatus(string $status): string
    {
        return match($status) {
            'en_attente' => 'En attente',
            'validé' => 'Validé',
            'payé' => 'Payé',
            'annulé' => 'Annulé',
            default => ucfirst($status),
        };
    }
}