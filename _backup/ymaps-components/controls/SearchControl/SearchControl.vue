<template>
  <div 
    :class="searchControlClasses"
    :style="searchControlStyle"
    role="search"
    aria-label="Поиск на карте"
  >
    <!-- Скелетон загрузки -->
    <div v-if="isLoading && !searchControl" class="search-control-skeleton">
      <div class="skeleton-input"></div>
      <div class="skeleton-button"></div>
    </div>

    <!-- Основной контент контрола -->
    <div v-else-if="searchControl" class="search-control-content">
      <!-- Поле ввода с кнопками встроено в JavaScript класс -->
    </div>

    <!-- Состояние ошибки -->
    <div v-else-if="error" class="search-control-error">
      <div class="error-icon">⚠️</div>
      <div class="error-message">
        <p class="error-title">Ошибка поиска</p>
        <p class="error-description">{{ error.message }}</p>
        <button 
          @click="recreate" 
          class="error-retry-button"
          type="button"
        >
          Попробовать снова
        </button>
      </div>
    </div>

    <!-- Внешний контейнер результатов поиска (опционально) -->
    <div 
      v-if="showExternalResults && searchResults.length > 0"
      class="search-external-results"
      role="listbox"
      :aria-label="`Найдено результатов: ${searchResults.length}`"
    >
      <h3 class="results-title">Результаты поиска</h3>
      <ul class="results-list">
        <li 
          v-for="(result, index) in searchResults"
          :key="result.index"
          :class="[
            'result-item',
            { 'result-item--selected': index === selectedResultIndex }
          ]"
          @click="selectExternalResult(index)"
          @keydown.enter="selectExternalResult(index)"
          @keydown.space="selectExternalResult(index)"
          tabindex="0"
          role="option"
          :aria-selected="index === selectedResultIndex"
        >
          <div class="result-content">
            <span class="result-name">{{ result.displayName }}</span>
            <span v-if="result.description" class="result-description">
              {{ result.description }}
            </span>
            <div v-if="result.coordinates" class="result-coordinates">
              {{ formatCoordinates(result.coordinates) }}
            </div>
          </div>
          <div class="result-type-badge">
            {{ getResultTypeLabel(result.type) }}
          </div>
        </li>
      </ul>
    </div>

    <!-- Статистика поиска (в режиме отладки) -->
    <div v-if="showDebugInfo && debugInfo" class="search-debug-info">
      <details class="debug-panel">
        <summary class="debug-summary">Debug Info</summary>
        <div class="debug-content">
          <div class="debug-section">
            <h4>Поиск</h4>
            <p>Запрос: <code>{{ debugInfo.lastQuery }}</code></p>
            <p>Результатов: {{ debugInfo.resultCount }}</p>
            <p>Время поиска: {{ debugInfo.searchTime }}мс</p>
          </div>
          <div class="debug-section">
            <h4>API</h4>
            <p>Геокодер: {{ debugInfo.hasGeocoder ? '✅' : '❌' }}</p>
            <p>Автодополнение: {{ debugInfo.hasSuggest ? '✅' : '❌' }}</p>
          </div>
          <div class="debug-section">
            <h4>Состояние</h4>
            <p>В поиске: {{ debugInfo.isSearching ? '✅' : '❌' }}</p>
            <p>Фокус: {{ debugInfo.hasFocus ? '✅' : '❌' }}</p>
          </div>
        </div>
      </details>
    </div>
  </div>
</template>

<script setup lang="ts">
import { 
  ref, 
  computed, 
  watch, 
  onMounted, 
  onUnmounted, 
  nextTick,
  defineProps,
  defineEmits,
  defineExpose
} from 'vue'
import SearchControl from './SearchControl.js'
import type { 
  SearchControlOptions,
  SearchResult,
  SearchControlEvents,
  ExtendedSearchControlOptions 
} from './SearchControl.d.ts'

// === ТИПЫ И ИНТЕРФЕЙСЫ ===

