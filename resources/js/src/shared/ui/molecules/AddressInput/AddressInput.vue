<template>
  <div class="address-input">
    <!-- Скрытый label для доступности -->
    <label v-if="label" :for="inputId" class="sr-only">{{ label }}</label>
    
    <div class="address-input__container">
      <div class="address-input__field-wrapper">
        <!-- Иконка дома с tooltip -->
        <div 
          v-if="localValue && showAddressIcon"
          class="address-input__home-icon"
          :title="fullAddressTooltip"
          @mouseenter="showTooltip = true"
          @mouseleave="showTooltip = false"
        >
          <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/>
          </svg>
          
          <!-- Tooltip с полным адресом -->
          <div
            v-if="showTooltip && fullAddressTooltip"
            class="address-input__tooltip"
          >
            {{ fullAddressTooltip }}
          </div>
        </div>

        <input
          :id="inputId"
          ref="inputRef"
          v-model="localValue"
          type="text"
          :name="name"
          :placeholder="placeholder"
          :disabled="disabled || loading"
          :aria-label="label || placeholder"
          :aria-invalid="!!error"
          :aria-describedby="error ? `${inputId}-error` : hint ? `${inputId}-hint` : undefined"
          class="address-input__field"
          :class="{ 
            'pr-10': localValue && showClearButton,
            'pl-12': localValue && showAddressIcon
          }"
          @input="handleInput"
          @focus="handleFocus"
          @blur="handleBlur"
          @keydown="handleKeydown"
          @keyup.enter="handleSearch"
          autocomplete="off"
        >
        
        <!-- Кнопка очистки -->
        <button
          v-if="localValue && showClearButton"
          type="button"
          class="address-input__clear"
          :class="{ 'address-input__clear--with-search': showSearchButton }"
          @click="clearInput"
          :disabled="disabled"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>

        <!-- Выпадающий список подсказок -->
        <div
          v-if="showSuggestions && suggestions.length > 0"
          class="address-input__suggestions"
          :class="{ 'address-input__suggestions--with-search': showSearchButton }"
        >
          <div
            v-for="(suggestion, index) in suggestions"
            :key="index"
            class="address-input__suggestion"
            :class="{ 'address-input__suggestion--active': index === selectedIndex }"
            @click="selectSuggestion(suggestion)"
            @mouseenter="selectedIndex = index"
          >
            <div class="address-input__suggestion-text">
              {{ suggestion.displayName }}
            </div>
            <div v-if="suggestion.description" class="address-input__suggestion-description">
              {{ suggestion.description }}
            </div>
          </div>
        </div>
      </div>
      
      <!-- Кнопка поиска -->
      <button
        v-if="showSearchButton"
        type="button"
        class="address-input__search"
        :class="{ 'address-input__search--loading': loading }"
        @click="handleSearch"
        :disabled="disabled || loading || !localValue.trim()"
      >
        <div v-if="loading" class="w-4 h-4 animate-spin rounded-full border-b-2 border-white"></div>
        <span v-else>Найти</span>
      </button>
    </div>
    
    <!-- Подсказка под полем -->
    <p v-if="hint" :id="`${inputId}-hint`" class="address-input__hint">
      {{ hint }}
    </p>
    
    <!-- Ошибка -->
    <p v-if="error" :id="`${inputId}-error`" class="address-input__error" role="alert">
      {{ error }}
    </p>
    
    <!-- Статус поиска -->
    <p v-if="searchStatus" class="address-input__status" :class="searchStatusClass">
      {{ searchStatus }}
    </p>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, computed, nextTick, onMounted } from 'vue'
import { useId } from '@/src/shared/composables/useId'

interface Suggestion {
  displayName: string
  description?: string
  value: string
  country?: string
  priority?: number
}

interface Props {
  modelValue?: string
  placeholder?: string
  hint?: string
  error?: string
  disabled?: boolean
  showClearButton?: boolean
  showSearchButton?: boolean
  loading?: boolean
  name?: string
  label?: string
  showAutocomplete?: boolean
  prioritizeCountry?: string // Приоритетная страна (по умолчанию 'Russia')
  showAddressIcon?: boolean // Показывать ли иконку дома
  fullAddress?: string // Полный адрес для tooltip
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: '',
  placeholder: 'Начните вводить адрес',
  hint: '',
  error: '',
  disabled: false,
  showClearButton: true,
  showSearchButton: false,
  loading: false,
  name: 'address',
  label: '',
  showAutocomplete: true,
  prioritizeCountry: 'Russia',
  showAddressIcon: true,
  fullAddress: ''
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
  'focus': []
  'blur': []
  'clear': []
  'search': [value: string]
  'suggestion-selected': [suggestion: Suggestion]
}>()

