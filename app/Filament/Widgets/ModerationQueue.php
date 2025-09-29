<?php

namespace App\Filament\Widgets;

use App\Domain\Ad\Models\Ad;
use App\Domain\Ad\Enums\AdStatus;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ModerationQueue extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Очередь модерации - Объявления';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Ad::query()
                    ->with(['user'])
                    ->whereIn('status', [
                        AdStatus::PENDING_MODERATION->value,
                        AdStatus::DRAFT->value
                    ])
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Тип')
                    ->formatStateUsing(fn () => 'Объявление')
                    ->color('info'),

                Tables\Columns\TextColumn::make('title')
                    ->label('Название')
                    ->limit(50)
                    ->searchable(),

                Tables\Columns\TextColumn::make('user.email')
                    ->label('Автор')
                    ->limit(30),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Статус')
                    ->formatStateUsing(fn ($state): string => match ($state instanceof AdStatus ? $state->value : $state) {
                        AdStatus::DRAFT->value => 'Черновик',
                        AdStatus::PENDING_MODERATION->value => 'На модерации',
                        default => $state instanceof AdStatus ? $state->value : $state,
                    })
                    ->color(fn ($state): string => match ($state instanceof AdStatus ? $state->value : $state) {
                        AdStatus::DRAFT->value => 'gray',
                        AdStatus::PENDING_MODERATION->value => 'warning',
                        default => 'secondary',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создано')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('views_count')
                    ->label('Просмотры')
                    ->color('primary'),
            ])
            ->actions([
                Action::make('view')
                    ->label('Просмотр')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Ad $record) => \App\Filament\Resources\AdResource::getUrl('view', ['record' => $record]))
                    ->openUrlInNewTab(),

                Action::make('approve')
                    ->label('Одобрить')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Ad $record): bool =>
                        $record->status === AdStatus::PENDING_MODERATION->value ||
                        $record->status === AdStatus::DRAFT->value
                    )
                    ->action(function (Ad $record): void {
                        $record->update(['status' => AdStatus::ACTIVE->value]);
                    }),

                Action::make('reject')
                    ->label('Отклонить')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Ad $record): void {
                        $record->update(['status' => AdStatus::REJECTED->value]);
                    }),
            ])
            ->defaultSort('created_at', 'asc')
            ->paginated([10, 25, 50])
            ->poll('60s');
    }
}