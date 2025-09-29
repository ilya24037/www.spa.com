<?php

namespace App\Filament\Widgets;

use App\Domain\User\Models\User;
use App\Enums\UserRole;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentUsers extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = [
        'md' => 2,
        'xl' => 3,
    ];

    protected static ?string $heading = 'Новые пользователи';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                User::query()
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('Фото')
                    ->circular()
                    ->defaultImageUrl(url('/images/default-avatar.png')),

                Tables\Columns\TextColumn::make('name')
                    ->label('Имя')
                    ->searchable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->limit(25)
                    ->copyable(),

                Tables\Columns\BadgeColumn::make('role')
                    ->label('Роль')
                    ->colors([
                        'gray' => UserRole::CLIENT->value,
                        'info' => UserRole::MASTER->value,
                        'warning' => UserRole::MODERATOR->value,
                        'success' => UserRole::ADMIN->value,
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        UserRole::CLIENT->value => 'Клиент',
                        UserRole::MASTER->value => 'Мастер',
                        UserRole::MODERATOR->value => 'Модератор',
                        UserRole::ADMIN->value => 'Админ',
                        default => $state,
                    }),

                Tables\Columns\IconColumn::make('email_verified_at')
                    ->label('Подтвержден')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->email_verified_at !== null),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Дата')
                    ->dateTime('d.m.Y')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Просмотр')
                    ->icon('heroicon-m-eye')
                    ->url(fn (User $record): string =>
                        \App\Filament\Resources\UserResource::getUrl('view', ['record' => $record])
                    ),
            ])
            ->paginated(false);
    }
}