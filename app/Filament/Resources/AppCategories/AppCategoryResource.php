<?php

namespace App\Filament\Resources\AppCategories;

use App\Filament\Resources\AppCategories\Pages\CreateAppCategory;
use App\Filament\Resources\AppCategories\Pages\EditAppCategory;
use App\Filament\Resources\AppCategories\Pages\ListAppCategories;
use App\Models\AppCategory;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AppCategoryResource extends Resource
{
    protected static ?string $model = AppCategory::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static string|\UnitEnum|null $navigationGroup = 'OAuth';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0)
                    ->required()
                    ->minValue(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->sortable()->searchable(),
                TextColumn::make('sort_order')->sortable(),
                TextColumn::make('apps_count')->counts('apps')->label('Apps'),
            ])
            ->defaultSort('sort_order');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAppCategories::route('/'),
            'create' => CreateAppCategory::route('/create'),
            'edit' => EditAppCategory::route('/{record}/edit'),
        ];
    }
}
