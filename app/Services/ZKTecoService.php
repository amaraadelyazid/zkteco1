<?php

namespace App\Services;

use App\Models\dispositif_biometrique;
use MehediJaman\LaravelZkteco\LaravelZkteco;
use Illuminate\Support\Facades\Log;

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
            
            return [
                'success' => true,
                'version' => $version
            ];
        } catch (\Exception $e) {
            Log::error('Erreur de connexion ZKTeco: ' . $e->getMessage());
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
            
            return true;
        } catch (\Exception $e) {
            Log::error('Erreur de synchronisation du statut ZKTeco: ' . $e->getMessage());
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
            
            return [
                'success' => true,
                'data' => $attendance
            ];
        } catch (\Exception $e) {
            Log::error('Erreur de récupération des pointages ZKTeco: ' . $e->getMessage());
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
            
            return [
                'success' => true,
                'data' => $users
            ];
        } catch (\Exception $e) {
            Log::error('Erreur de récupération des utilisateurs ZKTeco: ' . $e->getMessage());
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
            
            return [
                'success' => true,
                'message' => "Synchronisation réussie. Utilisateurs: $usersCount, Pointages: $attendanceCount"
            ];
        } catch (\Exception $e) {
            Log::error('Erreur de synchronisation ZKTeco: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
} 