// Локальные данные
const localValue = ref(props.modelValue)
const searchStatus = ref('')
const inputId = useId('address-input')
const inputRef = ref<HTMLInputElement>()
const showTooltip = ref(false)

// Автокомплит
const suggestions = ref<Suggestion[]>([])
const showSuggestions = ref(false)
const selectedIndex = ref(-1)
const suggestionTimeout = ref<NodeJS.Timeout>()

// Страна пользователя (определяется по IP)
const userCountry = ref<string>('Russia')

// Вычисляемые свойства
const searchStatusClass = computed(() => {
  if (searchStatus.value.includes('найден')) {
    return 'address-input__status--success'
  }
  if (searchStatus.value.includes('не найден') || searchStatus.value.includes('ошибка')) {
    return 'address-input__status--error'
  }
  return 'address-input__status--info'
})

const fullAddressTooltip = computed(() => {
  if (props.fullAddress) {
    return props.fullAddress
  }
  return localValue.value || ''
})

// Определение страны пользователя по IP
const detectUserCountry = async (): Promise<string> => {
  try {
    // Сначала пробуем через Яндекс.Карты (если доступны)
    if (window.ymaps) {
      try {
        const result = await window.ymaps.geolocation.get({
          provider: 'yandex',
          mapStateAutoApply: false
        })
        
        const geoObjects = result.geoObjects
        if (geoObjects.getLength() > 0) {
          const geoObject = geoObjects.get(0)
          const country = geoObject.getCountry()
          if (country) {
            return country
          }
        }
      } catch (error) {
        // Яндекс.Карты недоступны, используем дефолт
      }
    }
  } catch (error) {
    // Тихо обрабатываем ошибку без вывода в консоль
  }
  
  // Всегда возвращаем Россию по умолчанию
  // Убираем внешние API чтобы избежать CORS и лимитов
  return 'Россия'
}

