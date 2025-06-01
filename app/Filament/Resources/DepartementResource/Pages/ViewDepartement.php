<?php

namespace App\Filament\Resources\DepartementResource\Pages;

use App\Filament\Resources\DepartementResource;
use App\Filament\Resources\DepartementResource\RelationManagers\EmployesRelationManager;
use Filament\Resources\Pages\ViewRecord;

class ViewDepartement extends ViewRecord
{
    protected static string $resource = DepartementResource::class;

    protected static string $view = 'filament.resources.departement-resource.pages.view-departement';

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    public function getRelationManagers(): array
    {
        return [
            EmployesRelationManager::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getViewData(): array
    {
        return [
            'record' => $this->record,
        ];
    }
}
