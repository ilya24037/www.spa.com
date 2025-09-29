<?php

namespace App\Filament\Resources;

use App\Domain\Master\Models\MasterProfile;
use App\Enums\MasterStatus;
use App\Filament\Resources\MasterProfileResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class MasterProfileResource extends Resource
{
    protected static ?string $model = MasterProfile::class;

    // protected static $navigationIcon = 'heroicon-o-briefcase';

    // protected static $navigationLabel = 'Мастера';

    protected static ?string $modelLabel = 'Мастер';

    protected static ?string $pluralModelLabel = 'Мастера';

    // protected static $navigationGroup = 'Пользователи';

    // protected static $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return MasterProfile::where('status', MasterStatus::PENDING->value)->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Пользователь')
                            ->relationship('user', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->disabled(fn (string $context): bool => $context === 'edit'),

                        Forms\Components\TextInput::make('display_name')
                            ->label('Имя для отображения')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('slug')
                            ->label('URL')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\TextInput::make('phone')
                            ->label('Телефон')
                            ->tel()
                            ->maxLength(20),

                        Forms\Components\TextInput::make('whatsapp')
                            ->label('WhatsApp')
                            ->maxLength(20),

                        Forms\Components\TextInput::make('telegram')
                            ->label('Telegram')
                            ->maxLength(50),

                        Forms\Components\Toggle::make('show_contacts')
                            ->label('Показывать контакты')
                            ->default(false),

                        Forms\Components\TextInput::make('experience_years')
                            ->label('Опыт работы (лет)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(50),

                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options([
                                MasterStatus::DRAFT->value => 'Черновик',
                                MasterStatus::PENDING->value => 'На проверке',
                                MasterStatus::ACTIVE->value => 'Активен',
                                MasterStatus::INACTIVE->value => 'Неактивен',
                                MasterStatus::BLOCKED->value => 'Заблокирован',
                                MasterStatus::SUSPENDED->value => 'Приостановлен',
                                MasterStatus::VACATION->value => 'В отпуске',
                                MasterStatus::ARCHIVED->value => 'В архиве',
                            ])
                            ->required()
                            ->default(MasterStatus::DRAFT->value),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('О мастере')
                    ->schema([
                        Forms\Components\Textarea::make('bio')
                            ->label('Краткое описание')
                            ->rows(3)
                            ->maxLength(500),

                        Forms\Components\RichEditor::make('description')
                            ->label('Полное описание')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Физические параметры')
                    ->schema([
                        Forms\Components\TextInput::make('age')
                            ->label('Возраст')
                            ->numeric()
                            ->minValue(18)
                            ->maxValue(100),

                        Forms\Components\TextInput::make('height')
                            ->label('Рост (см)')
                            ->numeric()
                            ->minValue(100)
                            ->maxValue(250),

                        Forms\Components\TextInput::make('weight')
                            ->label('Вес (кг)')
                            ->numeric()
                            ->minValue(30)
                            ->maxValue(200),

                        Forms\Components\TextInput::make('breast_size')
                            ->label('Размер груди')
                            ->maxLength(10),

                        Forms\Components\Select::make('hair_color')
                            ->label('Цвет волос')
                            ->options([
                                'blonde' => 'Блондинка',
                                'brunette' => 'Брюнетка',
                                'redhead' => 'Рыжая',
                                'black' => 'Черные',
                                'gray' => 'Седые',
                                'other' => 'Другое',
                            ]),

                        Forms\Components\Select::make('eye_color')
                            ->label('Цвет глаз')
                            ->options([
                                'blue' => 'Голубые',
                                'green' => 'Зеленые',
                                'brown' => 'Карие',
                                'gray' => 'Серые',
                                'black' => 'Черные',
                                'other' => 'Другое',
                            ]),

                        Forms\Components\TextInput::make('nationality')
                            ->label('Национальность')
                            ->maxLength(50),
                    ])
                    ->columns(3)
                    ->collapsible(),

                Forms\Components\Section::make('Статистика и рейтинг')
                    ->schema([
                        Forms\Components\TextInput::make('rating')
                            ->label('Рейтинг')
                            ->numeric()
                            ->disabled()
                            ->default(0),

                        Forms\Components\TextInput::make('reviews_count')
                            ->label('Количество отзывов')
                            ->numeric()
                            ->disabled()
                            ->default(0),

                        Forms\Components\TextInput::make('completed_bookings')
                            ->label('Выполнено заказов')
                            ->numeric()
                            ->disabled()
                            ->default(0),

                        Forms\Components\TextInput::make('views_count')
                            ->label('Просмотров')
                            ->numeric()
                            ->disabled()
                            ->default(0),
                    ])
                    ->columns(4)
                    ->collapsible(),

                Forms\Components\Section::make('Верификация и премиум')
                    ->schema([
                        Forms\Components\Toggle::make('is_verified')
                            ->label('Верифицирован')
                            ->default(false),

                        Forms\Components\Toggle::make('is_premium')
                            ->label('Премиум аккаунт')
                            ->default(false),

                        Forms\Components\DateTimePicker::make('premium_until')
                            ->label('Премиум до'),

                        Forms\Components\Toggle::make('is_published')
                            ->label('Опубликован')
                            ->default(false),

                        Forms\Components\DateTimePicker::make('moderated_at')
                            ->label('Дата модерации'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('SEO')
                    ->schema([
                        Forms\Components\TextInput::make('meta_title')
                            ->label('Meta заголовок')
                            ->maxLength(255),

                        Forms\Components\Textarea::make('meta_description')
                            ->label('Meta описание')
                            ->rows(3)
                            ->maxLength(500),
                    ])
                    ->collapsible()
                    ->collapsed(),
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

                Tables\Columns\ImageColumn::make('avatar')
                    ->label('Фото')
                    ->circular()
                    ->defaultImageUrl(url('/images/default-avatar.png')),

                Tables\Columns\TextColumn::make('display_name')
                    ->label('Имя')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'gray' => MasterStatus::DRAFT->value,
                        'warning' => MasterStatus::PENDING->value,
                        'success' => MasterStatus::ACTIVE->value,
                        'gray' => MasterStatus::INACTIVE->value,
                        'danger' => MasterStatus::BLOCKED->value,
                        'danger' => MasterStatus::SUSPENDED->value,
                        'info' => MasterStatus::VACATION->value,
                        'gray' => MasterStatus::ARCHIVED->value,
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        MasterStatus::DRAFT->value => 'Черновик',
                        MasterStatus::PENDING->value => 'На проверке',
                        MasterStatus::ACTIVE->value => 'Активен',
                        MasterStatus::INACTIVE->value => 'Неактивен',
                        MasterStatus::BLOCKED->value => 'Заблокирован',
                        MasterStatus::SUSPENDED->value => 'Приостановлен',
                        MasterStatus::VACATION->value => 'В отпуске',
                        MasterStatus::ARCHIVED->value => 'В архиве',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('rating')
                    ->label('Рейтинг')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 1) . ' ⭐' : '—'),

                Tables\Columns\TextColumn::make('reviews_count')
                    ->label('Отзывов')
                    ->sortable()
                    ->badge(),

                Tables\Columns\TextColumn::make('completed_bookings')
                    ->label('Заказов')
                    ->sortable()
                    ->badge()
                    ->color('success'),

                Tables\Columns\IconColumn::make('is_verified')
                    ->label('Верифицирован')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_premium')
                    ->label('Премиум')
                    ->boolean()
                    ->trueColor('warning'),

                Tables\Columns\TextColumn::make('experience_years')
                    ->label('Опыт')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state ? $state . ' лет' : '—')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('views_count')
                    ->label('Просмотров')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->multiple()
                    ->options([
                        MasterStatus::DRAFT->value => 'Черновик',
                        MasterStatus::PENDING->value => 'На проверке',
                        MasterStatus::ACTIVE->value => 'Активен',
                        MasterStatus::INACTIVE->value => 'Неактивен',
                        MasterStatus::BLOCKED->value => 'Заблокирован',
                        MasterStatus::SUSPENDED->value => 'Приостановлен',
                        MasterStatus::VACATION->value => 'В отпуске',
                        MasterStatus::ARCHIVED->value => 'В архиве',
                    ])
                    ->default([MasterStatus::PENDING->value]),

                Tables\Filters\TernaryFilter::make('is_verified')
                    ->label('Верифицирован'),

                Tables\Filters\TernaryFilter::make('is_premium')
                    ->label('Премиум'),

                Tables\Filters\TernaryFilter::make('is_published')
                    ->label('Опубликован'),

                Tables\Filters\Filter::make('high_rating')
                    ->label('Высокий рейтинг (4.5+)')
                    ->query(fn (Builder $query): Builder => $query->where('rating', '>=', 4.5)),

                Tables\Filters\Filter::make('experienced')
                    ->label('Опытные (5+ лет)')
                    ->query(fn (Builder $query): Builder => $query->where('experience_years', '>=', 5)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                // Одобрить профиль
                Action::make('approve')
                    ->label('Одобрить')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (MasterProfile $record): bool => $record->status === MasterStatus::PENDING->value)
                    ->requiresConfirmation()
                    ->action(function (MasterProfile $record): void {
                        $record->update([
                            'status' => MasterStatus::ACTIVE->value,
                            'is_published' => true,
                            'moderated_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Профиль мастера одобрен')
                            ->success()
                            ->send();
                    }),

                // Отклонить профиль
                Action::make('reject')
                    ->label('Отклонить')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (MasterProfile $record): bool => $record->status === MasterStatus::PENDING->value)
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Причина отклонения')
                            ->required()
                            ->rows(3),
                    ])
                    ->requiresConfirmation()
                    ->action(function (MasterProfile $record, array $data): void {
                        $record->update([
                            'status' => MasterStatus::DRAFT->value,
                            'moderated_at' => now(),
                        ]);

                        // Можно отправить уведомление мастеру с причиной

                        Notification::make()
                            ->title('Профиль мастера отклонен')
                            ->success()
                            ->send();
                    }),

                // Блокировка/разблокировка
                Action::make('toggle_block')
                    ->label(fn (MasterProfile $record): string =>
                        in_array($record->status, [MasterStatus::BLOCKED->value, MasterStatus::SUSPENDED->value])
                            ? 'Разблокировать'
                            : 'Заблокировать'
                    )
                    ->icon(fn (MasterProfile $record): string =>
                        in_array($record->status, [MasterStatus::BLOCKED->value, MasterStatus::SUSPENDED->value])
                            ? 'heroicon-o-lock-open'
                            : 'heroicon-o-lock-closed'
                    )
                    ->color(fn (MasterProfile $record): string =>
                        in_array($record->status, [MasterStatus::BLOCKED->value, MasterStatus::SUSPENDED->value])
                            ? 'success'
                            : 'danger'
                    )
                    ->requiresConfirmation()
                    ->action(function (MasterProfile $record): void {
                        $isBlocked = in_array($record->status, [MasterStatus::BLOCKED->value, MasterStatus::SUSPENDED->value]);

                        $record->update([
                            'status' => $isBlocked ? MasterStatus::ACTIVE->value : MasterStatus::BLOCKED->value,
                        ]);

                        Notification::make()
                            ->title($isBlocked ? 'Мастер разблокирован' : 'Мастер заблокирован')
                            ->success()
                            ->send();
                    }),

                // Верификация
                Action::make('verify')
                    ->label('Верифицировать')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (MasterProfile $record): bool => !$record->is_verified)
                    ->requiresConfirmation()
                    ->action(function (MasterProfile $record): void {
                        $record->update([
                            'is_verified' => true,
                        ]);

                        Notification::make()
                            ->title('Мастер верифицирован')
                            ->success()
                            ->send();
                    }),

                // Премиум статус
                Action::make('toggle_premium')
                    ->label(fn (MasterProfile $record): string =>
                        $record->is_premium ? 'Убрать премиум' : 'Дать премиум'
                    )
                    ->icon('heroicon-o-star')
                    ->color('warning')
                    ->form([
                        Forms\Components\DatePicker::make('premium_until')
                            ->label('Премиум до')
                            ->required()
                            ->minDate(now())
                            ->default(now()->addMonth()),
                    ])
                    ->action(function (MasterProfile $record, array $data): void {
                        if ($record->is_premium) {
                            $record->update([
                                'is_premium' => false,
                                'premium_until' => null,
                            ]);
                            Notification::make()
                                ->title('Премиум статус снят')
                                ->success()
                                ->send();
                        } else {
                            $record->update([
                                'is_premium' => true,
                                'premium_until' => $data['premium_until'],
                            ]);
                            Notification::make()
                                ->title('Премиум статус активирован')
                                ->success()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Массовое одобрение
                    Tables\Actions\BulkAction::make('bulk_approve')
                        ->label('Одобрить выбранных')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records): void {
                            $records->each->update([
                                'status' => MasterStatus::ACTIVE->value,
                                'is_published' => true,
                                'moderated_at' => now(),
                            ]);

                            Notification::make()
                                ->title('Профили мастеров одобрены')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    // Массовая блокировка
                    Tables\Actions\BulkAction::make('bulk_block')
                        ->label('Заблокировать выбранных')
                        ->icon('heroicon-o-lock-closed')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records): void {
                            $records->each->update([
                                'status' => MasterStatus::BLOCKED->value,
                            ]);

                            Notification::make()
                                ->title('Мастера заблокированы')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListMasterProfiles::route('/'),
            'create' => Pages\CreateMasterProfile::route('/create'),
            'edit' => Pages\EditMasterProfile::route('/{record}/edit'),
            'view' => Pages\ViewMasterProfile::route('/{record}'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['display_name', 'phone', 'bio'];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user']);
    }
}