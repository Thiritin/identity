<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\Column;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;

class ActionsRelationManager extends RelationManager
{
    protected static string $relationship = 'actions';

    protected static ?string $recordTitleAttribute = 'description';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('description')->formatStateUsing(fn (string $state
                ) => __('activity.' . $state)),
                Tables\Columns\TextColumn::make('subject.name')
                    ->formatStateUsing(static function (Column $column, $state): ?string {
                        $record = $column->getRecord();
                        $record->load('subject', 'causer');

                        return $record->subject->name ?? $record->subject->data['client_name'] ?? $record->subject->id ?? $record->causer->name ?? $record->causer->id ?? null;
                    })
                    ->description(fn (Activity $record): string => ($record->subject_type ?? $record->causer_type)),

                // TextColumn::make('changes'),
                Tables\Columns\TextColumn::make('created_at')->label('Date')->dateTime()->description(function (
                    Activity $record
                ) {
                    return $record->created_at->since();
                })->sortable(),
            ])
            ->filters([
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    // ...
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['from'] ?? null) {
                            $indicators['from'] = 'Created from ' . Carbon::parse($data['from'])->toFormattedDateString();
                        }

                        if ($data['until'] ?? null) {
                            $indicators['until'] = 'Created until ' . Carbon::parse($data['until'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),
            ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('description')
                    ->required()
                    ->maxLength(255),
            ]);
    }
}