// Получение подсказок с приоритетом по стране
const getSuggestions = async (query: string): Promise<Suggestion[]> => {
  if (!window.ymaps || !query || query.length < 2) {
    return []
  }

  try {
    // Проверяем, что ymaps.geocode существует
    if (!window.ymaps.geocode) {
      return []
    }
    
    // Определяем тип поиска: если есть запятая и цифры, то ищем дома
    const hasCommaAndNumbers = query.includes(',') && /\d/.test(query)
    const searchKind = hasCommaAndNumbers ? 'house' : 'locality'
    
    // Добавляем префикс для поиска в России (только для городов, не для адресов)
    const isSearchingInRussia = props.prioritizeCountry === 'Russia' || userCountry.value === 'Россия'
    const searchQuery = isSearchingInRussia && !query.includes(',') && !hasCommaAndNumbers
      ? `Россия, ${query}` 
      : query
    
    // Получаем результаты для приоритетной страны и глобальные
    const [priorityResults, globalResults] = await Promise.all([
      // Поиск в приоритетной стране с явным указанием страны
      window.ymaps.geocode(searchQuery, { 
        results: hasCommaAndNumbers ? 5 : 3,
        kind: searchKind,
        strictBounds: false,
        boundedBy: props.prioritizeCountry === 'Russia' || userCountry.value === 'Россия' 
          ? [[36.0, 19.0], [82.0, 180.0]] // Исправленные границы России
          : undefined
      }),
      // Глобальный поиск без префикса
      window.ymaps.geocode(query, { 
        results: hasCommaAndNumbers ? 7 : 5,
        kind: searchKind
      })
    ])
    
    // Проверяем результаты
    
    const suggestions: Suggestion[] = []
    const addedAddresses = new Set<string>()
    
    // Обрабатываем результаты приоритетного поиска
    const priorityGeoObjects = priorityResults.geoObjects
    
    // ВРЕМЕННАЯ ОТЛАДКА
    if (query === 'пенз' || query === 'Пенз') {
      console.warn('[DEBUG] Ищем Пензу. Количество результатов:', priorityGeoObjects.getLength())
      console.warn('[DEBUG] searchQuery был:', searchQuery)
      for (let j = 0; j < priorityGeoObjects.getLength(); j++) {
        const obj = priorityGeoObjects.get(j)
        const address = obj.getAddressLine()
        const country = obj.getCountry()
        const localities = obj.getLocalities()
        console.warn(`[DEBUG] Результат ${j}:`)
        console.warn('  - Адрес:', address)
        console.warn('  - Страна:', country)
        console.warn('  - Населенные пункты:', localities)
        console.warn('  - Тип:', obj.getKind ? obj.getKind() : 'unknown')
      }
    }
    
    for (let i = 0; i < priorityGeoObjects.getLength(); i++) {
      const geoObject = priorityGeoObjects.get(i)
      
      
      const name = geoObject.getAddressLine()
      const country = geoObject.getCountry()
      const administrativeArea = geoObject.getAdministrativeAreas()
      
      // ОТЛАДКА: проверяем что приходит в name
      if ((query === 'пенз' || query === 'Пенз') && i === 0) {
        console.warn('[DEBUG] Первый name из API:', name)
        console.warn('[DEBUG] Тип name:', typeof name)
      }
      
      // Проверяем, что адрес не содержит странные пути к файлам
      if (!addedAddresses.has(name) && !name.includes('\\') && !name.includes('C:')) {
        let description = ''
        if (hasCommaAndNumbers) {
          // Для домов показываем город в описании
          description = geoObject.getLocalities().join(', ') || (administrativeArea && administrativeArea[0]) || country || ''
        } else {
          // Для городов показываем область/страну
          if (administrativeArea && administrativeArea.length > 0) {
            description = administrativeArea[0]
          } else if (country) {
            description = country
          }
        }
        
        // Приоритет для российских результатов
        const isRussian = country === 'Россия' || country === 'Russia' || 
                         name.includes('Россия') || description.includes('Россия')
        
        suggestions.push({
          displayName: name,
          description: description,
          value: name,
          country: country,
          priority: isRussian ? 1 : 3
        })
        
        addedAddresses.add(name)
      }
    }
    
    // Обрабатываем глобальные результаты
    const globalGeoObjects = globalResults.geoObjects
    const maxSuggestions = hasCommaAndNumbers ? 8 : 5
    for (let i = 0; i < globalGeoObjects.getLength() && suggestions.length < maxSuggestions; i++) {
      const geoObject = globalGeoObjects.get(i)
      const name = geoObject.getAddressLine()
      const country = geoObject.getCountry()
      const administrativeArea = geoObject.getAdministrativeAreas()
      
      // Проверяем, что адрес не содержит странные пути к файлам
      if (!addedAddresses.has(name) && !name.includes('\\') && !name.includes('C:')) {
        let description = ''
        if (hasCommaAndNumbers) {
          // Для домов показываем город в описании
          description = geoObject.getLocalities().join(', ') || (administrativeArea && administrativeArea[0]) || country || ''
        } else {
          // Для городов показываем область/страну
          if (administrativeArea && administrativeArea.length > 0) {
            description = administrativeArea[0]
          } else if (country) {
            description = country
          }
        }
        
        // Приоритет для российских результатов
        const isRussian = country === 'Россия' || country === 'Russia' || 
                         name.includes('Россия') || description.includes('Россия')
        
        suggestions.push({
          displayName: name,
          description: description,
          value: name,
          country: country,
          priority: isRussian ? 1 : 3
        })
        
        addedAddresses.add(name)
      }
    }
    
    // Сортируем по приоритету (сначала приоритетная страна)
    const sorted = suggestions.sort((a, b) => (a.priority || 2) - (b.priority || 2))
    
    // ВРЕМЕННАЯ ОТЛАДКА финального результата
    if (query === 'пенз' || query === 'Пенз') {
      console.warn('[DEBUG] Финальные suggestions:', sorted.map(s => ({
        displayName: s.displayName,
        value: s.value,
        description: s.description,
        country: s.country,
        priority: s.priority
      })))
    }
    
    return sorted
    
  } catch (error) {
    console.error('Ошибка получения подсказок:', error)
    return []
  }
}

// Обновление подсказок с задержкой
const updateSuggestions = async (query: string) => {
  if (!props.showAutocomplete) return

  // Очищаем предыдущий таймаут
  if (suggestionTimeout.value) {
    clearTimeout(suggestionTimeout.value)
  }

  // Скрываем подсказки если запрос слишком короткий или поле не пустое
  if (!query || query.length < 2) {
    showSuggestions.value = false
    suggestions.value = []
    return
  }

  // Показываем подсказки для городов и для домов
  // Не показываем только если это уже полный точный адрес (содержит много запятых)
  const commaCount = (query.match(/,/g) || []).length
  if (commaCount > 1 && query.length > 20) {
    showSuggestions.value = false
    suggestions.value = []
    return
  }

  // Задержка 300ms для уменьшения количества запросов к API
  suggestionTimeout.value = setTimeout(async () => {
    const newSuggestions = await getSuggestions(query)
    
    // ВРЕМЕННАЯ ОТЛАДКА
    if (query === 'пенз' || query === 'Пенз') {
      console.warn('[DEBUG] newSuggestions от getSuggestions:', newSuggestions)
      console.warn('[DEBUG] Устанавливаем suggestions.value')
    }
    
    suggestions.value = newSuggestions
    showSuggestions.value = newSuggestions.length > 0
    selectedIndex.value = -1
  }, 300)
}

