<?php

namespace App\Filament\Resources;

use App\Domain\Review\Models\Review;
use App\Domain\Review\Services\ReviewModerationService;
use App\Domain\Admin\Traits\LogsAdminActions;
use App\Filament\Resources\ReviewResource\Pages;
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
use Filament\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Gate;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-star';

    protected static string|null $navigationLabel = 'Отзывы';

    protected static string|null $modelLabel = 'Отзыв';

    protected static string|null $pluralModelLabel = 'Отзывы';

    protected static UnitEnum|string|null $navigationGroup = 'Контент';

    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        return Review::where('status', 'pending')->count();
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
                        Forms\Components\Select::make('reviewer_id')
                            ->label('Автор отзыва')
                            ->relationship('reviewer', 'email')
                            ->searchable()
                            ->required(),

                        Forms\Components\Select::make('reviewable_type')
                            ->label('Тип объекта')
                            ->options([
                                'App\\Domain\\Master\\Models\\MasterProfile' => 'Профиль мастера',
                                'App\\Domain\\Ad\\Models\\Ad' => 'Объявление',
                                'App\\Domain\\Service\\Models\\Service' => 'Услуга',
                            ])
                            ->required()
                            ->reactive(),

                        Forms\Components\TextInput::make('reviewable_id')
                            ->label('ID объекта')
                            ->numeric()
                            ->required(),

                        Forms\Components\Select::make('booking_id')
                            ->label('Бронирование')
                            ->relationship('booking', 'id')
                            ->searchable()
                            ->placeholder('Выберите бронирование (необязательно)'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Содержание отзыва')
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\TextInput::make('rating')
                            ->label('Рейтинг')
                            ->numeric()
                            ->min(1)
                            ->max(5)
                            ->required()
                            ->suffix('★'),

                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options([
                                'pending' => 'На модерации',
                                'approved' => 'Одобрен',
                                'rejected' => 'Отклонен',
                                'hidden' => 'Скрыт',
                            ])
                            ->required()
                            ->default('pending'),

                        Forms\Components\Textarea::make('comment')
                            ->label('Комментарий')
                            ->rows(4)
                            ->maxLength(2000),

                        Forms\Components\Textarea::make('master_reply')
                            ->label('Ответ мастера')
                            ->rows(3)
                            ->maxLength(1000),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Дополнительные параметры')
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\Toggle::make('is_verified')
                            ->label('Проверенный отзыв')
                            ->helperText('Отзыв от реального клиента'),

                        Forms\Components\Toggle::make('is_visible')
                            ->label('Видимый')
                            ->default(true)
                            ->helperText('Отображать отзыв на сайте'),

                        Forms\Components\DateTimePicker::make('verified_at')
                            ->label('Время верификации'),

                        Forms\Components\DateTimePicker::make('replied_at')
                            ->label('Время ответа мастера'),
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

                Tables\Columns\TextColumn::make('reviewer.email')
                    ->label('Автор')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-o-user')
                    ->iconColor('gray'),

                Tables\Columns\TextColumn::make('reviewable_type')
                    ->label('Объект')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'App\\Domain\\Master\\Models\\MasterProfile' => 'Мастер',
                        'App\\Domain\\Ad\\Models\\Ad' => 'Объявление',
                        'App\\Domain\\Service\\Models\\Service' => 'Услуга',
                        default => 'Неизвестно',
                    }),

                Tables\Columns\TextColumn::make('rating')
                    ->label('Рейтинг')
                    ->sortable()
                    ->formatStateUsing(fn (int $state): string => str_repeat('★', $state) . str_repeat('☆', 5 - $state))
                    ->color(fn (int $state): string => match (true) {
                        $state >= 4 => 'success',
                        $state >= 3 => 'warning',
                        default => 'danger',
                    }),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Статус')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                        'secondary' => 'hidden',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'На модерации',
                        'approved' => 'Одобрен',
                        'rejected' => 'Отклонен',
                        'hidden' => 'Скрыт',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('comment')
                    ->label('Комментарий')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $comment = $column->getState();
                        return strlen($comment) > 50 ? $comment : null;
                    }),

                Tables\Columns\IconColumn::make('is_verified')
                    ->label('Проверен')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('success')
                    ->falseColor('gray'),

                Tables\Columns\IconColumn::make('master_reply')
                    ->label('Ответ мастера')
                    ->getStateUsing(fn (Review $record): bool => !empty($record->master_reply))
                    ->boolean()
                    ->trueIcon('heroicon-o-chat-bubble-left-right')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('info')
                    ->falseColor('gray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->multiple()
                    ->options([
                        'pending' => 'На модерации',
                        'approved' => 'Одобрен',
                        'rejected' => 'Отклонен',
                        'hidden' => 'Скрыт',
                    ]),

                Tables\Filters\SelectFilter::make('rating')
                    ->label('Рейтинг')
                    ->multiple()
                    ->options([
                        '5' => '5 звезд',
                        '4' => '4 звезды',
                        '3' => '3 звезды',
                        '2' => '2 звезды',
                        '1' => '1 звезда',
                    ]),

                Tables\Filters\SelectFilter::make('reviewable_type')
                    ->label('Тип объекта')
                    ->options([
                        'App\\Domain\\Master\\Models\\MasterProfile' => 'Профиль мастера',
                        'App\\Domain\\Ad\\Models\\Ad' => 'Объявление',
                        'App\\Domain\\Service\\Models\\Service' => 'Услуга',
                    ]),

                Tables\Filters\TernaryFilter::make('is_verified')
                    ->label('Проверенные')
                    ->placeholder('Все отзывы')
                    ->trueLabel('Только проверенные')
                    ->falseLabel('Только непроверенные'),

                Tables\Filters\TernaryFilter::make('has_reply')
                    ->label('С ответом мастера')
                    ->placeholder('Все отзывы')
                    ->trueLabel('С ответом')
                    ->falseLabel('Без ответа')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('master_reply'),
                        false: fn (Builder $query) => $query->whereNull('master_reply'),
                    ),

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
                    ->visible(fn (Review $record): bool => $record->status !== 'approved')
                    ->action(function (Review $record): void {
                        app(ReviewModerationService::class)->approve($record);

                        Notification::make()
                            ->title('Отзыв одобрен')
                            ->success()
                            ->send();
                    }),

                // Кнопка отклонения
                Action::make('reject')
                    ->label('Отклонить')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Review $record): bool => $record->status !== 'rejected')
                    ->action(function (Review $record): void {
                        app(ReviewModerationService::class)->reject($record, 'Отклонено администратором');

                        Notification::make()
                            ->title('Отзыв отклонен')
                            ->warning()
                            ->send();
                    }),

                // Кнопка скрытия
                Action::make('hide')
                    ->label('Скрыть')
                    ->icon('heroicon-o-eye-slash')
                    ->color('secondary')
                    ->requiresConfirmation()
                    ->visible(fn (Review $record): bool => $record->status !== 'hidden' && $record->is_visible)
                    ->action(function (Review $record): void {
                        $record->update([
                            'status' => 'hidden',
                            'is_visible' => false,
                        ]);

                        Notification::make()
                            ->title('Отзыв скрыт')
                            ->info()
                            ->send();
                    }),

                // Кнопка показать
                Action::make('show')
                    ->label('Показать')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->requiresConfirmation()
                    ->visible(fn (Review $record): bool => !$record->is_visible)
                    ->action(function (Review $record): void {
                        $record->update([
                            'is_visible' => true,
                            'status' => 'approved',
                        ]);

                        Notification::make()
                            ->title('Отзыв опубликован')
                            ->success()
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
                            $approvedCount = $records->whereNotIn('status', ['approved'])->count();

                            $records->each(function (Review $review) {
                                if ($review->status !== 'approved') {
                                    $review->update([
                                        'status' => 'approved',
                                        'is_visible' => true,
                                        'verified_at' => now(),
                                    ]);
                                }
                            });

                            Notification::make()
                                ->title("Одобрено отзывов: {$approvedCount}")
                                ->success()
                                ->send();
                        }),

                    // Массовое отклонение
                    BulkAction::make('bulk_reject')
                        ->label('Отклонить выбранные')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records): void {
                            $rejectedCount = $records->whereNotIn('status', ['rejected'])->count();

                            $records->each(function (Review $review) {
                                if ($review->status !== 'rejected') {
                                    $review->update([
                                        'status' => 'rejected',
                                        'is_visible' => false,
                                    ]);
                                }
                            });

                            Notification::make()
                                ->title("Отклонено отзывов: {$rejectedCount}")
                                ->warning()
                                ->send();
                        }),

                    // Массовое скрытие
                    BulkAction::make('bulk_hide')
                        ->label('Скрыть выбранные')
                        ->icon('heroicon-o-eye-slash')
                        ->color('secondary')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records): void {
                            $hiddenCount = $records->where('is_visible', true)->count();

                            $records->each(function (Review $review) {
                                if ($review->is_visible) {
                                    $review->update([
                                        'status' => 'hidden',
                                        'is_visible' => false,
                                    ]);
                                }
                            });

                            Notification::make()
                                ->title("Скрыто отзывов: {$hiddenCount}")
                                ->info()
                                ->send();
                        }),

                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('60s');
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
            'index' => Pages\ListReviews::route('/'),
            'create' => Pages\CreateReview::route('/create'),
            'edit' => Pages\EditReview::route('/{record}/edit'),
            'view' => Pages\ViewReview::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['reviewer', 'booking']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['comment', 'reviewer.email', 'master_reply'];
    }

    public static function canViewAny(): bool
    {
        return Gate::allows('viewAny', Review::class);
    }

    public static function canCreate(): bool
    {
        return Gate::allows('create', Review::class);
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