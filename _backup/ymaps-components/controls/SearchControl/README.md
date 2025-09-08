# 🔍 SearchControl - Контрол поиска на карте

Мощный и гибкий контрол поиска для Yandex Maps с автодополнением, геокодированием и полной интеграцией с Vue 3.

## 📋 Особенности

- ✅ **Полнотекстовый поиск** - Адреса, POI, координаты
- ✅ **Автодополнение** - Интеллектуальные предложения в реальном времени
- ✅ **Геокодирование** - Точное определение координат
- ✅ **TypeScript** - Полная типизация без any
- ✅ **Vue 3 поддержка** - Готовый Vue компонент с Composition API
- ✅ **v-model интеграция** - Двустороннее связывание запроса
- ✅ **Клавиатурная навигация** - Arrow keys, Enter, Escape
- ✅ **Accessibility** - ARIA атрибуты, screen reader support
- ✅ **Мобильная оптимизация** - Touch-friendly интерфейс
- ✅ **Production-ready** - Полная обработка ошибок и edge cases

## 🚀 Быстрый старт

### Vanilla JavaScript

```javascript
import SearchControl from './SearchControl.js'
import YMapsCore from '../../core/YMapsCore.js'

async function initMap() {
  // Создаем карту
  const mapsCore = new YMapsCore({ apiKey: 'YOUR_API_KEY' })
  await mapsCore.loadAPI()
  const map = await mapsCore.createMap('map')
  
  // Создаем контрол поиска
  const searchControl = new SearchControl({
    placeholder: 'Поиск адреса или места...',
    position: 'topLeft',
    enableAutoComplete: true,
    maxResults: 15,
    fitResultBounds: true
  })
  
  // Добавляем на карту
  await searchControl.addToMap(map)
  
  // Обработчики событий
  searchControl.on('searchcomplete', (event) => {
    console.log(`Найдено ${event.total} результатов для "${event.query}"`)
    event.results.forEach((result, index) => {
      console.log(`${index + 1}. ${result.displayName} - ${result.address}`)
    })
  })
  
  searchControl.on('resultselect', (event) => {
    console.log('Выбрано место:', event.result.displayName)
    console.log('Координаты:', event.result.coordinates)
  })
}
```

### Vue 3 Composition API

```vue
<template>
  <div id="map" style="height: 500px"></div>
  
  <!-- SearchControl с полной интеграцией -->
  <SearchControlVue
    :map="map"
    v-model:query="searchQuery"
    :max-results="20"
    :search-delay="250"
    placeholder="Поиск ресторанов, кафе, достопримечательностей..."
    position="topRight"
    :show-external-results="true"
    :show-debug-info="isDevelopment"
    @searchcomplete="onSearchComplete"
    @resultselect="onResultSelect"
    @ready="onSearchReady"
  />
  
  <!-- Статистика поиска -->
  <div v-if="searchStats" class="search-stats">
    <h3>Статистика поиска</h3>
    <p>Запросов: {{ searchStats.totalQueries }}</p>
    <p>Найдено мест: {{ searchStats.totalResults }}</p>
    <p>Последний запрос: {{ searchStats.lastQuery }}</p>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import YMapsCore from '@/ymaps-components/core/YMapsCore'
import SearchControlVue from '@/ymaps-components/controls/SearchControl/SearchControl.vue'

const map = ref(null)
const searchQuery = ref('')
const searchStats = ref(null)

const isDevelopment = computed(() => process.env.NODE_ENV === 'development')

onMounted(async () => {
  const mapsCore = new YMapsCore({ apiKey: 'YOUR_KEY' })
  await mapsCore.loadAPI()
  map.value = await mapsCore.createMap('map', {
    center: [55.753994, 37.622093],
    zoom: 11
  })
})

const onSearchComplete = (event) => {
  searchStats.value = {
    totalQueries: (searchStats.value?.totalQueries || 0) + 1,
    totalResults: event.total,
    lastQuery: event.query
  }
  
  console.log('Поиск завершен:', event.results)
}

const onResultSelect = (event) => {
  console.log('Выбрано место:', event.result)
  
  // Можем сохранить в историю поиска
  localStorage.setItem('lastSelectedPlace', JSON.stringify({
    name: event.result.displayName,
    coordinates: event.result.coordinates,
    timestamp: Date.now()
  }))
}

const onSearchReady = (control) => {
  console.log('SearchControl готов к использованию')
  
  // Можем загрузить последний поисковый запрос
  const lastQuery = localStorage.getItem('lastSearchQuery')
  if (lastQuery) {
    searchQuery.value = lastQuery
  }
}
</script>
```

