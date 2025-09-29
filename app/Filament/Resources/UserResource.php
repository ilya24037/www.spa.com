<?php

namespace App\Filament\Resources;

use App\Domain\User\Models\User;
use App\Domain\Admin\Services\AdminActionsService;
use App\Domain\Admin\Traits\LogsAdminActions;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Resources\UserResource\Pages;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use UnitEnum;
use BackedEnum;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-users';

    protected static string|null $navigationLabel = 'Пользователи';

    protected static string|null $modelLabel = 'Пользователь';

    protected static string|null $pluralModelLabel = 'Пользователи';

    protected static UnitEnum|string|null $navigationGroup = 'Пользователи';

    protected static ?int $navigationSort = 1;

    private static function getStatusOptions(): array
    {
        return [
            UserStatus::ACTIVE->value => 'Активен',
            UserStatus::INACTIVE->value => 'Неактивен',
            UserStatus::SUSPENDED->value => 'Заблокирован',
            UserStatus::BANNED->value => 'Забанен',
            UserStatus::PENDING->value => 'Ожидает',
            UserStatus::DELETED->value => 'Удален',
        ];
    }

    private static function getStatusColors(): array
    {
        return [
            UserStatus::ACTIVE->value => 'success',
            UserStatus::INACTIVE->value => 'gray',
            UserStatus::SUSPENDED->value => 'warning',
            UserStatus::BANNED->value => 'danger',
            UserStatus::PENDING->value => 'info',
            UserStatus::DELETED->value => 'secondary',
        ];
    }

    private static function getRoleLabels(): array
    {
        return [
            UserRole::ADMIN->value => 'Администратор',
            UserRole::MODERATOR->value => 'Модератор',
            UserRole::MASTER->value => 'Мастер',
            UserRole::CLIENT->value => 'Клиент',
        ];
    }

    private static function getRoleColors(): array
    {
        return [
            UserRole::ADMIN->value => 'danger',
            UserRole::MODERATOR->value => 'warning',
            UserRole::MASTER->value => 'success',
            UserRole::CLIENT->value => 'gray',
        ];
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-users';
    }

    public static function getNavigationLabel(): string
    {
        return 'Пользователи';
    }


    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->columnSpanFull()
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

                        Forms\Components\TextInput::make('password')
                            ->label('Пароль')
                            ->password()
                            ->required(fn ($context) => $context === 'create')
                            ->maxLength(255)
                            ->dehydrateStateUsing(fn ($state) => !empty($state) ? bcrypt($state) : null)
                            ->dehydrated(fn ($state) => !empty($state)),

                        Forms\Components\Select::make('role')
                            ->label('Роль')
                            ->options([
                                UserRole::ADMIN->value => 'Администратор',
                                UserRole::MODERATOR->value => 'Модератор',
                                UserRole::MASTER->value => 'Мастер',
                                UserRole::CLIENT->value => 'Клиент',
                            ])
                            ->required()
                            ->default(UserRole::CLIENT->value),

                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options(self::getStatusOptions())
                            ->required()
                            ->default(UserStatus::ACTIVE->value),
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
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Имя')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('role')
                    ->label('Роль'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Статус'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Дата регистрации')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('Роль')
                    ->options([
                        UserRole::CLIENT->value => 'Клиент',
                        UserRole::MASTER->value => 'Мастер',
                        UserRole::MODERATOR->value => 'Модератор',
                        UserRole::ADMIN->value => 'Администратор',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options(self::getStatusOptions()),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return Gate::allows('viewAny', User::class);
    }

    public static function canCreate(): bool
    {
        return Gate::allows('create', User::class);
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
        return auth()->user() && auth()->user()->role === UserRole::ADMIN;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['ads']);
    }
}