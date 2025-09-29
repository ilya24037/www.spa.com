<?php

namespace App\Filament\Resources;

use App\Domain\Notification\Models\Notification;
use App\Enums\NotificationType;
use App\Enums\NotificationStatus;
use App\Filament\Resources\NotificationResource\Pages;
use Filament\Forms;
use Filament\Resources\Resource;
use UnitEnum;
use BackedEnum;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Schemas\Schema;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Filament\Notifications\Notification as FilamentNotification;
use App\Domain\User\Models\User;

class NotificationResource extends Resource
{
    protected static ?string $model = Notification::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-bell';

    protected static string|null $navigationLabel = 'Уведомления';

    protected static string|null $modelLabel = 'Уведомление';

    protected static string|null $pluralModelLabel = 'Уведомления';

    protected static UnitEnum|string|null $navigationGroup = 'Система';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        // Временно отключено - нужна миграция для добавления столбца status
        // return Notification::where('status', NotificationStatus::PENDING)->count();
        return null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Section::make('Получатель')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Пользователь')
                            ->relationship('user', 'email')
                            ->searchable()
                            ->placeholder('Выберите пользователя'),

                        Forms\Components\Select::make('type')
                            ->label('Тип уведомления')
                            ->options(NotificationType::options())
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options(NotificationStatus::options())
                            ->required()
                            ->default(NotificationStatus::PENDING),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Содержание')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Заголовок')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('message')
                            ->label('Сообщение')
                            ->required()
                            ->rows(4)
                            ->maxLength(1000),

                        Forms\Components\KeyValue::make('data')
                            ->label('Дополнительные данные')
                            ->keyLabel('Ключ')
                            ->valueLabel('Значение'),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Настройки доставки')
                    ->schema([
                        Forms\Components\CheckboxList::make('channels')
                            ->label('Каналы доставки')
                            ->options([
                                'database' => 'База данных',
                                'mail' => 'Email',
                                'sms' => 'SMS',
                                'push' => 'Push-уведомления',
                                'telegram' => 'Telegram',
                            ])
                            ->default(['database'])
                            ->required(),

                        Forms\Components\Select::make('priority')
                            ->label('Приоритет')
                            ->options([
                                'low' => 'Низкий',
                                'medium' => 'Средний',
                                'high' => 'Высокий',
                                'urgent' => 'Срочный',
                            ])
                            ->default('medium')
                            ->required(),

                        Forms\Components\TextInput::make('group_key')
                            ->label('Ключ группировки')
                            ->helperText('Для группировки похожих уведомлений'),

                        Forms\Components\TextInput::make('template')
                            ->label('Шаблон')
                            ->helperText('Имя шаблона для отправки'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Планирование')
                    ->schema([
                        Forms\Components\DateTimePicker::make('scheduled_at')
                            ->label('Запланировать на')
                            ->helperText('Оставьте пустым для немедленной отправки'),

                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('Истекает')
                            ->helperText('После этого времени уведомление станет неактуальным'),

                        Forms\Components\TextInput::make('max_retries')
                            ->label('Максимум попыток')
                            ->numeric()
                            ->default(3)
                            ->min(0)
                            ->max(10),

                        Forms\Components\TextInput::make('locale')
                            ->label('Локаль')
                            ->default('ru')
                            ->maxLength(5),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.email')
                    ->label('Получатель')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-o-user')
                    ->iconColor('gray')
                    ->placeholder('Все пользователи'),

                Tables\Columns\BadgeColumn::make('type')
                    ->label('Тип')
                    ->colors([
                        'info' => NotificationType::INFO->value,
                        'success' => NotificationType::SUCCESS->value,
                        'warning' => NotificationType::WARNING->value,
                        'danger' => NotificationType::ERROR->value,
                        'secondary' => NotificationType::BOOKING_REMINDER->value,
                        'primary' => NotificationType::PAYMENT_CONFIRMATION->value,
                    ])
                    ->formatStateUsing(fn (string $state): string => NotificationType::from($state)->getLabel()),

                Tables\Columns\TextColumn::make('title')
                    ->label('Заголовок')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $title = $column->getState();
                        return strlen($title) > 40 ? $title : null;
                    }),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'warning' => NotificationStatus::PENDING->value,
                        'info' => NotificationStatus::SENT->value,
                        'success' => NotificationStatus::DELIVERED->value,
                        'secondary' => NotificationStatus::READ->value,
                        'danger' => NotificationStatus::FAILED->value,
                    ])
                    ->formatStateUsing(fn (string $state): string => NotificationStatus::from($state)->getLabel()),

                Tables\Columns\TextColumn::make('channels')
                    ->label('Каналы')
                    ->badge()
                    ->separator(',')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'database' => 'БД',
                        'mail' => 'Email',
                        'sms' => 'SMS',
                        'push' => 'Push',
                        'telegram' => 'TG',
                        default => $state,
                    }),

                Tables\Columns\BadgeColumn::make('priority')
                    ->label('Приоритет')
                    ->colors([
                        'gray' => 'low',
                        'info' => 'medium',
                        'warning' => 'high',
                        'danger' => 'urgent',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'low' => 'Низкий',
                        'medium' => 'Средний',
                        'high' => 'Высокий',
                        'urgent' => 'Срочный',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label('Запланировано')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->placeholder('Сейчас'),

                Tables\Columns\TextColumn::make('sent_at')
                    ->label('Отправлено')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создано')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->multiple()
                    ->options(NotificationStatus::options()),

                Tables\Filters\SelectFilter::make('type')
                    ->label('Тип')
                    ->multiple()
                    ->options(NotificationType::options()),

                Tables\Filters\SelectFilter::make('priority')
                    ->label('Приоритет')
                    ->options([
                        'low' => 'Низкий',
                        'medium' => 'Средний',
                        'high' => 'Высокий',
                        'urgent' => 'Срочный',
                    ]),

                Tables\Filters\Filter::make('scheduled')
                    ->label('Запланированные')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('scheduled_at')),

                Tables\Filters\Filter::make('failed')
                    ->label('Неудачные')
                    ->query(fn (Builder $query): Builder => $query->where('status', NotificationStatus::FAILED)),

                Tables\Filters\Filter::make('unread')
                    ->label('Непрочитанные')
                    ->query(fn (Builder $query): Builder => $query->whereNull('read_at')),

                Tables\Filters\Filter::make('today')
                    ->label('Сегодня')
                    ->query(fn (Builder $query): Builder => $query->whereDate('created_at', today())),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                // Кнопка отправки
                Action::make('send')
                    ->label('Отправить')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Notification $record): bool => $record->status === NotificationStatus::PENDING)
                    ->action(function (Notification $record): void {
                        // Здесь должна быть логика отправки через соответствующий сервис
                        $record->update([
                            'status' => NotificationStatus::SENT,
                            'sent_at' => now(),
                        ]);

                        FilamentNotification::make()
                            ->title('Уведомление отправлено')
                            ->success()
                            ->send();
                    }),

                // Кнопка повторной отправки
                Action::make('resend')
                    ->label('Отправить повторно')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (Notification $record): bool => $record->status === NotificationStatus::FAILED)
                    ->action(function (Notification $record): void {
                        $record->update([
                            'status' => NotificationStatus::PENDING,
                            'retry_count' => $record->retry_count + 1,
                        ]);

                        FilamentNotification::make()
                            ->title('Уведомление поставлено в очередь')
                            ->info()
                            ->send();
                    }),

                // Кнопка отмены
                Action::make('cancel')
                    ->label('Отменить')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Notification $record): bool => in_array($record->status, [
                        NotificationStatus::PENDING,
                        NotificationStatus::FAILED
                    ]))
                    ->action(function (Notification $record): void {
                        $record->update([
                            'status' => NotificationStatus::CANCELLED,
                        ]);

                        FilamentNotification::make()
                            ->title('Уведомление отменено')
                            ->warning()
                            ->send();
                    }),
            ])
            ->headerActions([
                // Кнопка массовой отправки
                Action::make('bulk_send')
                    ->label('Массовая отправка')
                    ->icon('heroicon-o-megaphone')
                    ->color('primary')
                    ->form([
                        Forms\Components\Select::make('recipient_type')
                            ->label('Получатели')
                            ->options([
                                'all' => 'Все пользователи',
                                'masters' => 'Все мастера',
                                'clients' => 'Все клиенты',
                                'active' => 'Активные пользователи',
                                'custom' => 'Выбрать пользователей',
                            ])
                            ->required()
                            ->reactive(),

                        Forms\Components\Select::make('users')
                            ->label('Пользователи')
                            ->multiple()
                            ->relationship('user', 'email')
                            ->searchable()
                            ->visible(fn (Forms\Get $get): bool => $get('recipient_type') === 'custom'),

                        Forms\Components\Select::make('type')
                            ->label('Тип уведомления')
                            ->options(NotificationType::options())
                            ->required(),

                        Forms\Components\TextInput::make('title')
                            ->label('Заголовок')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('message')
                            ->label('Сообщение')
                            ->required()
                            ->rows(4),

                        Forms\Components\CheckboxList::make('channels')
                            ->label('Каналы доставки')
                            ->options([
                                'database' => 'База данных',
                                'mail' => 'Email',
                                'sms' => 'SMS',
                                'push' => 'Push-уведомления',
                            ])
                            ->default(['database'])
                            ->required(),

                        Forms\Components\Select::make('priority')
                            ->label('Приоритет')
                            ->options([
                                'low' => 'Низкий',
                                'medium' => 'Средний',
                                'high' => 'Высокий',
                                'urgent' => 'Срочный',
                            ])
                            ->default('medium'),

                        Forms\Components\DateTimePicker::make('scheduled_at')
                            ->label('Запланировать отправку'),
                    ])
                    ->action(function (array $data): void {
                        // Получаем пользователей в зависимости от выбранного типа
                        $users = collect();

                        switch ($data['recipient_type']) {
                            case 'all':
                                $users = User::all();
                                break;
                            case 'masters':
                                $users = User::whereHas('masterProfile')->get();
                                break;
                            case 'clients':
                                $users = User::whereDoesntHave('masterProfile')->get();
                                break;
                            case 'active':
                                $users = User::where('last_login_at', '>=', now()->subDays(30))->get();
                                break;
                            case 'custom':
                                $users = User::whereIn('id', $data['users'] ?? [])->get();
                                break;
                        }

                        // Создаем уведомления для каждого пользователя
                        $createdCount = 0;
                        foreach ($users as $user) {
                            Notification::create([
                                'user_id' => $user->id,
                                'type' => $data['type'],
                                'status' => NotificationStatus::PENDING,
                                'title' => $data['title'],
                                'message' => $data['message'],
                                'channels' => $data['channels'],
                                'priority' => $data['priority'] ?? 'medium',
                                'scheduled_at' => $data['scheduled_at'] ?? now(),
                                'locale' => 'ru',
                            ]);
                            $createdCount++;
                        }

                        FilamentNotification::make()
                            ->title("Создано уведомлений: {$createdCount}")
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Массовая отправка выбранных
                    BulkAction::make('bulk_send_selected')
                        ->label('Отправить выбранные')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('success')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records): void {
                            $sentCount = 0;
                            foreach ($records as $record) {
                                if ($record->status === NotificationStatus::PENDING) {
                                    $record->update([
                                        'status' => NotificationStatus::SENT,
                                        'sent_at' => now(),
                                    ]);
                                    $sentCount++;
                                }
                            }

                            FilamentNotification::make()
                                ->title("Отправлено уведомлений: {$sentCount}")
                                ->success()
                                ->send();
                        }),

                    // Массовая отмена
                    BulkAction::make('bulk_cancel')
                        ->label('Отменить выбранные')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records): void {
                            $cancelledCount = 0;
                            foreach ($records as $record) {
                                if (in_array($record->status, [NotificationStatus::PENDING, NotificationStatus::FAILED])) {
                                    $record->update(['status' => NotificationStatus::CANCELLED]);
                                    $cancelledCount++;
                                }
                            }

                            FilamentNotification::make()
                                ->title("Отменено уведомлений: {$cancelledCount}")
                                ->warning()
                                ->send();
                        }),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNotifications::route('/'),
            'create' => Pages\CreateNotification::route('/create'),
            'edit' => Pages\EditNotification::route('/{record}/edit'),
            'view' => Pages\ViewNotification::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'message', 'user.email'];
    }
}