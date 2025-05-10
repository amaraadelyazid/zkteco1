<?php

namespace App\Filament\Resources\DispositifBiometriqueResource\Pages;

use App\Filament\Resources\DispositifBiometriqueResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDispositifBiometrique extends EditRecord
{
    protected static string $resource = DispositifBiometriqueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
} 