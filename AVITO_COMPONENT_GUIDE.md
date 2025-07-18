# 🎯 Компонент ItemCard в стиле Avito - Руководство

## 🚀 Быстрый старт

### Демонстрация
Откройте в браузере: **http://127.0.0.1:8000/demo/item-card**

### Использование в коде
```vue
<template>
  <ItemCard 
    :item="serviceItem"
    @item-deleted="handleItemDeleted"
  />
</template>

<script setup>
import ItemCard from '@/Components/Profile/ItemCard.vue'

const serviceItem = {
  id: 1,
  name: "Расслабляющий массаж всего тела",
  price_from: 2500,
  avatar: "/images/masters/demo-1.jpg",
  photos_count: 4,
  company_name: "Массажный салон 'Релакс'",
  address: "Москва, ул. Тверская, 12",
  district: "Центральный район",
  home_service: true,
  status: "active",
  views_count: 47,
  subscribers_count: 3,
  favorites_count: 12,
  new_messages_count: 2,
  expires_at: "2024-02-15T12:00:00Z"
}
</script>
```

## 📋 Структура данных

### Обязательные поля
- `id` - уникальный ID
- `name` - название услуги

### Опциональные поля
- `price_from` - цена от (₽)
- `avatar` / `main_image` - изображение
- `photos_count` - количество фото
- `company_name` - название компании
- `address` - адрес
- `district` - район
- `home_service` - выезд к клиенту
- `status` - статус ('active', 'paused', 'draft')
- `views_count` - просмотры
- `subscribers_count` - подписчики
- `favorites_count` - избранное
- `new_messages_count` - новые сообщения
- `expires_at` - дата окончания

## 🎨 Внешний вид

### Структура карточки
```
┌─────────────────────────────────────────────────────────────────┐
│ [Изображение 160x120] │ [Заголовок]           │ [👁 7  👤 0  ❤️ 0] │
│ [••••]                │ [Цена 2500₽]          │ [Осталось 29 дней] │
│                       │ [Доступен для записи]  │ [💬 Нет новых]     │
│                       │ [🚗 Выезд к клиенту]   │ [Поднять просмотры] │
│                       │ [Компания]             │ [Рассылка] [•••]   │
│                       │ [Адрес]                │                    │
│                       │ [Район]                │                    │
└─────────────────────────────────────────────────────────────────┘
```

### Цвета и стили
- **Основной цвет**: #005bff (синий)
- **Цена**: text-2xl, жирный
- **Счетчики**: серый (#6b7280)
- **Предупреждение**: красный (< 7 дней)
- **Hover**: тень 0 4px 12px rgba(0,0,0,0.1)

## 🔧 Функциональность

### Действия
1. **Поднять просмотры** - основная кнопка
2. **Рассылка** - вторичная кнопка
3. **Выпадающее меню**:
   - Поднять просмотры
   - Редактировать
   - Забронировать
   - Снять с публикации
   - Удалить (с подтверждением)

### События
- `@item-deleted` - удаление объявления

## 📱 Адаптивность

### Desktop (>768px)
- Трёхколоночная структура
- Изображение 160x120px
- Полная функциональность

### Mobile (<768px)
- Вертикальная компоновка
- Изображение на всю ширину
- Центрированные счетчики

## 🎯 Интеграция с Dashboard

### В Dashboard.vue
```vue
<ItemCard 
  v-for="profile in filteredProfiles" 
  :key="profile.id"
  :item="profile"
  @item-deleted="handleItemDeleted"
/>
```

### Обработка удаления
```javascript
const handleItemDeleted = (itemId) => {
  profiles.value = profiles.value.filter(p => p.id !== itemId)
  // Показать уведомление об успешном удалении
}
```

## 🔍 Отладка

### Проверка изображений
1. Убедитесь, что изображения существуют в `public/images/masters/`
2. Проверьте правильность путей в данных
3. Используйте fallback изображения

### Проверка стилей
1. Убедитесь, что CSS скомпилирован: `npm run build`
2. Проверьте, что стили Avito загружены из `app.css`

## 🎨 Кастомизация

### Изменение цветов
Отредактируйте `resources/css/app.css`:
```css
.primary-button {
  background: #your-color;
}
```

### Добавление новых действий
Отредактируйте выпадающее меню в `ItemCard.vue`:
```vue
<a href="#" class="dropdown-item" @click.prevent="yourAction">
  Ваше действие
</a>
```

## 🚨 Важные примечания

1. **CSRF токен** должен быть в `<head>` для API запросов
2. **Изображения** должны быть в правильном формате (JPG, PNG)
3. **Права доступа** проверяются на бэкенде
4. **Валидация данных** происходит на стороне сервера

## 📞 Поддержка

При возникновении проблем:
1. Проверьте консоль браузера на ошибки
2. Убедитесь, что все зависимости установлены
3. Проверьте правильность данных в props

---

**Готово к использованию!** 🎉 