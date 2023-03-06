<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Services\Hydra\Client;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\PersonalAccessToken;

class TokensRelationManager extends RelationManager
{
    protected static string $relationship = 'tokens';

    protected static ?string $recordTitleAttribute = 'name';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('abilities'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\Action::make('Create')->action(function ($livewire, array $data) {
                    $token = $livewire->ownerRecord->createToken($data['name'], $data['abilities'], (is_null($data['expires_at'])) ? Carbon::parse($data['expires_at']) : null);
                    Cache::put('admin.accessKeyTmp.' . $token->accessToken->id, $token->plainTextToken, now()->addMinutes(3));
                })->form([
                    Forms\Components\TextInput::make('name'),
                    Forms\Components\Select::make('abilities')->options(static function () {
                        return collect((new Client())->getScopes())->mapWithKeys(fn($v) => [$v => $v]);
                    })->multiple(),
                    Forms\Components\DateTimePicker::make('expires_at'),
                ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('abilities')->options(static function () {
                    return collect((new Client())->getScopes())->mapWithKeys(fn($v) => [$v => $v]);
                })->multiple(),
                Forms\Components\TextInput::make('token')
                    ->disabled()
                    ->visible(fn(PersonalAccessToken $record) => Cache::has('admin.accessKeyTmp.' . $record->id))
                    ->hint('Will be hidden 3 minutes after access token creation.')
                    ->formatStateUsing(function (PersonalAccessToken $record) {
                        return Cache::get('admin.accessKeyTmp.' . $record->id);
                    }),
                Forms\Components\DateTimePicker::make('expires_at'),
            ]);
    }
}
