<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AvanceResource\Pages;
use App\Filament\Resources\AvanceResource\RelationManagers;
use App\Models\Avance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AvanceResource extends Resource
{
    protected static ?string $model = Avance::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_type')
                    ->label('Type utilisateur')
                    ->options([
                        'employe' => 'Employé',
                        'grh' => 'GRH',
                    ])
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn (callable $set) => $set('user_id', null)),
                Forms\Components\Select::make('user_id')
                    ->label('Utilisateur')
                    ->options(function (callable $get) {
                        $userType = $get('user_type');
                        if ($userType === 'employe') {
                            return \App\Models\Employe::query()
                                ->get()
                                ->mapWithKeys(fn ($e) => [
                                    $e->id => "[{$e->id}] {$e->name} {$e->prenom} | {$e->email} | {$e->Numero_telephone}",
                                ]);
                        } elseif ($userType === 'grh') {
                            return \App\Models\Grh::query()
                                ->get()
                                ->mapWithKeys(fn ($g) => [
                                    $g->id => "[{$g->id}] {$g->name} {$g->prenom} | {$g->email} | {$g->Numero_telephone}",
                                ]);
                        }
                        return [];
                    })
                    ->required()
                    ->searchable()
                    ->disabled(fn (callable $get) => !$get('user_type')),
                Forms\Components\TextInput::make('mois')
                    ->label('Mois (YYYY-MM)')
                    ->required(),
                Forms\Components\TextInput::make('montant')
                    ->label('Montant')
                    ->numeric()
                    ->required(),
                Forms\Components\Textarea::make('motif')
                    ->label('Motif'),
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
                Tables\Columns\TextColumn::make('user_type')->label('Type utilisateur')->sortable(),
                Tables\Columns\TextColumn::make('user_id')->label('ID utilisateur')->sortable(),
                Tables\Columns\TextColumn::make('mois')->label('Mois')->sortable(),
                Tables\Columns\TextColumn::make('montant')->label('Montant')->sortable(),
                Tables\Columns\TextColumn::make('motif')->label('Motif')->limit(30),
                Tables\Columns\TextColumn::make('created_at')->label('Créé le')->dateTime('d/m/Y H:i'),
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
            'index' => Pages\ListAvances::route('/'),
            'create' => Pages\CreateAvance::route('/create'),
            'edit' => Pages\EditAvance::route('/{record}/edit'),
        ];
    }
}
