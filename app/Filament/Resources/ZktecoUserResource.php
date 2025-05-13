<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ZktecoUserResource\Pages;
use App\Models\dispositif_biometrique;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use MehediJaman\LaravelZkteco\LaravelZkteco;
use Filament\Notifications\Notification;

class ZktecoUserResource extends Resource
{
    protected static ?string $model = null;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Configuration';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('device_id')
                    ->label('Dispositif')
                    ->options(dispositif_biometrique::pluck('ip', 'id'))
                    ->required(),
                Forms\Components\TextInput::make('uid')
                    ->label('ID Unique')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(65535),
                Forms\Components\TextInput::make('userid')
                    ->label('ID Utilisateur')
                    ->required()
                    ->maxLength(9)
                    ->regex('/^[0-9]+$/'),
                Forms\Components\TextInput::make('name')
                    ->label('Nom')
                    ->required()
                    ->maxLength(24),
                Forms\Components\TextInput::make('password')
                    ->label('Mot de passe')
                    ->required()
                    ->maxLength(8)
                    ->regex('/^[0-9]+$/'),
                Forms\Components\Select::make('role')
                    ->label('Rôle')
                    ->options([
                        0 => 'Utilisateur',
                        14 => 'Administrateur',
                    ])
                    ->default(0)
                    ->required(),
                Forms\Components\TextInput::make('cardno')
                    ->label('Numéro de carte')
                    ->numeric()
                    ->maxLength(10)
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('uid')
                    ->label('ID Unique')
                    ->sortable(),
                Tables\Columns\TextColumn::make('userid')
                    ->label('ID Utilisateur')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable(),
                Tables\Columns\TextColumn::make('role')
                    ->label('Rôle')
                    ->formatStateUsing(fn ($state) => $state == 14 ? 'Administrateur' : 'Utilisateur'),
                Tables\Columns\TextColumn::make('cardno')
                    ->label('Numéro de carte'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('edit_user')
                    ->label('Modifier')
                    ->form([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom')
                            ->required()
                            ->maxLength(24),
                        Forms\Components\TextInput::make('password')
                            ->label('Mot de passe')
                            ->required()
                            ->maxLength(8)
                            ->regex('/^[0-9]+$/'),
                        Forms\Components\Select::make('role')
                            ->label('Rôle')
                            ->options([
                                0 => 'Utilisateur',
                                14 => 'Administrateur',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('cardno')
                            ->label('Numéro de carte')
                            ->numeric()
                            ->maxLength(10),
                    ])
                    ->action(function (array $data, array $arguments) {
                        try {
                            $device = dispositif_biometrique::find($arguments['device_id']);
                            $zk = new LaravelZkteco($device->ip, $device->port);
                            $connected = $zk->connect();
                            
                            if ($connected) {
                                $result = $zk->setUser(
                                    $arguments['uid'],
                                    $arguments['userid'],
                                    $data['name'],
                                    $data['password'],
                                    $data['role'],
                                    $data['cardno']
                                );
                                $zk->disconnect();
                                
                                if ($result) {
                                    Notification::make()
                                        ->title('Utilisateur modifié avec succès')
                                        ->success()
                                        ->send();
                                } else {
                                    Notification::make()
                                        ->title('Échec de la modification')
                                        ->danger()
                                        ->send();
                                }
                            }
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Erreur lors de la modification')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Tables\Actions\Action::make('delete_user')
                    ->label('Supprimer')
                    ->requiresConfirmation()
                    ->action(function (array $arguments) {
                        try {
                            $device = dispositif_biometrique::find($arguments['device_id']);
                            $zk = new LaravelZkteco($device->ip, $device->port);
                            $connected = $zk->connect();
                            
                            if ($connected) {
                                $result = $zk->removeUser($arguments['uid']);
                                $zk->disconnect();
                                
                                if ($result) {
                                    Notification::make()
                                        ->title('Utilisateur supprimé avec succès')
                                        ->success()
                                        ->send();
                                } else {
                                    Notification::make()
                                        ->title('Échec de la suppression')
                                        ->danger()
                                        ->send();
                                }
                            }
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Erreur lors de la suppression')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListZktecoUsers::route('/'),
            'create' => Pages\CreateZktecoUser::route('/create'),
        ];
    }
} 