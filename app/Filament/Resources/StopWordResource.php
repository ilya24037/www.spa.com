<?php

namespace App\Filament\Resources;

use App\Domain\Moderation\Models\StopWord;
use App\Filament\Resources\StopWordResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class StopWordResource extends Resource
{
    protected static ?string $model = StopWord::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';
    
    protected static ?string $navigationLabel = 'Стоп-слова';
    
    protected static ?string $modelLabel = 'Стоп-слово';
    
    protected static ?string $pluralModelLabel = 'Стоп-слова';
    
    protected static ?string $navigationGroup = 'Модерация';
    
    protected static ?int $navigationSort = 5;

    public static function getNavigationBadge(): ?string
    {
        return (string) StopWord::where('is_active', true)->count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основная информация')
                    ->schema([
                        Forms\Components\TextInput::make('word')
                            ->label('Слово или фраза')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('Введите слово или фразу для блокировки'),

                        Forms\Components\Select::make('category')
                            ->label('Категория')
                            ->options(StopWord::CATEGORIES)
                            ->required()
                            ->default('other')
                            ->helperText('Выберите категорию для группировки'),

                        Forms\Components\Select::make('severity')
                            ->label('Серьезность')
                            ->options(StopWord::SEVERITIES)
                            ->required()
                            ->default('medium')
                            ->helperText('Уровень серьезности нарушения'),

                        Forms\Components\TextInput::make('weight')
                            ->label('Вес (1-10)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(10)
                            ->default(5)
                            ->required()
                            ->helperText('Вес для расчета общего скора модерации'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Настройки применения')
                    ->schema([
                        Forms\Components\Select::make('context')
                            ->label('Где применять')
                            ->options(StopWord::CONTEXTS)
                            ->default('all')
                            ->required()
                            ->helperText('Где будет проверяться это слово'),

                        Forms\Components\Toggle::make('is_regex')
                            ->label('Регулярное выражение')
                            ->default(false)
                            ->helperText('Использовать как регулярное выражение')
                            ->reactive()
                            ->afterStateUpdated(fn ($state, Forms\Set $set) => 
                                $state ? $set('regex_hint', 'Например: /\b(слово1|слово2)\b/iu') : $set('regex_hint', '')
                            ),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Активно')
                            ->default(true)
                            ->helperText('Включить проверку этого слова'),

                        Forms\Components\Textarea::make('description')
                            ->label('Описание')
                            ->rows(2)
                            ->maxLength(500)
                            ->helperText('Пояснение для модераторов'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Статистика')
                    ->schema([
                        Forms\Components\Placeholder::make('hits_count')
                            ->label('Срабатываний')
                            ->content(fn (?StopWord $record): string => $record ? number_format($record->hits_count) : '0'),

                        Forms\Components\Placeholder::make('false_positives')
                            ->label('Ложных срабатываний')
                            ->content(fn (?StopWord $record): string => $record ? number_format($record->false_positives) : '0'),

                        Forms\Components\Placeholder::make('effectiveness')
                            ->label('Эффективность')
                            ->content(fn (?StopWord $record): string => $record ? $record->effectiveness : 'Нет данных'),

                        Forms\Components\Placeholder::make('created_at')
                            ->label('Добавлено')
                            ->content(fn (?StopWord $record): string => $record ? $record->created_at->format('d.m.Y H:i') : ''),
                    ])
                    ->columns(4)
                    ->visible(fn (?StopWord $record) => $record && $record->exists),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('word')
                    ->label('Слово/Фраза')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),

                Tables\Columns\BadgeColumn::make('category')
                    ->label('Категория')
                    ->formatStateUsing(fn (string $state): string => StopWord::CATEGORIES[$state] ?? $state)
                    ->colors([
                        'danger' => 'illegal',
                        'warning' => 'adult',
                        'info' => 'medical',
                        'success' => 'financial',
                        'primary' => 'scam',
                        'secondary' => 'offensive',
                        'gray' => 'spam',
                    ]),

                Tables\Columns\BadgeColumn::make('severity')
                    ->label('Серьезность')
                    ->formatStateUsing(fn (string $state): string => StopWord::SEVERITIES[$state] ?? $state)
                    ->colors([
                        'danger' => 'critical',
                        'warning' => 'high',
                        'info' => 'medium',
                        'gray' => 'low',
                    ]),

                Tables\Columns\TextColumn::make('weight')
                    ->label('Вес')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color(fn (int $state): string => match(true) {
                        $state >= 8 => 'danger',
                        $state >= 5 => 'warning',
                        $state >= 3 => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('context')
                    ->label('Где')
                    ->formatStateUsing(fn (string $state): string => StopWord::CONTEXTS[$state] ?? $state)
                    ->badge(),

                Tables\Columns\IconColumn::make('is_regex')
                    ->label('Regex')
                    ->boolean()
                    ->trueIcon('heroicon-o-code-bracket')
                    ->falseIcon('heroicon-o-document-text'),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Активно')
                    ->onColor('success')
                    ->offColor('danger')
                    ->afterStateUpdated(function ($record, $state) {
                        StopWord::clearCache();
                        Notification::make()
                            ->title($state ? 'Стоп-слово активировано' : 'Стоп-слово деактивировано')
                            ->success()
                            ->send();
                    }),

                Tables\Columns\TextColumn::make('hits_count')
                    ->label('Срабатываний')
                    ->sortable()
                    ->alignCenter()
                    ->formatStateUsing(fn (int $state): string => number_format($state)),

                Tables\Columns\TextColumn::make('false_positive_rate')
                    ->label('% ложных')
                    ->sortable()
                    ->alignCenter()
                    ->formatStateUsing(fn (float $state): string => $state . '%')
                    ->color(fn (float $state): string => match(true) {
                        $state > 50 => 'danger',
                        $state > 30 => 'warning',
                        $state > 10 => 'info',
                        default => 'success',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Добавлено')
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Категория')
                    ->options(StopWord::CATEGORIES)
                    ->multiple(),

                Tables\Filters\SelectFilter::make('severity')
                    ->label('Серьезность')
                    ->options(StopWord::SEVERITIES),

                Tables\Filters\SelectFilter::make('context')
                    ->label('Контекст')
                    ->options(StopWord::CONTEXTS),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Активные'),

                Tables\Filters\Filter::make('high_false_positive')
                    ->label('Много ложных (>30%)')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereRaw('(false_positives * 100.0 / NULLIF(hits_count, 0)) > 30')
                    ),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                
                Tables\Actions\Action::make('test')
                    ->label('Тест')
                    ->icon('heroicon-o-beaker')
                    ->color('info')
                    ->form([
                        Forms\Components\Textarea::make('test_text')
                            ->label('Текст для проверки')
                            ->required()
                            ->rows(3)
                            ->placeholder('Введите текст для проверки на наличие стоп-слова'),
                    ])
                    ->action(function (StopWord $record, array $data): void {
                        $found = $record->checkText($data['test_text']);
                        
                        Notification::make()
                            ->title($found ? 'Слово найдено!' : 'Слово не найдено')
                            ->body($found 
                                ? "Стоп-слово '{$record->word}' обнаружено в тексте" 
                                : "Стоп-слово '{$record->word}' не найдено в тексте")
                            ->status($found ? 'warning' : 'success')
                            ->send();
                    }),

                Tables\Actions\Action::make('mark_false_positive')
                    ->label('Ложное')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (StopWord $record): void {
                        $record->markAsFalsePositive();
                        
                        Notification::make()
                            ->title('Отмечено как ложное срабатывание')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\DeleteAction::make()
                    ->after(fn () => StopWord::clearCache()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Активировать')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->action(function (Collection $records): void {
                            $records->each->update(['is_active' => true]);
                            StopWord::clearCache();
                            
                            Notification::make()
                                ->title('Стоп-слова активированы')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Деактивировать')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->action(function (Collection $records): void {
                            $records->each->update(['is_active' => false]);
                            StopWord::clearCache();
                            
                            Notification::make()
                                ->title('Стоп-слова деактивированы')
                                ->warning()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\DeleteBulkAction::make()
                        ->after(fn () => StopWord::clearCache()),
                ]),
            ])
            ->defaultSort('hits_count', 'desc');
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
            'index' => Pages\ListStopWords::route('/'),
            'create' => Pages\CreateStopWord::route('/create'),
            'edit' => Pages\EditStopWord::route('/{record}/edit'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            StopWordResource\Widgets\StopWordStats::class,
        ];
    }
}