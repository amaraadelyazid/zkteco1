<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DepartementResource\Pages;
use App\Models\Departement;
use Filament\Facades\Filament;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class DepartementResource extends Resource
{
    protected static ?string $model = Departement::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Gestion RH';
    protected static ?string $label = 'Département';
    protected static ?string $pluralLabel = 'Départements';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('nom')
                    ->label('Nom')
                    ->required()
                    ->maxLength(255),

                Textarea::make('description')
                    ->label('Description')
                    ->rows(5)
                    ->maxLength(65535),
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
                Tables\Actions\Action::make('viewemployes')
                    ->label('Voir Employés')
                    ->icon('heroicon-o-users')
                    ->color('secondary')
                    ->url(fn ($record) => static::getUrl('view', ['record' => $record]) . '?tab=employes'),
            ],
            'admin' => [
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('viewemployes')
                    ->label('Voir Employés')
                    ->icon('heroicon-o-users')
                    ->color('secondary')
                    ->url(fn ($record) => static::getUrl('view', ['record' => $record]) . '?tab=employes'),
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
                TextColumn::make('id')->sortable()->searchable(),
                TextColumn::make('nom')->label('Nom')->sortable()->searchable(),
                TextColumn::make('description')->label('Description')->limit(50),
                TextColumn::make('created_at')->label('Créé le')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([])
            ->actions($actions)
            ->bulkActions($bulkActions);
    }


    public static function getRelations(): array
    {
        return [
            // Pas de relations pour l’instant
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDepartements::route('/'),
            'create' => Pages\CreateDepartement::route('/create'),
            'edit' => Pages\EditDepartement::route('/{record}/edit'),
            'view' => Pages\ViewDepartement::route('/{record}'),
        ];
    }
}

