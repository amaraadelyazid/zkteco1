<?php

namespace App\Filament\Resources;

use App\Actions\CalculatePayslips;
use App\Filament\Resources\FicheDePaieResource\Pages;
use App\Models\Employe;
use App\Models\fiche_de_paie;
use App\Models\Grh;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class FicheDePaieResource extends Resource
{
    protected static ?string $model = fiche_de_paie::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Fiches de Paie';
    protected static ?string $modelLabel = 'Fiche de Paie';
    protected static ?string $pluralModelLabel = 'Fiches de Paie';
    protected static ?string $slug = 'fiche-de-paies';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('user_type')
                    ->options([
                        'employe' => 'Employé',
                        'grh' => 'GRH',
                    ])
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn (callable $set) => $set('user_id', null)),
                Select::make('user_id')
                    ->label('Utilisateur')
                    ->options(function (callable $get) {
                        $userType = $get('user_type');
                        if ($userType === 'employe') {
                            return Employe::all()->mapWithKeys(fn ($employe) => [
                                $employe->id => "{$employe->name} {$employe->prenom}",
                            ]);
                        } elseif ($userType === 'grh') {
                            return Grh::all()->mapWithKeys(fn ($grh) => [
                                $grh->id => "{$grh->name} {$grh->prenom}",
                            ]);
                        }
                        return [];
                    })
                    ->required()
                    ->searchable()
                    ->disabled(fn (callable $get) => !$get('user_type')),
                TextInput::make('mois')
                    ->label('Mois')
                    ->required()
                    ->formatStateUsing(fn ($state) => Carbon::parse($state . '-01')->format('Y-m'))
                    ->dehydrateStateUsing(fn ($state) => Carbon::parse($state)->format('Y-m'))
                    ->placeholder('YYYY-MM')
                    ->mask('9999-99'),
                TextInput::make('montant')
                    ->label('Montant')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->suffix('€'),
                TextInput::make('heures_sup')
                    ->label('Heures Supplémentaires')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->suffix('heures'),
                TextInput::make('prime')
                    ->label('Primes')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->suffix('€'),
                TextInput::make('avance')
                    ->label('Avance')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->suffix('€'),
                Select::make('status')
                    ->options([
                        'en_attente' => 'En Attente',
                        'paye' => 'Payé',
                    ])
                    ->required()
                    ->default('en_attente'),
                DatePicker::make('date_generation')
                    ->label('Date de Génération')
                    ->required()
                    ->default(now())
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        $panel = Filament::getCurrentPanel()?->getId();

        $actions = match ($panel) {
            'grh' => [
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ],
            default => [],
        };

        $bulkActions = match ($panel) {
            'grh' => [
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ],
            default => [],
        };

        return $table
            ->columns([
                TextColumn::make('user_type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => $state === 'employe' ? 'Employé' : 'GRH')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label('Nom')
                    ->getStateUsing(fn (fiche_de_paie $record) => $record->user_type === 'employe'
                        ? Employe::find($record->user_id)?->name
                        : Grh::find($record->user_id)?->name)
                    ->sortable(query: fn (Builder $query, string $direction) => $query
                        ->leftJoin('employes', fn ($join) => $join
                            ->on('fiche_de_paies.user_id', '=', 'employes.id')
                            ->where('fiche_de_paies.user_type', 'employe'))
                        ->leftJoin('grhs', fn ($join) => $join
                            ->on('fiche_de_paies.user_id', '=', 'grhs.id')
                            ->where('fiche_de_paies.user_type', 'grh'))
                        ->orderByRaw("COALESCE(employes.name, grhs.name) {$direction}"))
                    ->searchable(query: fn (Builder $query, string $search) => $query
                        ->whereHas('employe', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('grh', fn ($q) => $q->where('name', 'like', "%{$search}%"))),
                TextColumn::make('user.prenom')
                    ->label('Prénom')
                    ->getStateUsing(fn (fiche_de_paie $record) => $record->user_type === 'employe'
                        ? Employe::find($record->user_id)?->prenom
                        : Grh::find($record->user_id)?->prenom)
                    ->sortable(query: fn (Builder $query, string $direction) => $query
                        ->leftJoin('employes', fn ($join) => $join
                            ->on('fiche_de_paies.user_id', '=', 'employes.id')
                            ->where('fiche_de_paies.user_type', 'employe'))
                        ->leftJoin('grhs', fn ($join) => $join
                            ->on('fiche_de_paies.user_id', '=', 'grhs.id')
                            ->where('fiche_de_paies.user_type', 'grh'))
                        ->orderByRaw("COALESCE(employes.prenom, grhs.prenom) {$direction}"))
                    ->searchable(query: fn (Builder $query, string $search) => $query
                        ->whereHas('employe', fn ($q) => $q->where('prenom', 'like', "%{$search}%"))
                        ->orWhereHas('grh', fn ($q) => $q->where('prenom', 'like', "%{$search}%"))),
                TextColumn::make('user.poste')
                    ->label('Poste')
                    ->getStateUsing(fn (fiche_de_paie $record) => $record->user_type === 'employe'
                        ? Employe::find($record->user_id)?->poste
                        : Grh::find($record->user_id)?->poste)
                    ->sortable(query: fn (Builder $query, string $direction) => $query
                        ->leftJoin('employes', fn ($join) => $join
                            ->on('fiche_de_paies.user_id', '=', 'employes.id')
                            ->where('fiche_de_paies.user_type', 'employe'))
                        ->leftJoin('grhs', fn ($join) => $join
                            ->on('fiche_de_paies.user_id', '=', 'grhs.id')
                            ->where('fiche_de_paies.user_type', 'grh'))
                        ->orderByRaw("COALESCE(employes.poste, grhs.poste) {$direction}"))
                    ->searchable(query: fn (Builder $query, string $search) => $query
                        ->whereHas('employe', fn ($q) => $q->where('poste', 'like', "%{$search}%"))
                        ->orWhereHas('grh', fn ($q) => $q->where('poste', 'like', "%{$search}%"))),
                TextColumn::make('user.departement.nom')
                    ->label('Département')
                    ->getStateUsing(fn (fiche_de_paie $record) =>
                        $record->user_type === 'employe'
                            ? Employe::find($record->user_id)?->departement?->nom
                            : null // grh n'a pas de département
                    )
                    ->sortable(query: fn (Builder $query, string $direction) => $query
                        ->leftJoin('employes', fn ($join) => $join
                            ->on('fiche_de_paies.user_id', '=', 'employes.id')
                            ->where('fiche_de_paies.user_type', 'employe'))
                        ->leftJoin('departements as emp_dep', 'employes.departement_id', '=', 'emp_dep.id')
                        ->orderByRaw("emp_dep.nom {$direction}")
                    )
                    ->formatStateUsing(fn ($state, $record) =>
                        $record->user_type === 'employe'
                            ? ($state ?? '—')
                            : '—'
                    )
                    ->searchable(query: fn (Builder $query, string $search) => $query
                        ->whereHas('employe.departement', fn ($q) => $q->where('nom', 'like', "%{$search}%"))
                    ),
                TextColumn::make('mois')
                    ->label('Mois')
                    ->formatStateUsing(fn ($state) => Carbon::parse($state . '-01')->format('F Y'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('montant')
                    ->label('Montant')
                    ->money('EUR')
                    ->sortable(),
                TextColumn::make('heures_sup')
                    ->label('Heures Supp.')
                    ->formatStateUsing(fn ($state) => number_format($state, 2) . ' h')
                    ->sortable(),
                TextColumn::make('prime')
                    ->label('Primes')
                    ->money('EUR')
                    ->sortable(),
                TextColumn::make('avance')
                    ->label('Avance')
                    ->money('EUR')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'en_attente' => 'En Attente',
                        'paye' => 'Payé',
                        default => $state
                    })
                    ->sortable()
                    ->searchable()
                    ->action(function (fiche_de_paie $record, $column) {
                        $currentStatus = $record->status;
                        $nextStatus = match ($currentStatus) {
                            'en_attente' => 'paye',
                            'paye' => 'en_attente',
                            default => 'en_attente',
                        };
                        $record->update(['status' => $nextStatus]);
                        Notification::make()
                            ->title('Statut mis à jour')
                            ->body("Le statut est maintenant : " . match ($nextStatus) {
                                'en_attente' => 'En Attente',
                                'paye' => 'Payé',
                            })
                            ->success()
                            ->send();
                    }),
                TextColumn::make('date_generation')
                    ->label('Date de Génération')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('user_type')
                    ->options([
                        'employe' => 'Employé',
                        'grh' => 'GRH',
                    ])
                    ->label('Type d’Utilisateur'),
                SelectFilter::make('status')
                    ->options([
                        'en_attente' => 'En Attente',
                        'paye' => 'Payé',
                    ])
                    ->label('Statut'),
            ])
            ->actions($actions)
            ->bulkActions($bulkActions)
            ->headerActions([
                Tables\Actions\Action::make('calculate_new')
                    ->label('Calculer Nouvelle Fiche')
                    ->icon('heroicon-o-calculator')
                    ->url(fn () => route('filament.grh.resources.fiche-de-paies.calculate'))
                    ->visible(fn () => Filament::getCurrentPanel()?->getId() === 'grh'),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $panel = Filament::getCurrentPanel()?->getId();

        if ($panel === 'employe') {
            $user = Auth::user();
            if ($user && $user->id) {
                return $query->where('user_type', 'employe')->where('user_id', $user->id);
            }
            return $query->whereRaw('1 = 0');
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFicheDePaies::route('/'),
            'create' => Pages\CreateFicheDePaie::route('/create'),
            'edit' => Pages\EditFicheDePaie::route('/{record}/edit'),
            'calculate' => Pages\CalculateFicheDePaie::route('/calculate'),
        ];
    }
}