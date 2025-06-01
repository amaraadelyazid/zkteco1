<x-filament::page>
    <x-filament::section>
        <x-slot name="heading">
            <h2 class="text-xl font-bold">
                @if(request()->get('tab') === 'employes')
                    Employés du département : {{ $record->nom }}
                @else
                    Détails du département : {{ $record->nom }}
                @endif
            </h2>
        </x-slot>

        <div class="mt-4">
            @if(request()->get('tab') === 'employes')
                @livewire(\App\Filament\Resources\DepartementResource\RelationManagers\EmployesRelationManager::class, [
                    'ownerRecord' => $record,
                    'pageClass' => \App\Filament\Resources\DepartementResource\Pages\ViewDepartement::class,
                ])
            @else
                <div class="mb-4">
                    <strong>Description :</strong> {{ $record->description ?? '-' }}
                </div>
                <div class="mb-4">
                    <strong>Créé le :</strong> {{ $record->created_at ? $record->created_at->format('d/m/Y H:i') : '-' }}
                </div>
                {{-- Ajoutez ici d'autres informations du département si besoin --}}
            @endif
        </div>
    </x-filament::section>
</x-filament::page>
