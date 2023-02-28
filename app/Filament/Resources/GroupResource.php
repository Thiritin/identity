<?php

namespace App\Filament\Resources;

use App\Enums\GroupTypeEnum;
use App\Filament\Resources\GroupResource\Pages;
use App\Filament\Resources\GroupResource\RelationManagers\UsersRelationManager;
use App\Models\Group;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Group as FilamentGroup;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables\Columns\TextColumn;

class GroupResource extends Resource
{
    use Translatable;

    protected static ?string $model = Group::class;

    protected static ?string $slug = 'groups';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationIcon = "heroicon-o-users";

    public static function getTranslatableLocales(): array
    {
        return ['en', 'de'];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                FilamentGroup::make()->columnSpan(2)->schema([
                    Card::make()->schema([
                        \Filament\Forms\Components\Group::make()->columns()->schema([
                            Placeholder::make('id')
                                ->label('Internal ID')
                                ->content(fn(?Group $record): string => $record?->id ?? '-'),
                            Placeholder::make('hashid')
                                ->label('Public ID')
                                ->content(fn(?Group $record): string => $record?->hashid() ?? '-'),
                        ]),
                        TextInput::make('name')
                            ->hint('Translatable')
                            ->hintIcon('heroicon-s-translate')
                            ->required(),
                        Textarea::make('description')->rows(5),
                    ]),
                ]),

                FilamentGroup::make()->columnSpan(1)->schema([
                    Card::make()->schema([

                        Select::make('Type')->options(GroupTypeEnum::class),

                        FileUpload::make('logo')
                            ->image()
                            ->disk('avatars')
                            ->label('Group Photo')
                            ->imageResizeTargetWidth('512')
                            ->imageResizeTargetHeight('512')
                            ->imagePreviewHeight('256'),
                    ]),

                    Placeholder::make('created_at')
                        ->label('Created Date')
                        ->content(fn(?Group $record): string => $record?->created_at?->diffForHumans() ?? '-'),

                    Placeholder::make('updated_at')
                        ->label('Last Modified Date')
                        ->content(fn(?Group $record): string => $record?->updated_at?->diffForHumans() ?? '-'),
                ]),
            ])->columns(3);
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

    public static function getRelations(): array
    {
        return [
            UsersRelationManager::class
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }
}