## ⚙️ Конфигурация

### Опции конструктора (JavaScript)

```typescript
interface SearchControlOptions {
  // Поле ввода
  placeholder?: string                    // Плейсхолдер
  showClearButton?: boolean              // Кнопка очистки
  showSearchButton?: boolean             // Кнопка поиска
  
  // Поведение поиска
  enableAutoComplete?: boolean           // Автодополнение
  searchDelay?: number                   // Задержка поиска (мс)
  maxResults?: number                    // Максимум результатов (1-50)
  searchTypes?: string[]                 // Типы поиска ['text', 'geo', 'biz']
  
  // Обработка результатов
  fitResultBounds?: boolean              // Подстраивать карту
  addResultMarker?: boolean              // Добавлять маркер
  formatResult?: (result) => string      // Кастомное форматирование
  filterResults?: (result) => boolean    // Фильтрация результатов
  
  // Внешний вид
  position?: string                      // Позиция на карте
  visible?: boolean                      // Видимость
  enabled?: boolean                      // Активность
  zIndex?: number                        // Z-index
  margin?: object                        // Отступы
}
```

### Props Vue компонента

```typescript
interface Props {
  map?: any                              // Экземпляр карты
  query?: string                         // Поисковый запрос (v-model)
  placeholder?: string                   // Плейсхолдер
  showClearButton?: boolean              // Кнопка очистки
  showSearchButton?: boolean             // Кнопка поиска
  enableAutoComplete?: boolean           // Автодополнение
  searchDelay?: number                   // Задержка поиска
  maxResults?: number                    // Максимум результатов
  searchTypes?: string[]                 // Типы поиска
  fitResultBounds?: boolean              // Подстраивать карту
  addResultMarker?: boolean              // Маркер результата
  position?: string                      // Позиция
  visible?: boolean                      // Видимость
  enabled?: boolean                      // Активность
  showExternalResults?: boolean          // Внешние результаты
  showDebugInfo?: boolean                // Debug информация
  zIndex?: number                        // Z-index
  margin?: object                        // Отступы
  extendedOptions?: object               // Расширенные опции
}
```

## 🔧 API методы

### JavaScript класс

```typescript
class SearchControl {
  // Управление запросами
  setQuery(query: string, triggerSearch?: boolean): void    // Установить запрос
  getQuery(): string                                        // Получить запрос
  search(): Promise<SearchResult[]>                         // Запустить поиск
  clear(): void                                            // Очистить поиск
  
  // Результаты поиска
  getResults(): SearchResult[]                              // Все результаты
  getSelectedResult(): SearchResult | null                  // Выбранный результат
  
  // Фокус и видимость
  focus(): void                                            // Фокус на поле
  blur(): void                                             // Убрать фокус
  show(): void                                             // Показать
  hide(): void                                             // Скрыть
  enable(): void                                           // Включить
  disable(): void                                          // Отключить
  
  // Настройки
  setPlaceholder(placeholder: string): void                 // Плейсхолдер
  getPlaceholder(): string                                 // Получить плейсхолдер
  setMaxResults(maxResults: number): void                  // Макс результатов
  getMaxResults(): number                                  // Получить макс
  setAutoComplete(enabled: boolean): void                  // Автодополнение
  isAutoCompleteEnabled(): boolean                         // Статус автодополнения
  setSearchDelay(delay: number): void                      // Задержка поиска
  getSearchDelay(): number                                 // Получить задержку
  
  // Состояние
  isSearching(): boolean                                   // Идет ли поиск
  getResultMarker(): ymaps.Placemark | null               // Маркер результата
  getLastSearchBounds(): Array | null                     // Последние границы
  
  // События
  on(event: string, handler: Function): void              // Подписаться
  off(event: string, handler: Function): void             // Отписаться
  
  // Жизненный цикл
  addToMap(map: ymaps.Map): Promise<void>                 // Добавить на карту
  removeFromMap(): Promise<void>                          // Удалить с карты
  destroy(): void                                         // Уничтожить
}
```

### Vue компонент (defineExpose)

