<?php

namespace App\Filament\Resources;

use App\Enums\GroupTypeEnum;
use App\Filament\Resources\GroupResource\Pages;
use App\Filament\Resources\GroupResource\RelationManagers\UsersRelationManager;
use App\Models\Group;
use Filament\Forms\Components\Group as FilamentGroup;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Concerns\Translatable;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GroupResource extends Resource
{

    protected static ?string $model = Group::class;

    protected static ?string $slug = 'groups';
    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationIcon = "heroicon-o-users";

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                FilamentGroup::make()->columnSpan(2)->schema([
                    Section::make()->schema([
                        FilamentGroup::make()->columns()->schema([
                            Placeholder::make('id')
                                ->label('Internal ID')
                                ->content(fn(?Group $record): string => $record?->id ?? '-'),
                            Placeholder::make('hashid')
                                ->label('Public ID')
                                ->content(fn(?Group $record): string => $record?->hashid() ?? '-'),
                        ]),
                        TextInput::make('system_name')
                            ->label('System Name')
                            ->hint('Unique system name, should be left empty in most cases.')
                            ->unique('groups', 'system_name', ignoreRecord: true)
                            ->disabled(fn(?Group $record) => $record?->exists),
                        TextInput::make('name')
                            ->required(),
                        TextInput::make('nextcloud_folder_name')
                            ->label('Nextcloud Folder Name')
                            ->hiddenOn('create')
                            ->required(fn(Group|null $record
                            ) => $record !== null && !empty($record->nextcloud_folder_id))
                            ->hint('Leave empty if the group should not be allowed to access Nextcloud.')
                            ->unique('groups', 'nextcloud_folder_name', ignoreRecord: true),
                        Textarea::make('description')->rows(5),
                    ]),
                ]),

                FilamentGroup::make()->columnSpan(1)->schema([
                    Section::make()->schema([

                        Select::make('type')->options([
                            GroupTypeEnum::Default->value => "Default",
                            GroupTypeEnum::Automated->value => "Automated",
                            GroupTypeEnum::Department->value => "Department",
                        ])->required(),
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
                TextColumn::make('type')
                    ->sortable()
                    ->formatStateUsing(fn($state) => ucfirst($state->value)),
            ])
            ->actions([
                EditAction::make(),
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
            UsersRelationManager::class,
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }
}
