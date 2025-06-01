<?php

namespace App\Filament\Resources\PointagesResource\Pages;

use App\Filament\Resources\PointagesResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPointages extends ListRecords
{
    protected static string $resource = PointagesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
