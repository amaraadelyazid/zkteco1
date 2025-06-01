<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmployeResource\Pages;
use App\Models\Employe;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

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
            Forms\Components\Section::make('Informations personnelles')
                ->schema([
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->label('Nom')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('Ex. Dupont')
                                ->autofocus(),

                            Forms\Components\TextInput::make('prenom')
                                ->label('Prénom')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('Ex. Jean'),

                            Forms\Components\TextInput::make('email')
                                ->label('Email')
                                ->email()
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->placeholder('Ex. jean.dupont@example.com'),

                            Forms\Components\TextInput::make('Numero_telephone')
                                ->label('Numéro de téléphone')
                                ->tel()
                                ->required()
                                ->maxLength(20)
                                ->placeholder('Ex. +33 6 12 34 56 78')
                                ->rule('regex:/^([+]?[\s0-9]+)?(\d{3}|[(]?[0-9]+[)])?([-]?[\s]?[0-9])+$/')
                                ->helperText('Format international requis.'),
                        ]),
                    Forms\Components\Textarea::make('adresse')
                        ->label('Adresse')
                        ->required()
                        ->maxLength(500)
                        ->placeholder('Ex. 123 Rue Principale, 75001 Paris')
                        ->rows(3),
                ])
                ->collapsible(),

            Forms\Components\Section::make('Informations professionnelles')
                ->schema([
                    Forms\Components\Grid::make(2)
                        ->schema([
                            Forms\Components\TextInput::make('biometric_id')
                                ->label('ID biométrique')
                                ->numeric()
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->minValue(1)
                                ->placeholder('Ex. 3001')
                                ->helperText('Identifiant unique pour le système biométrique.'),

                            Forms\Components\TextInput::make('salaire')
                                ->label('Salaire horaire')
                                ->numeric()
                                ->required()
                                ->prefix('€')
                                ->minValue(0)
                                ->step(0.01)
                                ->placeholder('Ex. 15.50')
                                ->helperText('Taux horaire.'),

                            Forms\Components\TextInput::make('poste')
                                ->label('Poste')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('Ex. Développeur')
                                ->helperText('Rôle ou fonction dans l\'entreprise.'),

                            Forms\Components\Select::make('departement_id')
                                ->label('Département')
                                ->relationship('departement', 'nom')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->placeholder('Sélectionnez un département'),

                            Forms\Components\Select::make('shift_id')
                                ->label('Horaire')
                                ->relationship('shift', 'nom')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->placeholder('Sélectionnez un horaire'),
                        ]),
                ])
                ->collapsible(),

            Forms\Components\Section::make('Authentification')
                ->schema([
                    Forms\Components\TextInput::make('password')
                        ->label('Mot de passe')
                        ->password()
                        ->required(fn (string $context) => $context === 'create')
                        ->minLength(8)
                        ->dehydrated(fn ($state) => filled($state) ? Hash::make($state) : null)
                        ->same('password_confirmation')
                        ->placeholder('Minimum 8 caractères')
                        ->helperText('Laissez vide pour ne pas modifier lors de l\'édition.'),

                    Forms\Components\TextInput::make('password_confirmation')
                        ->label('Confirmer le mot de passe')
                        ->password()
                        ->required(fn (string $context, $state, $record) => $context === 'create' || filled($state))
                        ->dehydrated(false)
                        ->placeholder('Retapez le mot de passe')
                        ->helperText('Requis si le mot de passe est modifié.'),
                ])
                ->collapsible(),
        ])
        ->columns(1);
    }

    public static function table(Table $table): Table
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

            Tables\Columns\TextColumn::make('salaire')
                ->label('Salaire horaire')
                ->money('EUR')
                ->sortable(),

            Tables\Columns\TextColumn::make('poste')
                ->label('Poste')
                ->searchable()
                ->toggleable(true),

            Tables\Columns\TextColumn::make('departement.nom')
                ->label('Département')
                ->toggleable(),

            Tables\Columns\TextColumn::make('shift.nom')
                ->label('Horaire')
                ->toggleable(),

            Tables\Columns\TextColumn::make('created_at')
                ->label('Créé')
                ->dateTime('d/m/Y H:i')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ])
        ->filters([
            Tables\Filters\SelectFilter::make('departement_id')
                ->label('Département')
                ->relationship('departement', 'nom')
                ->searchable()
                ->preload(),

            Tables\Filters\SelectFilter::make('shift_id')
                ->label('Horaire')
                ->relationship('shift', 'nom')
                ->searchable()
                ->preload(),
        ])
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