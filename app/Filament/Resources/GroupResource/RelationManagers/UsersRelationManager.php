<?php

namespace App\Filament\Resources\GroupResource\RelationManagers;

use App\Enums\GroupUserLevel;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Actions\AttachAction;

class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    protected static ?string $recordTitleAttribute = 'name';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\SelectColumn::make('level')
                    ->options(GroupUserLevel::class)
                    ->rules(['required']),
                Tables\Columns\TextInputColumn::make('title'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                AttachAction::make()->form(fn(AttachAction $action): array => [
                    $action->getRecordSelect(),
                    Forms\Components\Select::make('level')->required()
                        ->options(GroupUserLevel::class),
                ]),
            ])
            ->actions([
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DetachBulkAction::make(),
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('level')->required()
                    ->options(GroupUserLevel::class),
            ]);
    }
}
