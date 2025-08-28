# 📋 ПЛАН ЗАВЕРШЕНИЯ РЕФАКТОРИНГА ПО FSD АРХИТЕКТУРЕ

## 📅 Дата создания: 28.08.2025
## 🎯 Цель: Завершить миграцию на Feature-Sliced Design

---

## 📊 ЧАСТЬ 1: АНАЛИЗ ТЕКУЩЕГО СОСТОЯНИЯ (AS IS)

### 🔴 Главная проблема: Masters/Show.vue
- **Размер:** 545 строк (монолитный компонент)
- **Проблема:** Содержит всю логику отображения профиля мастера
- **Нарушение FSD:** Страница напрямую работает с entities
- **Дублирование:** Ozon-style галерея реализована прямо в странице

### 📂 Текущая структура файлов
```
resources/js/
├── Pages/
│   └── Masters/
│       └── Show.vue (545 строк ❌ МОНОЛИТ - ТРЕБУЕТ РЕФАКТОРИНГА)
│
└── src/
    ├── widgets/
    │   └── master-profile/
    │       ├── MasterProfile.vue (123 строки ✅ готов)
    │       └── MasterProfileDetailed.vue (300+ строк ✅ ГОТОВ К ИСПОЛЬЗОВАНИЮ)
    │
    ├── entities/
    │   ├── master/ui/
    │   │   ├── MasterInfo/MasterInfo.vue ✅ готов
    │   │   ├── MasterGallery/MasterGallery.vue ❌ ЗАГЛУШКА - ТРЕБУЕТ РЕАЛИЗАЦИИ
    │   │   ├── MasterServices/MasterServices.vue ✅ готов
    │   │   ├── MasterReviews/MasterReviews.vue ✅ готов
    │   │   └── MasterContact/MasterContact.vue ✅ готов
    │   └── booking/ui/
    │       └── BookingWidget/BookingWidget.vue ✅ готов
    │
    └── features/
        └── gallery/ui/
            └── PhotoViewer/PhotoViewer.vue ✅ готов
```

### ⚠️ Компоненты требующие доработки:
1. **MasterGallery.vue** - только заглушка, нужна реализация Ozon-style галереи
2. **Masters/Show.vue** - монолит 545 строк, нужен рефакторинг в обертку

---

## ✅ ЧАСТЬ 2: ЦЕЛЕВАЯ АРХИТЕКТУРА (TO BE)

### 🎯 Целевая структура после рефакторинга
```
resources/js/
├── Pages/
│   └── Masters/
│       └── Show.vue (80 строк ✅ ЛЕГКОВЕСНАЯ ОБЕРТКА)
│
└── src/
    └── widgets/
        └── master-profile/
            └── MasterProfileDetailed.vue (ГЛАВНЫЙ ВИДЖЕТ)
                ├── использует → MasterGallery (Ozon-style)
                ├── использует → MasterInfo
                ├── использует → MasterServices
                ├── использует → MasterReviews
                ├── использует → BookingWidget
                └── использует → MasterContact
```

### 📐 Правильная иерархия по FSD:
```
Слои FSD (сверху вниз):
┌─────────────────────────────────────┐
│ pages/     → Show.vue (80 строк)    │ ← Точка входа
├─────────────────────────────────────┤
│ widgets/   → MasterProfileDetailed  │ ← Композиция
├─────────────────────────────────────┤
│ features/  → PhotoGallery, etc.     │ ← Функциональности
├─────────────────────────────────────┤
│ entities/  → MasterInfo, etc.       │ ← Базовые сущности
├─────────────────────────────────────┤
│ shared/    → StarRating, Button     │ ← UI компоненты
└─────────────────────────────────────┘
```

---

## 🔄 ЧАСТЬ 3: ПОШАГОВЫЙ ПЛАН МИГРАЦИИ

### ШАГ 1: Реализация MasterGallery entity (30 минут)
**Файл:** `src/entities/master/ui/MasterGallery/MasterGallery.vue`

**Было (заглушка):**
```vue
<template>
  <div class="mastergallery">
    <p>Component MasterGallery is under development</p>
  </div>
</template>
```