interface Props {
  /** Экземпляр карты */
  map?: any
  /** Плейсхолдер поля ввода */
  placeholder?: string
  /** Показать кнопку очистки */
  showClearButton?: boolean
  /** Показать кнопку поиска */
  showSearchButton?: boolean
  /** Включить автодополнение */
  enableAutoComplete?: boolean
  /** Задержка поиска в миллисекундах */
  searchDelay?: number
  /** Максимальное количество результатов */
  maxResults?: number
  /** Типы поиска */
  searchTypes?: string[]
  /** Подстраивать границы карты под результаты */
  fitResultBounds?: boolean
  /** Добавлять маркер результата */
  addResultMarker?: boolean
  /** Позиция контрола на карте */
  position?: string
  /** Z-index контрола */
  zIndex?: number
  /** Отступы контрола */
  margin?: object
  /** Видимость контрола */
  visible?: boolean
  /** Активность контрола */
  enabled?: boolean
  /** Текущий поисковый запрос (v-model) */
  query?: string
  /** Показать внешние результаты */
  showExternalResults?: boolean
  /** Показать отладочную информацию */
  showDebugInfo?: boolean
  /** CSS классы */
  class?: string | string[] | object
  /** Inline стили */
  style?: string | object
  /** Расширенные опции */
  extendedOptions?: ExtendedSearchControlOptions
}

interface Emits {
  /** Обновление поискового запроса (v-model) */
  (event: 'update:query', query: string): void
  /** Изменение текста в поле ввода */
  (event: 'inputchange', value: string): void
  /** Начало поиска */
  (event: 'searchstart'): void
  /** Окончание поиска */
  (event: 'searchend'): void
  /** Завершение поиска с результатами */
  (event: 'searchcomplete', data: { query: string; results: SearchResult[]; total: number }): void
  /** Выбор результата */
  (event: 'resultselect', data: { result: SearchResult; index: number }): void
  /** Обработка результата на карте */
  (event: 'resultprocessed', data: { result: SearchResult; marker: any }): void
  /** Очистка поиска */
  (event: 'clear'): void
  /** Фокус поля поиска */
  (event: 'focus'): void
  /** Потеря фокуса поля поиска */
  (event: 'blur'): void
  /** Готовность контрола */
  (event: 'ready', control: SearchControl): void
  /** Ошибка */
  (event: 'error', error: Error): void
}

interface DebugInfo {
  lastQuery: string
  resultCount: number
  searchTime: number
  hasGeocoder: boolean
  hasSuggest: boolean
  isSearching: boolean
  hasFocus: boolean
}

// === PROPS И EMITS ===

const props = withDefaults(defineProps<Props>(), {
  placeholder: 'Поиск места...',
  showClearButton: true,
  showSearchButton: true,
  enableAutoComplete: true,
  searchDelay: 300,
  maxResults: 10,
  searchTypes: () => ['text', 'geo', 'biz'],
  fitResultBounds: true,
  addResultMarker: true,
  position: 'topLeft',
  zIndex: 1000,
  visible: true,
  enabled: true,
  query: '',
  showExternalResults: false,
  showDebugInfo: false
})

const emit = defineEmits<Emits>()

// === РЕАКТИВНЫЕ СОСТОЯНИЯ ===

const searchControl = ref<SearchControl | null>(null)
const isLoading = ref(false)
const error = ref<Error | null>(null)
const searchResults = ref<SearchResult[]>([])
const selectedResultIndex = ref(-1)
const isSearching = ref(false)
const hasFocus = ref(false)

// Debug информация
const debugInfo = ref<DebugInfo | null>(null)
const searchStartTime = ref(0)

// === ВЫЧИСЛЯЕМЫЕ СВОЙСТВА ===

const searchControlClasses = computed(() => {
  const classes: string[] = ['vue-search-control']
  
  if (isLoading.value) classes.push('search-control--loading')
  if (error.value) classes.push('search-control--error')
  if (!props.enabled) classes.push('search-control--disabled')
  if (!props.visible) classes.push('search-control--hidden')
  if (isSearching.value) classes.push('search-control--searching')
  if (hasFocus.value) classes.push('search-control--focused')
  
  // Пользовательские классы
  if (props.class) {
    if (typeof props.class === 'string') {
      classes.push(props.class)
    } else if (Array.isArray(props.class)) {
      classes.push(...props.class)
    } else {
      Object.entries(props.class).forEach(([className, condition]) => {
        if (condition) classes.push(className)
      })
    }
  }
  
  return classes
})

