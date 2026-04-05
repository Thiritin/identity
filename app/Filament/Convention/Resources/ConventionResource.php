<?php

namespace App\Filament\Convention\Resources;

use App\Filament\Convention\Resources\ConventionResource\Pages\CreateConvention;
use App\Filament\Convention\Resources\ConventionResource\Pages\EditConvention;
use App\Filament\Convention\Resources\ConventionResource\Pages\ListConventions;
use App\Models\Convention;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ConventionResource extends Resource
{
    protected static ?string $model = Convention::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('year')
                    ->numeric()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->minValue(1995),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->sortable()->searchable(),
                TextColumn::make('year')->sortable(),
                TextColumn::make('attendees_count')->counts('attendees')->label('Attendees'),
            ])
            ->defaultSort('year', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListConventions::route('/'),
            'create' => CreateConvention::route('/create'),
            'edit' => EditConvention::route('/{record}/edit'),
        ];
    }
}
