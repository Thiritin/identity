<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Models\Group;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Table;
use Filament\Tables\Columns\TextColumn;

class GroupsRelationManager extends RelationManager
{
    protected static string $relationship = 'groups';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('type')
                         ->required(),

                FileUpload::make('logo')
                          ->image()
                          ->disk('avatars')
                          ->label('Group Photo')
                          ->imageResizeTargetWidth('512')
                          ->imageResizeTargetHeight('512')
                          ->imagePreviewHeight('256'),

                Placeholder::make('created_at')
                           ->label('Created Date')
                           ->content(fn(?Group $record): string => $record?->created_at?->diffForHumans() ?? '-'),

                Placeholder::make('updated_at')
                           ->label('Last Modified Date')
                           ->content(fn(?Group $record): string => $record?->updated_at?->diffForHumans() ?? '-'),

                TextInput::make('internal_name')
                         ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type'),

                TextColumn::make('name')
                          ->searchable()
                          ->sortable(),

                TextColumn::make('description'),
            ]);
    }
}
