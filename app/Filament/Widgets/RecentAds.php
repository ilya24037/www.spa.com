<?php

namespace App\Filament\Widgets;

use App\Domain\Ad\Models\Ad;
use App\Domain\Ad\Enums\AdStatus;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentAds extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Последние объявления';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Ad::query()
                    ->with(['user'])
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Заголовок')
                    ->limit(30)
                    ->searchable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Пользователь')
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'gray' => AdStatus::DRAFT->value,
                        'warning' => AdStatus::PENDING_MODERATION->value,
                        'success' => AdStatus::ACTIVE->value,
                        'danger' => AdStatus::REJECTED->value,
                        'danger' => AdStatus::BLOCKED->value,
                        'gray' => AdStatus::ARCHIVED->value,
                    ])
                    ->formatStateUsing(fn ($state): string => match ($state instanceof AdStatus ? $state->value : $state) {
                        AdStatus::DRAFT->value => 'Черновик',
                        AdStatus::PENDING_MODERATION->value => 'На модерации',
                        AdStatus::ACTIVE->value => 'Активно',
                        AdStatus::REJECTED->value => 'Отклонено',
                        AdStatus::BLOCKED->value => 'Заблокировано',
                        AdStatus::ARCHIVED->value => 'В архиве',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('price')
                    ->label('Цена')
                    ->money('RUB')
                    ->sortable(),

                Tables\Columns\TextColumn::make('views_count')
                    ->label('Просмотров')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создано')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->actions([
                Action::make('view')
                    ->label('Просмотр')
                    ->icon('heroicon-m-eye')
                    ->url(fn (Ad $record): string =>
                        \App\Filament\Resources\AdResource::getUrl('view', ['record' => $record])
                    ),
            ])
            ->paginated([5, 10]);
    }
}