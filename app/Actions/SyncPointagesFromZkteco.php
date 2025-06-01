<?php

namespace App\Actions;

use App\Models\dispositif_biometrique;
use Carbon\Carbon;
use App\Models\Presence;
use App\Models\PointageBiometrique;
use MehediJaman\LaravelZkteco\LaravelZkteco;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncPointagesFromZkteco
{
    public function __invoke(): void
    {
        set_time_limit(300);
        Log::info("Starting ZKTeco sync. Server timezone: " . config('app.timezone') . ", Date: " . now()->toDateString());

        $devices = dispositif_biometrique::all();
        Log::info("Found {$devices->count()} biometric devices");
        $hasRecords = false;

        if ($devices->isEmpty()) {
            Log::info('No ZKTeco devices configured. Using mock data.');
            $this->processMockData();
            $this->organiserPresences();
            return;
        }

        foreach ($devices as $device) {
            Log::info("Processing device: {$device->ip}:{$device->port}");
            if (! $this->isDeviceReachable($device->ip, $device->port)) {
                Log::warning("Device {$device->ip}:{$device->port} is not reachable");
                continue;
            }

            try {
                $zk = new LaravelZkteco($device->ip, $device->port);
                if (! $zk->connect()) {
                    Log::error("Failed to connect to device {$device->ip}:{$device->port}");
                    continue;
                }

                $records = $zk->getAttendance();
                Log::info("Retrieved " . count($records) . " records from {$device->ip}:{$device->port}");

                if (!empty($records)) {
                    $hasRecords = true;
                }

                foreach ($records as $record) {
                    $uid = $record['uid'];
                    $timestamp = Carbon::parse($record['timestamp']);
                    Log::info("Processing record: UID={$uid}, Timestamp={$timestamp}");

                    $user = $this->resolveUser($uid);
                    if (! $user) {
                        Log::warning("No user found for UID: {$uid}");
                        continue;
                    }

                    $exists = PointageBiometrique::where('user_type', $user['type'])
                        ->where('user_id', $user['id'])
                        ->where('timestamp', $timestamp)
                        ->exists();

                    if ($exists) {
                        Log::info("Pointage already exists for {$user['type']}:{$user['id']} at {$timestamp}");
                        continue;
                    }

                    try {
                        PointageBiometrique::create([
                            'user_type' => $user['type'],
                            'user_id' => $user['id'],
                            'timestamp' => $timestamp,
                        ]);
                        Log::info("Created pointage for {$user['type']}:{$user['id']} at {$timestamp}");
                    } catch (\Exception $e) {
                        Log::error("Failed to create pointage for UID: {$uid}, Timestamp: {$timestamp}. Error: {$e->getMessage()}");
                    }
                }

                $zk->disconnect();
            } catch (\Exception $e) {
                Log::error("Error processing device {$device->ip}:{$device->port}: {$e->getMessage()}");
                continue;
            }
        }

        if (! $hasRecords) {
            Log::info('No records retrieved from devices. Using mock data as fallback.');
            $this->processMockData();
        }

        $this->organiserPresences();
        Log::info("Completed ZKTeco sync");
    }

    protected function processMockData(): void
    {
        Log::info("Processing mock data for testing");
        $mockRecords = [
            ['uid' => '3001', 'timestamp' => now()->setTime(8, 13)->toDateTimeString()], // employe:1 check-in
            ['uid' => '2001', 'timestamp' => now()->setTime(8, 0)->toDateTimeString()], // grh:1 check-in
            ['uid' => '3002', 'timestamp' => now()->setTime(20, 0)->toDateTimeString()], // employe:2 check-in (same day)
            ['uid' => '3001', 'timestamp' => now()->setTime(15, 2)->toDateTimeString()], // employe:1 check-out
            ['uid' => '2001', 'timestamp' => now()->setTime(17, 0)->toDateTimeString()], // grh:1 check-out
            ['uid' => '3002', 'timestamp' => now()->addDay()->setTime(4, 0)->toDateTimeString()], // employe:2 check-out (next day)
        ];

        foreach ($mockRecords as $record) {
            $uid = $record['uid'];
            $timestamp = Carbon::parse($record['timestamp']);
            Log::info("Processing mock record: UID={$uid}, Timestamp={$timestamp}");

            $user = $this->resolveUser($uid);
            if (! $user) {
                Log::warning("Utilisateur inconnu pour uid : {$uid} (mock data)");
                continue;
            }

            $exists = PointageBiometrique::where('user_type', $user['type'])
                ->where('user_id', $user['id'])
                ->where('timestamp', $timestamp)
                ->exists();

            if ($exists) {
                Log::info("Mock pointage already exists for {$user['type']}:{$user['id']} at {$timestamp}");
                continue;
            }

            try {
                PointageBiometrique::create([
                    'user_type' => $user['type'],
                    'user_id' => $user['id'],
                    'timestamp' => $timestamp,
                ]);
                Log::info("Created mock pointage for {$user['type']}:{$user['id']} at {$timestamp}");
            } catch (\Exception $e) {
                Log::error("Failed to create mock pointage for UID: {$uid}, Timestamp: {$timestamp}. Error: {$e->getMessage()}");
            }
        }
    }

    protected function resolveUser(string $uid): ?array
    {
        if ($employe = DB::table('employes')->where('biometric_id', $uid)->first()) {
            return [
                'type' => 'employe',
                'id' => $employe->id,
                'shift_id' => $employe->shift_id,
                'name' => $employe->name,
                'prenom' => $employe->prenom,
            ];
        }

        if ($grh = DB::table('grhs')->where('biometric_id', $uid)->first()) {
            return [
                'type' => 'grh',
                'id' => $grh->id,
                'shift_id' => $grh->shift_id,
                'name' => $grh->name,
                'prenom' => $grh->prenom,
            ];
        }

        return null;
    }

    protected function organiserPresences(): void
    {
        $pointages = PointageBiometrique::whereDate('timestamp', '>=', now()->toDateString())
            ->whereDate('timestamp', '<=', now()->addDay()->toDateString())
            ->get();
        Log::info("Found {$pointages->count()} pointages for date range: " . now()->toDateString() . " to " . now()->addDay()->toDateString());

        $groupes = $pointages->groupBy(function ($item) {
            $timestamp = Carbon::parse($item->timestamp);
            $shift = $this->getShift($item->user_type, $item->user_id);
            if ($shift && Carbon::parse($shift->heure_fin)->lessThanOrEqualTo(Carbon::parse($shift->heure_debut))) {
                // For night shifts, group by the date of the shift start (checkIn date)
                $shiftStart = Carbon::parse($shift->heure_debut)->setDateFrom($timestamp);
                if ($timestamp->hour < 12) { // Assume checkOut is early next day (e.g., 04:00)
                    $shiftStart->subDay();
                }
                return $item->user_type . '|' . $item->user_id . '|' . $shiftStart->toDateString();
            }
            return $item->user_type . '|' . $item->user_id . '|' . $timestamp->toDateString();
        });

        foreach ($groupes as $key => $group) {
            [$type, $id, $date] = explode('|', $key);
            Log::info("Processing presence for {$type}:{$id} on {$date}");

            $times = $group->sortBy('timestamp')->pluck('timestamp')->map(fn($t) => Carbon::parse($t));
            $checkIn = $times->first();
            $checkOut = $times->count() > 1 ? $times->last() : null;

            $shift = $this->getShift($type, $id);
            $isWorkingDay = $shift && $this->isJourTravail($date, $shift);

            $heures = $this->calculerHeuresTravaillees($checkIn, $checkOut, $shift);
            $etatCheckIn = $this->determinerEtatCheckIn($checkIn, $shift);
            $anomalie = $this->detecterAnomalie($checkIn, $checkOut, $shift);

            // Set anomaly for non-working days
            if ($shift && !$isWorkingDay) {
                $anomalie = 'hors_shift';
                Log::info("Set anomalie_type to 'hors_shift' for {$type}:{$id} on {$date}: Non-working day.");
            }

            // Calculate theoretical shift hours and check for 'incomplet' on working days
            if ($isWorkingDay && $checkIn && $checkOut && $shift && $heures) {
                $heureDebut = Carbon::parse($shift->heure_debut)->setDateFrom(Carbon::parse($date));
                $heureFin = Carbon::parse($shift->heure_fin)->setDateFrom(Carbon::parse($date));
                if ($heureFin->lessThanOrEqualTo($heureDebut)) {
                    $heureFin->addDay();
                }
                $theoreticalMinutes = $heureDebut->diffInMinutes($heureFin);
                if ($shift->pause && $shift->heure_debut_pause && $shift->heure_fin_pause) {
                    $pauseDebut = Carbon::parse($shift->heure_debut_pause)->setDateFrom(Carbon::parse($date));
                    $pauseFin = Carbon::parse($shift->heure_fin_pause)->setDateFrom(Carbon::parse($date));
                    if ($pauseFin->lessThanOrEqualTo($pauseDebut)) {
                        $pauseFin->addDay();
                    }
                    $pauseMinutes = $pauseDebut->diffInMinutes($pauseFin);
                    $theoreticalMinutes -= $pauseMinutes;
                } elseif ($shift->pause && $shift->duree_pause) {
                    $theoreticalMinutes -= $shift->duree_pause;
                }

                $adjustedMinPresence = $theoreticalMinutes - ($shift->tolerance_retard ?? 0) - ($shift->depart_anticipe ?? 0);
                $workedMinutes = $this->parseHeuresToMinutes($heures);

                if ($workedMinutes < $adjustedMinPresence) {
                    $anomalie = 'incomplet';
                    Log::info("Set anomalie_type to 'incomplet' for {$type}:{$id} on {$date}: worked {$workedMinutes} minutes < adjusted minimum {$adjustedMinPresence} minutes");
                }
            }

            // Set anomalie_resolue to false for hors_shift, true for no anomalies
            $anomalieResolue = $anomalie === 'hors_shift' ? false : ($anomalie === null);

            // Fetch user details to populate name and prenom
            $userDetails = $this->resolveUserByTypeAndId($type, $id);

            try {
                Presence::updateOrCreate(
                    [
                        'user_type' => $type,
                        'user_id' => $id,
                        'date' => $date,
                    ],
                    [
                        'check_in' => $checkIn,
                        'etat_check_in' => $etatCheckIn,
                        'check_out' => $checkOut,
                        'etat_check_out' => $checkOut ? $this->determinerEtatCheckOut($checkOut, $shift) : null,
                        'heures_travaillees' => $heures,
                        'anomalie_type' => $anomalie,
                        'anomalie_resolue' => $anomalieResolue,
                        'name' => $userDetails['name'] ?? null,
                        'prenom' => $userDetails['prenom'] ?? null,
                    ]
                );
                Log::info("Created/Updated presence for {$type}:{$id} on {$date} with name: {$userDetails['name']}, prenom: {$userDetails['prenom']}, anomalie_resolue: " . ($anomalieResolue ? 'true' : 'false'));
            } catch (\Exception $e) {
                Log::error("Failed to create/update presence for {$type}:{$id} on {$date}: {$e->getMessage()}");
            }
        }
    }

    protected function parseHeuresToMinutes(?string $heures): int
    {
        if (!$heures) {
            return 0;
        }
        [$hours, $minutes] = explode(':', $heures);
        return (int)$hours * 60 + (int)$minutes;
    }

    protected function resolveUserByTypeAndId(string $type, int $id): array
    {
        $table = $type === 'employe' ? 'employes' : 'grhs';
        $user = DB::table($table)->where('id', $id)->first();
        return [
            'name' => $user->name ?? null,
            'prenom' => $user->prenom ?? null,
        ];
    }

    protected function getShift(string $userType, int $userId): ?object
    {
        $table = $userType === 'employe' ? 'employes' : 'grhs';
        $shiftId = DB::table($table)->where('id', $userId)->value('shift_id');
        return $shiftId ? DB::table('shifts')->find($shiftId) : null;
    }

    protected function isJourTravail(string $date, object $shift): bool
    {
        $joursTravail = json_decode($shift->jours_travail, true);
        $jour = strtolower(Carbon::parse($date)->locale('fr')->dayName);
        return in_array($jour, $joursTravail);
    }

    protected function determinerEtatCheckIn(?Carbon $checkIn, ?object $shift): string
    {
        if (! $checkIn || ! $shift) {
            return 'absent';
        }

        $heureDebut = Carbon::today()->setTimeFromTimeString($shift->heure_debut);
        $tolerance = $shift->tolerance_retard;
        $diffMinutes = $checkIn->diffInMinutes($heureDebut, false);

        return $diffMinutes < -$tolerance ? 'retard' : 'present';
    }

    protected function determinerEtatCheckOut(?Carbon $checkOut, ?object $shift): string
    {
        if (! $checkOut || ! $shift) {
            return 'absent';
        }

        $heureFin = Carbon::today()->setTimeFromTimeString($shift->heure_fin);
        $departAnticipe = $shift->depart_anticipe;
        $diffMinutes = $checkOut->diffInMinutes($heureFin, false);

        return $diffMinutes > $departAnticipe ? 'retard' : 'present';
    }

    protected function calculerHeuresTravaillees(?Carbon $checkIn, ?Carbon $checkOut, ?object $shift): ?string
    {
        if (! $checkIn || ! $checkOut || ! $shift) {
            Log::warning("Missing data for hours calculation: checkIn=" . ($checkIn ? $checkIn->toDateTimeString() : 'null') .
                ", checkOut=" . ($checkOut ? $checkOut->toDateTimeString() : 'null') .
                ", shift=" . ($shift ? json_encode($shift) : 'null'));
            return null;
        }

        // Log shift details for debugging
        Log::info("Calculating hours for shift ID: {$shift->id}, heure_debut: {$shift->heure_debut}, heure_fin: {$shift->heure_fin}");

        // Set shift start and end times
        $heureDebut = Carbon::parse($shift->heure_debut)->setDateFrom($checkIn);
        $heureFin = Carbon::parse($shift->heure_fin)->setDateFrom($checkIn);
        if ($heureFin->lessThanOrEqualTo($heureDebut)) {
            $heureFin->addDay();
            Log::info("Adjusted heure_fin for night shift: {$shift->heure_fin} -> {$heureFin->toDateTimeString()}");
        }

        // Adjust start time to shift start (exclude hours before heure_debut)
        $effectiveStart = $checkIn->max($heureDebut);
        Log::info("Effective start time: {$effectiveStart->toDateTimeString()} (checkIn: {$checkIn->toDateTimeString()}, heure_debut: {$heureDebut->toDateTimeString()})");

        // Adjust end time to shift end or check_out, whichever is earlier
        $adjustedCheckOut = $checkOut->copy();
        if ($checkOut->lessThanOrEqualTo($checkIn)) {
            $adjustedCheckOut->addDay();
            Log::info("Adjusted checkOut for night shift: {$checkOut->toDateTimeString()} -> {$adjustedCheckOut->toDateTimeString()}");
        }
        $effectiveEnd = $adjustedCheckOut->min($heureFin);
        Log::info("Effective end time: {$effectiveEnd->toDateTimeString()} (checkOut: {$adjustedCheckOut->toDateTimeString()}, heure_fin: {$heureFin->toDateTimeString()})");

        // Calculate total minutes worked within shift
        if ($effectiveEnd->lessThanOrEqualTo($effectiveStart)) {
            Log::warning("Effective end time is before or equal to start time. Setting hours to 0.");
            return '00:00:00';
        }
        $totalMinutes = $effectiveStart->diffInMinutes($effectiveEnd);
        Log::info("Initial total minutes: {$totalMinutes} (from {$effectiveStart->toDateTimeString()} to {$effectiveEnd->toDateTimeString()})");

        // Handle pause if defined
        if ($shift->pause && $shift->heure_debut_pause && $shift->heure_fin_pause) {
            $pauseDebut = Carbon::parse($shift->heure_debut_pause)->setDateFrom($checkIn);
            $pauseFin = Carbon::parse($shift->heure_fin_pause)->setDateFrom($checkIn);
            if ($pauseFin->lessThanOrEqualTo($pauseDebut)) {
                $pauseFin->addDay();
                Log::info("Adjusted pauseFin for night shift: {$shift->heure_fin_pause} -> {$pauseFin->toDateTimeString()}");
            }
            $overlapStart = $effectiveStart->max($pauseDebut);
            $overlapEnd = $effectiveEnd->min($pauseFin);
            if ($overlapEnd->greaterThan($overlapStart)) {
                $overlapMinutes = $overlapStart->diffInMinutes($overlapEnd);
                $totalMinutes -= $overlapMinutes;
                Log::info("Subtracted pause: {$overlapMinutes} minutes (from {$overlapStart->toDateTimeString()} to {$overlapEnd->toDateTimeString()})");
            }
        } elseif ($shift->pause && $shift->duree_pause) {
            $totalMinutes -= $shift->duree_pause;
            Log::info("Subtracted fixed pause duration: {$shift->duree_pause} minutes");
        }

        // Ensure non-negative minutes
        if ($totalMinutes < 0) {
            Log::warning("Negative total minutes calculated: {$totalMinutes}. Setting to 0.");
            $totalMinutes = 0;
        }

        // Convert to HH:MM:00 format
        $heures = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;
        $result = sprintf('%02d:%02d:00', $heures, $minutes);
        Log::info("Final calculated hours: {$result}");

        return $result;
    }

    protected function detecterAnomalie(?Carbon $in, ?Carbon $out, ?object $shift): ?string
    {
        if (! $in && ! $out) {
            return 'absent';
        }
        if ($in && ! $out) {
            return 'unique_pointage';
        }
        if (! $in && $out) {
            return 'incomplet';
        }
        if ($in && $out && $shift) {
            $heureDebut = Carbon::parse($shift->heure_debut)->setDateFrom($in);
            $heureFin = Carbon::parse($shift->heure_fin)->setDateFrom($in);
            if ($heureFin->lessThanOrEqualTo($heureDebut)) {
                $heureFin->addDay();
            }
            $tolerance = $shift->tolerance_retard + 60;
            if ($in->lt($heureDebut->subMinutes($tolerance)) || $out->gt($heureFin->addMinutes($tolerance))) {
                return 'hors_shift';
            }
        }
        return null;
    }

    protected function isDeviceReachable(string $ip, int $port): bool
    {
        $connection = @fsockopen($ip, $port, $errno, $errstr, 2);
        if (is_resource($connection)) {
            fclose($connection);
            return true;
        }
        Log::error("Device {$ip}:{$port} is not reachable. Error: {$errstr} ({$errno})");
        return false;
    }
}