```typescript
// Методы, доступные через template ref
interface ExposedMethods {
  getControl(): SearchControl | null        // JS экземпляр
  getQuery(): string                        // Текущий запрос
  setQuery(query: string, triggerSearch?: boolean): Promise<void>  // Установить запрос
  search(): Promise<SearchResult[]>          // Запустить поиск
  clear(): void                             // Очистить
  focus(): void                             // Фокус
  blur(): void                              // Убрать фокус
  getResults(): SearchResult[]              // Результаты
  getSelectedResult(): SearchResult | null   // Выбранный результат
  recreate(): Promise<void>                 // Пересоздать
}

// Использование в родительском компоненте
const searchRef = ref()

const performSearch = async () => {
  const results = await searchRef.value.search()
  console.log('Найдено мест:', results.length)
}

const clearSearch = () => {
  searchRef.value.clear()
}
```

## 📡 События

### JavaScript

```javascript
searchControl.on('inputchange', (event) => {
  console.log('Пользователь ввел:', event.value)
})

searchControl.on('searchstart', () => {
  console.log('Начинаем поиск...')
  showLoadingSpinner()
})

searchControl.on('searchend', () => {
  console.log('Поиск завершен')
  hideLoadingSpinner()
})

searchControl.on('searchcomplete', (event) => {
  console.log(`Найдено ${event.total} результатов для "${event.query}"`)
  event.results.forEach(result => {
    console.log(`- ${result.displayName} (${result.type})`)
  })
})

searchControl.on('resultselect', (event) => {
  console.log('Выбран результат:', event.result.displayName)
  console.log('Координаты:', event.result.coordinates)
  
  // Сохраняем в аналитику
  analytics.track('place_selected', {
    name: event.result.displayName,
    type: event.result.type,
    coordinates: event.result.coordinates
  })
})

searchControl.on('resultprocessed', (event) => {
  console.log('Результат обработан на карте')
  if (event.marker) {
    console.log('Маркер добавлен:', event.marker)
  }
})

// События фокуса
searchControl.on('focus', () => {
  console.log('Поле поиска получило фокус')
})

searchControl.on('blur', () => {
  console.log('Поле поиска потеряло фокус')
})

searchControl.on('clear', () => {
  console.log('Поиск очищен')
})
```

### Vue

```vue
<template>
  <SearchControlVue
    :map="map"
    v-model:query="query"
    @inputchange="onInputChange"
    @searchstart="onSearchStart"
    @searchend="onSearchEnd"
    @searchcomplete="onSearchComplete"
    @resultselect="onResultSelect"
    @resultprocessed="onResultProcessed"
    @focus="onFocus"
    @blur="onBlur"
    @clear="onClear"
    @error="onError"
    @ready="onReady"
  />
</template>

<script setup>
const onInputChange = (value) => {
  console.log('Ввод изменился:', value)
}

const onSearchComplete = (data) => {
  const { query, results, total } = data
  console.log(`"${query}": ${total} результатов`)
}

const onError = (error) => {
  console.error('Ошибка поиска:', error.message)
  // Показать уведомление пользователю
  showErrorToast('Ошибка поиска. Попробуйте позже.')
}
</script>
```

## 🎨 Кастомизация стилей

### CSS переменные

```css
.ymaps-search-control {
  --input-height: 40px;           /* Высота поля ввода */
  --input-padding: 12px 16px;     /* Внутренние отступы */
  --font-size: 14px;              /* Размер шрифта */
  --border-radius: 8px;           /* Скругление */
  --border-color: #e2e8f0;        /* Цвет рамки */
  --focus-color: #3b82f6;         /* Цвет фокуса */
  --button-size: 36px;            /* Размер кнопок */
  --results-max-height: 400px;    /* Макс высота результатов */
}

/* Размеры */
.ymaps-search-control--small {
  --input-height: 32px;
  --input-padding: 8px 12px;
  --font-size: 13px;
  --button-size: 28px;
}

.ymaps-search-control--large {
  --input-height: 48px;
  --input-padding: 16px 20px;
  --font-size: 16px;
  --button-size: 44px;
}
```

### Кастомные стили

