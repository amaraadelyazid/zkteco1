<?php

namespace App\Filament\Resources\ShiftResource\Pages;

use App\Filament\Resources\ShiftResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListShifts extends ListRecords
{
    protected static string $resource = ShiftResource::class;
    
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
