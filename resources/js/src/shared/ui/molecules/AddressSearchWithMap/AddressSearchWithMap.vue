<template>
  <div class="address-search-with-map">
    <!-- Поле поиска адреса -->
    <div class="search-section">
      <label class="search-label">
        📍 Адрес
        <span class="text-red-500">*</span>
      </label>
      
      <div class="search-input-group relative">
        <input
          ref="addressInput"
          v-model="searchQuery"
          type="text"
          class="search-input"
          placeholder="Введите адрес или кликните на карту"
          @input="onSearchInput"
          @keydown.enter.prevent="performSearch"
          @keydown.arrow-down.prevent="selectNextSuggestion"
          @keydown.arrow-up.prevent="selectPrevSuggestion" 
          @keydown.escape.prevent="closeSuggestions"
          @focus="onSearchFocus"
          @blur="onSearchBlur"
        />
        
        <!-- Автоподсказки -->
        <div 
          v-if="showSuggestions && suggestions.length > 0" 
          class="suggestions-dropdown"
        >
          <div
            v-for="(suggestion, index) in suggestions"
            :key="index"
            class="suggestion-item"
            :class="{ 'suggestion-selected': selectedSuggestionIndex === index }"
            @mousedown.prevent="selectSuggestion(suggestion)"
            @mouseenter="selectedSuggestionIndex = index"
          >
            <div class="suggestion-text">{{ suggestion.displayName }}</div>
            <div class="suggestion-subtext">{{ suggestion.description }}</div>
          </div>
        </div>
        
        <button
          type="button"
          class="search-button"
          @click="performSearch"
          :disabled="!searchQuery.trim() || isSearching"
        >
          <svg v-if="!isSearching"
               class="w-5 h-5"
               fill="none"
               stroke="currentColor"
               viewBox="0 0 24 24">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <div v-else class="animate-spin rounded-full h-4 w-4 border-b-2 border-current"></div>
        </button>
        
        <button
          v-if="selectedAddress"
          type="button"
          class="clear-button"
          @click="clearAddress"
          title="Очистить адрес"
        >
          <svg class="w-4 h-4"
               fill="none"
               stroke="currentColor"
               viewBox="0 0 24 24">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
      
      <!-- Статус поиска -->
      <div v-if="searchStatus" class="search-status" :class="searchStatusClass">
        {{ searchStatus }}
      </div>
    </div>
    
    <!-- Карта в iframe -->
    <div class="map-section">
      <div class="map-header">
        <h3 class="map-title">Уточните местоположение на карте</h3>
        <div v-if="selectedAddress" class="selected-address">
          📍 {{ selectedAddress }}
        </div>
      </div>
      
      <div 
        class="map-container"
        :style="{ '--map-height': `${props.height}px` }"
      >
        <iframe
          ref="mapIframe"
          src="/maps/address-picker/index.html"
          class="address-map-iframe custom-height"
          :class="{ 'map-loading': !isMapReady }"
          @load="onMapLoad"
        />
        
        <!-- Лоадер карты -->
        <div v-if="!isMapReady" class="map-loader">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
          <p class="mt-2 text-sm text-gray-600">Загрузка карты...</p>
        </div>
      </div>
    </div>
    
    <!-- Скрытые поля для формы -->
    <input type="hidden" :name="`${fieldName}_address`" :value="selectedAddress || ''" />
    <input type="hidden" :name="`${fieldName}_lat`" :value="coordinates.lat || ''" />
    <input type="hidden" :name="`${fieldName}_lng`" :value="coordinates.lng || ''" />
    <input type="hidden" :name="`${fieldName}_precision`" :value="addressPrecision || ''" />
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, watch, nextTick, onMounted, onUnmounted, computed } from 'vue'

// Props
interface Props {
  modelValue?: {
    address?: string
    lat?: number
    lng?: number
  }
  fieldName?: string
  height?: number
  required?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  fieldName: 'location',
  height: 400,
  required: true
})

// Emits
const emit = defineEmits<{
  'update:modelValue': [value: { address: string, lat: number, lng: number }]
  'address-selected': [data: { address: string, lat: number, lng: number, precision: string }]
  'address-cleared': []
}>()

// Reactive state
const searchQuery = ref('')
const selectedAddress = ref('')
const coordinates = reactive({ lat: null as number | null, lng: null as number | null })
const addressPrecision = ref('')