```css
/* Стильный современный дизайн */
.ymaps-search-control-input {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  border: 2px solid transparent;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.ymaps-search-control-input:focus {
  background: white;
  border-color: #667eea;
  box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1),
              0 4px 16px rgba(0, 0, 0, 0.12);
  transform: translateY(-1px);
}

/* Стильные кнопки */
.ymaps-search-control-search {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  color: white;
  font-size: 16px;
  box-shadow: 0 2px 8px rgba(102, 126, 234, 0.4);
  transition: all 0.2s ease;
}

.ymaps-search-control-search:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.6);
}

.ymaps-search-control-search:active {
  transform: translateY(0);
}

/* Результаты поиска с анимацией */
.ymaps-search-results-list {
  animation: slideDown 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-8px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.ymaps-search-result-item {
  transition: all 0.2s ease;
  border-left: 3px solid transparent;
}

.ymaps-search-result-item:hover {
  background: linear-gradient(90deg, rgba(102, 126, 234, 0.1) 0%, transparent 100%);
  border-left-color: #667eea;
  transform: translateX(4px);
}

/* Темная тема */
@media (prefers-color-scheme: dark) {
  .ymaps-search-control-input {
    background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
    color: #f9fafb;
    border-color: #374151;
  }
  
  .ymaps-search-control-input:focus {
    background: #1f2937;
    border-color: #667eea;
  }
  
  .ymaps-search-result-item {
    background: #1f2937;
    color: #f9fafb;
    border-color: #374151;
  }
}
```

## 📱 Адаптивность

SearchControl автоматически адаптируется для разных устройств:

```css
/* Мобильные устройства */
@media (max-width: 768px) {
  .ymaps-search-control {
    --input-height: 44px;        /* Больше для touch */
    --input-padding: 12px 16px;
    --font-size: 16px;          /* Предотвращает zoom в iOS */
    --button-size: 44px;
  }
  
  /* Полноэкранные результаты */
  .vue-search-control .search-external-results {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    z-index: 10000;
    background: white;
  }
  
  .vue-search-control .results-list {
    max-height: calc(100vh - 80px);
    padding-bottom: env(safe-area-inset-bottom);
  }
}

/* Планшеты */
@media (min-width: 768px) and (max-width: 1024px) {
  .ymaps-search-control {
    --input-height: 42px;
    --font-size: 15px;
  }
  
  .vue-search-control .search-external-results {
    max-width: 500px;
    margin: 0 auto;
  }
}

/* Touch устройства */
@media (hover: none) and (pointer: coarse) {
  .ymaps-search-result-item {
    min-height: 56px;           /* Достаточно для пальца */
    padding: 16px;
  }
  
  .ymaps-search-control-clear,
  .ymaps-search-control-search {
    min-width: 44px;
    min-height: 44px;
  }
}
```

## 🎯 Продвинутые примеры

### Поиск с фильтрацией по типам

```javascript
const searchControl = new SearchControl({
  placeholder: 'Поиск ресторанов и кафе...',
  maxResults: 20,
  filterResults: (result) => {
    // Показываем только рестораны, кафе и бары
    const foodTypes = ['cafe', 'restaurant', 'bar', 'food']
    return foodTypes.some(type => 
      result.kind?.includes(type) || 
      result.displayName?.toLowerCase().includes(type)
    )
  },
  formatResult: (result) => {
    // Кастомное форматирование с иконками
    const getIcon = (type) => {
      const icons = {
        cafe: '☕',
        restaurant: '🍴',
        bar: '🍺',
        food: '🍕'
      }
      return icons[type] || '📍'
    }
    
    const icon = getIcon(result.kind)
    return `${icon} ${result.displayName}`
  }
})
```

### Интеграция с геолокацией

```javascript
const searchControl = new SearchControl({
  placeholder: 'Поиск рядом с вами...',
  searchOptions: {
    // Приоритет результатам рядом с пользователем
    boundedBy: null, // устанавливается динамически
    results: 15
  }
})

// Получаем позицию пользователя
navigator.geolocation.getCurrentPosition(async (position) => {
  const userCoords = [position.coords.latitude, position.coords.longitude]
  
  // Создаем границы поиска (радиус 5км)
  const bounds = [
    [userCoords[0] - 0.045, userCoords[1] - 0.045],
    [userCoords[0] + 0.045, userCoords[1] + 0.045]
  ]
  
  // Обновляем опции поиска
  searchControl.setOption('searchOptions', {
    ...searchControl.getOptions().searchOptions,
    boundedBy: bounds
  })
})
```

### Vue с историей поиска

