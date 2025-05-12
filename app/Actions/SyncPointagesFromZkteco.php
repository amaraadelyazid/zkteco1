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
            ['uid' => '3001', 'timestamp' => now()->setTime(8, 13)->toDateTimeString()],
            ['uid' => '2001', 'timestamp' => now()->setTime(8, 0)->toDateTimeString()],
            ['uid' => '3002', 'timestamp' => now()->setTime(10, 0)->toDateTimeString()],
            ['uid' => '3001', 'timestamp' => now()->setTime(16, 02)->toDateTimeString()],
            ['uid' => '2001', 'timestamp' => now()->setTime(19, 0)->toDateTimeString()],
            ['uid' => '3002', 'timestamp' => now()->setTime(15, 0)->toDateTimeString()],
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
        $pointages = PointageBiometrique::whereDate('timestamp', now()->toDateString())->get();
        Log::info("Found {$pointages->count()} pointages for date: " . now()->toDateString());

        $groupes = $pointages->groupBy(function ($item) {
            return $item->user_type . '|' . $item->user_id . '|' . Carbon::parse($item->timestamp)->toDateString();
        });

        foreach ($groupes as $key => $group) {
            [$type, $id, $date] = explode('|', $key);
            Log::info("Processing presence for {$type}:{$id} on {$date}");

            $times = $group->sortBy('timestamp')->pluck('timestamp')->map(fn($t) => Carbon::parse($t));
            $checkIn = $times->first();
            $checkOut = $times->count() > 1 ? $times->last() : null;

            $shift = $this->getShift($type, $id);
            if ($shift && ! $this->isJourTravail($date, $shift)) {
                Log::info("Skipping presence for {$type}:{$id} on {$date}. Not a working day.");
                continue;
            }

            $heures = $this->calculerHeuresTravaillees($checkIn, $checkOut, $shift);
            $etatCheckIn = $this->determinerEtatCheckIn($checkIn, $shift);
            $anomalie = $this->detecterAnomalie($checkIn, $checkOut, $shift);

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
                        'anomalie_resolue' => false,
                        'name' => $userDetails['name'] ?? null, // Add name
                        'prenom' => $userDetails['prenom'] ?? null, // Add prenom
                    ]
                );
                Log::info("Created/Updated presence for {$type}:{$id} on {$date} with name: {$userDetails['name']}, prenom: {$userDetails['prenom']}");
            } catch (\Exception $e) {
                Log::error("Failed to create/update presence for {$type}:{$id} on {$date}: {$e->getMessage()}");
            }
        }
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
            return null;
        }

        $totalMinutes = $checkIn->diffInMinutes($checkOut);

        if ($shift->pause && $shift->heure_debut_pause && $shift->heure_fin_pause) {
            $pauseDebut = Carbon::parse($shift->heure_debut_pause)->setDateFrom($checkIn);
            $pauseFin = Carbon::parse($shift->heure_fin_pause)->setDateFrom($checkIn);
            $overlapStart = $checkIn->copy()->max($pauseDebut);
            $overlapEnd = $checkOut->copy()->min($pauseFin);
            if ($overlapEnd->greaterThan($overlapStart)) {
                $overlapMinutes = $overlapStart->diffInMinutes($overlapEnd);
                $totalMinutes -= $overlapMinutes;
            }
        } elseif ($shift->pause && $shift->duree_pause) {
            $totalMinutes -= $shift->duree_pause;
        }

        if ($totalMinutes < 0) {
            $totalMinutes = 0;
        }

        if ($totalMinutes < $shift->duree_min_presence) {
            return null;
        }

        $heures = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;

        return sprintf('%02d:%02d:00', $heures, $minutes);
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
            $heureDebut = Carbon::today()->setTimeFromTimeString($shift->heure_debut);
            $heureFin = Carbon::today()->setTimeFromTimeString($shift->heure_fin);
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