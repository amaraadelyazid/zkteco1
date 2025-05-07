<?php

namespace App\Filament\Resources\DemandeCongeResource\Pages;

use App\Filament\Resources\DemandeCongeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDemandeConge extends EditRecord
{
    protected static string $resource = DemandeCongeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => auth()->guard('grh')->check()),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (auth()->guard('grh')->check()) {
            $data['grh_id'] = auth()->guard('grh')->id();
        }

        return $data;
    }
}
