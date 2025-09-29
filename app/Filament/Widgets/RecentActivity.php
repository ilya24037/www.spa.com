<?php

namespace App\Filament\Widgets;

use App\Domain\Booking\Models\Booking;
use Filament\Actions\ViewAction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentActivity extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Последние бронирования';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Booking::query()
                    ->with(['client', 'master', 'ad'])
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('client.email')
                    ->label('Клиент')
                    ->searchable()
                    ->limit(20)
                    ->copyable()
                    ->icon('heroicon-o-user'),

                Tables\Columns\TextColumn::make('master.email')
                    ->label('Мастер')
                    ->searchable()
                    ->limit(20)
                    ->copyable()
                    ->icon('heroicon-o-briefcase'),

                Tables\Columns\TextColumn::make('ad.title')
                    ->label('Услуга')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->ad?->title),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'gray' => 'pending',
                        'warning' => 'awaiting_payment',
                        'success' => 'confirmed',
                        'danger' => 'cancelled',
                        'info' => 'completed',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Ожидает',
                        'awaiting_payment' => 'Оплата',
                        'confirmed' => 'Подтверждено',
                        'cancelled' => 'Отменено',
                        'completed' => 'Завершено',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label('Дата и время')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->icon('heroicon-o-calendar'),

                Tables\Columns\TextColumn::make('total_price')
                    ->label('Сумма')
                    ->money('RUB')
                    ->sortable()
                    ->icon('heroicon-o-currency-dollar'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создано')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'pending' => 'Ожидает',
                        'awaiting_payment' => 'Оплата',
                        'confirmed' => 'Подтверждено',
                        'cancelled' => 'Отменено',
                        'completed' => 'Завершено',
                    ]),

                Tables\Filters\Filter::make('today')
                    ->label('За сегодня')
                    ->query(fn (Builder $query): Builder => $query->whereDate('created_at', today())),

                Tables\Filters\Filter::make('upcoming')
                    ->label('Предстоящие')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('scheduled_at', '>=', now())
                        ->where('status', 'confirmed')
                    ),
            ])
            ->actions([
                ViewAction::make()
                    ->url(fn ($record) => "/admin/bookings/{$record->id}"),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([5, 10, 25]);
    }
}