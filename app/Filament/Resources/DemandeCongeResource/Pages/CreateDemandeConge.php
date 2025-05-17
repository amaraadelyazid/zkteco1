<?php

namespace App\Filament\Resources\DemandeCongeResource\Pages;

use App\Filament\Resources\DemandeCongeResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateDemandeConge extends CreateRecord
{
    protected static string $resource = DemandeCongeResource::class;
//
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['employe_id'] = auth()->guard('employe')->id();
        $data['date_demande'] = now();
        $data['status'] = 'en_attente';

        return $data;
    }
}
