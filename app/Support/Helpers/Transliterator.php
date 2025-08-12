<?php

namespace App\Support\Helpers;

class Transliterator
{
    /**
     * Таблица транслитерации кириллицы в латиницу
     */
    private static array $translitTable = [
        // Русские буквы
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
        'е' => 'e', 'ё' => 'e', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
        'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
        'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
        'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'ts', 'ч' => 'ch',
        'ш' => 'sh', 'щ' => 'sch', 'ъ' => '', 'ы' => 'y', 'ь' => '',
        'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
        
        // Заглавные
        'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D',
        'Е' => 'E', 'Ё' => 'E', 'Ж' => 'Zh', 'З' => 'Z', 'И' => 'I',
        'Й' => 'Y', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N',
        'О' => 'O', 'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T',
        'У' => 'U', 'Ф' => 'F', 'Х' => 'H', 'Ц' => 'Ts', 'Ч' => 'Ch',
        'Ш' => 'Sh', 'Щ' => 'Sch', 'Ъ' => '', 'Ы' => 'Y', 'Ь' => '',
        'Э' => 'E', 'Ю' => 'Yu', 'Я' => 'Ya',
        
        // Украинские дополнительные
        'і' => 'i', 'І' => 'I', 'ї' => 'yi', 'Ї' => 'Yi',
        'є' => 'ye', 'Є' => 'Ye', 'ґ' => 'g', 'Ґ' => 'G',
    ];
    
    /**
     * Транслитерация строки
     */
    public static function transliterate(string $text): string
    {
        // Заменяем кириллицу
        $result = strtr($text, self::$translitTable);
        
        // Убираем все кроме букв, цифр и пробелов
        $result = preg_replace('/[^a-zA-Z0-9\s\-]/', '', $result);
        
        // Заменяем пробелы на дефисы
        $result = preg_replace('/\s+/', '-', $result);
        
        // Убираем множественные дефисы
        $result = preg_replace('/-+/', '-', $result);
        
        // Убираем дефисы в начале и конце
        $result = trim($result, '-');
        
        // Приводим к нижнему регистру
        return strtolower($result);
    }
    
    /**
     * Генерация имени папки для пользователя
     * Берем только первое слово (имя) и добавляем ID
     */
    public static function generateUserFolderName(string $fullName, int $userId): string
    {
        // Если имя пустое
        if (empty(trim($fullName))) {
            return "user-{$userId}";
        }
        
        // Берем только первое слово (имя)
        $parts = explode(' ', trim($fullName));
        $firstName = $parts[0];
        
        // Транслитерируем
        $transliterated = self::transliterate($firstName);
        
        // Если после транслитерации пусто (например, китайские иероглифы)
        if (empty($transliterated)) {
            return "user-{$userId}";
        }
        
        // Ограничиваем длину имени до 20 символов
        if (strlen($transliterated) > 20) {
            $transliterated = substr($transliterated, 0, 20);
        }
        
        // Возвращаем имя-id
        return "{$transliterated}-{$userId}";
    }
    
    /**
     * Проверка, нужно ли обновить имя папки
     */
    public static function needsUpdate(string $currentFolderName, string $fullName, int $userId): bool
    {
        $newFolderName = self::generateUserFolderName($fullName, $userId);
        return $currentFolderName !== $newFolderName;
    }
}