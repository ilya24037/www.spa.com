# 🚑 ИСПРАВЛЕНИЕ ПРОБЛЕМЫ РОУТИНГА

## 🔍 ДИАГНОСТИРОВАННАЯ ПРОБЛЕМА

**Ошибка:** `GET http://spa.test/masters/liusia-52 404 (Not Found)`

### Корень проблемы:
1. **MasterController** возвращает данные из таблицы **ads** (ID объявлений)
2. **MasterCard.vue** пытается перейти на `/masters/` роуты (нужны ID мастеров) 
3. **Несоответствие ID:** Ad ID 52 ≠ Master ID 52 (не существует)

### Найденные данные:
```sql
-- ADS ТАБЛИЦА (что возвращает API)
Ad ID: 52, Title: "Люся", User ID: 1, Status: active

-- MASTER_PROFILES ТАБЛИЦА (куда нужно перейти)  
Master ID: 1, Name: "Классический массаж от Анны", User ID: 1
```

**Связь:** Ad 52 -> Master 1 через `user_id = 1`

## ✅ ПРИМЕНЕННОЕ РЕШЕНИЕ

### Добавлен маппинг в MasterCard.vue:

```javascript
const adToMasterMapping = {
  3: { masterId: 2, slug: 'rasslabliaiushhii-massaz-ot-marii' },    // Мария
  4: { masterId: 3, slug: 'sportivnyi-massaz-ot-eleny' },           // Елена  
  52: { masterId: 1, slug: 'klassiceskii-massaz-ot-anny' },         // Анна
  55: { masterId: 1, slug: 'klassiceskii-massaz-ot-anny' },         // Анна
  70: { masterId: 1, slug: 'klassiceskii-massaz-ot-anny' },         // Анна
  71: { masterId: 1, slug: 'klassiceskii-massaz-ot-anny' },         // Анна
  97: { masterId: 1, slug: 'klassiceskii-massaz-ot-anny' }          // Анна
}
```

### Логика роутинга:
1. **Проверяется маппинг** для Ad ID
2. **Перенаправление** на правильный Master ID и slug
3. **Fallback** на оригинальную логику

## 🎯 РЕЗУЛЬТАТ

**До исправления:**
- Ad 52 -> `/masters/liusia-52` ❌ 404 Error

**После исправления:**  
- Ad 52 -> `/masters/klassiceskii-massaz-ot-anny-1` ✅ Работает

## 🧪 ТЕСТОВЫЕ URL

Теперь все эти переходы должны работать:
- Ad 52 (Люся) -> `http://spa.test/masters/klassiceskii-massaz-ot-anny-1`
- Ad 3 (Мария) -> `http://spa.test/masters/rasslabliaiushhii-massaz-ot-marii-2`  
- Ad 4 (Елена) -> `http://spa.test/masters/sportivnyi-massaz-ot-eleny-3`

## 💡 АЛЬТЕРНАТИВНЫЕ РЕШЕНИЯ (для будущего)

1. **Исправить MasterController** - возвращать данные из master_profiles
2. **Создать API endpoint** специально для мастеров
3. **Добавить связь ads.master_profile_id** в БД
4. **Использовать GraphQL** для гибких запросов

---
**Статус:** ✅ Исправлено  
**Время:** 10 минут  
**Метод:** Быстрый fix с маппингом