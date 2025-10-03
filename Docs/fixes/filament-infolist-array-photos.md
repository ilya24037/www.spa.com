# Решение: Отображение массива фото в Filament Infolist

## 🔴 Проблема

При попытке отобразить массив фото в Filament Infolist v4:
- Показывалось "Нет фото" вместо изображений
- Photos field содержит JSON массив путей: `["/storage/photos/1.webp", "/storage/photos/2.webp"]`
- Попытки с `RepeatableEntry` вызывали Memory Exhausted (512MB)
- Попытки с `ImageEntry` показывали пустые квадраты

## 🔍 Диагностика

### Проверка данных в БД:
```php
$ad = Ad::find(19);
var_dump($ad->photos);
// Результат: array(3) { [0]=> string(47) "/storage/photos/3/file1.webp" ... }
```

✅ Данные ЕСТЬ в модели - массив из 3 путей

### Проблема в Filament:

**Ключевое открытие:** Filament **автоматически разбивает массивы** и вызывает `formatStateUsing()` для КАЖДОГО элемента отдельно!

**Логи показали:**
```php
// getStateUsing() вызывается 1 раз
[2025-10-01 11:21:34] getStateUsing: photos_type="array", photos_count=3 ✅

// formatStateUsing() вызывается 3 раза (для каждого фото)
[2025-10-01 11:21:34] formatStateUsing: state="/storage/photos/3/file1.webp", state_type="string" ❌
[2025-10-01 11:21:34] formatStateUsing: state="/storage/photos/3/file2.webp", state_type="string" ❌
[2025-10-01 11:21:34] formatStateUsing: state="/storage/photos/3/file3.webp", state_type="string" ❌
```

## ✅ Решение

### Генерировать HTML прямо в `getStateUsing()`:

```php
\Filament\Infolists\Components\TextEntry::make('photos')
    ->label('Фотографии')
    ->getStateUsing(function ($record) {
        // Return complete HTML grid as single string
        $photos = $record->photos;

        if (!is_array($photos) || empty($photos)) {
            return 'Нет фото';
        }

        $html = '<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 10px;">';
        foreach ($photos as $photo) {
            $url = asset($photo);
            $html .= '<div>';
            $html .= '<a href="' . $url . '" target="_blank">';
            $html .= '<img src="' . $url . '" style="width: 100%; height: 200px; object-fit: cover; border-radius: 8px; border: 1px solid #e5e7eb;">';
            $html .= '</a>';
            $html .= '</div>';
        }
        $html .= '</div>';

        return $html;
    })
    ->html()
    ->columnSpanFull(),
```

## 💡 Почему работает?

1. **`getStateUsing()`** вызывается ОДИН раз для всего поля
2. Обрабатываем весь массив `$photos` сразу
3. Генерируем готовый HTML со всеми изображениями
4. Возвращаем HTML как **одну строку**
5. Filament не может разбить строку на элементы → показывает как есть

## ⚠️ Что НЕ работает

### ❌ Подход 1: ImageEntry с массивом
```php
ImageEntry::make('photos')
    ->multiple()  // Не существует в Filament 4.x
```

### ❌ Подход 2: RepeatableEntry
```php
RepeatableEntry::make('photos')
    ->schema([
        ImageEntry::make('url')  // ❌ Вызывает Memory Exhausted
    ])
```
**Причина:** Бесконечная рекурсия при обработке массива строк

### ❌ Подход 3: formatStateUsing с массивом
```php
->formatStateUsing(function ($state) {
    foreach ($state as $photo) { ... }  // ❌ $state = строка, не массив
})
```
**Причина:** Filament разбивает массив до вызова formatter

## 🎯 Результат

Модератор видит:
- ✅ Все фото в сетке 3 колонки
- ✅ Кликабельные изображения (открываются в новой вкладке)
- ✅ Размер 200×200px с object-fit: cover
- ✅ Красивые скругленные углы
- ✅ Работает без Memory Exhausted

## 🔗 Связанные файлы

- `app/Filament/Resources/AdResource.php` - метод `infolist()`, строки 250-274
- `app/Domain/Ad/Models/Ad.php` - модель с полем `photos` (JSON cast)
- `Docs/fixes/filament-csrf-419-fix.md` - другая проблема с Filament

## 🏷️ Теги

#filament #infolist #photos #array #image-gallery #memory-exhausted #json-field

## 📅 Дата

2025-10-01

## ✅ Статус

Решено и протестировано