const searchControlStyle = computed(() => {
  const styles: Record<string, string> = {}
  
  if (props.zIndex !== undefined) {
    styles['z-index'] = String(props.zIndex)
  }
  
  if (props.margin) {
    Object.entries(props.margin).forEach(([key, value]) => {
      if (typeof value === 'number') {
        styles[`margin-${key}`] = `${value}px`
      }
    })
  }
  
  // Пользовательские стили
  if (props.style) {
    if (typeof props.style === 'string') {
      return [styles, props.style].filter(Boolean).join('; ')
    } else {
      Object.assign(styles, props.style)
    }
  }
  
  return styles
})

// === МЕТОДЫ ===

/**
 * Создает экземпляр SearchControl
 */
const createSearchControl = async (): Promise<void> => {
  if (!props.map) return

  try {
    isLoading.value = true
    error.value = null

    // Базовые опции
    const options: SearchControlOptions = {
      placeholder: props.placeholder,
      showClearButton: props.showClearButton,
      showSearchButton: props.showSearchButton,
      enableAutoComplete: props.enableAutoComplete,
      searchDelay: props.searchDelay,
      maxResults: props.maxResults,
      searchTypes: props.searchTypes as any,
      fitResultBounds: props.fitResultBounds,
      addResultMarker: props.addResultMarker,
      position: props.position,
      zIndex: props.zIndex,
      visible: props.visible,
      enabled: props.enabled,
      margin: props.margin as any
    }

    // Расширенные опции
    if (props.extendedOptions) {
      Object.assign(options, props.extendedOptions)
    }

    // Создаем контрол
    const control = new SearchControl(options)
    
    // Подключаем обработчики событий
    setupEventHandlers(control)
    
    // Добавляем на карту
    await control.addToMap(props.map)
    
    searchControl.value = control
    
    // Устанавливаем начальный запрос
    if (props.query) {
      control.setQuery(props.query)
    }

    // Инициализируем debug информацию
    if (props.showDebugInfo) {
      initializeDebugInfo()
    }

    emit('ready', control)

  } catch (err) {
    console.error('[SearchControlVue] Ошибка создания контрола:', err)
    error.value = err instanceof Error ? err : new Error(String(err))
    emit('error', error.value)
  } finally {
    isLoading.value = false
  }
}

/**
 * Настраивает обработчики событий SearchControl
 */
const setupEventHandlers = (control: SearchControl): void => {
  // Изменение запроса
  control.on('inputchange', (event) => {
    emit('update:query', event.value)
    emit('inputchange', event.value)
    updateDebugInfo({ lastQuery: event.value })
  })

  // События поиска
  control.on('searchstart', () => {
    isSearching.value = true
    searchStartTime.value = Date.now()
    updateDebugInfo({ isSearching: true })
    emit('searchstart')
  })

  control.on('searchend', () => {
    isSearching.value = false
    updateDebugInfo({ 
      isSearching: false,
      searchTime: Date.now() - searchStartTime.value
    })
    emit('searchend')
  })

  control.on('searchcomplete', (event) => {
    searchResults.value = event.results
    selectedResultIndex.value = -1
    updateDebugInfo({ resultCount: event.results.length })
    emit('searchcomplete', event)
  })

  // Выбор результата
  control.on('resultselect', (event) => {
    selectedResultIndex.value = event.index
    emit('resultselect', event)
  })

  control.on('resultprocessed', (event) => {
    emit('resultprocessed', event)
  })

  // Фокус
  control.on('focus', () => {
    hasFocus.value = true
    updateDebugInfo({ hasFocus: true })
    emit('focus')
  })

  control.on('blur', () => {
    hasFocus.value = false
    updateDebugInfo({ hasFocus: false })
    emit('blur')
  })

  // Очистка
  control.on('clear', () => {
    searchResults.value = []
    selectedResultIndex.value = -1
    emit('clear')
  })

  // Готовность API
  control.on('apiready', (event) => {
    updateDebugInfo({
      hasGeocoder: event.geocoder,
      hasSuggest: event.suggest
    })
  })

  // Ошибки
  control.on('error', (event) => {
    console.error('[SearchControlVue] Ошибка контрола:', event.error)
    error.value = event.error
    emit('error', event.error)
  })
}

/**
 * Инициализирует debug информацию
 */
