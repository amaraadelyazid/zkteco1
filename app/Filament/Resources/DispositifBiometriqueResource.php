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
                        ->visible(fn (dispositif_biometrique $record) => $record->exists),
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
                        ->visible(fn (dispositif_biometrique $record) => $record->exists),
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
                        ->visible(fn (dispositif_biometrique $record) => $record->exists),
                    Forms\Components\Actions\Action::make('get_device_info')
                        ->label('Informations du dispositif')
                        ->action(function (dispositif_biometrique $record) {
                            try {
                                $zk = new LaravelZkteco($record->ip, $record->port);
                                $connected = $zk->connect();
                                
                                if ($connected) {
                                    $info = [
                                        'Version' => $zk->version(),
                                        'OS Version' => $zk->osVersion(),
                                        'Platform' => $zk->platform(),
                                        'Firmware Version' => $zk->fmVersion(),
                                        'Serial Number' => $zk->serialNumber(),
                                        'Device Name' => $zk->deviceName(),
                                    ];
                                    $zk->disconnect();
                                    
                                    Notification::make()
                                        ->title('Informations du dispositif')
                                        ->body(collect($info)->map(fn($value, $key) => "$key: $value")->join("\n"))
                                        ->success()
                                        ->send();
                                }
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Erreur de récupération des informations')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->visible(fn (dispositif_biometrique $record) => $record->exists),
                    Forms\Components\Actions\Action::make('device_control')
                        ->label('Contrôle du dispositif')
                        ->action(function (dispositif_biometrique $record, array $data) {
                            try {
                                $zk = new LaravelZkteco($record->ip, $record->port);
                                $connected = $zk->connect();
                                
                                if ($connected) {
                                    $action = $data['action'];
                                    $result = false;
                                    
                                    switch ($action) {
                                        case 'enable':
                                            $result = $zk->enableDevice();
                                            break;
                                        case 'disable':
                                            $result = $zk->disableDevice();
                                            break;
                                        case 'restart':
                                            $result = $zk->restart();
                                            break;
                                        case 'shutdown':
                                            $result = $zk->shutdown();
                                            break;
                                        case 'sleep':
                                            $result = $zk->sleep();
                                            break;
                                        case 'resume':
                                            $result = $zk->resume();
                                            break;
                                    }
                                    
                                    $zk->disconnect();
                                    
                                    if ($result) {
                                        Notification::make()
                                            ->title('Action réussie')
                                            ->success()
                                            ->send();
                                    } else {
                                        Notification::make()
                                            ->title('Action échouée')
                                            ->danger()
                                            ->send();
                                    }
                                }
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Erreur lors de l\'action')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->form([
                            Forms\Components\Select::make('action')
                                ->label('Action')
                                ->options([
                                    'enable' => 'Activer',
                                    'disable' => 'Désactiver',
                                    'restart' => 'Redémarrer',
                                    'shutdown' => 'Éteindre',
                                    'sleep' => 'Mettre en veille',
                                    'resume' => 'Réveiller',
                                ])
                                ->required(),
                        ])
                        ->visible(fn (dispositif_biometrique $record) => $record->exists),
                    Forms\Components\Actions\Action::make('sync_time')
                        ->label('Synchroniser l\'heure')
                        ->action(function (dispositif_biometrique $record) {
                            try {
                                $zk = new LaravelZkteco($record->ip, $record->port);
                                $connected = $zk->connect();
                                
                                if ($connected) {
                                    $result = $zk->setTime(date('Y-m-d H:i:s'));
                                    $zk->disconnect();
                                    
                                    if ($result) {
                                        Notification::make()
                                            ->title('Heure synchronisée avec succès')
                                            ->success()
                                            ->send();
                                    } else {
                                        Notification::make()
                                            ->title('Échec de la synchronisation')
                                            ->danger()
                                            ->send();
                                    }
                                }
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Erreur de synchronisation')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->visible(fn (dispositif_biometrique $record) => $record->exists),
                    Forms\Components\Actions\Action::make('clear_attendance')
                        ->label('Effacer les pointages')
                        ->requiresConfirmation()
                        ->action(function (dispositif_biometrique $record) {
                            try {
                                $zk = new LaravelZkteco($record->ip, $record->port);
                                $connected = $zk->connect();
                                
                                if ($connected) {
                                    $result = $zk->clearAttendance();
                                    $zk->disconnect();
                                    
                                    if ($result) {
                                        Notification::make()
                                            ->title('Pointages effacés avec succès')
                                            ->success()
                                            ->send();
                                    } else {
                                        Notification::make()
                                            ->title('Échec de l\'effacement')
                                            ->danger()
                                            ->send();
                                    }
                                }
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Erreur lors de l\'effacement')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->visible(fn (dispositif_biometrique $record) => $record->exists)
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