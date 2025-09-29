<?php

namespace App\Domain\Moderation\Services;

use App\Domain\Moderation\Models\StopWord;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class StopWordService
{
    /**
     * Проверить текст на наличие стоп-слов
     * 
     * @param string $text Текст для проверки
     * @param string $context Контекст (ads, reviews, messages, all)
     * @return array ['passed' => bool, 'score' => int, 'found_words' => array, 'should_block' => bool]
     */
    public function checkText(string $text, string $context = 'all'): array
    {
        $text = mb_strtolower($text);
        $foundWords = [];
        $totalScore = 0;
        
        // Получаем активные стоп-слова для контекста
        $stopWords = $this->getStopWords($context);
        
        foreach ($stopWords as $category => $words) {
            foreach ($words as $stopWord) {
                if ($this->wordFoundInText($stopWord, $text)) {
                    $foundWords[] = [
                        'word' => $stopWord->word,
                        'category' => $category,
                        'weight' => $stopWord->weight,
                        'severity' => $stopWord->severity,
                    ];
                    
                    $totalScore += $stopWord->weight;
                    
                    // Увеличиваем счетчик срабатываний
                    $stopWord->incrementHits();
                    
                    Log::info('Стоп-слово найдено', [
                        'word' => $stopWord->word,
                        'category' => $category,
                        'weight' => $stopWord->weight,
                        'context' => $context,
                    ]);
                }
            }
        }
        
        // Определяем действие на основе общего скора
        $shouldBlock = $totalScore >= StopWord::THRESHOLDS['auto_block'];
        $needsModeration = $totalScore >= StopWord::THRESHOLDS['to_moderation'];
        $hasWarning = $totalScore >= StopWord::THRESHOLDS['warning'];
        
        return [
            'passed' => empty($foundWords),
            'score' => $totalScore,
            'found_words' => $foundWords,
            'should_block' => $shouldBlock,
            'needs_moderation' => $needsModeration,
            'has_warning' => $hasWarning,
            'action' => $this->determineAction($totalScore),
        ];
    }
    
    /**
     * Получить стоп-слова для контекста
     */
    private function getStopWords(string $context): array
    {
        return Cache::remember("stop_words_{$context}", 3600, function () use ($context) {
            return StopWord::active()
                ->forContext($context)
                ->get()
                ->groupBy('category');
        });
    }
    
    /**
     * Проверить наличие слова в тексте
     */
    private function wordFoundInText(StopWord $stopWord, string $text): bool
    {
        if ($stopWord->is_regex) {
            // Для регулярных выражений
            try {
                return preg_match($stopWord->word, $text) === 1;
            } catch (\Exception $e) {
                Log::error('Ошибка в регулярном выражении', [
                    'word' => $stopWord->word,
                    'error' => $e->getMessage(),
                ]);
                return false;
            }
        } else {
            // Для обычных слов
            $word = mb_strtolower($stopWord->word);
            return mb_strpos($text, $word) !== false;
        }
    }
    
    /**
     * Определить действие на основе скора
     */
    private function determineAction(int $score): string
    {
        if ($score >= StopWord::THRESHOLDS['auto_block']) {
            return 'block';
        }
        
        if ($score >= StopWord::THRESHOLDS['to_moderation']) {
            return 'moderate';
        }
        
        if ($score >= StopWord::THRESHOLDS['warning']) {
            return 'warning';
        }
        
        return 'approve';
    }
    
    /**
     * Отметить слово как ложное срабатывание
     */
    public function markAsFalsePositive(string $word): void
    {
        $stopWord = StopWord::where('word', $word)->first();
        
        if ($stopWord) {
            $stopWord->markAsFalsePositive();
            
            // Если слишком много ложных срабатываний, деактивируем
            if ($stopWord->false_positive_rate > 70) {
                $stopWord->update(['is_active' => false]);
                
                Log::warning('Стоп-слово деактивировано из-за высокого процента ложных срабатываний', [
                    'word' => $word,
                    'false_positive_rate' => $stopWord->false_positive_rate,
                ]);
            }
        }
    }
    
    /**
     * Добавить новое стоп-слово
     */
    public function addStopWord(array $data): StopWord
    {
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();
        
        $stopWord = StopWord::create($data);
        
        // Очищаем кэш
        StopWord::clearCache();
        
        Log::info('Добавлено новое стоп-слово', [
            'word' => $stopWord->word,
            'category' => $stopWord->category,
            'by_user' => auth()->id(),
        ]);
        
        return $stopWord;
    }
    
    /**
     * Получить статистику по стоп-словам
     */
    public function getStatistics(): array
    {
        return [
            'total_words' => StopWord::count(),
            'active_words' => StopWord::where('is_active', true)->count(),
            'total_hits' => StopWord::sum('hits_count'),
            'total_false_positives' => StopWord::sum('false_positives'),
            'by_category' => StopWord::select('category')
                ->selectRaw('COUNT(*) as count')
                ->selectRaw('SUM(hits_count) as hits')
                ->groupBy('category')
                ->get()
                ->mapWithKeys(fn ($item) => [
                    $item->category => [
                        'count' => $item->count,
                        'hits' => $item->hits,
                    ]
                ])
                ->toArray(),
            'top_words' => StopWord::orderBy('hits_count', 'desc')
                ->limit(10)
                ->get(['word', 'category', 'hits_count', 'false_positives'])
                ->toArray(),
            'ineffective_words' => StopWord::whereRaw('(false_positives * 100.0 / NULLIF(hits_count, 0)) > 50')
                ->where('hits_count', '>', 10)
                ->get(['word', 'hits_count', 'false_positives'])
                ->toArray(),
        ];
    }
}