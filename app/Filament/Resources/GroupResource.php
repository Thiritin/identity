<?php

namespace App\Filament\Resources;

use App\Enums\GroupTypeEnum;
use App\Filament\Resources\GroupResource\Pages\CreateGroup;
use App\Filament\Resources\GroupResource\Pages\EditGroup;
use App\Filament\Resources\GroupResource\Pages\ListGroups;
use App\Filament\Resources\GroupResource\RelationManagers\UsersRelationManager;
use App\Models\Group;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group as SchemaGroup;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class GroupResource extends Resource
{
    protected static ?string $model = Group::class;

    protected static ?string $slug = 'groups';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|\UnitEnum|null $navigationGroup = 'Identity';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                SchemaGroup::make()->columnSpan(2)->schema([
                    Section::make()->schema([
                        SchemaGroup::make()->columns()->schema([
                            Placeholder::make('id')
                                ->label('Internal ID')
                                ->content(fn (?Group $record): string => $record?->id ?? '-'),
                            Placeholder::make('hashid')
                                ->label('Public ID')
                                ->content(fn (?Group $record): string => $record?->hashid() ?? '-'),
                        ]),
                        TextInput::make('system_name')
                            ->label('System Name')
                            ->hint('Unique system name, should be left empty in most cases.')
                            ->unique('groups', 'system_name', ignoreRecord: true)
                            ->disabled(fn (?Group $record) => $record?->exists),
                        TextInput::make('name')
                            ->required(),
                        TextInput::make('nextcloud_folder_name')
                            ->label('Nextcloud Folder Name')
                            ->hiddenOn('create')
                            ->required(fn (?Group $record
                            ) => $record !== null && ! empty($record->nextcloud_folder_id))
                            ->hint('Leave empty if the group should not be allowed to access Nextcloud.')
                            ->unique('groups', 'nextcloud_folder_name', ignoreRecord: true),
                        Textarea::make('description')->rows(5),
                    ]),
                ]),

                SchemaGroup::make()->columnSpan(1)->schema([
                    Section::make()->schema([

                        Select::make('type')->options([
                            GroupTypeEnum::Default->value => 'Default',
                            GroupTypeEnum::Automated->value => 'Automated',
                            GroupTypeEnum::Department->value => 'Department',
                            GroupTypeEnum::Team->value => 'Team',
                        ])->required(),
                    ]),

                    Placeholder::make('created_at')
                        ->label('Created Date')
                        ->content(fn (?Group $record): string => $record?->created_at?->diffForHumans() ?? '-'),

                    Placeholder::make('updated_at')
                        ->label('Last Modified Date')
                        ->content(fn (?Group $record): string => $record?->updated_at?->diffForHumans() ?? '-'),
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
                    ->badge()
                    ->color(fn (GroupTypeEnum $state): string => match ($state) {
                        GroupTypeEnum::Default => 'gray',
                        GroupTypeEnum::Automated => 'info',
                        GroupTypeEnum::Department => 'success',
                        GroupTypeEnum::Team => 'warning',
                    })
                    ->sortable(),

                TextColumn::make('users_count')
                    ->label('Members')
                    ->counts('users')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(GroupTypeEnum::class),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGroups::route('/'),
            'create' => CreateGroup::route('/create'),
            'edit' => EditGroup::route('/{record}/edit'),
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
