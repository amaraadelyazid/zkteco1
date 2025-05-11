<?php

namespace App\Filament\Resources\DispositifBiometriqueResource\Pages;

use App\Filament\Resources\DispositifBiometriqueResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use MehediJaman\LaravelZkteco\LaravelZkteco;

class CreateDispositifBiometrique extends CreateRecord
{
    protected static string $resource = DispositifBiometriqueResource::class;

    protected function afterCreate(): void
    {
        try {
            $record = $this->record;
            $zk = new LaravelZkteco($record->ip, $record->port);
            
            if ($zk->connect()) {
                // Synchroniser le statut avec ZKTeco
                $zk->setStatus($record->status === 'active');
                $zk->disconnect();
                
                Notification::make()
                    ->title('Dispositif créé avec succès')
                    ->success()
                    ->persistent()
                    ->send();
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création du dispositif: ' . $e->getMessage());
            Notification::make()
                ->title('Erreur lors de la création')
                ->body('Le dispositif a été créé dans la base de données mais la synchronisation avec ZKTeco a échoué: ' . $e->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }
    }
} 