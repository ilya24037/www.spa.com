# 📋 ИНСТРУКЦИИ ДЛЯ ТЕСТИРОВАНИЯ

## Проверка сохранения полей specialty, work_format, service_provider

### 1. Тест в браузере

1. Открыть http://spa.test
2. Войти в систему
3. Перейти на страницу создания объявления
4. Заполнить форму, обязательно указав:
   - **Специализация** (specialty) - выбрать из списка
   - **Формат работы** (work_format) - выбрать вариант
   - **Кто оказывает услуги** (service_provider) - выбрать варианты
5. Нажать "Сохранить как черновик"
6. Обновить страницу (F5)
7. **Проверить:** поля должны остаться заполненными

### 2. Проверка логов

```bash
# Windows PowerShell
Get-Content storage\logs\laravel.log -Tail 100 | Select-String "DraftService"

# Искать строки:
# "🔍 DraftService::saveOrUpdate - Входящие данные"
# "✅ DraftService: Объявление создано и проверено"
```

### 3. Проверка в БД

```sql
-- Последнее созданное объявление
SELECT id, title, specialty, work_format, service_provider, created_at
FROM ads
WHERE user_id = 1  -- замените на ваш user_id
ORDER BY created_at DESC
LIMIT 1;
```

### 4. Что должно работать

После всех изменений:
- ✅ `specialty` - сохраняется как строка
- ✅ `work_format` - сохраняется как строка
- ✅ `service_provider` - сохраняется как JSON массив

### 5. Если НЕ работает

Проверить:
1. Очистить кэш Laravel:
```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

2. Проверить fillable в модели Ad:
```php
// app/Domain/Ad/Models/Ad.php
protected $fillable = [
    // ...
    'specialty',
    'work_format',
    'service_provider',
    // ...
];
```

3. Проверить логи на ошибки:
```bash
tail -f storage/logs/laravel.log
```

### 6. Текущие изменения в коде

**Добавлено логирование:**
- `app/Domain/Ad/Services/DraftService.php` - логирование входящих данных и результатов
- `app/Application/Http/Controllers/Ad/DraftController.php` - логирование проблемных полей

**Добавлены связи:**
- `app/Domain/Ad/Models/Ad.php` - метод masterProfile()
- `app/Domain/Master/Models/MasterProfile.php` - метод ads()

**НЕ изменено (откачено):**
- ❌ AdCreationService - не используется
- ❌ AddItemController - нет роутов

---
*Тестирование должно подтвердить, что проблема решена*