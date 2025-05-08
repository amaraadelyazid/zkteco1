<?php

namespace App\Filament\Resources\ReclamationResource\Pages;

use App\Filament\Resources\ReclamationResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateReclamation extends CreateRecord
{
    protected static string $resource = ReclamationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['employe_id'] = auth()->guard('employe')->id();
        $data['date_reclamation'] = now();
        $data['statut'] = 'en_attente';

        return $data;
    }
}