// Выбор подсказки
const selectSuggestion = (suggestion: Suggestion) => {
  // ВРЕМЕННАЯ ОТЛАДКА
  console.warn('[DEBUG] Выбрана подсказка:', {
    displayName: suggestion.displayName,
    value: suggestion.value,
    description: suggestion.description,
    country: suggestion.country
  })
  
  localValue.value = suggestion.value
  
  // Немедленно скрываем подсказки
  showSuggestions.value = false
  selectedIndex.value = -1
  suggestions.value = []
  
  // Очищаем таймаут если он активен
  if (suggestionTimeout.value) {
    clearTimeout(suggestionTimeout.value)
    suggestionTimeout.value = undefined
  }
  
  emit('update:modelValue', suggestion.value)
  emit('suggestion-selected', suggestion)
  
  // Возвращаем фокус на поле
  nextTick(() => {
    inputRef.value?.focus()
  })
}

// Навигация по подсказкам стрелками
const handleKeydown = (event: KeyboardEvent) => {
  if (!showSuggestions.value || suggestions.value.length === 0) return

  switch (event.key) {
    case 'ArrowDown':
      event.preventDefault()
      selectedIndex.value = Math.min(selectedIndex.value + 1, suggestions.value.length - 1)
      break
    
    case 'ArrowUp':
      event.preventDefault()
      selectedIndex.value = Math.max(selectedIndex.value - 1, -1)
      break
    
    case 'Enter':
      if (selectedIndex.value >= 0) {
        event.preventDefault()
        selectSuggestion(suggestions.value[selectedIndex.value])
      }
      break
    
    case 'Escape':
      showSuggestions.value = false
      selectedIndex.value = -1
      break
  }
}

// Следим за изменениями props
watch(() => props.modelValue, (newValue) => {
  // ВРЕМЕННАЯ ОТЛАДКА
  if (newValue && newValue.includes('\\')) {
    console.error('[DEBUG] ВНИМАНИЕ! modelValue содержит путь:', newValue)
    console.trace('[DEBUG] Стек вызовов modelValue:')
  }
  
  localValue.value = newValue
})

// Следим за ошибками
watch(() => props.error, (newError) => {
  if (newError) {
    searchStatus.value = ''
  }
})

// Обработчики
const handleInput = () => {
  // ВРЕМЕННАЯ ОТЛАДКА
  if (localValue.value && localValue.value.includes('\\')) {
    console.error('[DEBUG] ВНИМАНИЕ! В поле попал путь к файлу:', localValue.value)
    console.trace('[DEBUG] Стек вызовов:')
  }
  
  searchStatus.value = ''
  emit('update:modelValue', localValue.value)
  
  // Обновляем подсказки
  updateSuggestions(localValue.value)
}

const handleFocus = () => {
  emit('focus')
  
  // Показываем подсказки если поле содержит ввод (города или начало адреса дома)
  if (localValue.value && localValue.value.length >= 2) {
    const commaCount = (localValue.value.match(/,/g) || []).length
    // Показываем подсказки для городов и для начала ввода домов, но не для полных адресов
    if (commaCount <= 1 || localValue.value.length <= 20) {
      updateSuggestions(localValue.value)
    }
  }
}

const handleBlur = (event: FocusEvent) => {
  // Задержка чтобы клик по подсказке успел сработать
  setTimeout(() => {
    showSuggestions.value = false
    selectedIndex.value = -1
    emit('blur')
  }, 150)
}

const clearInput = () => {
  localValue.value = ''
  searchStatus.value = ''
  showSuggestions.value = false
  suggestions.value = []
  selectedIndex.value = -1
  
  emit('update:modelValue', '')
  emit('clear')
}

const handleSearch = () => {
  if (!localValue.value.trim() || props.loading) return
  
  searchStatus.value = ''
  showSuggestions.value = false
  emit('search', localValue.value.trim())
}

// Методы для внешнего управления статусом
const setSearchStatus = (status: string) => {
  searchStatus.value = status
}

const clearStatus = () => {
  searchStatus.value = ''
}

// Инициализация - определяем страну пользователя
onMounted(async () => {
  userCountry.value = await detectUserCountry()
})

// Экспорт методов
defineExpose({
  setSearchStatus,
  clearStatus
})

