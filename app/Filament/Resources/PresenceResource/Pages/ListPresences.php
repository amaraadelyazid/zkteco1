<?php

namespace App\Filament\Resources\PresenceResource\Pages;

use App\Filament\Resources\PresenceResource;
use App\Actions\SyncPointagesFromZkteco;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;
use App\Models\PointageBiometrique;
use App\Models\Presence;

class ListPresences extends ListRecords
{
    protected static string $resource = PresenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('sync_zkteco')
                ->label('Synchroniser ZKTeco')
                ->icon('heroicon-o-arrow-path')
                ->requiresConfirmation()
                ->action(function () {
                    try {
                        (new SyncPointagesFromZkteco)();
                        $pointagesCount = PointageBiometrique::whereDate('timestamp', now()->toDateString())->count();
                        $presencesCount = Presence::whereDate('date', now()->toDateString())->count();
                        if ($pointagesCount === 0 && $presencesCount === 0) {
                            \Filament\Notifications\Notification::make()
                                ->title('Aucune donnée synchronisée')
                                ->body('Aucun pointage ou présence n\'a été enregistré. Vérifiez les appareils ou les utilisateurs.')
                                ->warning()
                                ->duration(5000) // Ensure notification persists for 5 seconds
                                ->send();
                            return;
                        }
                        \Filament\Notifications\Notification::make()
                            ->title('Synchronisation réussie')
                            ->body("{$pointagesCount} pointages et {$presencesCount} présences synchronisés.")
                            ->success()
                            ->duration(5000) // Ensure notification persists for 5 seconds
                            ->send();
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error('Erreur de synchronisation ZKTeco : ' . $e->getMessage());
                        \Filament\Notifications\Notification::make()
                            ->title('Erreur lors de la synchronisation')
                            ->body($e->getMessage())
                            ->danger()
                            ->duration(5000) // Ensure notification persists for 5 seconds
                            ->send();
                    }
                })
                ->extraAttributes(['wire:loading.attr' => 'disabled']),
        ];
    }
}