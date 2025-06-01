<?php

namespace App\Filament\Resources\DepartementResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class EmployesRelationManager extends RelationManager
{
    protected static string $relationship = 'employes';
    protected static ?string $title = 'Liste des employés';

    public function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')
                ->label('Nom')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('prenom')
                ->label('Prénom')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('email')
                ->label('Email')
                ->searchable()
                ->toggleable(),

            Tables\Columns\TextColumn::make('biometric_id')
                ->label('ID biométrique')
                ->sortable(),

            Tables\Columns\TextColumn::make('poste')
                ->label('Poste')
                ->searchable()
                ->toggleable(true),

            Tables\Columns\TextColumn::make('shift.nom')
                ->label('Horaire')
                ->toggleable(),

            Tables\Columns\TextColumn::make('created_at')
                ->label('Créé')
                ->dateTime('d/m/Y H:i')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ])
            ->filters([])
            ->headerActions([]) // On désactive les boutons Ajouter
            ->actions([]) // Pas de actions (Edit/Delete)
            ->bulkActions([]);
    }
}
