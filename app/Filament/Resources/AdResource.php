<?php

namespace App\Filament\Resources;

use App\Domain\Ad\Models\Ad;
use App\Domain\Ad\Services\AdModerationService;
use App\Domain\Admin\Services\AdminActionsService;
use App\Domain\Ad\Enums\AdStatus;
use App\Services\AdminActionLogger;
use App\Filament\Resources\AdResource\Pages;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Gate;
use UnitEnum;
use BackedEnum;

class AdResource extends Resource
{
    protected static ?string $model = Ad::class;

    private static function getStatusOptions(): array
    {
        return [
            AdStatus::DRAFT->value => 'Черновик',
            AdStatus::PENDING_MODERATION->value => 'На модерации',
            AdStatus::ACTIVE->value => 'Активно',
            AdStatus::REJECTED->value => 'Отклонено',
            AdStatus::ARCHIVED->value => 'В архиве',
            AdStatus::BLOCKED->value => 'Заблокировано',
        ];
    }

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-newspaper';

    protected static string|null $navigationLabel = 'Объявления';

    protected static string|null $modelLabel = 'Объявление';

    protected static string|null $pluralModelLabel = 'Объявления';

    protected static UnitEnum|string|null $navigationGroup = 'Контент';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        $count = Ad::where('status', AdStatus::PENDING_MODERATION->value)->count();
        return $count > 0 ? (string)$count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Название')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('category')
                            ->label('Категория')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('specialty')
                            ->label('Специализация')
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->label('Описание')
                            ->maxLength(5000)
                            ->rows(5),

                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options(self::getStatusOptions())
                            ->required(),

