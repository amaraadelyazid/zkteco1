<?php

namespace App\Filament\Resources\DemandeCongeResource\Pages;

use App\Filament\Resources\DemandeCongeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Filament\Facades\Filament;

class ListDemandeConges extends ListRecords
{
    protected static string $resource = DemandeCongeResource::class;


    protected function getHeaderActions(): array
    {
        if ($panel = \Filament\Facades\Filament::getCurrentPanel()?->getId()) {
            return match ($panel) {
                'grh' => [
                    Actions\CreateAction::make(),
                ],
                'employe' => [
                    Actions\CreateAction::make(),
                ],
                default => [],
            };
        }
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getTableQuery(): Builder
    {
        // Récupérer l'ID du panel actuel
        $panelId = Filament::getCurrentPanel()?->getId();

        // Si l'utilisateur est connecté au panel "employe"
        if ($panelId === 'employe') {
            // Récupérer l'utilisateur employé connecté
            $employe = auth('employe')->user();

            // Vérifier si l'utilisateur employé est valide
            if ($employe) {
                return parent::getTableQuery()->where('employe_id', $employe->id);
            }
        }

        // GRH et Admin voient tout
        return parent::getTableQuery();
    }
}
