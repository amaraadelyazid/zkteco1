<?php

namespace App\Filament\Resources\ReclamationResource\Pages;

use App\Filament\Resources\ReclamationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReclamation extends EditRecord
{
    protected static string $resource = ReclamationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => auth()->guard('grh')->check() || auth()->guard('admin')->check()),
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