                        Forms\Components\Toggle::make('is_published')
                            ->label('Опубликовано')
                            ->default(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Параметры мастера')
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\TextInput::make('age')
                            ->label('Возраст')
                            ->maxLength(50),

                        Forms\Components\TextInput::make('height')
                            ->label('Рост')
                            ->maxLength(50),

                        Forms\Components\TextInput::make('weight')
                            ->label('Вес')
                            ->maxLength(50),

                        Forms\Components\TextInput::make('nationality')
                            ->label('Национальность')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('experience')
                            ->label('Опыт работы')
                            ->maxLength(255),

                        Forms\Components\Select::make('work_format')
                            ->label('Формат работы')
                            ->options([
                                'individual' => 'Индивидуально',
                                'group' => 'В группе',
                            ]),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Цены и условия')
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\TextInput::make('price')
                            ->label('Цена')
                            ->numeric()
                            ->prefix('₽'),

                        Forms\Components\TextInput::make('price_per_hour')
                            ->label('Цена за час')
                            ->numeric()
                            ->prefix('₽'),

                        Forms\Components\TextInput::make('outcall_price')
                            ->label('Выезд')
                            ->numeric()
                            ->prefix('₽'),

                        Forms\Components\TextInput::make('price_two_hours')
                            ->label('2 часа')
                            ->numeric()
                            ->prefix('₽'),

                        Forms\Components\TextInput::make('min_duration')
                            ->label('Минимальная продолжительность (мин)')
                            ->numeric()
                            ->suffix('мин'),

                        Forms\Components\TextInput::make('discount')
                            ->label('Скидка')
                            ->maxLength(255),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Контакты и локация')
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\TextInput::make('phone')
                            ->label('Телефон')
                            ->tel(),

                        Forms\Components\TextInput::make('whatsapp')
                            ->label('WhatsApp')
                            ->tel(),

                        Forms\Components\TextInput::make('telegram')
                            ->label('Telegram'),

                        Forms\Components\TextInput::make('address')
                            ->label('Адрес')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('travel_area')
                            ->label('Район выезда')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('travel_radius')
                            ->label('Радиус выезда')
                            ->maxLength(255),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Администрирование')
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Автор')
                            ->relationship('user', 'email')
                            ->searchable()
                            ->required(),

                        Forms\Components\Toggle::make('is_paid')
                            ->label('Оплачено'),

                        Forms\Components\DateTimePicker::make('paid_at')
                            ->label('Дата оплаты'),

                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('Истекает'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function infolist(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema
            ->schema([
                \Filament\Schemas\Components\Section::make('Основная информация')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('title')
                            ->label('Название')
                            ->columnSpanFull(),
                        \Filament\Infolists\Components\TextEntry::make('category')
                            ->label('Категория'),
                        \Filament\Infolists\Components\TextEntry::make('specialty')
                            ->label('Специализация'),
                        \Filament\Infolists\Components\TextEntry::make('status')
                            ->label('Статус')
                            ->badge()
                            ->formatStateUsing(fn ($state) => self::getStatusOptions()[$state instanceof AdStatus ? $state->value : $state] ?? $state),
                        \Filament\Infolists\Components\IconEntry::make('is_published')
                            ->label('Опубликовано')
                            ->boolean(),
                        \Filament\Infolists\Components\TextEntry::make('description')
                            ->label('Описание')
                            ->columnSpanFull()
                            ->prose()
                            ->markdown()
                            ->default('Описание отсутствует'),
                    ])
                    ->columns(2),

                \Filament\Schemas\Components\Section::make('Фото и медиа')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('photos')
                            ->label('Фотографии')
                            ->getStateUsing(function ($record) {
                                // Generate HTML grid from photos array
                                // Note: Using custom HTML because ImageEntry expects disk-relative paths,
                                // but our DB stores full paths like "/storage/photos/3/file.webp"
                                $photos = $record->photos;

                                if (!is_array($photos) || empty($photos)) {
                                    return 'Нет фото';
                                }

                                $html = '<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 10px;">';
                                foreach ($photos as $photo) {
                                    $url = asset($photo);
                                    $html .= '<div>';
                                    $html .= '<a href="' . $url . '" target="_blank">';
                                    $html .= '<img src="' . $url . '" style="width: 100%; height: 200px; object-fit: cover; border-radius: 8px; border: 1px solid #e5e7eb;">';
                                    $html .= '</a>';
                                    $html .= '</div>';
                                }
                                $html .= '</div>';

                                return $html;
                            })
                            ->html()
                            ->columnSpanFull(),
                        \Filament\Infolists\Components\TextEntry::make('video')
                            ->label('Видео')
                            ->default('Нет')
                            ->placeholder('Видео не добавлено'),
                    ])
                    ->collapsed(false)
                    ->columns(1),

                \Filament\Schemas\Components\Section::make('Параметры мастера')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('age')
                            ->label('Возраст'),
                        \Filament\Infolists\Components\TextEntry::make('height')
                            ->label('Рост'),
                        \Filament\Infolists\Components\TextEntry::make('weight')
                            ->label('Вес'),
                        \Filament\Infolists\Components\TextEntry::make('nationality')
                            ->label('Национальность'),
                        \Filament\Infolists\Components\TextEntry::make('experience')
                            ->label('Опыт работы'),
                        \Filament\Infolists\Components\TextEntry::make('work_format')
                            ->label('Формат работы'),
                    ])
                    ->columns(3),

                \Filament\Schemas\Components\Section::make('Цены и условия')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('price')
                            ->label('Цена')
                            ->money('RUB')
                            ->default('Не указана'),
                        \Filament\Infolists\Components\TextEntry::make('price_per_hour')
                            ->label('Цена за час')
                            ->money('RUB')
                            ->default('Не указана'),
                        \Filament\Infolists\Components\TextEntry::make('outcall_price')
                            ->label('Выезд')
                            ->money('RUB')
                            ->default('Не указана'),
                        \Filament\Infolists\Components\TextEntry::make('price_two_hours')
                            ->label('2 часа')
                            ->money('RUB')
                            ->default('Не указана'),
                        \Filament\Infolists\Components\TextEntry::make('min_duration')
                            ->label('Мин. продолжительность')
                            ->suffix(' мин')
                            ->default('Не указано'),
                        \Filament\Infolists\Components\TextEntry::make('discount')
                            ->label('Скидка')
                            ->default('Нет'),
                    ])
                    ->columns(3),

                \Filament\Schemas\Components\Section::make('Контакты и локация')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('phone')
                            ->label('Телефон'),
                        \Filament\Infolists\Components\TextEntry::make('whatsapp')
                            ->label('WhatsApp'),
                        \Filament\Infolists\Components\TextEntry::make('telegram')
                            ->label('Telegram'),
                        \Filament\Infolists\Components\TextEntry::make('address')
                            ->label('Адрес'),
                        \Filament\Infolists\Components\TextEntry::make('travel_area')
                            ->label('Район выезда'),
                        \Filament\Infolists\Components\TextEntry::make('travel_radius')
                            ->label('Радиус выезда'),
                    ])
                    ->columns(2),

                \Filament\Schemas\Components\Section::make('Информация об авторе')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('user.email')
                            ->label('Email автора'),
                        \Filament\Infolists\Components\TextEntry::make('created_at')
                            ->label('Создано')
                            ->dateTime('d.m.Y H:i'),
                        \Filament\Infolists\Components\TextEntry::make('updated_at')
                            ->label('Обновлено')
                            ->dateTime('d.m.Y H:i'),
                        \Filament\Infolists\Components\TextEntry::make('views_count')
                            ->label('Просмотров'),
                        \Filament\Infolists\Components\TextEntry::make('favorites_count')
                            ->label('В избранном'),
                        \Filament\Infolists\Components\IconEntry::make('is_paid')
                            ->label('Оплачено')
                            ->boolean(),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\ImageColumn::make('main_photo')
                    ->label('Фото')
                    ->getStateUsing(function (Ad $record): ?string {
                        $photos = is_string($record->photos) ? json_decode($record->photos, true) : $record->photos;
                        return is_array($photos) && count($photos) > 0 ? $photos[0]['url'] ?? null : null;
                    })
                    ->circular()
                    ->size(50),

                Tables\Columns\TextColumn::make('title')
                    ->label('Название')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        return strlen($column->getState()) > 30 ? $column->getState() : null;
                    }),

                Tables\Columns\TextColumn::make('user.email')
                    ->label('Автор')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-o-user')
                    ->iconColor('gray'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'gray' => AdStatus::DRAFT->value,
                        'warning' => AdStatus::PENDING_MODERATION->value,
                        'success' => AdStatus::ACTIVE->value,
                        'danger' => AdStatus::REJECTED->value,
                        'secondary' => AdStatus::ARCHIVED->value,
                        'primary' => AdStatus::BLOCKED->value,
                    ])
                    ->formatStateUsing(fn ($state): string =>
                        self::getStatusOptions()[$state instanceof AdStatus ? $state->value : $state]
                        ?? ($state instanceof AdStatus ? $state->value : $state)
                    ),

                Tables\Columns\IconColumn::make('is_published')
                    ->label('Опубликовано')
                    ->boolean(),

                Tables\Columns\TextColumn::make('price')
                    ->label('Цена')
                    ->money('RUB')
                    ->sortable(),

                Tables\Columns\TextColumn::make('price_per_hour')
                    ->label('Цена/час')
                    ->money('RUB')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('views_count')
                    ->label('Просмотры')
                    ->numeric()
                    ->sortable()
                    ->icon('heroicon-o-eye'),

                Tables\Columns\TextColumn::make('complaints_count')
                    ->label('Жалобы')
                    ->getStateUsing(fn (Ad $record): int => $record->complaints()->count())
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'danger' : 'gray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создано')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->multiple()
                    ->options(self::getStatusOptions())
                    ->default([AdStatus::PENDING_MODERATION->value]), // По умолчанию показываем на модерации

                Tables\Filters\TernaryFilter::make('is_published')
                    ->label('Опубликовано'),

                Tables\Filters\Filter::make('has_complaints')
                    ->label('С жалобами')
                    ->query(fn (Builder $query): Builder => $query->whereHas('complaints')),

                Tables\Filters\Filter::make('created_today')
                    ->label('Созданы сегодня')
                    ->query(fn (Builder $query): Builder => $query->whereDate('created_at', today())),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),

                // Кнопка одобрения
                Action::make('approve')
                    ->label('Одобрить')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Ad $record): bool => in_array($record->status->value, [
                        AdStatus::PENDING_MODERATION->value,
                        AdStatus::REJECTED->value
                    ]))
                    ->action(function (Ad $record): void {
                        $logger = app(AdminActionLogger::class);
                        $logger->log('approve_ad', $record);

                        app(AdModerationService::class)->approveAd($record);

                        Notification::make()
                            ->title('Объявление одобрено')
                            ->success()
                            ->send();
                    }),

                // Кнопка отклонения
                Action::make('reject')
                    ->label('Отклонить')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Ad $record): bool => !in_array($record->status->value, [
                        AdStatus::REJECTED->value,
                        AdStatus::BLOCKED->value
                    ]))
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Причина отклонения')
                            ->required(),
                    ])
                    ->action(function (Ad $record, array $data): void {
                        $logger = app(AdminActionLogger::class);
                        $logger->log('reject_ad', $record, ['reason' => $data['reason']]);

                        app(AdModerationService::class)->rejectAd($record, $data['reason']);

                        Notification::make()
                            ->title('Объявление отклонено')
                            ->warning()
                            ->send();
                    }),

                // Кнопка блокировки
                Action::make('block')
                    ->label('Заблокировать')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Ad $record): bool => $record->status->value !== AdStatus::BLOCKED->value)
                    ->action(function (Ad $record): void {
                        $logger = app(AdminActionLogger::class);
                        $logger->log('block_ad', $record);

                        app(AdModerationService::class)->block($record, 'Заблокировано администратором');

                        Notification::make()
                            ->title('Объявление заблокировано')
                            ->danger()
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    // Массовое одобрение
                    BulkAction::make('bulk_approve')
                        ->label('Одобрить выбранные')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records): void {
                            $logger = app(AdminActionLogger::class);
                            $ids = $records->pluck('id')->toArray();
                            $logger->logBulkAction('approve', $ids, Ad::class);

                            $service = app(AdminActionsService::class);
                            $result = $service->performBulkAction($ids, 'approve');

                            Notification::make()
                                ->title($result['message'])
                                ->success()
                                ->send();
                        }),

                    // Массовое отклонение
                    BulkAction::make('bulk_reject')
                        ->label('Отклонить выбранные')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->form([
                            Forms\Components\Textarea::make('reason')
                                ->label('Причина отклонения')
                                ->required(),
                        ])
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records, array $data): void {
                            $service = app(AdminActionsService::class);
                            $result = $service->performBulkAction(
                                $records->pluck('id')->toArray(),
                                'reject',
                                $data['reason']
                            );

                            Notification::make()
                                ->title($result['message'])
                                ->warning()
                                ->send();
                        }),

                    // Массовая блокировка
                    BulkAction::make('bulk_block')
                        ->label('Заблокировать выбранные')
                        ->icon('heroicon-o-no-symbol')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records): void {
                            $service = app(AdminActionsService::class);
                            $result = $service->performBulkAction(
                                $records->pluck('id')->toArray(),
                                'block',
                                'Заблокировано администратором'
                            );

                            Notification::make()
                                ->title($result['message'])
                                ->danger()
                                ->send();
                        }),

                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('60s')
            ->deferFilters();
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
            'index' => Pages\ListAds::route('/'),
            'create' => Pages\CreateAd::route('/create'),
            'edit' => Pages\EditAd::route('/{record}/edit'),
            'view' => Pages\ViewAd::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        // Показываем ВСЕ объявления для админа, не только свои
        return parent::getEloquentQuery()
            ->with(['user', 'complaints']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'description', 'user.email', 'phone'];
    }

    public static function canViewAny(): bool
    {
        return Gate::allows('viewAny', Ad::class);
    }

    public static function canCreate(): bool
    {
        return Gate::allows('create', Ad::class);
    }

    public static function canEdit($record): bool
    {
        return Gate::allows('update', $record);
    }

    public static function canDelete($record): bool
    {
        return Gate::allows('delete', $record);
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user() && auth()->user()->role->isAdmin();
    }
}