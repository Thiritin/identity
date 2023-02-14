<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AppResource\Pages;
use App\Filament\Resources\AppResource\RelationManagers\GroupsRelationManager;
use App\Models\App;
use App\Services\Hydra\Client;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables\Columns\TextColumn;

class AppResource extends Resource
{
    protected static ?string $model = App::class;

    protected static ?string $slug = 'apps';

    protected static ?string $recordTitleAttribute = 'client_id';

    protected static ?string $navigationIcon = "heroicon-o-collection";

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()->columnSpan(2)->schema([
                    Group::make()->columns()->schema([
                        TextInput::make('client_id')->label('Client ID')->disabled(),
                        TextInput::make('data.client_secret')->disabled(),
                        TextInput::make('data.client_name')->columnSpan(2)->required(),
                    ]),
                    Tabs::make('Tabs')->tabs([
                        Tabs\Tab::make('Information')->icon('heroicon-o-paper-clip')->schema([
                            TextInput::make('data.client_uri')
                                ->url()
                                ->helperText('A URL string of a web page providing information about the client'),
                            TextInput::make('data.logo_uri')
                                ->url()
                                ->helperText('A URL string that references a logo for the client'),
                            TextInput::make('data.policy_uri')
                                ->url()
                                ->helperText('A URL string that references the privacy policy for the client'),
                            TextInput::make('data.tos_uri')
                                ->url()
                                ->helperText('A URL string that references the terms of service for the client'),
                        ]),
                        Tabs\Tab::make('Login')->icon('heroicon-o-login')->schema([
                            TagsInput::make('data.redirect_uris')->columnSpan(2),
                            Select::make('data.subject_type')->options([
                                "public" => "public",
                                "pairwise" => "pairwise",
                            ])->columnSpan(2),
                            Select::make('data.grant_types')->multiple()->options([
                                "authorization_code" => "authorization_code",
                                "refresh_token" => "refresh_token",
                                "implicit" => "implicit",
                                "client_credentials" => "client_credentials",
                            ])->columnSpan(2),
                            Select::make('data.response_types')->multiple()->options([
                                "code" => "code",
                                "id_token" => "id_token",
                                "token" => "token",
                            ])->columnSpan(2),
                        ]),
                        Tabs\Tab::make('Token')->icon('heroicon-o-lock-closed')->schema([
                            Select::make('data.token_endpoint_auth_method')->options([
                                "client_secret_post" => "client_secret_post",
                                "client_secret_basic" => "client_secret_basic",
                                "private_key_jwt" => "private_key_jwt",
                                "none" => "none",
                            ]),
                            TextInput::make('data.jwks_uri')
                                ->helperText('When authenticating with a signed jwks key against the token endpoint, you may enter a path to a keys.json containing the public keys of your app here.')
                                ->url(),

                            CheckboxList::make('data.scope')->options(static function () {
                                return collect((new Client())->getScopes())->mapWithKeys(fn($v) => [$v => $v]);
                            })->afterStateHydrated(function (CheckboxList $component, $state) {
                                if (!is_array($state)) {
                                    $state = explode(" ", $state);
                                }
                                $component->state($state);
                            }),

                        ]),
                        Tabs\Tab::make('Logout')->icon('heroicon-o-logout')->schema([
                            TagsInput::make('data.post_logout_redirect_uris')->columnSpan(2),
                            Section::make('Frontchannel Logout')
                                ->description('Configure frontchannel logout.')
                                ->schema([
                                    TextInput::make('data.frontchannel_logout_uri')
                                        ->helperText('Client URL that will cause the client to log itself out when rendered in an iframe by Identity')
                                        ->url(),
                                    Checkbox::make('data.frontchannel_logout_session_required')
                                        ->helperText('Specifying whether the client requires that a sid (session ID) Claim be included in the Logout Token to identify the client session with the OP when the frontchannel logout is used.'),
                                ]),
                            Section::make('Backchannel Logout')
                                ->description('Configure backchannel logout.')
                                ->schema([
                                    TextInput::make('data.backchannel_logout_uri')
                                        ->helperText('Client URL that will cause the client to log itself out when sent a Logout Token by Identity.')
                                        ->url(),
                                    Checkbox::make('data.backchannel_logout_session_required')
                                        ->helperText('Specifying whether the client requires that a sid (session ID) Claim be included in the Logout Token to identify the client session with the OP when the backchannel logout is used.'),

                                ]),
                        ]),

                        Tabs\Tab::make('Request')->icon('heroicon-o-status-online')->schema([
                            TagsInput::make('data.request_uris')->columnSpan(2),
                            Select::make('data.request_object_signing_alg')->options([
                                "none" => "none",
                                "RS256" => "RS256",
                                "ES256" => "ES256",
                            ])->columnSpan(2),
                        ]),

                        Tabs\Tab::make('CORS')->icon('heroicon-o-globe-alt')->schema([
                            TagsInput::make('data.allowed_cors_origins')->columnSpan(2),
                        ]),

                        Tabs\Tab::make('Audience')->schema([
                            TagsInput::make('data.audience')->columnSpan(1),
                        ]),
                    ]),
                ]),

                Group::make()->columnSpan(1)->schema([
                    Section::make('Owner')
                        ->columns(1)
                        ->schema([
                            Select::make('user_id')
                                ->label('Owner')
                                ->relationship('owner', 'name')->required(),
                        ]),
                    Section::make('Dashboard Settings')
                        ->description('These settings control the visibility of the app on the dashboard. This is NOT access control in any way.')
                        ->columns(1)
                        ->schema([
                            DateTimePicker::make('starts_at')->hint('UTC')->nullable(),
                            DateTimePicker::make('ends_at')->hint('UTC')->nullable(),
                            TextInput::make('priority')->required()->default('1000')->numeric()->minValue('1')->maxValue('10000')->hint('Sets order of dashboard apps'),
                            Checkbox::make('public')->hint('App is publicly visible (ignoring group selection)'),
                            TextInput::make('name')->required(),
                            TextInput::make('description')->required(),
                            Select::make('icon')->options([
                                "TicketDuotone" => "TicketDuotone",
                                "CogsDuotone" => "CogsDuotone",
                            ])->hint('Request new Icons @ Thiritin')->required(),
                            TextInput::make('url')->url(),
                        ]),
                    Card::make()->schema([
                        Placeholder::make('created_at')
                            ->label("Created At")
                            ->content(fn(?App $record): string => $record?->created_at?->diffForHumans() ?? '-'),

                        Placeholder::make('updated_at')
                            ->label("Updated At")
                            ->content(fn(?App $record): string => $record?->updated_at?->diffForHumans() ?? '-'),
                    ]),

                ]),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('client_id'),
                TextColumn::make('data.client_name')->label('Name'),
                TextColumn::make('owner.name'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApps::route('/'),
            'create' => Pages\CreateApp::route('/create'),
            'edit' => Pages\EditApp::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            GroupsRelationManager::class,
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [
            "data.client_name",
        ];
    }
}