**Станет (Ozon-style галерея):**
```vue
<template>
  <div class="ozon-gallery flex gap-4 p-4">
    <!-- Миниатюры слева (вертикальный список) -->
    <div class="thumbnails flex flex-col gap-2 w-20">
      <div 
        v-for="(image, index) in visibleThumbnails" 
        :key="index"
        @click="currentImageIndex = index"
        :class="[
          'thumbnail-item w-20 h-16 rounded-lg overflow-hidden cursor-pointer border-2 transition-all',
          currentImageIndex === index 
            ? 'border-blue-500 shadow-lg' 
            : 'border-gray-200 hover:border-gray-400'
        ]"
      >
        <img :src="image" :alt="`Фото ${index + 1}`" class="w-full h-full object-cover">
        
        <!-- Индикатор дополнительных фото на последней миниатюре -->
        <div 
          v-if="index === 5 && images.length > 6"
          class="absolute inset-0 bg-black bg-opacity-60 flex items-center justify-center"
        >
          <span class="text-white font-bold">+{{ images.length - 6 }}</span>
        </div>
      </div>
    </div>
    
    <!-- Основное изображение справа -->
    <div class="main-image flex-1 relative">
      <img 
        :src="currentImage" 
        :alt="'Главное фото'"
        @click="openViewer"
        class="w-full h-96 object-cover rounded-lg cursor-pointer"
      >
      
      <!-- Индикатор количества фото -->
      <div class="absolute bottom-4 right-4 bg-black bg-opacity-60 text-white px-3 py-1 rounded-lg">
        📷 {{ images.length }} фото
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'

const props = defineProps<{
  images: string[]
}>()

const currentImageIndex = ref(0)
const currentImage = computed(() => props.images[currentImageIndex.value])
const visibleThumbnails = computed(() => props.images.slice(0, 6))

const openViewer = () => {
  // Эмит события для открытия PhotoViewer
  emit('open-viewer', currentImageIndex.value)
}

const emit = defineEmits(['open-viewer'])
</script>
```

### ШАГ 2: Обновление импорта в MasterProfileDetailed (5 минут)
**Файл:** `src/widgets/master-profile/MasterProfileDetailed.vue`

**Изменение в импортах:**
```javascript
// Было:
import { PhotoGallery, PhotoViewer } from '@/src/features/gallery'

// Станет:
import MasterGallery from '@/src/entities/master/ui/MasterGallery/MasterGallery.vue'
import { PhotoViewer } from '@/src/features/gallery'
```

**Изменение в template:**
```vue
<!-- Было: -->
<PhotoGallery :images="galleryImages" />

<!-- Станет: -->
<MasterGallery :images="galleryImages" @open-viewer="handleOpenViewer" />
```

### ШАГ 3: Рефакторинг Masters/Show.vue (15 минут)
**Файл:** `resources/js/Pages/Masters/Show.vue`

**Новое содержимое (80 строк вместо 545):**
```vue
<template>
  <AppLayout :title="meta?.title || 'Профиль мастера'">
    <Head :title="meta?.title || 'Профиль мастера'" />
    
    <!-- Основной контент - делегируем виджету -->
    <MasterProfileDetailed 
      :master="master"
      :loading="false"
    />
    
    <!-- Дополнительная секция похожих мастеров (опционально) -->
    <div v-if="similarMasters?.length" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-12">
      <h2 class="text-2xl font-bold text-gray-900 mb-6">
        Похожие мастера
      </h2>
      <SimilarMastersSection :masters="similarMasters" />
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head } from '@inertiajs/vue3'
import MasterProfileDetailed from '@/src/widgets/master-profile/MasterProfileDetailed.vue'
import SimilarMastersSection from '@/src/features/similar-masters/ui/SimilarMastersSection.vue'

// Props от Inertia
interface Props {
  master: {
    id: number
    name: string
    avatar?: string
    rating?: number
    reviews_count?: number
    photos?: Array<{ url: string; thumbnail_url?: string }>
    services?: Array<any>
    reviews?: Array<any>
    [key: string]: any
  }
  meta?: {
    title?: string
    description?: string
    [key: string]: any
  }
  similarMasters?: Array<any>
}

const props = defineProps<Props>()

// Вся логика делегирована в MasterProfileDetailed widget
// Show.vue остается чистой страницей-оберткой
</script>

<style scoped>
/* Минимальные стили, если нужны */
</style>
```

---

## 📏 ЧАСТЬ 4: АРХИТЕКТУРНЫЕ ПРАВИЛА FSD

### 🚫 ЗАПРЕЩЕНО:
1. **Pages не могут импортировать Entities напрямую**
   ```javascript
   // ❌ НЕПРАВИЛЬНО
   // В pages/Show.vue
   import MasterInfo from '@/entities/master/ui/MasterInfo'
   ```

