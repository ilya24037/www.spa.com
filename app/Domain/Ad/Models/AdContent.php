<?php

namespace App\Domain\Ad\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Контент объявления (тексты, описания)
 */
class AdContent extends Model
{
    use HasFactory;

    protected $table = 'ad_contents';

    protected $fillable = [
        'ad_id',
        'title',
        'description',
        'specialty',
        'additional_features',
        'services_additional_info',
        'schedule_notes',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Связь с объявлением
     */
    public function ad(): BelongsTo
    {
        return $this->belongsTo(Ad::class);
    }

    /**
     * Получить короткое описание (для списков)
     */
    public function getShortDescriptionAttribute(): string
    {
        if (!$this->description) {
            return '';
        }
        
        return mb_strlen($this->description) > 150 
            ? mb_substr($this->description, 0, 150) . '...'
            : $this->description;
    }

    /**
     * Получить количество слов в описании
     */
    public function getWordCountAttribute(): int
    {
        if (!$this->description) {
            return 0;
        }
        
        return str_word_count(strip_tags($this->description));
    }

    /**
     * Проверить заполненность контента
     */
    public function isComplete(): bool
    {
        return !empty($this->title) && 
               !empty($this->description) && 
               mb_strlen($this->description) >= 50;
    }
}