<?php

namespace App\Filament\Resources\FicheDePaieResource\Pages;

use App\Filament\Resources\FicheDePaieResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFicheDePaie extends EditRecord
{
    protected static string $resource = FicheDePaieResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
