<?php

namespace App\Filament\Resources\ZktecoUserResource\Pages;

use App\Filament\Resources\ZktecoUserResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\dispositif_biometrique;
use MehediJaman\LaravelZkteco\LaravelZkteco;
use Filament\Notifications\Notification;

class CreateZktecoUser extends CreateRecord
{
    protected static string $resource = ZktecoUserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        try {
            $device = dispositif_biometrique::find($data['device_id']);
            $zk = new LaravelZkteco($device->ip, $device->port);
            $connected = $zk->connect();
            
            if ($connected) {
                $result = $zk->setUser(
                    $data['uid'],
                    $data['userid'],
                    $data['name'],
                    $data['password'],
                    $data['role'],
                    $data['cardno']
                );
                $zk->disconnect();
                
                if ($result) {
                    Notification::make()
                        ->title('Utilisateur créé avec succès')
                        ->success()
                        ->send();
                } else {
                    Notification::make()
                        ->title('Échec de la création')
                        ->danger()
                        ->send();
                }
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Erreur lors de la création')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }

        return $data;
    }
} 