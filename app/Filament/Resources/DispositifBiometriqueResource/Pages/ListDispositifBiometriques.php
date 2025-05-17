<?php

namespace App\Filament\Resources\DispositifBiometriqueResource\Pages;

use App\Filament\Resources\DispositifBiometriqueResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDispositifBiometriques extends ListRecords
{
    protected static string $resource = DispositifBiometriqueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
} 