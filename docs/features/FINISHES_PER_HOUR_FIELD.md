# Добавление поля "Финалов в час"

## Дата: 10.09.2025

## Описание
Добавлено новое поле выпадающего списка "Финалов в час" в секцию "Дополнительные параметры" блока цен.

## Изменения

### 1. Frontend (Vue.js)

#### PricingSection.vue
- Добавлен новый блок "Дополнительные параметры"
- Создан выпадающий список с опциями:
  - 1
  - до 2-х
  - до 3-х
  - до 4-х
  - до 5-ти
  - безгранично
- Обновлен watcher для сохранения значения при загрузке черновика

#### adFormModel.ts
- Добавлено поле `finishes_per_hour` в интерфейс AdFormData
- Инициализация поля со значением по умолчанию ''
- Добавлена передача поля в formData при сохранении

### 2. Backend (Laravel)

#### Модель Ad.php
- Поле `finishes_per_hour` сохраняется в JSON поле `prices`
- Не требуется миграция БД

## Техническое решение

### Проблема со сбросом значения
При повторном открытии черновика поле сбрасывалось из-за особенности работы watcher.

**Решение:** В PricingSection.vue обновлен watcher для сохранения текущего значения `finishes_per_hour` если оно не приходит в новых props.

```javascript
watch(() => props.prices, (newPrices) => {
  if (newPrices) {
    // Сохраняем текущее значение finishes_per_hour если оно есть
    const currentFinishesPerHour = localPrices.finishes_per_hour
    
    Object.keys(newPrices).forEach(key => {
      if (newPrices[key] !== undefined) {
        localPrices[key] = newPrices[key]
      }
    })
    
    // Восстанавливаем finishes_per_hour если его не было в newPrices
    if (newPrices.finishes_per_hour === undefined && currentFinishesPerHour) {
      localPrices.finishes_per_hour = currentFinishesPerHour
    }
  }
}, { deep: true, immediate: true })
```

## Тестирование
1. ✅ Создание нового объявления с выбором значения
2. ✅ Сохранение черновика
3. ✅ Загрузка черновика с восстановлением значения
4. ✅ Изменение значения и повторное сохранение

## Статус: Завершено