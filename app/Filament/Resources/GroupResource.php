<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GroupResource\Pages;
use App\Models\Group;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables\Columns\TextColumn;

class GroupResource extends Resource
{
    protected static ?string $model = Group::class;

    protected static ?string $slug = 'groups';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
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
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                          ->searchable()
                          ->sortable(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGroups::route('/'),
            'create' => Pages\CreateGroup::route('/create'),
            'edit' => Pages\EditGroup::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }
}
