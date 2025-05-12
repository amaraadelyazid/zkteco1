<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PresenceResource\Pages;
use App\Filament\Resources\PresenceResource\RelationManagers;
use App\Models\Presence;
use App\Actions\SyncPointagesFromZkteco;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class PresenceResource extends Resource
{
    protected static ?string $model = Presence::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Présences';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_type')
                    ->label('Type d\'utilisateur')
                    ->options([
                        'employe' => 'Employé',
                        'admin' => 'Admin',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('user_id')
                    ->label('ID Utilisateur')
                    ->required(),
                Forms\Components\TextInput::make('name')
                    ->label('Nom')
                    ->required(),
                Forms\Components\TextInput::make('prenom')
                    ->label('Prénom')
                    ->required(),
                Forms\Components\DatePicker::make('date')
                    ->label('Date')
                    ->required(),
                Forms\Components\TimePicker::make('check_in')
                    ->label('Check-in')
                    ->required(),
                Forms\Components\TimePicker::make('check_out')
                    ->label('Check-out')
                    ->required(),
                Forms\Components\Select::make('etat_check_in')
                    ->label('État Check-in')
                    ->options([
                        'present' => 'Présent',
                        'retard' => 'Retard',
                        'absent' => 'Absent',
                    ])
                    ->required(),
                Forms\Components\Select::make('etat_check_out')
                    ->label('État Check-out')
                    ->options([
                        'present' => 'Présent',
                        'retard' => 'Retard',
                        'absent' => 'Absent',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('heures_travaillees')
                    ->label('Heures travaillées')
                    ->numeric()
                    ->required(),
                Forms\Components\Select::make('anomalie_type')
                    ->label('Type d\'anomalie')
                    ->options([
                        'unique_pointage' => 'Pointage unique',
                        'absent' => 'Absent',
                        'incomplet' => 'Incomplet',
                        'hors_shift' => 'Hors shift',
                    ]),
                Forms\Components\Toggle::make('anomalie_resolue')
                    ->label('Anomalie résolue')
                    ->inline(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        $panel = \Filament\Facades\Filament::getCurrentPanel()?->getId();

        $actions = match ($panel) {
            'grh', 'admin' => [
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ],
            'employe' => [],
            default => [],
        };

        $bulkActions = match ($panel) {
            'grh', 'admin' => [
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ],
            default => [],
        };

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user_type')
                    ->label('Type d\'utilisateur'),
                Tables\Columns\TextColumn::make('user_id')
                    ->label('ID Utilisateur'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom'), // Added to display name
                Tables\Columns\TextColumn::make('prenom')
                    ->label('Prénom'), // Added to display first name
                Tables\Columns\TextColumn::make('date')
                    ->label('Date')
                    ->date(),
                Tables\Columns\TextColumn::make('check_in')
                    ->label('Check-in')
                    ->dateTime('H:i:s'),
                Tables\Columns\TextColumn::make('etat_check_in')
                    ->label('État Check-in'),
                Tables\Columns\TextColumn::make('check_out')
                    ->label('Check-out')
                    ->dateTime('H:i:s'),
                Tables\Columns\TextColumn::make('etat_check_out')
                    ->label('État Check-out'),
                Tables\Columns\TextColumn::make('heures_travaillees')
                    ->label('Heures travaillées'),
                Tables\Columns\TextColumn::make('anomalie_type')
                    ->label('Type d\'anomalie'),
                Tables\Columns\IconColumn::make('anomalie_resolue')
                    ->label('Anomalie résolue')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('etat_check_in')
                    ->options([
                        'present' => 'Présent',
                        'retard' => 'Retard',
                        'absent' => 'Absent',
                    ])
                    ->label('État Check-in'),
                SelectFilter::make('anomalie_type')
                    ->options([
                        'unique_pointage' => 'Pointage unique',
                        'absent' => 'Absent',
                        'incomplet' => 'Incomplet',
                        'hors_shift' => 'Hors shift',
                    ])
                    ->label('Type d\'anomalie'),
            ])
            ->actions($actions)
            ->bulkActions($bulkActions);
    }

    public static function getRelations(): array
    {
        return [
            // Définir les relations ici
        ];
    }
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
{
    $query = parent::getEloquentQuery();

    $panel = Filament::getCurrentPanel()?->getId();

    if ($panel === 'employe') {
        $user = Auth::user();
        return $query->where('user_type', 'employe')->where('user_id', $user->id);
    }

    return $query;
}


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPresences::route('/'),
            'create' => Pages\CreatePresence::route('/create'),
            'edit' => Pages\EditPresence::route('/{record}/edit'),
        ];
    }
}