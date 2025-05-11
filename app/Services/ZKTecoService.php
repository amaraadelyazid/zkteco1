<?php

namespace App\Services;

use App\Models\dispositif_biometrique;
use App\Models\log;
use MehediJaman\LaravelZkteco\LaravelZkteco;
use Illuminate\Support\Facades\Log as LaravelLog;

class ZKTecoService
{
    private static function getZKTecoInstance(dispositif_biometrique $device): LaravelZkteco
    {
        return new LaravelZkteco($device->ip, $device->port);
    }

    /**
     * Teste la connexion à un dispositif ZKTeco
     */
    public static function testConnection(dispositif_biometrique $device): array
    {
        try {
            $zk = self::getZKTecoInstance($device);
            $connected = $zk->connect();
            
            if (!$connected) {
                throw new \Exception('Impossible de se connecter au dispositif');
            }

            $version = $zk->version();
            $zk->disconnect();
            
            log::logZKTecoAction(
                'test_connection',
                'Connexion réussie au dispositif',
                'info',
                ['version' => $version],
                $device->ip,
                $device->port
            );
            
            return [
                'success' => true,
                'version' => $version
            ];
        } catch (\Exception $e) {
            LaravelLog::error('Erreur de connexion ZKTeco: ' . $e->getMessage());
            log::logZKTecoError(
                'test_connection',
                'Erreur de connexion au dispositif: ' . $e->getMessage(),
                ['error' => $e->getMessage()],
                $device->ip,
                $device->port
            );
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Synchronise le statut du dispositif
     */
    public static function syncStatus(dispositif_biometrique $device): bool
    {
        try {
            $result = self::testConnection($device);
            if (!$result['success']) {
                throw new \Exception($result['message']);
            }

            $zk = self::getZKTecoInstance($device);
            $zk->connect();
            $zk->setStatus($device->status === 'active');
            $zk->disconnect();
            
            log::logZKTecoAction(
                'sync_status',
                'Statut synchronisé avec succès',
                'info',
                ['status' => $device->status],
                $device->ip,
                $device->port
            );
            
            return true;
        } catch (\Exception $e) {
            LaravelLog::error('Erreur de synchronisation du statut ZKTeco: ' . $e->getMessage());
            log::logZKTecoError(
                'sync_status',
                'Erreur de synchronisation du statut: ' . $e->getMessage(),
                ['error' => $e->getMessage(), 'status' => $device->status],
                $device->ip,
                $device->port
            );
            return false;
        }
    }

    /**
     * Récupère les pointages du dispositif
     */
    public static function getAttendance(dispositif_biometrique $device): array
    {
        try {
            $result = self::testConnection($device);
            if (!$result['success']) {
                throw new \Exception($result['message']);
            }

            $zk = self::getZKTecoInstance($device);
            $zk->connect();
            $attendance = $zk->getAttendance();
            $zk->disconnect();
            
            $count = count($attendance);
            log::logZKTecoAction(
                'get_attendance',
                "Récupération de $count pointages",
                'info',
                ['count' => $count],
                $device->ip,
                $device->port
            );
            
            return [
                'success' => true,
                'data' => $attendance
            ];
        } catch (\Exception $e) {
            LaravelLog::error('Erreur de récupération des pointages ZKTeco: ' . $e->getMessage());
            log::logZKTecoError(
                'get_attendance',
                'Erreur de récupération des pointages: ' . $e->getMessage(),
                ['error' => $e->getMessage()],
                $device->ip,
                $device->port
            );
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Récupère les utilisateurs du dispositif
     */
    public static function getUsers(dispositif_biometrique $device): array
    {
        try {
            $result = self::testConnection($device);
            if (!$result['success']) {
                throw new \Exception($result['message']);
            }

            $zk = self::getZKTecoInstance($device);
            $zk->connect();
            $users = $zk->getUser();
            $zk->disconnect();
            
            $count = count($users);
            log::logZKTecoAction(
                'get_users',
                "Récupération de $count utilisateurs",
                'info',
                ['count' => $count],
                $device->ip,
                $device->port
            );
            
            return [
                'success' => true,
                'data' => $users
            ];
        } catch (\Exception $e) {
            LaravelLog::error('Erreur de récupération des utilisateurs ZKTeco: ' . $e->getMessage());
            log::logZKTecoError(
                'get_users',
                'Erreur de récupération des utilisateurs: ' . $e->getMessage(),
                ['error' => $e->getMessage()],
                $device->ip,
                $device->port
            );
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Synchronise les données du dispositif
     */
    public static function syncDevice(dispositif_biometrique $device): array
    {
        try {
            $result = self::testConnection($device);
            if (!$result['success']) {
                throw new \Exception($result['message']);
            }

            $zk = self::getZKTecoInstance($device);
            $zk->connect();

            // Synchroniser le statut
            $zk->setStatus($device->status === 'active');

            // Récupérer les utilisateurs
            $users = $zk->getUser();
            $usersCount = count($users);

            // Récupérer les pointages
            $attendance = $zk->getAttendance();
            $attendanceCount = count($attendance);

            $zk->disconnect();
            
            log::logZKTecoAction(
                'sync_device',
                "Synchronisation complète réussie",
                'info',
                [
                    'users_count' => $usersCount,
                    'attendance_count' => $attendanceCount,
                    'status' => $device->status
                ],
                $device->ip,
                $device->port
            );
            
            return [
                'success' => true,
                'message' => "Synchronisation réussie. Utilisateurs: $usersCount, Pointages: $attendanceCount"
            ];
        } catch (\Exception $e) {
            LaravelLog::error('Erreur de synchronisation ZKTeco: ' . $e->getMessage());
            log::logZKTecoError(
                'sync_device',
                'Erreur de synchronisation complète: ' . $e->getMessage(),
                ['error' => $e->getMessage()],
                $device->ip,
                $device->port
            );
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
} 