const initializeDebugInfo = (): void => {
  debugInfo.value = {
    lastQuery: props.query || '',
    resultCount: 0,
    searchTime: 0,
    hasGeocoder: false,
    hasSuggest: false,
    isSearching: false,
    hasFocus: false
  }
}

/**
 * Обновляет debug информацию
 */
const updateDebugInfo = (updates: Partial<DebugInfo>): void => {
  if (!debugInfo.value) return
  Object.assign(debugInfo.value, updates)
}

/**
 * Выбирает результат из внешнего списка
 */
const selectExternalResult = async (index: number): Promise<void> => {
  if (!searchControl.value || index < 0 || index >= searchResults.value.length) return

  try {
    const result = searchResults.value[index]
    selectedResultIndex.value = index

    // Обновляем поисковый запрос
    const query = result.displayName || result.value || ''
    searchControl.value.setQuery(query)
    emit('update:query', query)

    // Обрабатываем результат
    emit('resultselect', { result, index })

  } catch (err) {
    console.error('[SearchControlVue] Ошибка выбора результата:', err)
    emit('error', err instanceof Error ? err : new Error(String(err)))
  }
}

/**
 * Форматирует координаты для отображения
 */
const formatCoordinates = (coordinates: [number, number]): string => {
  const [lat, lng] = coordinates
  return `${lat.toFixed(6)}, ${lng.toFixed(6)}`
}

/**
 * Получает локализованную метку типа результата
 */
const getResultTypeLabel = (type: string): string => {
  const labels: Record<string, string> = {
    suggestion: 'Предложение',
    geocode: 'Адрес',
    unknown: 'Неизвестно'
  }
  return labels[type] || type
}

/**
 * Пересоздает контрол после ошибки
 */
const recreate = async (): Promise<void> => {
  if (searchControl.value) {
    searchControl.value.destroy()
    searchControl.value = null
  }
  
  error.value = null
  searchResults.value = []
  selectedResultIndex.value = -1
  
  await nextTick()
  await createSearchControl()
}

// === WATCHERS ===

// Реагируем на изменение карты
watch(() => props.map, async (newMap, oldMap) => {
  if (newMap !== oldMap) {
    if (searchControl.value) {
      searchControl.value.destroy()
      searchControl.value = null
    }
    
    if (newMap) {
      await createSearchControl()
    }
  }
}, { immediate: false })

// Реагируем на изменение запроса извне
watch(() => props.query, (newQuery) => {
  if (searchControl.value && searchControl.value.getQuery() !== newQuery) {
    searchControl.value.setQuery(newQuery || '')
  }
})

// Реагируем на изменение видимости
watch(() => props.visible, (visible) => {
  if (searchControl.value) {
    if (visible) {
      searchControl.value.show()
    } else {
      searchControl.value.hide()
    }
  }
})

// Реагируем на изменение активности
watch(() => props.enabled, (enabled) => {
  if (searchControl.value) {
    if (enabled) {
      searchControl.value.enable()
    } else {
      searchControl.value.disable()
    }
  }
})

// Реагируем на изменение плейсхолдера
watch(() => props.placeholder, (placeholder) => {
  if (searchControl.value && placeholder) {
    searchControl.value.setPlaceholder(placeholder)
  }
})

// Реагируем на изменение максимального количества результатов
watch(() => props.maxResults, (maxResults) => {
  if (searchControl.value && maxResults) {
    searchControl.value.setMaxResults(maxResults)
  }
})

// Реагируем на изменение настроек автодополнения
watch(() => props.enableAutoComplete, (enabled) => {
  if (searchControl.value) {
    searchControl.value.setAutoComplete(enabled)
  }
})

// Реагируем на изменение задержки поиска
watch(() => props.searchDelay, (delay) => {
  if (searchControl.value && delay) {
    searchControl.value.setSearchDelay(delay)
  }
})

// === LIFECYCLE HOOKS ===

onMounted(async () => {
  if (props.map) {
    await createSearchControl()
  }
})

onUnmounted(() => {
  if (searchControl.value) {
    searchControl.value.destroy()
    searchControl.value = null
  }
})

// === EXPOSE METHODS ===