```vue
<template>
  <SearchControlVue
    :map="map"
    v-model:query="searchQuery"
    @searchcomplete="saveToHistory"
    @resultselect="selectResult"
  />
  
  <!-- История поиска -->
  <div v-if="searchHistory.length > 0" class="search-history">
    <h3>Недавние поиски</h3>
    <ul>
      <li 
        v-for="item in searchHistory" 
        :key="item.id"
        @click="repeatSearch(item)"
        class="history-item"
      >
        <span class="history-query">{{ item.query }}</span>
        <span class="history-time">{{ formatTime(item.timestamp) }}</span>
      </li>
    </ul>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const searchQuery = ref('')
const searchHistory = ref([])

onMounted(() => {
  // Загружаем историю из localStorage
  const saved = localStorage.getItem('searchHistory')
  if (saved) {
    searchHistory.value = JSON.parse(saved)
  }
})

const saveToHistory = (event) => {
  if (event.total === 0) return
  
  const historyItem = {
    id: Date.now(),
    query: event.query,
    resultsCount: event.total,
    timestamp: new Date()
  }
  
  // Добавляем в начало, ограничиваем до 10 элементов
  searchHistory.value = [historyItem, ...searchHistory.value]
    .filter((item, index, arr) => 
      // Убираем дубликаты по запросу
      arr.findIndex(x => x.query === item.query) === index
    )
    .slice(0, 10)
  
  // Сохраняем в localStorage
  localStorage.setItem('searchHistory', JSON.stringify(searchHistory.value))
}

const repeatSearch = (historyItem) => {
  searchQuery.value = historyItem.query
}

const formatTime = (timestamp) => {
  return new Intl.RelativeTimeFormat('ru').format(
    Math.floor((new Date(timestamp) - new Date()) / (1000 * 60)),
    'minute'
  )
}
</script>
```

### Интеграция с состоянием приложения (Pinia)

```typescript
// store/searchStore.ts
export const useSearchStore = defineStore('search', () => {
  const currentQuery = ref('')
  const searchResults = ref([])
  const isSearching = ref(false)
  const searchHistory = ref([])
  const favorites = ref([])
  
  // Мутации
  const setQuery = (query: string) => {
    currentQuery.value = query
  }
  
  const setResults = (results: SearchResult[]) => {
    searchResults.value = results
  }
  
  const addToHistory = (query: string, results: SearchResult[]) => {
    const historyItem = {
      id: Date.now(),
      query,
      resultsCount: results.length,
      timestamp: new Date()
    }
    
    searchHistory.value = [historyItem, ...searchHistory.value]
      .filter((item, index, arr) => 
        arr.findIndex(x => x.query === item.query) === index
      )
      .slice(0, 20)
    
    // Синхронизируем с localStorage
    localStorage.setItem('searchHistory', JSON.stringify(searchHistory.value))
  }
  
  const addToFavorites = (result: SearchResult) => {
    const favorite = {
      id: Date.now(),
      name: result.displayName,
      address: result.address,
      coordinates: result.coordinates,
      addedAt: new Date()
    }
    
    favorites.value.push(favorite)
    localStorage.setItem('searchFavorites', JSON.stringify(favorites.value))
  }
  
  // Геттеры
  const getPopularQueries = computed(() => {
    const queryCount = new Map()
    searchHistory.value.forEach(item => {
      queryCount.set(item.query, (queryCount.get(item.query) || 0) + 1)
    })
    
    return Array.from(queryCount.entries())
      .sort(([,a], [,b]) => b - a)
      .slice(0, 5)
      .map(([query]) => query)
  })
  
  return {
    currentQuery,
    searchResults,
    isSearching,
    searchHistory,
    favorites,
    setQuery,
    setResults,
    addToHistory,
    addToFavorites,
    getPopularQueries
  }
})

// Component.vue
<template>
  <SearchControlVue
    :map="map"
    v-model:query="searchStore.currentQuery"
    @searchcomplete="onSearchComplete"
    @resultselect="onResultSelect"
  />
</template>

<script setup>
const searchStore = useSearchStore()

const onSearchComplete = (event) => {
  searchStore.setResults(event.results)
  searchStore.addToHistory(event.query, event.results)
}

const onResultSelect = (event) => {
  // Можем добавить в избранное
  if (confirm('Добавить место в избранное?')) {
    searchStore.addToFavorites(event.result)
  }
}
</script>
```

## 🐛 Решение проблем

### Поиск не работает

