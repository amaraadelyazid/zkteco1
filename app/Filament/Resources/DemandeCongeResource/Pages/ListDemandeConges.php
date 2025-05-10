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
        
        $panelId = Filament::getCurrentPanel()?->getId();

        
        if ($panelId === 'employe') {
            
            $employe = auth('employe')->user();

            
            if ($employe) {
                return parent::getTableQuery()->where('employe_id', $employe->id);
            }
        }

        
        return parent::getTableQuery();
    }
}