defineExpose({
  /**
   * Получить экземпляр SearchControl
   */
  getControl(): SearchControl | null {
    return searchControl.value
  },

  /**
   * Получить текущий поисковый запрос
   */
  getQuery(): string {
    return searchControl.value?.getQuery() || ''
  },

  /**
   * Установить поисковый запрос
   */
  async setQuery(query: string, triggerSearch = false): Promise<void> {
    if (searchControl.value) {
      searchControl.value.setQuery(query, triggerSearch)
      emit('update:query', query)
    }
  },

  /**
   * Запустить поиск
   */
  async search(): Promise<SearchResult[]> {
    if (searchControl.value) {
      return await searchControl.value.search()
    }
    return []
  },

  /**
   * Очистить поиск
   */
  clear(): void {
    if (searchControl.value) {
      searchControl.value.clear()
      emit('update:query', '')
    }
  },

  /**
   * Фокус на поле поиска
   */
  focus(): void {
    searchControl.value?.focus()
  },

  /**
   * Снять фокус
   */
  blur(): void {
    searchControl.value?.blur()
  },

  /**
   * Получить результаты поиска
   */
  getResults(): SearchResult[] {
    return searchResults.value
  },

  /**
   * Получить выбранный результат
   */
  getSelectedResult(): SearchResult | null {
    return searchControl.value?.getSelectedResult() || null
  },

  /**
   * Пересоздать контрол
   */
  async recreate(): Promise<void> {
    await recreate()
  }
})
</script>