const isMapReady = ref(false)
const isSearching = ref(false)
const isSearchFocused = ref(false)
const searchStatus = ref('')

// Состояние для автоподсказок
const suggestions = ref<Array<{
  displayName: string
  description: string
  coordinates: [number, number]
  precision: string
}>>([])
const showSuggestions = ref(false)
const selectedSuggestionIndex = ref(-1)
const searchTimeout = ref<ReturnType<typeof setTimeout> | null>(null)

// Refs
const addressInput = ref<HTMLInputElement>()
const mapIframe = ref<HTMLIFrameElement>()

// Computed
const searchStatusClass = computed(() => {
  if (searchStatus.value.includes('❌') || searchStatus.value.includes('Ошибка')) {
    return 'text-red-600'
  }
  if (searchStatus.value.includes('✅') || searchStatus.value.includes('найден')) {
    return 'text-green-600'
  }
  if (searchStatus.value.includes('🔄') || searchStatus.value.includes('Поиск')) {
    return 'text-blue-600'
  }
  return 'text-gray-600'
})

// Methods
const performSearch = async () => {
  // Если есть выбранная автоподсказка, используем её
  if (selectedSuggestionIndex.value >= 0 && suggestions.value[selectedSuggestionIndex.value]) {
    selectSuggestion(suggestions.value[selectedSuggestionIndex.value])
    return
  }
  
  const query = searchQuery.value.trim()
  if (!query || isSearching.value) return
  
  console.log('🔍 [AddressSearchWithMap] Поиск адреса:', query)
  
  isSearching.value = true
  searchStatus.value = '🔄 Поиск адреса...'
  showSuggestions.value = false // Скрываем автоподсказки при поиске
  
  try {
    // Отправляем команду поиска в iframe
    if (mapIframe.value?.contentWindow) {
      mapIframe.value.contentWindow.postMessage({
        type: 'searchAddress',
        data: { query }
      }, window.location.origin)
    }
  } catch (error) {
    console.error('❌ [AddressSearchWithMap] Ошибка поиска:', error)
    searchStatus.value = '❌ Ошибка при поиске адреса'
    isSearching.value = false
  }
}

const onSearchInput = () => {
  // Очищаем статус при вводе нового текста
  if (searchStatus.value && !isSearching.value) {
    searchStatus.value = ''
  }
  
  // Запускаем поиск автоподсказок с задержкой
  if (searchTimeout.value) {
    clearTimeout(searchTimeout.value)
  }
  
  const query = searchQuery.value.trim()
  if (query.length >= 3) {
    searchTimeout.value = setTimeout(() => {
      searchSuggestions(query)
    }, 300)
  } else {
    suggestions.value = []
    showSuggestions.value = false
  }
}

const searchSuggestions = async (query: string) => {
  if (!query || query.length < 3) return
  
  try {
    console.log('🔍 [AddressSearchWithMap] Поиск автоподсказок:', query)
    
    // Используем встроенный в браузер fetch для обращения к Yandex Geocoder API
    const apiKey = '23ff8acc-835f-4e99-8b19-d33c5d346e18'
    const response = await fetch(
      `https://geocode-maps.yandex.ru/1.x/?apikey=${apiKey}&geocode=${encodeURIComponent(query)}&results=5&format=json`
    )
    
    if (!response.ok) throw new Error('Network response was not ok')
    
    const data = await response.json()
    const geoObjects = data.response?.GeoObjectCollection?.featureMember || []
    
    suggestions.value = geoObjects.map((item: any) => {
      const geoObject = item.GeoObject
      const pos = geoObject.Point.pos.split(' ').map(Number) // [lng, lat] из Yandex
      const coordinates = [pos[1], pos[0]] // Преобразуем в [lat, lng]
      
      console.log('🔍 [AddressSearchWithMap] Обработка геообъекта:', {
        original_pos: pos,
        converted_coordinates: coordinates,
        address: geoObject.metaDataProperty.GeocoderMetaData.text
      })
      
      return {
        displayName: geoObject.metaDataProperty.GeocoderMetaData.text,
        description: geoObject.description || geoObject.name || '',
        coordinates: coordinates as [number, number],
        precision: geoObject.metaDataProperty.GeocoderMetaData.precision || 'unknown'
      }
    })
    
    showSuggestions.value = suggestions.value.length > 0
    selectedSuggestionIndex.value = -1
    
    console.log('✅ [AddressSearchWithMap] Найдено автоподсказок:', suggestions.value.length)
    
  } catch (error) {
    console.error('❌ [AddressSearchWithMap] Ошибка поиска автоподсказок:', error)
    suggestions.value = []
    showSuggestions.value = false
  }
}

