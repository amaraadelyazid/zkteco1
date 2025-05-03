<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeResource\Pages;
use App\Models\Employe;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EmployeResource extends Resource
{
    protected static ?string $model = Employe::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Gestion RH';
    protected static ?string $label = 'Employé';
    protected static ?string $pluralLabel = 'Employés';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Nom')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('prenom')
                ->label('Prénom')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('email')
                ->email()
                ->required()
                ->unique(ignoreRecord: true),

            Forms\Components\TextInput::make('biometric_id')
                ->label('ID biométrique')
                ->numeric()
                ->required(),

            Forms\Components\TextInput::make('salaire')
                ->label('Salaire')
                ->numeric()
                ->prefix('€')
                ->required(),

            Forms\Components\Select::make('departement_id')
                ->label('Département')
                ->relationship('departement', 'nom') // Assure-toi que `nom` existe
                ->searchable()
                ->preload()
                ->required(),

            Forms\Components\Select::make('shift_id')
                ->label('Horaire')
                ->relationship('shift', 'nom') // Assure-toi que `nom` existe
                ->searchable()
                ->preload()
                ->required(),

            Forms\Components\TextInput::make('password')
                ->password()
                ->required(fn (string $context) => $context === 'create')
                ->dehydrated(fn ($state) => filled($state))
                ->same('password_confirmation')
                ->label('Mot de passe'),

            Forms\Components\TextInput::make('password_confirmation')
                ->password()
                ->dehydrated(false)
                ->label('Confirmer le mot de passe'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label('Nom')->searchable(),
            Tables\Columns\TextColumn::make('prenom')->label('Prénom')->searchable(),
            Tables\Columns\TextColumn::make('email')->label('Email')->searchable(),
            Tables\Columns\TextColumn::make('biometric_id')->label('ID biométrique'),
            Tables\Columns\TextColumn::make('salaire')->label('Salaire')->money('EUR'),
            Tables\Columns\TextColumn::make('departement.nom')->label('Département'),
            Tables\Columns\TextColumn::make('shift.nom')->label('Horaire'),
            Tables\Columns\TextColumn::make('created_at')->label('Créé le')->dateTime(),
        ])
        ->filters([])
        ->actions([
            Tables\Actions\ViewAction::make(),
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ])
        ->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
            ]),
        ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployes::route('/'),
            'create' => Pages\CreateEmploye::route('/create'),
            'edit' => Pages\EditEmploye::route('/{record}/edit'),
        ];
    }
}