2. **Entities не могут импортировать Widgets**
   ```javascript
   // ❌ НЕПРАВИЛЬНО  
   // В entities/MasterInfo.vue
   import MasterProfile from '@/widgets/master-profile'
   ```

3. **Нижние слои не знают о верхних**
   - shared не знает об entities
   - entities не знает о features
   - features не знает о widgets
   - widgets не знает о pages

### ✅ РАЗРЕШЕНО:
1. **Pages импортируют Widgets**
   ```javascript
   // ✅ ПРАВИЛЬНО
   // В pages/Show.vue
   import MasterProfileDetailed from '@/widgets/master-profile'
   ```

2. **Widgets импортируют Entities и Features**
   ```javascript
   // ✅ ПРАВИЛЬНО
   // В widgets/MasterProfileDetailed.vue
   import MasterInfo from '@/entities/master/ui/MasterInfo'
   import PhotoGallery from '@/features/gallery'
   ```

3. **Однонаправленный поток зависимостей**
   ```
   pages → widgets → features → entities → shared
   ```

---

## 📊 ЧАСТЬ 5: МЕТРИКИ И ОЖИДАЕМЫЕ РЕЗУЛЬТАТЫ

### Метрики изменений:
| Метрика | Было | Станет | Улучшение |
|---------|------|--------|-----------|
| **Строк в Show.vue** | 545 | 80 | -85% ✅ |
| **Импортов в Show.vue** | 15-20 | 4 | -75% ✅ |
| **Переиспользуемость галереи** | 0% | 100% | +100% ✅ |
| **Соответствие FSD** | 20% | 100% | +80% ✅ |

### Ожидаемые улучшения:
1. **Производительность** - меньше импортов = быстрее сборка
2. **Поддержка** - изменения в одном месте, а не во всех страницах
3. **Тестирование** - можно тестировать виджеты отдельно
4. **Масштабирование** - легко добавлять новые страницы используя готовые виджеты

---

## ✅ ЧАСТЬ 6: ФИНАЛЬНЫЙ ЧЕКЛИСТ

### До начала работы:
- [ ] Создать резервную копию текущего Show.vue
- [ ] Проверить что все entities компоненты готовы
- [ ] Убедиться что MasterProfileDetailed работает

### Шаг 1 - MasterGallery:
- [ ] Скопировать Ozon-галерею из Show.vue в MasterGallery.vue
- [ ] Добавить TypeScript типизацию props
- [ ] Добавить emit для открытия PhotoViewer
- [ ] Протестировать отдельно компонент

### Шаг 2 - MasterProfileDetailed:
- [ ] Заменить PhotoGallery на MasterGallery
- [ ] Добавить обработчик handleOpenViewer
- [ ] Проверить что galleryImages передаются корректно
- [ ] Протестировать виджет целиком

### Шаг 3 - Show.vue:
- [ ] Удалить всю лишнюю логику
- [ ] Оставить только импорт MasterProfileDetailed
- [ ] Проверить передачу props от Inertia
- [ ] Убедиться что страница < 100 строк

### Финальная проверка:
- [ ] Галерея работает (миниатюры + главное фото)
- [ ] Переключение фото по клику работает
- [ ] Модальное окно PhotoViewer открывается
- [ ] Все данные мастера отображаются
- [ ] Бронирование работает
- [ ] Отзывы загружаются

---

## 🚀 ВРЕМЯ ВЫПОЛНЕНИЯ

**Общее время:** ~50 минут

1. MasterGallery реализация - 30 минут
2. Обновление MasterProfileDetailed - 5 минут  
3. Рефакторинг Show.vue - 15 минут

---

## 📝 КОМАНДЫ ДЛЯ ПРОВЕРКИ

```bash
# Проверить размер файлов после рефакторинга
ls -la resources/js/Pages/Masters/Show.vue
ls -la resources/js/src/entities/master/ui/MasterGallery/MasterGallery.vue

# Пересобрать фронтенд
npm run dev

# Открыть в браузере для тестирования
start chrome "http://spa.test/masters/klassiceskii-massaz-ot-anny-1"
```

---

## 📚 ДОПОЛНИТЕЛЬНЫЕ МАТЕРИАЛЫ

- [Feature-Sliced Design документация](https://feature-sliced.design)
- [FSD в Vue приложениях](https://feature-sliced.design/docs/guides/tech/with-vue)
- [Миграция legacy кода на FSD](https://feature-sliced.design/docs/guides/migration)

---

**Документ создан:** 28.08.2025  
**Автор:** AI Assistant  
**Статус:** Готов к выполнению