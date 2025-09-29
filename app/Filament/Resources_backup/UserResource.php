<?php

namespace App\Filament\Resources;

use App\Domain\User\Models\User;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Resources\UserResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    // protected static $navigationIcon = 'heroicon-o-users';

    // protected static $navigationLabel = 'Пользователи';

    protected static ?string $modelLabel = 'Пользователь';

    protected static ?string $pluralModelLabel = 'Пользователи';

    // protected static $navigationGroup = 'Пользователи';

    // protected static $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return User::whereDate('created_at', today())->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Имя')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\TextInput::make('phone')
                            ->label('Телефон')
                            ->tel()
                            ->maxLength(20),

                        Forms\Components\DateTimePicker::make('email_verified_at')
                            ->label('Email подтверждён'),

                        Forms\Components\TextInput::make('password')
                            ->label('Пароль')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Роли и статусы')
                    ->schema([
                        Forms\Components\Select::make('role')
                            ->label('Роль')
                            ->options([
                                UserRole::CLIENT->value => 'Клиент',
                                UserRole::MASTER->value => 'Мастер',
                                UserRole::MODERATOR->value => 'Модератор',
                                UserRole::ADMIN->value => 'Администратор',
                            ])
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options([
                                UserStatus::ACTIVE->value => 'Активный',
                                UserStatus::INACTIVE->value => 'Неактивный',
                                UserStatus::BLOCKED->value => 'Заблокирован',
                                UserStatus::SUSPENDED->value => 'Приостановлен',
                            ])
                            ->default(UserStatus::ACTIVE->value),

                        Forms\Components\Toggle::make('is_blocked')
                            ->label('Заблокирован')
                            ->default(false),

                        Forms\Components\Toggle::make('is_verified')
                            ->label('Верифицирован')
                            ->default(false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Дополнительная информация')
                    ->schema([
                        Forms\Components\Textarea::make('about')
                            ->label('О себе')
                            ->rows(3)
                            ->maxLength(1000),

                        Forms\Components\TextInput::make('last_login_ip')
                            ->label('Последний IP')
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('last_login_at')
                            ->label('Последний вход')
                            ->disabled(),
                    ])
                    ->columns(1)
                    ->collapsible(),
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
                    ->label('Аватар')
                    ->circular()
                    ->defaultImageUrl(url('/images/default-avatar.png')),

                Tables\Columns\TextColumn::make('name')
                    ->label('Имя')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-o-envelope'),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),

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
                        UserRole::ADMIN->value => 'Администратор',
                        default => $state,
                    }),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'success' => UserStatus::ACTIVE->value,
                        'gray' => UserStatus::INACTIVE->value,
                        'danger' => UserStatus::BLOCKED->value,
                        'warning' => UserStatus::SUSPENDED->value,
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        UserStatus::ACTIVE->value => 'Активный',
                        UserStatus::INACTIVE->value => 'Неактивный',
                        UserStatus::BLOCKED->value => 'Заблокирован',
                        UserStatus::SUSPENDED->value => 'Приостановлен',
                        default => $state,
                    }),

                Tables\Columns\IconColumn::make('email_verified_at')
                    ->label('Email подтверждён')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->email_verified_at !== null),

                Tables\Columns\IconColumn::make('is_blocked')
                    ->label('Заблокирован')
                    ->boolean(),

                Tables\Columns\TextColumn::make('ads_count')
                    ->label('Объявлений')
                    ->counts('ads')
                    ->badge(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Зарегистрирован')
                    ->dateTime('d.m.Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('last_login_at')
                    ->label('Последний вход')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('Роль')
                    ->multiple()
                    ->options([
                        UserRole::CLIENT->value => 'Клиент',
                        UserRole::MASTER->value => 'Мастер',
                        UserRole::MODERATOR->value => 'Модератор',
                        UserRole::ADMIN->value => 'Администратор',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        UserStatus::ACTIVE->value => 'Активный',
                        UserStatus::INACTIVE->value => 'Неактивный',
                        UserStatus::BLOCKED->value => 'Заблокирован',
                        UserStatus::SUSPENDED->value => 'Приостановлен',
                    ]),

                Tables\Filters\TernaryFilter::make('is_blocked')
                    ->label('Заблокирован'),

                Tables\Filters\TernaryFilter::make('email_verified_at')
                    ->label('Email подтверждён')
                    ->nullable(),

                Tables\Filters\Filter::make('created_today')
                    ->label('Зарегистрированы сегодня')
                    ->query(fn (Builder $query): Builder => $query->whereDate('created_at', today())),

                Tables\Filters\Filter::make('has_ads')
                    ->label('С объявлениями')
                    ->query(fn (Builder $query): Builder => $query->has('ads')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                // Блокировка/разблокировка
                Action::make('toggle_block')
                    ->label(fn (User $record): string => $record->is_blocked ? 'Разблокировать' : 'Заблокировать')
                    ->icon(fn (User $record): string => $record->is_blocked ? 'heroicon-o-lock-open' : 'heroicon-o-lock-closed')
                    ->color(fn (User $record): string => $record->is_blocked ? 'success' : 'danger')
                    ->requiresConfirmation()
                    ->action(function (User $record): void {
                        $record->update([
                            'is_blocked' => !$record->is_blocked,
                            'status' => $record->is_blocked ? UserStatus::ACTIVE->value : UserStatus::BLOCKED->value
                        ]);

                        Notification::make()
                            ->title($record->is_blocked ? 'Пользователь заблокирован' : 'Пользователь разблокирован')
                            ->success()
                            ->send();
                    }),

                // Верификация
                Action::make('verify')
                    ->label('Верифицировать')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (User $record): bool => !$record->is_verified)
                    ->requiresConfirmation()
                    ->action(function (User $record): void {
                        $record->update([
                            'is_verified' => true,
                            'email_verified_at' => now()
                        ]);

                        Notification::make()
                            ->title('Пользователь верифицирован')
                            ->success()
                            ->send();
                    }),

                // Смена пароля
                Action::make('change_password')
                    ->label('Сменить пароль')
                    ->icon('heroicon-o-key')
                    ->form([
                        Forms\Components\TextInput::make('password')
                            ->label('Новый пароль')
                            ->password()
                            ->required()
                            ->confirmed(),
                        Forms\Components\TextInput::make('password_confirmation')
                            ->label('Подтверждение пароля')
                            ->password()
                            ->required(),
                    ])
                    ->action(function (User $record, array $data): void {
                        $record->update([
                            'password' => Hash::make($data['password'])
                        ]);

                        Notification::make()
                            ->title('Пароль успешно изменён')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Массовая блокировка
                    Tables\Actions\BulkAction::make('bulk_block')
                        ->label('Заблокировать выбранных')
                        ->icon('heroicon-o-lock-closed')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records): void {
                            $records->each->update([
                                'is_blocked' => true,
                                'status' => UserStatus::BLOCKED->value
                            ]);

                            Notification::make()
                                ->title('Пользователи заблокированы')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    // Массовая разблокировка
                    Tables\Actions\BulkAction::make('bulk_unblock')
                        ->label('Разблокировать выбранных')
                        ->icon('heroicon-o-lock-open')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records): void {
                            $records->each->update([
                                'is_blocked' => false,
                                'status' => UserStatus::ACTIVE->value
                            ]);

                            Notification::make()
                                ->title('Пользователи разблокированы')
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
            'view' => Pages\ViewUser::route('/{record}'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email', 'phone'];
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['ads']);
    }
}