<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityResource\Pages\ListActivities;
use Filament\Resources\Resource;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static bool $isGloballySearchable = false;

    protected static string|\UnitEnum|null $navigationGroup = 'Logs';

    protected static ?string $slug = 'activities';

    protected static ?string $recordTitleAttribute = 'id';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('description')->formatStateUsing(fn (string $state) => __('activity.' . $state)),
                TextColumn::make('causer.name')->description(fn (Activity $record): string => ($record->causer_type ?? '')),
                TextColumn::make('subject.name')
                    ->formatStateUsing(static function (Column $column, $state): ?string {
                        $record = $column->getRecord();
                        $record->load('subject', 'causer');

                        return $record->subject->name ?? $record->subject->data['client_name'] ?? $record->subject->id ?? '';
                    })
                    ->description(fn (Activity $record): string => ($record->subject_type ?? '')),

                // TextColumn::make('changes'),
                TextColumn::make('created_at')->label('Date')->dateTime()->description(function (Activity $record) {
                    return $record->created_at->since();
                })->sortable(),
            ])
            ->filters([
                SelectFilter::make('causer_type')
                    ->label('Causer Type')
                    ->options(fn () => Activity::query()
                        ->whereNotNull('causer_type')
                        ->distinct()
                        ->pluck('causer_type', 'causer_type')
                        ->mapWithKeys(fn ($value) => [$value => class_basename($value)])
                        ->toArray()
                    ),
                SelectFilter::make('description')
                    ->label('Action')
                    ->options(fn () => Activity::query()
                        ->distinct()
                        ->pluck('description', 'description')
                        ->filter()
                        ->toArray()
                    ),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivities::route('/'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }
}
