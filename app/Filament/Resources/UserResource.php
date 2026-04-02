<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Filament\Resources\UserResource\RelationManagers\ActionsRelationManager;
use App\Filament\Resources\UserResource\RelationManagers\GroupsRelationManager;
use App\Filament\Resources\UserResource\RelationManagers\TokensRelationManager;
use App\Filament\Resources\UserResource\Widgets\UserStatsWidget;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $slug = 'users';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user';

    protected static string|\UnitEnum|null $navigationGroup = 'Identity';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),

                TextInput::make('email')
                    ->email()
                    ->required(),

                DatePicker::make('email_verified_at')
                    ->disabled()
                    ->label('Verified At'),

                FileUpload::make('profile_photo_path')
                    ->image()
                    ->disk('s3-avatars')
                    ->label('Profile Photo')
                    ->imageResizeTargetWidth('512')
                    ->imageResizeTargetHeight('512')
                    ->imagePreviewHeight('256'),

                Placeholder::make('created_at')
                    ->label('Created Date')
                    ->content(fn (?User $record): string => $record?->created_at?->diffForHumans() ?? '-'),

                Placeholder::make('updated_at')
                    ->label('Last Modified Date')
                    ->content(fn (?User $record): string => $record?->updated_at?->diffForHumans() ?? '-'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount('twoFactors'))
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),

                IconColumn::make('email_verified_at')
                    ->label('Verified')
                    ->boolean(),

                IconColumn::make('is_admin')
                    ->label('Admin')
                    ->boolean(),

                IconColumn::make('two_factor_enabled')
                    ->label('2FA')
                    ->boolean()
                    ->getStateUsing(fn (User $record): bool => $record->two_factors_count > 0),

                IconColumn::make('suspended_at')
                    ->label('Suspended')
                    ->boolean()
                    ->trueColor('danger')
                    ->falseColor('success'),

                TextColumn::make('groups_count')
                    ->label('Groups')
                    ->counts('groups')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('email_verified_at')
                    ->label('Verified')
                    ->nullable(),

                TernaryFilter::make('is_admin')
                    ->label('Admin'),

                TernaryFilter::make('has_two_factor')
                    ->label('2FA')
                    ->queries(
                        true: fn ($query) => $query->has('twoFactors'),
                        false: fn ($query) => $query->doesntHave('twoFactors'),
                    ),

                TernaryFilter::make('suspended')
                    ->label('Suspended')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('suspended_at'),
                        false: fn ($query) => $query->whereNull('suspended_at'),
                    ),
            ])
            ->recordActions([
                Action::make('suspend')
                    ->label('Suspend')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Suspend User')
                    ->modalDescription('This will immediately revoke all sessions and block the user from logging in.')
                    ->visible(fn (User $record): bool => ! $record->isSuspended() && $record->id !== auth()->id())
                    ->action(fn (User $record) => $record->suspend()),

                Action::make('unsuspend')
                    ->label('Unsuspend')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (User $record): bool => $record->isSuspended())
                    ->action(fn (User $record) => $record->unsuspend()),
            ])
            ->groupedBulkActions([
                BulkAction::make('verify')
                    ->label('Verify Email')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->action(fn (Collection $records) => $records->each(
                        fn ($record) => $record->update(['email_verified_at' => now()])
                    ))
                    ->deselectRecordsAfterCompletion(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            GroupsRelationManager::class,
            ActionsRelationManager::class,
            TokensRelationManager::class,
        ];
    }

    public static function getWidgets(): array
    {
        return [
            UserStatsWidget::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email'];
    }
}