<style scoped>
/* === ОСНОВНЫЕ СТИЛИ === */
.vue-search-control {
  position: relative;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* === СКЕЛЕТОН ЗАГРУЗКИ === */
.search-control-skeleton {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  animation: pulse 1.5s ease-in-out infinite alternate;
}

.skeleton-input {
  flex: 1;
  height: 36px;
  background: #e2e8f0;
  border-radius: 4px;
}

.skeleton-button {
  width: 36px;
  height: 36px;
  background: #e2e8f0;
  border-radius: 4px;
}

@keyframes pulse {
  from { opacity: 0.6; }
  to { opacity: 1; }
}

/* === СОСТОЯНИЕ ОШИБКИ === */
.search-control-error {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 16px;
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 8px;
  color: #991b1b;
}

.error-icon {
  font-size: 20px;
  flex-shrink: 0;
  margin-top: 2px;
}

.error-message {
  flex: 1;
  min-width: 0;
}

.error-title {
  margin: 0 0 4px 0;
  font-weight: 600;
  font-size: 14px;
}

.error-description {
  margin: 0 0 12px 0;
  font-size: 13px;
  color: #7f1d1d;
  word-break: break-word;
}

.error-retry-button {
  padding: 6px 12px;
  background: #dc2626;
  color: white;
  border: none;
  border-radius: 4px;
  font-size: 12px;
  cursor: pointer;
  transition: background-color 0.2s;
}

.error-retry-button:hover {
  background: #b91c1c;
}

.error-retry-button:active {
  background: #991b1b;
}

/* === ВНЕШНИЕ РЕЗУЛЬТАТЫ === */
.search-external-results {
  margin-top: 8px;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.results-title {
  margin: 0;
  padding: 12px 16px;
  font-size: 14px;
  font-weight: 600;
  color: #374151;
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
}

.results-list {
  margin: 0;
  padding: 0;
  list-style: none;
  max-height: 300px;
  overflow-y: auto;
}

.result-item {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  padding: 12px 16px;
  cursor: pointer;
  border-bottom: 1px solid #f3f4f6;
  transition: background-color 0.15s;
}

.result-item:last-child {
  border-bottom: none;
}

.result-item:hover,
.result-item:focus {
  background-color: #f9fafb;
  outline: none;
}

.result-item--selected {
  background-color: #eff6ff;
  border-left: 3px solid #3b82f6;
}

.result-content {
  flex: 1;
  min-width: 0;
  margin-right: 12px;
}

.result-name {
  display: block;
  font-size: 14px;
  font-weight: 500;
  color: #111827;
  margin-bottom: 2px;
  word-break: break-word;
}

.result-description {
  display: block;
  font-size: 13px;
  color: #6b7280;
  margin-bottom: 4px;
  word-break: break-word;
}

.result-coordinates {
  font-size: 12px;
  color: #9ca3af;
  font-family: 'SF Mono', Monaco, 'Cascadia Code', monospace;
}

.result-type-badge {
  flex-shrink: 0;
  padding: 2px 8px;
  background: #f3f4f6;
  color: #6b7280;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 500;
  text-transform: uppercase;
}

/* === DEBUG ПАНЕЛЬ === */
.search-debug-info {
  margin-top: 16px;
  font-size: 12px;
}

.debug-panel {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  overflow: hidden;
}

.debug-summary {
  padding: 8px 12px;
  background: #e2e8f0;
  cursor: pointer;
  font-weight: 600;
  color: #374151;
  user-select: none;
}

.debug-summary:hover {
  background: #d1d5db;
}

.debug-content {
  padding: 12px;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 16px;
}

.debug-section h4 {
  margin: 0 0 6px 0;
  font-size: 11px;
  font-weight: 700;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.debug-section p {
  margin: 0 0 4px 0;
  color: #374151;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.debug-section code {
  background: #e5e7eb;
  padding: 2px 4px;
  border-radius: 2px;
  font-family: 'SF Mono', Monaco, 'Cascadia Code', monospace;
  font-size: 11px;
  max-width: 100px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* === СОСТОЯНИЯ КОМПОНЕНТА === */
.search-control--loading {
  pointer-events: none;
  opacity: 0.8;
}

.search-control--disabled {
  pointer-events: none;
  opacity: 0.5;
}

.search-control--hidden {
  display: none;
}

.search-control--searching .search-external-results {
  opacity: 0.6;
}

.search-control--focused {
  /* Дополнительные стили при фокусе */
}

/* === АДАПТИВНОСТЬ === */
@media (max-width: 768px) {
  .search-external-results {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 90vw;
    max-width: 400px;
    max-height: 60vh;
    z-index: 10000;
  }
  
  .results-list {
    max-height: calc(60vh - 60px);
  }
  
  .result-item {
    padding: 16px;
  }
  
  .result-name {
    font-size: 15px;
  }
  
  .debug-content {
    grid-template-columns: 1fr;
    gap: 12px;
  }
}

/* === ТЕМНАЯ ТЕМА === */
@media (prefers-color-scheme: dark) {
  .search-control-skeleton {
    background: #1f2937;
    border-color: #374151;
  }
  
  .skeleton-input,
  .skeleton-button {
    background: #374151;
  }
  
  .search-control-error {
    background: #431a1a;
    border-color: #7f1d1d;
    color: #fca5a5;
  }
  
  .error-description {
    color: #f87171;
  }
  
  .search-external-results {
    background: #1f2937;
    border-color: #374151;
  }
  
  .results-title {
    background: #111827;
    color: #e5e7eb;
    border-color: #374151;
  }
  
  .result-item {
    border-color: #374151;
  }
  
  .result-item:hover,
  .result-item:focus {
    background: #111827;
  }
  
  .result-item--selected {
    background: #1e3a8a;
  }
  
  .result-name {
    color: #f9fafb;
  }
  
  .result-description {
    color: #9ca3af;
  }
  
  .result-coordinates {
    color: #6b7280;
  }
  
  .result-type-badge {
    background: #374151;
    color: #9ca3af;
  }
  
  .debug-panel {
    background: #1f2937;
    border-color: #374151;
  }
  
  .debug-summary {
    background: #374151;
    color: #e5e7eb;
  }
  
  .debug-summary:hover {
    background: #4b5563;
  }
  
  .debug-section h4 {
    color: #9ca3af;
  }
  
  .debug-section p {
    color: #e5e7eb;
  }
  
  .debug-section code {
    background: #374151;
    color: #e5e7eb;
  }
}

/* === ВЫСОКИЙ КОНТРАСТ === */
@media (prefers-contrast: high) {
  .search-control-error {
    border-width: 2px;
  }
  
  .result-item--selected {
    border-left-width: 4px;
  }
  
  .result-item:focus {
    outline: 2px solid #3b82f6;
    outline-offset: -2px;
  }
}

/* === СНИЖЕННАЯ АНИМАЦИЯ === */
@media (prefers-reduced-motion: reduce) {
  .search-control-skeleton {
    animation: none;
  }
  
  .result-item,
  .error-retry-button {
    transition: none;
  }
}
</style>