// Типы для window.ymaps
declare global {
  interface Window {
    ymaps: any
  }
}
</script>

<style scoped>
.address-input {
  width: 100%;
}

.address-input__container {
  display: flex;
  gap: 12px;
}

.address-input__field-wrapper {
  flex: 1;
  position: relative;
}

.address-input__field {
  width: 100%;
  padding: 12px 16px;
  font-size: 16px;
  line-height: 1.5;
  color: #1a1a1a;
  background-color: #fff;
  border: 1px solid #d6d6d6;
  border-radius: 8px;
  transition: all 0.2s ease;
  outline: none;
}

.address-input__field:hover:not(:disabled) {
  border-color: #b3b3b3;
}

.address-input__field:focus:not(:disabled) {
  border-color: #0066ff;
  box-shadow: 0 0 0 3px rgba(0, 102, 255, 0.1);
}

.address-input__field:disabled {
  background-color: #f5f5f5;
  color: #999;
  cursor: not-allowed;
}

/* Иконка дома */
.address-input__home-icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: #666;
  z-index: 10;
  cursor: help;
  transition: color 0.2s ease;
}

.address-input__home-icon:hover {
  color: #0066ff;
}

/* Tooltip */
.address-input__tooltip {
  position: absolute;
  bottom: 100%;
  left: 50%;
  transform: translateX(-50%);
  margin-bottom: 8px;
  padding: 8px 12px;
  background: #1a1a1a;
  color: white;
  font-size: 13px;
  line-height: 1.4;
  border-radius: 6px;
  white-space: nowrap;
  max-width: 300px;
  overflow: hidden;
  text-overflow: ellipsis;
  z-index: 1001;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.address-input__tooltip::after {
  content: '';
  position: absolute;
  top: 100%;
  left: 50%;
  transform: translateX(-50%);
  border: 4px solid transparent;
  border-top-color: #1a1a1a;
}

.address-input__clear {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  padding: 4px;
  color: #999;
  background: transparent;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10;
}

.address-input__clear:hover:not(:disabled) {
  color: #666;
  background-color: #f0f0f0;
}

.address-input__clear:disabled {
  cursor: not-allowed;
  opacity: 0.5;
}

.address-input__clear--with-search {
  right: 12px;
}

.address-input__search {
  padding: 12px 24px;
  font-size: 16px;
  font-weight: 500;
  color: white;
  background-color: #0066ff;
  border: 1px solid #0066ff;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  white-space: nowrap;
  min-width: 80px;
}

.address-input__search:hover:not(:disabled) {
  background-color: #0052cc;
  border-color: #0052cc;
}

.address-input__search:disabled {
  background-color: #cccccc;
  border-color: #cccccc;
  cursor: not-allowed;
}

.address-input__search--loading {
  background-color: #0052cc;
  border-color: #0052cc;
}

/* Выпадающий список подсказок */
.address-input__suggestions {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: white;
  border: 1px solid #d6d6d6;
  border-top: none;
  border-radius: 0 0 8px 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  z-index: 1000;
  max-height: 200px;
  overflow-y: auto;
}

.address-input__suggestions--with-search {
  right: 108px; /* Учитываем ширину кнопки поиска */
}

.address-input__suggestion {
  padding: 12px 16px;
  cursor: pointer;
  border-bottom: 1px solid #f0f0f0;
  transition: background-color 0.2s ease;
}

.address-input__suggestion:last-child {
  border-bottom: none;
}

.address-input__suggestion:hover,
.address-input__suggestion--active {
  background-color: #f8f9fa;
}

.address-input__suggestion-text {
  font-size: 14px;
  color: #1a1a1a;
  font-weight: 500;
}

.address-input__suggestion-description {
  font-size: 12px;
  color: #666;
  margin-top: 2px;
}

.address-input__hint {
  margin-top: 6px;
  font-size: 13px;
  line-height: 1.4;
  color: #666;
}

.address-input__error {
  margin-top: 6px;
  font-size: 13px;
  line-height: 1.4;
  color: #ff3333;
}

.address-input__status {
  margin-top: 6px;
  font-size: 13px;
  line-height: 1.4;
}

.address-input__status--success {
  color: #00b894;
}

.address-input__status--error {
  color: #ff3333;
}

.address-input__status--info {
  color: #666;
}

/* Класс для скрытия label визуально, но оставляя доступным для скринридеров */
.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border-width: 0;
}

/* Адаптивность */
@media (max-width: 768px) {
  .address-input__container {
    flex-direction: column;
    gap: 8px;
  }
  
  .address-input__search {
    width: 100%;
  }
  
  .address-input__suggestions--with-search {
    right: 0;
  }
}
</style>
