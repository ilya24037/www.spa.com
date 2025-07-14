# 🛠️ Быстрые исправления

## Проблема: Ошибка импорта компонентов

### Решение 1: Проверить пути
```bash
# Убедиться что все файлы на месте
ls -la resources/js/Components/Upload/
ls -la resources/js/Components/Forms/
ls -la resources/js/Composables/
```

### Решение 2: Откатиться на бэкап
```bash
# Если что-то сломалось, вернуться к оригиналу
cp resources/js/Pages/AddItem/Massage.backup.vue resources/js/Pages/AddItem/Massage.vue
```

## Проблема: Ошибка сборки

### Решение: Очистить кэш и пересобрать
```bash
npm run build --clear-cache
npm run dev
```

## Проблема: 404 при загрузке изображений

### Решение: Настроить Laravel storage
```bash
php artisan storage:link
```

И добавить в config/filesystems.php:
```php
'disks' => [
    'public' => [
        'driver' => 'local',
        'root' => storage_path('app/public'),
        'url' => env('APP_URL').'/storage',
        'visibility' => 'public',
    ],
]
```

## Проблема: Ошибки TypeScript (если используется)

### Решение: Добавить типы
```javascript
// В любой .d.ts файл
declare module '@/Composables/*' {
  const value: any
  export default value
}
```

---

## ✅ Тест готовности

Попробуйте:
1. Заполнить форму на 50% → обновить страницу → данные должны восстановиться
2. Загрузить 3 фото → назначить главное → проверить превью
3. Нажать "Предварительный просмотр" → должна открыться модалка
4. Попытаться отправить пустую форму → должны показаться ошибки

**Если всё работает - внедрение успешно! 🎉**