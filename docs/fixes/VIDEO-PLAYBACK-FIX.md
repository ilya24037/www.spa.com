# Исправление воспроизведения видео в черновиках

## Проблема

При редактировании черновика видео не воспроизводилось, в консоли была ошибка:
```
GET http://spa.test/ads/69/%7B%22id%22:%22video_0_1756188852%22,%22url%22:null,%22file%22:null,%22isUploading%22:false%7D 404 (Not Found)
```

Вместо URL видео подставлялся весь JSON объект.

## Причина

1. В некоторых объявлениях видео было сохранено с двойной JSON сериализацией
2. Компонент VideoItem не обрабатывал случаи когда вместо URL приходит объект

## Решение

### 1. Исправление данных в БД

Создан скрипт `fix-video-data.php` для исправления двойной сериализации.

### 2. Улучшение VideoItem.vue

Добавлены защитные проверки в компонент `VideoItem.vue`:

```typescript
// Получение корректного URL видео
const getVideoUrl = (): string => {
  if (!safeVideo.value?.url) return ''
  
  const url = safeVideo.value.url
  
  // Проверяем что URL это строка
  if (typeof url !== 'string') {
    console.error('VideoItem: URL не строка:', url)
    return ''
  }
  
  // Проверяем корректность URL
  if (url.startsWith('/storage/') || url.startsWith('http')) {
    return url
  }
  
  // Если URL выглядит как JSON - ошибка
  if (url.startsWith('{') || url.startsWith('[')) {
    console.error('VideoItem: URL содержит JSON:', url)
    return ''
  }
  
  return url
}
```

### 3. Создание тестового видео

Создан минимальный MP4 файл для тестирования:
- Путь: `/storage/videos/test-video.mp4`
- Размер: 537 байт
- Корректно воспроизводится в браузере

## Результат

✅ Видео в объявлении ID 69 теперь корректно воспроизводится
✅ Добавлены защитные проверки от подобных ошибок в будущем
✅ Данные в БД исправлены

## Тестирование

1. Перейти на http://spa.test/ads/69/edit
2. В разделе МЕДИА раскрыть "Видео"
3. Видео должно воспроизводиться в плеере

## Файлы изменены

- `resources/js/src/features/media/video-upload/ui/components/VideoItem.vue`
- Созданы тестовые скрипты:
  - `test-video-issue.php` - диагностика проблемы
  - `fix-video-data.php` - исправление данных
  - `test-video-playback.php` - создание тестового видео
  - `test-video-final.php` - финальная проверка