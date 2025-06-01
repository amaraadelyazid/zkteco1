<?php

namespace App\Filament\Resources\GrhResource\Pages;

use App\Filament\Resources\GrhResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGrhs extends ListRecords
{
    protected static string $resource = GrhResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
