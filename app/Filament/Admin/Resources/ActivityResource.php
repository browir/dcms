<?php

namespace App\Filament\Admin\Resources;

use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Jacobtims\FilamentLogger\Resources\ActivityResource as BaseActivityResource;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\Models\Activity as ActivityModel;

class ActivityResource extends BaseActivityResource
{
    public static function getNavigationIcon(): string
    {
        return '';
    }

    // Inherit everything from the base resource, override just the table
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Column 1: Mobile card view (shows on mobile, hidden on desktop via CSS)
                TextColumn::make('id')
                    ->label('Log')
                    ->view('filament.tables.columns.activity-log-mobile-card'),

                // Column 2+: Desktop-only columns
                TextColumn::make('log_name')
                    ->label(__('filament-logger::filament-logger.resource.label.type'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucwords($state))
                    ->sortable()
                    ->visibleFrom('md'),

                TextColumn::make('event')
                    ->label(__('filament-logger::filament-logger.resource.label.event'))
                    ->sortable()
                    ->visibleFrom('md'),

                TextColumn::make('description')
                    ->label(__('filament-logger::filament-logger.resource.label.description'))
                    ->toggleable()
                    ->toggledHiddenByDefault()
                    ->wrap()
                    ->visibleFrom('md'),

                TextColumn::make('subject_type')
                    ->label(__('filament-logger::filament-logger.resource.label.subject'))
                    ->formatStateUsing(function ($state, Model $record) {
                        /** @var Activity&ActivityModel $record */
                        if (! $state) {
                            return '-';
                        }

                        return Str::of($state)->afterLast('\\')->headline().' # '.$record->subject_id;
                    })
                    ->visibleFrom('md'),

                TextColumn::make('causer.name')
                    ->label(__('filament-logger::filament-logger.resource.label.user'))
                    ->visibleFrom('md'),

                TextColumn::make('created_at')
                    ->label(__('filament-logger::filament-logger.resource.label.logged_at'))
                    ->dateTime(config('filament-logger.datetime_format', 'd/m/Y H:i:s'), config('app.timezone'))
                    ->sortable()
                    ->visibleFrom('md'),
            ])
            ->defaultSort('created_at', 'desc')
            ->toolbarActions([])
            ->filters([
                SelectFilter::make('log_name')
                    ->label(__('filament-logger::filament-logger.resource.label.type'))
                    ->options(static::getLogNameListOptions()),

                SelectFilter::make('subject_type')
                    ->label(__('filament-logger::filament-logger.resource.label.subject_type'))
                    ->options(static::getSubjectTypeListOptions()),

                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('logged_at')
                            ->label(__('filament-logger::filament-logger.resource.label.logged_at'))
                            ->displayFormat(config('filament-logger.date_format', 'd/m/Y')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['logged_at'],
                            fn (Builder $query, $date): Builder => $query->whereDate('created_at', $date),
                        );
                    }),
            ]);
    }

    protected static function getSubjectTypeListOptions(): array
    {
        if (config('filament-logger.resources.enabled', true)) {
            $subjects = [];
            $exceptResources = [...config('filament-logger.resources.exclude'), config('filament-logger.activity_resource')];
            $removedExcludedResources = collect(Filament::getResources())->filter(function ($resource) use ($exceptResources) {
                return ! in_array($resource, $exceptResources);
            });
            foreach ($removedExcludedResources as $resource) {
                $model = $resource::getModel();
                $subjects[$model] = Str::of(class_basename($model))->headline();
            }

            return $subjects;
        }

        return [];
    }

    protected static function getLogNameListOptions(): array
    {
        $customs = [];
        foreach (config('filament-logger.custom') ?? [] as $custom) {
            $customs[$custom['log_name']] = $custom['log_name'];
        }

        return array_merge(
            config('filament-logger.resources.enabled') ? [config('filament-logger.resources.log_name') => config('filament-logger.resources.log_name')] : [],
            config('filament-logger.models.enabled') ? [config('filament-logger.models.log_name') => config('filament-logger.models.log_name')] : [],
            config('filament-logger.access.enabled') ? [config('filament-logger.access.log_name') => config('filament-logger.access.log_name')] : [],
            config('filament-logger.notifications.enabled') ? [config('filament-logger.notifications.log_name') => config('filament-logger.notifications.log_name')] : [],
            $customs,
        );
    }
}
