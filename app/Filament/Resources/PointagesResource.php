<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PointagesResource\Pages;
use App\Filament\Resources\PointagesResource\RelationManagers;
use App\Models\PointageBiometrique;
use App\Models\Pointages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PointagesResource extends Resource
{
    protected static ?string $model = PointageBiometrique::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_type')
                    ->label('Type d\'utilisateur')
                    ->options([
                        'employe' => 'Employé',
                        'grh' => 'GRH',
                        'admin' => 'Admin',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('user_id')
                    ->label('ID Utilisateur')
                    ->required(),
                Forms\Components\DateTimePicker::make('timestamp')
                    ->label('Horodatage')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        $panel = \Filament\Facades\Filament::getCurrentPanel()?->getId();

        $actions = match ($panel) {
            'grh' => [
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ],
            'admin' => [
                Tables\Actions\ViewAction::make(),
            ],
            'employe' => [],
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
                Tables\Columns\TextColumn::make('user_type')
                    ->label('Type d\'utilisateur')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('user_id')
                    ->label('ID Utilisateur')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('timestamp')
                    ->label('Horodatage')
                    ->dateTime()
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('time_range')
                    ->label('Période')
                    ->options([
                        'today' => 'Aujourd\'hui',
                        'this_week' => 'Cette semaine',
                        'this_month' => 'Ce mois-ci',
                    ]),
            ])
            ->actions($actions)
            ->bulkActions($bulkActions)
            ->defaultSort('timestamp', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPointages::route('/'),
            'create' => Pages\CreatePointages::route('/create'),
            'edit' => Pages\EditPointages::route('/{record}/edit'),
        ];
    }
}
