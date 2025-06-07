<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DispositifBiometriqueResource\Pages;
use App\Models\dispositif_biometrique;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use MehediJaman\LaravelZkteco\LaravelZkteco;
use Filament\Notifications\Notification;

class DispositifBiometriqueResource extends Resource
{
    protected static ?string $model = dispositif_biometrique::class;

    protected static ?string $navigationIcon = 'heroicon-o-finger-print';

    protected static ?string $navigationGroup = 'Configuration';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('ip')
                    ->required()
                    ->label('Adresse IP')
                    ->placeholder('192.168.1.201'),
                Forms\Components\TextInput::make('port')
                    ->required()
                    ->numeric()
                    ->default(4370)
                    ->label('Port'),
                Forms\Components\TextInput::make('version')
                    ->required()
                    ->label('Version')
                    ->placeholder('V1.0'),
                Forms\Components\Select::make('status')
                    ->required()
                    ->options([
                        'active' => 'Actif',
                        'inactive' => 'Inactif',
                    ])
                    ->label('Statut'),
                Forms\Components\Actions::make([
                    Forms\Components\Actions\Action::make('test_connection')
                        ->label('Tester la connexion')
                        ->action(function (dispositif_biometrique $record) {
                            try {
                                $zk = new LaravelZkteco($record->ip, $record->port);
                                $connected = $zk->connect();
                                
                                if ($connected) {
                                    $version = $zk->version();
                                    $zk->disconnect();
                                    
                                    Notification::make()
                                        ->title('Connexion réussie')
                                        ->body('Version du dispositif: ' . $version)
                                        ->success()
                                        ->send();
                                }
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Erreur de connexion')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->visible(fn (?dispositif_biometrique $record) => $record?->exists ?? false),
                    Forms\Components\Actions\Action::make('test_attendance')
                        ->label('Tester les pointages')
                        ->action(function (dispositif_biometrique $record) {
                            try {
                                $zk = new LaravelZkteco($record->ip, $record->port);
                                $connected = $zk->connect();
                                
                                if ($connected) {
                                    $attendance = $zk->getAttendance();
                                    $zk->disconnect();
                                    
                                    if (empty($attendance)) {
                                        Notification::make()
                                            ->title('Aucun pointage trouvé')
                                            ->warning()
                                            ->send();
                                    } else {
                                        Notification::make()
                                            ->title('Pointages récupérés')
                                            ->body('Nombre de pointages: ' . count($attendance))
                                            ->success()
                                            ->send();
                                    }
                                }
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Erreur de récupération des pointages')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->visible(fn (?dispositif_biometrique $record) => $record?->exists ?? false),
                    Forms\Components\Actions\Action::make('test_users')
                        ->label('Tester les utilisateurs')
                        ->action(function (dispositif_biometrique $record) {
                            try {
                                $zk = new LaravelZkteco($record->ip, $record->port);
                                $connected = $zk->connect();
                                
                                if ($connected) {
                                    $users = $zk->getUser();
                                    $zk->disconnect();
                                    
                                    if (empty($users)) {
                                        Notification::make()
                                            ->title('Aucun utilisateur trouvé')
                                            ->warning()
                                            ->send();
                                    } else {
                                        Notification::make()
                                            ->title('Utilisateurs récupérés')
                                            ->body('Nombre d\'utilisateurs: ' . count($users))
                                            ->success()
                                            ->send();
                                    }
                                }
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Erreur de récupération des utilisateurs')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->visible(fn (?dispositif_biometrique $record) => $record?->exists ?? false)
                ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ip')
                    ->label('Adresse IP')
                    ->searchable(),
                Tables\Columns\TextColumn::make('port')
                    ->label('Port')
                    ->sortable(),
                Tables\Columns\TextColumn::make('version')
                    ->label('Version')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Statut')
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
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
        return [
            
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDispositifBiometriques::route('/'),
            'create' => Pages\CreateDispositifBiometrique::route('/create'),
            'edit' => Pages\EditDispositifBiometrique::route('/{record}/edit'),
        ];
    }
} 