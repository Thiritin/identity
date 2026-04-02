<?php

namespace App\Filament\Resources\GroupResource\RelationManagers;

use App\Enums\GroupUserLevel;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Table;

class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    protected static ?string $recordTitleAttribute = 'name';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name'),
                SelectColumn::make('level')
                    ->options(GroupUserLevel::class)
                    ->rules(['required']),
                TextInputColumn::make('title'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                AttachAction::make()->form(fn (AttachAction $action): array => [
                    $action->getRecordSelect(),
                    Select::make('level')->required()
                        ->options(GroupUserLevel::class),
                ]),
            ])
            ->recordActions([
                DetachAction::make(),
            ])
            ->toolbarActions([
                DetachBulkAction::make(),
            ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Select::make('level')->required()
                    ->options(GroupUserLevel::class),
            ]);
    }
}
