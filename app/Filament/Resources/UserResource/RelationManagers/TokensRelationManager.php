<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Services\Hydra\Client;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\PersonalAccessToken;

class TokensRelationManager extends RelationManager
{
    protected static string $relationship = 'tokens';

    protected static ?string $recordTitleAttribute = 'name';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('abilities'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Action::make('Create')->action(function ($livewire, array $data) {
                    $expiresAt = ! is_null($data['expires_at']) ? Carbon::parse($data['expires_at']) : null;
                    $token = $livewire->ownerRecord->createToken($data['name'], $data['abilities'], $expiresAt);
                    Cache::put('admin.accessKeyTmp.' . $token->accessToken->id, $token->plainTextToken, now()->addMinutes(3));
                })->schema([
                    TextInput::make('name'),
                    Select::make('abilities')->options(static function () {
                        return collect((new Client())->getScopes())->mapWithKeys(fn ($v) => [$v => $v]);
                    })->multiple(),
                    DateTimePicker::make('expires_at'),
                ]),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Select::make('abilities')->options(static function () {
                    return collect((new Client())->getScopes())->mapWithKeys(fn ($v) => [$v => $v]);
                })->multiple(),
                TextInput::make('token')
                    ->disabled()
                    ->visible(fn (PersonalAccessToken $record) => Cache::has('admin.accessKeyTmp.' . $record->id))
                    ->hint('Will be hidden 3 minutes after access token creation.')
                    ->formatStateUsing(function (PersonalAccessToken $record) {
                        return Cache::get('admin.accessKeyTmp.' . $record->id);
                    }),
                DateTimePicker::make('expires_at'),
            ]);
    }
}
