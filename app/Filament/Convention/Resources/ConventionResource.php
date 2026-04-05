<?php

namespace App\Filament\Convention\Resources;

use App\Filament\Convention\Resources\ConventionResource\Pages\CreateConvention;
use App\Filament\Convention\Resources\ConventionResource\Pages\EditConvention;
use App\Filament\Convention\Resources\ConventionResource\Pages\ListConventions;
use App\Models\Convention;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ConventionResource extends Resource
{
    protected static ?string $model = Convention::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    public static function form(Schema $schema): Schema
    {
        return $schema->inlineLabel()->components([
            Tabs::make('Convention')
                ->tabs([
                    Tab::make('Details')
                        ->schema([
                            TextInput::make('name')->required()->maxLength(255),
                            TextInput::make('year')
                                ->numeric()
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->minValue(1995),
                            TextInput::make('theme')->maxLength(255),
                            DatePicker::make('start_date'),
                            DatePicker::make('end_date'),
                            TextInput::make('location')->maxLength(255),
                            TextInput::make('attendees_count')->numeric()->minValue(0),
                            TextInput::make('website_url')->url()->maxLength(255),
                            TextInput::make('conbook_url')->url()->maxLength(255),
                        ]),
                    Tab::make('Background')
                        ->schema([
                            FileUpload::make('background_image_path')
                                ->disk('s3')
                                ->directory('conventions/backgrounds')
                                ->visibility('public')
                                ->image()
                                ->imagePreviewHeight('200')
                                ->maxSize(10240)
                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp']),
                        ]),
                    Tab::make('Dailies')
                        ->schema([
                            Repeater::make('dailies')
                                ->schema([
                                    TextInput::make('title')->required(),
                                    TextInput::make('url')->required(),
                                ])
                                ->reorderable()
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string => $state['title'] ?? null),
                        ]),
                    Tab::make('Videos')
                        ->schema([
                            Repeater::make('videos')
                                ->schema([
                                    TextInput::make('title')->required(),
                                    TextInput::make('url')->url()->required(),
                                ])
                                ->reorderable()
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string => $state['title'] ?? null),
                        ]),
                    Tab::make('Photos')
                        ->schema([
                            Repeater::make('photos')
                                ->schema([
                                    TextInput::make('title')->required(),
                                    TextInput::make('url')->url()->required(),
                                    TextInput::make('thumb')->url()->required(),
                                ])
                                ->reorderable()
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string => $state['title'] ?? null),
                        ]),
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('background_image_path')
                    ->disk('s3')
                    ->label('Background'),
                TextColumn::make('year')->sortable(),
                TextColumn::make('name')->sortable()->searchable(),
                TextColumn::make('theme')->toggleable(),
                TextColumn::make('start_date')->date()->toggleable(),
                TextColumn::make('end_date')->date()->toggleable(),
                TextColumn::make('attendees_count')->label('Attendees')->sortable(),
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
