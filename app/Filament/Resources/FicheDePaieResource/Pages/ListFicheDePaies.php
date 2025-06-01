<?php

namespace App\Filament\Resources\FicheDePaieResource\Pages;

use App\Filament\Resources\FicheDePaieResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Facades\Filament;

class ListFicheDePaies extends ListRecords
{
    protected static string $resource = FicheDePaieResource::class;

    protected function getHeaderActions(): array
    {
        if ($panel = \Filament\Facades\Filament::getCurrentPanel()?->getId()) {
            return match ($panel) {
                'grh' => [
                    Actions\CreateAction::make(),
                ],
                'admin' => [],
                default => [],
            };
        }
        return [
            Actions\CreateAction::make(),
        ];
    }
}
