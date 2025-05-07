<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FicheDePaieResource\Pages;
use App\Models\fiche_de_paie;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

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
                Forms\Components\Select::make('employe_id')
                    ->relationship('employe', 'name')
                    ->required()
                    ->label('Employé'),
                Forms\Components\TextInput::make('mois')
                    ->required()
                    ->label('Mois'),
                Forms\Components\TextInput::make('montant')
                    ->required()
                    ->numeric()
                    ->label('Montant'),
                Forms\Components\TextInput::make('avance')
                    ->numeric()
                    ->default(0)
                    ->label('Avance'),
                Forms\Components\TextInput::make('heures_sup')
                    ->numeric()
                    ->default(0)
                    ->label('Heures Supplémentaires'),
                Forms\Components\TextInput::make('primes')
                    ->numeric()
                    ->default(0)
                    ->label('Primes'),
                Forms\Components\Select::make('status')
                    ->options([
                        'en_attente' => 'En Attente',
                        'payee' => 'Payée',
                        'annulee' => 'Annulée',
                    ])
                    ->required()
                    ->label('Statut'),
                Forms\Components\DateTimePicker::make('date_generation')
                    ->default(now())
                    ->required()
                    ->label('Date de Génération'),
            ]);
    }

    public static function table(Table $table): Table
    {
        $panel = Filament::getCurrentPanel()?->getId();

        $actions = match ($panel) {
            'grh' => [
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                tables\Actions\DeleteAction::make(),
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
            ->recordUrl(null)
            ->columns([
                Tables\Columns\TextColumn::make('employe.name')
                    ->label('Employé')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('mois')
                    ->label('Mois')
                    ->sortable(),
                Tables\Columns\TextColumn::make('montant')
                    ->label('Montant')
                    ->money('MAD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'en_attente' => 'warning',
                        'payee' => 'success',
                        'paid' => 'success',
                        'annulee' => 'danger',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('date_generation')
                    ->label('Date de Génération')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([])
            ->actions($actions)
            ->bulkActions($bulkActions);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFicheDePaies::route('/'),
            'create' => Pages\CreateFicheDePaie::route('/create'),
            'edit' => Pages\EditFicheDePaie::route('/{record}/edit'),
        ];
    }

}
