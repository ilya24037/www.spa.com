<?php

namespace App\Filament\Resources\StopWordResource\Pages;

use App\Filament\Resources\StopWordResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStopWords extends ListRecords
{
    protected static string $resource = StopWordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            
            Actions\Action::make('import')
                ->label('Импорт')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('info')
                ->form([
                    \Filament\Forms\Components\Textarea::make('words')
                        ->label('Список слов')
                        ->helperText('Каждое слово с новой строки. Формат: слово|категория|вес')
                        ->rows(10)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $lines = explode("\n", $data['words']);
                    $imported = 0;
                    
                    foreach ($lines as $line) {
                        $line = trim($line);
                        if (empty($line)) continue;
                        
                        $parts = explode('|', $line);
                        $word = trim($parts[0] ?? '');
                        $category = trim($parts[1] ?? 'other');
                        $weight = (int) trim($parts[2] ?? 5);
                        
                        if ($word) {
                            \App\Domain\Moderation\Models\StopWord::updateOrCreate(
                                ['word' => $word],
                                [
                                    'category' => $category,
                                    'weight' => $weight,
                                    'severity' => $weight >= 8 ? 'high' : ($weight >= 5 ? 'medium' : 'low'),
                                    'context' => 'all',
                                    'is_active' => true,
                                ]
                            );
                            $imported++;
                        }
                    }
                    
                    \App\Domain\Moderation\Models\StopWord::clearCache();
                    
                    \Filament\Notifications\Notification::make()
                        ->title("Импортировано слов: {$imported}")
                        ->success()
                        ->send();
                }),
                
            Actions\Action::make('export')
                ->label('Экспорт')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function (): \Symfony\Component\HttpFoundation\StreamedResponse {
                    $words = \App\Domain\Moderation\Models\StopWord::all();
                    
                    return response()->streamDownload(function () use ($words) {
                        echo "word|category|weight|severity|context|is_active\n";
                        foreach ($words as $word) {
                            echo "{$word->word}|{$word->category}|{$word->weight}|{$word->severity}|{$word->context}|" . ($word->is_active ? '1' : '0') . "\n";
                        }
                    }, 'stop_words_' . date('Y-m-d') . '.csv');
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            StopWordResource\Widgets\StopWordStats::class,
        ];
    }
}