const onSearchFocus = () => {
  isSearchFocused.value = true
  // Показываем автоподсказки если есть и есть текст
  if (suggestions.value.length > 0 && searchQuery.value.trim().length >= 3) {
    showSuggestions.value = true
  }
}

const onSearchBlur = () => {
  isSearchFocused.value = false
  // Скрываем автоподсказки с небольшой задержкой
  setTimeout(() => {
    showSuggestions.value = false
    selectedSuggestionIndex.value = -1
  }, 200)
}

const selectNextSuggestion = () => {
  if (!showSuggestions.value || suggestions.value.length === 0) return
  
  selectedSuggestionIndex.value = selectedSuggestionIndex.value < suggestions.value.length - 1 
    ? selectedSuggestionIndex.value + 1 
    : 0
}

const selectPrevSuggestion = () => {
  if (!showSuggestions.value || suggestions.value.length === 0) return
  
  selectedSuggestionIndex.value = selectedSuggestionIndex.value > 0 
    ? selectedSuggestionIndex.value - 1 
    : suggestions.value.length - 1
}

const closeSuggestions = () => {
  showSuggestions.value = false
  selectedSuggestionIndex.value = -1
}

const selectSuggestion = (suggestion: typeof suggestions.value[0]) => {
  console.log('📍 [AddressSearchWithMap] Выбрана автоподсказка:', suggestion)
  
  // Обновляем поле поиска
  searchQuery.value = suggestion.displayName
  selectedAddress.value = suggestion.displayName
  coordinates.lat = suggestion.coordinates[0]
  coordinates.lng = suggestion.coordinates[1]
  addressPrecision.value = suggestion.precision
  
  // Скрываем автоподсказки
  showSuggestions.value = false
  selectedSuggestionIndex.value = -1
  
  // Уведомляем карту о выборе адреса (с проверкой готовности)
  if (mapIframe.value?.contentWindow && isMapReady.value) {
    console.log('📍 [AddressSearchWithMap] Отправляем setMarker в iframe:', {
      coordinates: [suggestion.coordinates[0], suggestion.coordinates[1]],
      address: suggestion.displayName
    })
    
    mapIframe.value.contentWindow.postMessage({
      type: 'setMarker',
      data: {
        coordinates: [suggestion.coordinates[0], suggestion.coordinates[1]],
        address: suggestion.displayName,
        zoom: 16
      }
    }, window.location.origin)
  } else if (!isMapReady.value) {
    console.warn('⚠️ [AddressSearchWithMap] Карта еще не готова, ждем инициализации')
    
    // Пытаемся отправить команду через небольшую задержку
    setTimeout(() => {
      if (mapIframe.value?.contentWindow) {
        console.log('📍 [AddressSearchWithMap] Повторная отправка setMarker после задержки')
        mapIframe.value.contentWindow.postMessage({
          type: 'setMarker',
          data: {
            coordinates: [suggestion.coordinates[0], suggestion.coordinates[1]],
            address: suggestion.displayName,
            zoom: 16
          }
        }, window.location.origin)
      }
    }, 500)
  }
  
  // Уведомляем родительский компонент
  const result = {
    address: suggestion.displayName,
    lat: suggestion.coordinates[0],
    lng: suggestion.coordinates[1]
  }
  
  emit('update:modelValue', result)
  emit('address-selected', {
    address: suggestion.displayName,
    lat: suggestion.coordinates[0],
    lng: suggestion.coordinates[1],
    precision: suggestion.precision
  })
  
  searchStatus.value = `✅ Адрес найден (точность: ${suggestion.precision})`
}

const clearAddress = () => {
  console.log('🧹 [AddressSearchWithMap] Очистка адреса')
  
  searchQuery.value = ''
  selectedAddress.value = ''
  coordinates.lat = null
  coordinates.lng = null
  addressPrecision.value = ''
  searchStatus.value = ''
  
  // Очищаем маркер на карте
  if (mapIframe.value?.contentWindow) {
    mapIframe.value.contentWindow.postMessage({
      type: 'clearMarker'
    }, window.location.origin)
  }
  
  emit('update:modelValue', { address: '', lat: null, lng: null })
  emit('address-cleared')
}

