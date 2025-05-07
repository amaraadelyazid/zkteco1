<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DemandeCongeResource\Pages;
use App\Models\demande_conge;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DemandeCongeResource extends Resource
{
    protected static ?string $model = demande_conge::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationLabel = 'Demandes de Congé';
    protected static ?string $modelLabel = 'Demande de Congé';
    protected static ?string $pluralModelLabel = 'Demandes de Congé';
    protected static ?string $slug = 'demande-conges';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('type')
                    ->options([
                        'annuel' => 'Congé Annuel',
                        'maladie' => 'Congé Maladie',
                        'maternite' => 'Congé Maternité',
                        'paternite' => 'Congé Paternité',
                        'exceptionnel' => 'Congé Exceptionnel',
                    ])
                    ->required()
                    ->label('Type de Congé'),
                Forms\Components\DatePicker::make('date_debut')
                    ->required()
                    ->label('Date de Début'),
                Forms\Components\DatePicker::make('date_fin')
                    ->required()
                    ->label('Date de Fin'),
                Forms\Components\Textarea::make('message')
                    ->required()
                    ->label('Motif'),
                Forms\Components\FileUpload::make('photo')
                    ->image()
                    ->directory('demande-conges')
                    ->label('Justificatif (Optionnel)'),
                Forms\Components\Select::make('status')
                    ->options([
                        'en_attente' => 'En Attente',
                        'approuvee' => 'Approuvée',
                        'refusee' => 'Refusée',
                    ])
                    ->default('en_attente') 
                    ->label('Statut'),
                Forms\Components\Textarea::make('reponse')
                    ->label('Réponse'),
            ]);
    }

    public static function table(Table $table): Table
    {
        $panel = Filament::getCurrentPanel()?->getId();

        $actions = match ($panel) {
            'grh' => [
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ],
            'employe' => [
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => $record->status === 'en_attente'), // L'employé peut modifier uniquement si le statut est "en_attente"
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
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('date_debut')
                    ->label('Date Début')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('date_fin')
                    ->label('Date Fin')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'en_attente' => 'warning',
                        'approuvee' => 'success',
                        'refusee' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('date_demande')
                    ->label('Date Demande')
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
            'index' => Pages\ListDemandeConges::route('/'),
            'create' => Pages\CreateDemandeConge::route('/create'),
            'edit' => Pages\EditDemandeConge::route('/{record}/edit'),
        ];
    }
}
