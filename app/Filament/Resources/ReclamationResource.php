<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReclamationResource\Pages;
use App\Models\reclamations;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReclamationResource extends Resource
{
    protected static ?string $model = reclamations::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-circle';
    protected static ?string $navigationLabel = 'Réclamations';
    protected static ?string $modelLabel = 'Réclamation';
    protected static ?string $pluralModelLabel = 'Réclamations';
    protected static ?string $slug = 'reclamations';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Textarea::make('message')
                    ->required()
                    ->label('Message'),
                Forms\Components\Select::make('statut')
                    ->options([
                        'en_attente' => 'En Attente',
                        'traitee' => 'Traitée',
                        'rejetee' => 'Rejetée',
                    ])
                    ->default('en_attente') // Définit "en_attente" comme valeur par défaut
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
                    ->visible(fn ($record) => $record->statut === 'en_attente'), // L'employé peut modifier uniquement si le statut est "en_attente"
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
                Tables\Columns\TextColumn::make('message')
                    ->label('Message')
                    ->limit(50)
                    ->searchable(),
                Tables\Columns\TextColumn::make('statut')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'en_attente' => 'warning',
                        'traitee' => 'success',
                        'rejetee' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('date_reclamation')
                    ->label('Date Réclamation')
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
            'index' => Pages\ListReclamations::route('/'),
            'create' => Pages\CreateReclamation::route('/create'),
            'edit' => Pages\EditReclamation::route('/{record}/edit'),
        ];
    }
}