const onMapLoad = () => {
  console.log('📍 [AddressSearchWithMap] iframe карты загружен')
  setupMessageListener()
  
  // Принудительно обновляем размеры iframe
  setTimeout(() => {
    if (mapIframe.value) {
      mapIframe.value.style.height = `${props.height}px`
      console.log('🔧 [AddressSearchWithMap] Размеры iframe обновлены:', props.height)
    }
  }, 100)
}

const setupMessageListener = () => {
  window.addEventListener('message', handleMapMessage)
}

const handleMapMessage = (event: MessageEvent) => {
  // Проверяем источник
  if (event.origin !== window.location.origin) return
  
  const { type, data, source } = event.data || {}
  
  // Проверяем что сообщение от нашего iframe карты
  if (source !== 'address-picker') return
  
  console.log('📥 [AddressSearchWithMap] Получено сообщение от карты:', type, data)
  
  switch (type) {
    case 'mapReady':
      isMapReady.value = true
      console.log('✅ [AddressSearchWithMap] Карта готова к работе')
      
      // Если есть начальные данные, устанавливаем маркер
      if (props.modelValue?.lat && props.modelValue?.lng) {
        setInitialMarker()
      }
      break
      
    case 'addressSelected':
      handleAddressSelected(data)
      break
      
    case 'searchError':
      handleSearchError(data)
      break
      
    case 'addressError':
      console.error('❌ [AddressSearchWithMap] Ошибка геокодирования:', data)
      searchStatus.value = '❌ Не удалось определить адрес'
      isSearching.value = false
      break
  }
}

const handleAddressSelected = (data: any) => {
  console.log('📍 [AddressSearchWithMap] Адрес выбран:', data)
  
  const { address, coordinates: coords, precision } = data
  
  if (address && coords && coords.length >= 2) {
    selectedAddress.value = address
    coordinates.lat = coords[0]
    coordinates.lng = coords[1] 
    addressPrecision.value = precision || 'unknown'
    
    // Обновляем поле поиска
    searchQuery.value = address
    searchStatus.value = `✅ Адрес найден (точность: ${precision})`
    
    // Уведомляем родительский компонент
    const result = {
      address: address,
      lat: coords[0],
      lng: coords[1]
    }
    
    emit('update:modelValue', result)
    emit('address-selected', {
      address: address,
      lat: coords[0], 
      lng: coords[1],
      precision: precision
    })
  }
  
  isSearching.value = false
}

const handleSearchError = (data: any) => {
  console.error('❌ [AddressSearchWithMap] Ошибка поиска:', data)
  searchStatus.value = `❌ ${data.message || 'Адрес не найден'}`
  isSearching.value = false
}

const setInitialMarker = () => {
  if (!props.modelValue?.lat || !props.modelValue?.lng) return
  
  console.log('📍 [AddressSearchWithMap] Установка начального маркера:', props.modelValue)
  
  if (mapIframe.value?.contentWindow) {
    mapIframe.value.contentWindow.postMessage({
      type: 'setMarker',
      data: {
        coordinates: [props.modelValue.lat, props.modelValue.lng],
        address: props.modelValue.address,
        zoom: 16
      }
    }, window.location.origin)
  }
  
  // Устанавливаем локальное состояние
  if (props.modelValue.address) {
    searchQuery.value = props.modelValue.address
    selectedAddress.value = props.modelValue.address
  }
  coordinates.lat = props.modelValue.lat
  coordinates.lng = props.modelValue.lng
}

// Watchers
watch(() => props.modelValue, (newValue) => {
  if (newValue && newValue.lat && newValue.lng && isMapReady.value) {
    setInitialMarker()
  }
}, { deep: true, immediate: true })

// Lifecycle
onMounted(() => {
  console.log('📍 [AddressSearchWithMap] Компонент смонтирован')
  
  // Обработчик изменения размеров окна
  const handleResize = () => {
    if (mapIframe.value && isMapReady.value) {
      mapIframe.value.style.height = `${props.height}px`
      console.log('🔧 [AddressSearchWithMap] Размеры iframe обновлены после resize')
    }
  }
  
  window.addEventListener('resize', handleResize)
  
  // Сохраняем обработчик для удаления
  window._addressSearchResizeHandler = handleResize
})

