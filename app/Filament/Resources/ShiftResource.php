<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShiftResource\Pages;
use App\Filament\Resources\ShiftResource\RelationManagers;
use App\Models\Shift;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ShiftResource extends Resource
{
    protected static ?string $model = Shift::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        
        return $form
            ->schema([
                Forms\Components\TextInput::make('nom')->label('Nom')->required(),
                Forms\Components\TimePicker::make('heure_debut')->label('Heure début')->required(),
                Forms\Components\TimePicker::make('heure_fin')->label('Heure fin')->required(),
                Forms\Components\Toggle::make('pause')->label('Pause'),
                Forms\Components\TextInput::make('duree_pause')
                    ->label('Durée pause (min)')
                    ->numeric()->minValue(0)
                    ->visible(fn($get) => $get('pause') && !$get('heure_debut_pause') && !$get('heure_fin_pause'))
                    ->disabled(fn($get) => !$get('pause') || $get('heure_debut_pause') || $get('heure_fin_pause')),
                Forms\Components\TimePicker::make('heure_debut_pause')
                    ->label('Début pause')
                    ->visible(fn($get) => $get('pause') && !$get('duree_pause'))
                    ->disabled(fn($get) => !$get('pause') || $get('duree_pause')),
                Forms\Components\TimePicker::make('heure_fin_pause')
                    ->label('Fin pause')
                    ->visible(fn($get) => $get('pause') && !$get('duree_pause'))
                    ->disabled(fn($get) => !$get('pause') || $get('duree_pause')),
                Forms\Components\CheckboxList::make('jours_travail')
                    ->label('Jours de travail')
                    ->options([
                        'lundi' => 'Lundi',
                        'mardi' => 'Mardi',
                        'mercredi' => 'Mercredi',
                        'jeudi' => 'Jeudi',
                        'vendredi' => 'Vendredi',
                        'samedi' => 'Samedi',
                        'dimanche' => 'Dimanche',
                    ])->columns(2)->required(),
                Forms\Components\TextInput::make('tolerance_retard')->label('Tolérance retard (min)')->numeric()->minValue(0)->default(0),
                Forms\Components\TextInput::make('depart_anticipe')->label('Départ anticipé (min)')->numeric()->minValue(0)->default(0),
                Forms\Components\TextInput::make('duree_min_presence')->label('Durée min. présence (min)')->numeric()->minValue(0)->required(),
                Forms\Components\Toggle::make('is_decalable')->label('Décalable'),
                Forms\Components\Textarea::make('description')->label('Description'),
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
                Tables\Columns\TextColumn::make('nom')->label('Nom')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('heure_debut')->label('Heure début'),
                Tables\Columns\TextColumn::make('heure_fin')->label('Heure fin'),
                Tables\Columns\IconColumn::make('pause')->label('Pause')->boolean(),
                Tables\Columns\TextColumn::make('duree_pause')->label('Durée pause (min)'),
                Tables\Columns\TextColumn::make('jours_travail')->label('Jours de travail')->formatStateUsing(fn($state) => is_array($state) ? implode(', ', $state) : $state),
                Tables\Columns\TextColumn::make('tolerance_retard')->label('Tolérance retard (min)'),
                Tables\Columns\TextColumn::make('depart_anticipe')->label('Départ anticipé (min)'),
                Tables\Columns\TextColumn::make('duree_min_presence')->label('Durée min. présence (min)'),
                Tables\Columns\IconColumn::make('is_decalable')->label('Décalable')->boolean(),
            ])
            ->filters([
                //
            ])
            ->actions($actions)
            ->bulkActions($bulkActions);
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
            'index' => Pages\ListShifts::route('/'),
            'create' => Pages\CreateShift::route('/create'),
            'edit' => Pages\EditShift::route('/{record}/edit'),
        ];
    }
}