```javascript
// Проверьте API ключ и инициализацию
const searchControl = new SearchControl({
  // опции
})

searchControl.on('apierror', (event) => {
  console.error('Ошибка API:', event.error.message)
  // Возможные причины:
  // 1. Неверный API ключ
  // 2. Превышен лимит запросов
  // 3. API недоступен
})

searchControl.on('error', (event) => {
  console.error('Ошибка поиска:', event.error.message)
  // Возможные причины:
  // 1. Нет интернета
  // 2. Сервер недоступен
  // 3. Некорректный запрос
})

// Проверьте что карта создана корректно
if (map && map.container) {
  await searchControl.addToMap(map)
} else {
  console.error('Карта не готова')
}
```

### Автодополнение не появляется

```javascript
// Проверьте настройки
const searchControl = new SearchControl({
  enableAutoComplete: true,    // Включено ли
  searchDelay: 300,           // Достаточная задержка
  maxResults: 10              // Есть ли лимит
})

// Проверьте минимальную длину запроса
searchControl.on('inputchange', (event) => {
  console.log('Длина запроса:', event.value.length)
  // Автодополнение работает от 2 символов
})

// Проверьте доступность API
searchControl.on('apiready', (event) => {
  console.log('Автодополнение доступно:', event.suggest)
  if (!event.suggest) {
    console.warn('API автодополнения недоступен')
  }
})
```

### Vue компонент не реагирует

```vue
<template>
  <!-- Убедитесь что map передается -->
  <SearchControlVue
    :key="mapKey"  // Принудительное обновление
    :map="map"
    v-model:query="query"
  />
</template>

<script setup>
// Пересоздание при изменении ключевых свойств
watch([apiKey, mapType], () => {
  mapKey.value++  // Принудительное пересоздание
})

// Проверка готовности карты
watch(() => props.map, (newMap) => {
  console.log('Карта изменилась:', !!newMap)
}, { immediate: true })
</script>
```

### Медленный поиск

```javascript
// Оптимизация настроек
const searchControl = new SearchControl({
  searchDelay: 500,           // Увеличить задержку
  maxResults: 5,              // Уменьшить количество результатов
  enableAutoComplete: false,   // Отключить автодополнение
  searchOptions: {
    results: 5,
    // Ограничить область поиска
    boundedBy: mapBounds,
    strictBounds: true
  }
})

// Кеширование результатов
const searchCache = new Map()

searchControl.on('searchcomplete', (event) => {
  // Кешируем результаты
  searchCache.set(event.query, event.results)
})
```

## 🔍 Отладка и диагностика

### Включение debug режима

```javascript
// Глобальный debug режим
window.YMAPS_DEBUG = true

const searchControl = new SearchControl({
  // опции
})

// Подробное логирование событий
searchControl.on('*', (event) => {
  console.log(`[SearchControl] ${event.type}:`, event)
})
```

### Vue компонент с отладкой

```vue
<template>
  <SearchControlVue
    :map="map"
    :show-debug-info="true"
    v-model:query="query"
  />
</template>
```

### Проверка состояния

```javascript
// Получение информации о состоянии
console.log('Текущий запрос:', searchControl.getQuery())
console.log('Результаты:', searchControl.getResults())
console.log('В поиске:', searchControl.isSearching())
console.log('Плейсхолдер:', searchControl.getPlaceholder())
console.log('Макс результатов:', searchControl.getMaxResults())
console.log('Автодополнение:', searchControl.isAutoCompleteEnabled())
console.log('Задержка:', searchControl.getSearchDelay())
console.log('Маркер результата:', searchControl.getResultMarker())
console.log('Последние границы:', searchControl.getLastSearchBounds())

// Проверка DOM элементов
const element = searchControl.getElement()
console.log('DOM элемент:', element)
console.log('Видимость:', window.getComputedStyle(element).visibility)
console.log('Позиция:', window.getComputedStyle(element).position)
```

## 📚 См. также

- [ControlBase](../ControlBase.js) - Базовый класс для всех контролов
- [controlHelpers](../../utils/controlHelpers.js) - Утилиты для создания контролов
- [ZoomControl](../ZoomControl/) - Контрол управления масштабом
- [TypeSelector](../TypeSelector/) - Контрол переключения типов карт
- [RouteEditor](../RouteEditor/) - Контрол построения маршрутов

---

<div align="center">
  <strong>Создано с ❤️ для SPA Platform</strong><br>
  <sub>SearchControl v1.0.0 | Production Ready</sub>
</div>