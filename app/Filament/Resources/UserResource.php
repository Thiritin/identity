<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers\ActionsRelationManager;
use App\Filament\Resources\UserResource\RelationManagers\GroupsRelationManager;
use App\Filament\Resources\UserResource\RelationManagers\TokensRelationManager;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $slug = 'users';

    protected static ?string $recordTitleAttribute = 'name';
    protected static ?string $navigationIcon = "heroicon-o-user";

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
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
                    ->content(fn(?User $record): string => $record?->created_at?->diffForHumans() ?? '-'),

                Placeholder::make('updated_at')
                    ->label('Last Modified Date')
                    ->content(fn(?User $record): string => $record?->updated_at?->diffForHumans() ?? '-'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),

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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email'];
    }
}
