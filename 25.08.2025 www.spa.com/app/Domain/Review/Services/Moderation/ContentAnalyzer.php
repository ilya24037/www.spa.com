<?php

namespace App\Domain\Review\Services\Moderation;

use App\Domain\Review\Models\Review;

/**
 * Анализатор контента отзывов
 */
class ContentAnalyzer
{
    // Списки запрещенных слов (можно вынести в конфиг)
    protected array $bannedWords = [
        'спам', 'мошенник', 'развод', 'обман', 'кидала',
        // Добавить другие запрещенные слова
    ];

    protected array $suspiciousPatterns = [
        '/\b\d{10,}\b/', // Телефонные номера
        '/https?:\/\/[^\s]+/', // Ссылки
        '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', // Email
    ];

    /**
     * Рассчитать спам-скор отзыва
     */
    public function calculateSpamScore(Review $review): int
    {
        $score = 0;

        // Проверка длины комментария
        $commentLength = strlen($review->comment);
        if ($commentLength < 10) {
            $score += 30;
        } elseif ($commentLength > 1000) {
            $score += 20;
        }

        // Проверка на повторяющиеся символы
        if (preg_match('/(.)\1{5,}/', $review->comment)) {
            $score += 25;
        }

        // Проверка на капс
        $capsRatio = strlen(preg_replace('/[^A-ZА-Я]/', '', $review->comment)) / max(1, $commentLength);
        if ($capsRatio > 0.5) {
            $score += 20;
        }

        // Проверка на множественные восклицательные знаки
        if (substr_count($review->comment, '!') > 5) {
            $score += 15;
        }

        return min(100, $score);
    }

    /**
     * Проверить наличие запрещенных слов
     */
    public function hasBannedWords(Review $review): bool
    {
        $text = strtolower($review->comment . ' ' . $review->title);
        
        foreach ($this->bannedWords as $word) {
            if (strpos($text, strtolower($word)) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Проверить подозрительные паттерны
     */
    public function hasSuspiciousPatterns(Review $review): bool
    {
        $text = $review->comment . ' ' . $review->title;
        
        foreach ($this->suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Проверить дубликат
     */
    public function isDuplicate(Review $review): bool
    {
        return Review::where('user_id', $review->user_id)
            ->where('reviewable_type', $review->reviewable_type)
            ->where('reviewable_id', $review->reviewable_id)
            ->where('id', '!=', $review->id)
            ->where('comment', $review->comment)
            ->exists();
    }

    /**
     * Получить детальный анализ контента
     */
    public function analyzeContent(Review $review): array
    {
        return [
            'spam_score' => $this->calculateSpamScore($review),
            'has_banned_words' => $this->hasBannedWords($review),
            'has_suspicious_patterns' => $this->hasSuspiciousPatterns($review),
            'is_duplicate' => $this->isDuplicate($review),
            'content_length' => strlen($review->comment),
            'word_count' => str_word_count($review->comment),
            'has_excessive_caps' => $this->hasExcessiveCaps($review->comment),
            'has_repeated_chars' => $this->hasRepeatedChars($review->comment),
        ];
    }

    /**
     * Проверить избыточное использование заглавных букв
     */
    private function hasExcessiveCaps(string $text): bool
    {
        $commentLength = strlen($text);
        if ($commentLength === 0) return false;
        
        $capsRatio = strlen(preg_replace('/[^A-ZА-Я]/', '', $text)) / $commentLength;
        return $capsRatio > 0.5;
    }

    /**
     * Проверить повторяющиеся символы
     */
    private function hasRepeatedChars(string $text): bool
    {
        return preg_match('/(.)\1{5,}/', $text);
    }

    /**
     * Обновить списки запрещенных слов
     */
    public function updateBannedWords(array $words): void
    {
        $this->bannedWords = array_merge($this->bannedWords, $words);
    }

    /**
     * Обновить подозрительные паттерны
     */
    public function updateSuspiciousPatterns(array $patterns): void
    {
        $this->suspiciousPatterns = array_merge($this->suspiciousPatterns, $patterns);
    }
}