onUnmounted(() => {
  window.removeEventListener('message', handleMapMessage)
  
  // Удаляем обработчик изменения размеров
  if (window._addressSearchResizeHandler) {
    window.removeEventListener('resize', window._addressSearchResizeHandler)
    delete window._addressSearchResizeHandler
  }
  
  // Очищаем таймер поиска автоподсказок
  if (searchTimeout.value) {
    clearTimeout(searchTimeout.value)
  }
})
</script>

<style scoped>
.address-search-with-map {
  @apply space-y-6;
}

.search-section {
  @apply space-y-3;
}

.search-label {
  @apply block text-sm font-medium text-gray-700;
}

.search-input-group {
  @apply relative flex items-center;
}

.search-input {
  @apply flex-1 px-3 py-2 border border-gray-300 rounded-l-md shadow-sm;
  @apply focus:ring-blue-500 focus:border-blue-500;
  @apply placeholder-gray-400 text-sm;
}

.search-button {
  @apply px-3 py-2 bg-blue-600 text-white border border-blue-600;
  @apply hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500;
  @apply disabled:bg-gray-300 disabled:cursor-not-allowed;
  @apply transition-colors duration-200;
}

.clear-button {
  @apply px-2 py-2 bg-gray-100 text-gray-600 border border-l-0 border-gray-300 rounded-r-md;
  @apply hover:bg-gray-200 hover:text-gray-800;
  @apply focus:outline-none focus:ring-2 focus:ring-blue-500;
  @apply transition-colors duration-200;
}

.search-status {
  @apply text-xs font-medium;
}

.map-section {
  @apply space-y-3;
}

.map-header {
  @apply flex items-center justify-between;
}

.map-title {
  @apply text-sm font-medium text-gray-700;
}

.selected-address {
  @apply text-xs text-green-600 bg-green-50 px-2 py-1 rounded;
  @apply max-w-xs truncate;
}

.map-container {
  @apply relative rounded-lg border border-gray-300 overflow-hidden;
  @apply bg-gray-50;
  min-height: 400px;
  height: 400px;
}

.address-map-iframe {
  @apply w-full border-0;
  height: 400px;
  min-height: 400px;
  display: block;
  background: #f0f0f0;
}

.address-map-iframe.custom-height {
  height: var(--map-height);
  min-height: var(--map-height);
}

.address-map-iframe.map-loading {
  @apply opacity-50;
}

.map-loader {
  @apply absolute inset-0 flex flex-col items-center justify-center;
  @apply bg-white bg-opacity-90 backdrop-blur-sm;
}

/* Стили для автоподсказок */
.suggestions-dropdown {
  @apply absolute top-full left-0 right-0 z-50 bg-white border border-gray-300 rounded-b-md shadow-lg max-h-60 overflow-y-auto;
  border-top: none;
}

.suggestion-item {
  @apply px-3 py-3 cursor-pointer border-b border-gray-100 last:border-b-0;
  @apply hover:bg-blue-50 transition-colors duration-150;
}

.suggestion-selected {
  @apply bg-blue-100;
}

.suggestion-text {
  @apply text-sm font-medium text-gray-900 mb-1;
}

.suggestion-subtext {
  @apply text-xs text-gray-500;
}

/* Мобильная адаптация */
@media (max-width: 640px) {
  .search-input-group {
    @apply flex-col;
  }
  
  .search-input {
    @apply rounded-md rounded-b-none;
  }
  
  .search-button {
    @apply w-full rounded-md rounded-t-none border-t-0;
  }
  
  .clear-button {
    @apply absolute top-2 right-2 bg-white border border-gray-300 rounded;
  }
  
  .map-header {
    @apply flex-col items-start space-y-2;
  }
  
  .selected-address {
    @apply max-w-full;
  }
  
  /* Автоподсказки на мобильном */
  .suggestions-dropdown {
    @apply max-h-40;
  }
  
  .suggestion-item {
    @apply px-4 py-3;
  }
  
  .suggestion-text {
    @apply text-base;
  }
  
  .suggestion-subtext {
    @apply text-sm;
  }
}
</style>