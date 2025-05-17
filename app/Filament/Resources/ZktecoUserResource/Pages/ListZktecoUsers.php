<?php

namespace App\Filament\Resources\ZktecoUserResource\Pages;

use App\Filament\Resources\ZktecoUserResource;
use App\Models\dispositif_biometrique;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;
use MehediJaman\LaravelZkteco\LaravelZkteco;
use Filament\Notifications\Notification;

class ListZktecoUsers extends ListRecords
{
    protected static string $resource = ZktecoUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('refresh_users')
                ->label('Actualiser les utilisateurs')
                ->action(function () {
                    $devices = dispositif_biometrique::all();
                    $users = [];
                    
                    foreach ($devices as $device) {
                        try {
                            $zk = new LaravelZkteco($device->ip, $device->port);
                            $connected = $zk->connect();
                            
                            if ($connected) {
                                $deviceUsers = $zk->getUser();
                                $zk->disconnect();
                                
                                foreach ($deviceUsers as $user) {
                                    $users[] = array_merge($user, ['device_id' => $device->id]);
                                }
                            }
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Erreur lors de la récupération des utilisateurs')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }
                    
                    $this->data = $users;
                    
                    Notification::make()
                        ->title('Utilisateurs actualisés')
                        ->success()
                        ->send();
                }),
        ];
    }
} 