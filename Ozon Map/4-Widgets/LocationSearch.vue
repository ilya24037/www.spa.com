<template>
  <div class="location-search-widget">
    <div class="search-container">
      <div class="search-input-wrapper">
        <input
          ref="searchInput"
          v-model="searchQuery"
          type="text"
          class="search-input"
          :placeholder="placeholder"
          :disabled="disabled"
          autocomplete="off"
          @input="handleInput"
          @focus="handleFocus"
          @blur="handleBlur"
          @keydown="handleKeyDown"
        />
        
        <!-- Search Icon -->
        <div class="search-icon">
          <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
            <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
          </svg>
        </div>
        
        <!-- Clear Button -->
        <button
          v-if="searchQuery"
          type="button"
          class="clear-button"
          title="Очистить"
          @click="clearSearch"
        >
          <svg width="12" height="12" viewBox="0 0 12 12" fill="currentColor">
            <path d="M9.5 3.205L8.795 2.5 6 5.295 3.205 2.5l-.705.705L5.295 6 2.5 8.795l.705.705L6 6.705 8.795 9.5l.705-.705L6.705 6z"/>
          </svg>
        </button>
      </div>
      
      <!-- Results Dropdown -->
      <div 
        v-if="showResults && (suggestions.length > 0 || isLoading)"
        class="search-results"
        :class="{ 'has-suggestions': suggestions.length > 0 }"
      >
        <!-- Loading State -->
        <div v-if="isLoading" class="search-result-item loading">
          <div class="loading-icon"></div>
          <span>Поиск...</span>
        </div>
        
        <!-- Suggestions -->
        <div
          v-for="(suggestion, index) in suggestions"
          :key="suggestion.id || index"
          class="search-result-item"
          :class="{ 'highlighted': index === highlightedIndex }"
          @click="selectSuggestion(suggestion)"
          @mouseenter="highlightedIndex = index"
        >
          <div class="suggestion-icon">
            <svg width="14" height="14" viewBox="0 0 14 14" fill="currentColor">
              <path d="M7 2a5 5 0 0 1 5 5 5 5 0 0 1-5 5 5 5 0 0 1-5-5 5 5 0 0 1 5-5m0 1a4 4 0 0 0-4 4 4 4 0 0 0 4 4 4 4 0 0 0 4-4 4 4 0 0 0-4-4m0 1.5A2.5 2.5 0 0 1 9.5 7 2.5 2.5 0 0 1 7 9.5 2.5 2.5 0 0 1 4.5 7 2.5 2.5 0 0 1 7 4.5"/>
            </svg>
          </div>
          <div class="suggestion-content">
            <div class="suggestion-title">{{ suggestion.title }}</div>
            <div v-if="suggestion.subtitle" class="suggestion-subtitle">
              {{ suggestion.subtitle }}
            </div>
          </div>
          <div class="suggestion-distance" v-if="suggestion.distance">
            {{ formatDistance(suggestion.distance) }}
          </div>
        </div>
        
        <!-- No Results -->
        <div v-if="!isLoading && suggestions.length === 0 && searchQuery.length > 2" class="search-result-item no-results">
          <span>Ничего не найдено</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue'
import type { Map as MapLibreMap } from 'maplibre-gl'

export interface LocationSuggestion {
  id: string
  title: string
  subtitle?: string
  coordinates: [number, number]
  distance?: number
  type?: 'address' | 'poi' | 'city' | 'street'
  bounds?: [[number, number], [number, number]]
}

export interface LocationSearchProps {
  /** Instance карты MapLibre */
  map?: MapLibreMap
  /** Плейсхолдер для поиска */
  placeholder?: string
  /** Виджет отключен */
  disabled?: boolean
  /** Минимальная длина запроса для поиска */
  minLength?: number
  /** Задержка перед поиском (debounce) */
  debounceMs?: number
  /** Максимальное количество результатов */
  maxResults?: number
  /** API ключ для геокодинга */
  apiKey?: string
  /** Провайдер геокодинга */
  provider?: 'yandex' | 'osm' | 'custom'
  /** Показывать расстояние до результатов */
  showDistance?: boolean
  /** Центр для расчета расстояния */
  distanceCenter?: [number, number]
}

export interface LocationSearchEmits {
  (e: 'location-selected', suggestion: LocationSuggestion): void
  (e: 'search-start', query: string): void
  (e: 'search-complete', suggestions: LocationSuggestion[]): void
  (e: 'search-error', error: string): void
}

const props = withDefaults(defineProps<LocationSearchProps>(), {
  placeholder: 'Поиск адреса или места...',
  disabled: false,
  minLength: 3,
  debounceMs: 300,
  maxResults: 10,
  provider: 'osm',
  showDistance: true
})

const emit = defineEmits<LocationSearchEmits>()

// State
const searchInput = ref<HTMLInputElement>()
const searchQuery = ref('')
const suggestions = ref<LocationSuggestion[]>([])
const isLoading = ref(false)
const showResults = ref(false)
const highlightedIndex = ref(-1)
const isFocused = ref(false)

let debounceTimer: number | null = null

// Computed
const hasValidQuery = computed(() => 
  searchQuery.value.length >= props.minLength
)

// Methods
const handleInput = (event: Event) => {
  const target = event.target as HTMLInputElement
  searchQuery.value = target.value
  
  // Clear previous timer
  if (debounceTimer) {
    clearTimeout(debounceTimer)
  }
  
  // Reset state
  highlightedIndex.value = -1
  
  if (hasValidQuery.value) {
    // Debounced search
    debounceTimer = setTimeout(() => {
      performSearch(searchQuery.value)
    }, props.debounceMs)
  } else {
    suggestions.value = []
    showResults.value = false
  }
}

const handleFocus = () => {
  isFocused.value = true
  showResults.value = hasValidQuery.value && suggestions.value.length > 0
}

const handleBlur = () => {
  isFocused.value = false
  // Delay hiding to allow click on suggestions
  setTimeout(() => {
    showResults.value = false
  }, 150)
}

const handleKeyDown = (event: KeyboardEvent) => {
  if (!showResults.value || suggestions.value.length === 0) return
  
  switch (event.key) {
    case 'ArrowDown':
      event.preventDefault()
      highlightedIndex.value = Math.min(
        highlightedIndex.value + 1,
        suggestions.value.length - 1
      )
      break
      
    case 'ArrowUp':
      event.preventDefault()
      highlightedIndex.value = Math.max(highlightedIndex.value - 1, -1)
      break
      
    case 'Enter':
      event.preventDefault()
      if (highlightedIndex.value >= 0) {
        selectSuggestion(suggestions.value[highlightedIndex.value])
      }
      break
      
    case 'Escape':
      event.preventDefault()
      showResults.value = false
      searchInput.value?.blur()
      break
  }
}

const clearSearch = () => {
  searchQuery.value = ''
  suggestions.value = []
  showResults.value = false
  highlightedIndex.value = -1
  searchInput.value?.focus()
}

const selectSuggestion = (suggestion: LocationSuggestion) => {
  searchQuery.value = suggestion.title
  showResults.value = false
  highlightedIndex.value = -1
  
  // Center map on selected location
  if (props.map) {
    if (suggestion.bounds) {
      props.map.fitBounds(suggestion.bounds, { padding: 50 })
    } else {
      props.map.setCenter(suggestion.coordinates)
      props.map.setZoom(15)
    }
  }
  
  emit('location-selected', suggestion)
}

const performSearch = async (query: string) => {
  if (!query || query.length < props.minLength) return
  
  isLoading.value = true
  showResults.value = true
  emit('search-start', query)
  
  try {
    let results: LocationSuggestion[] = []
    
    switch (props.provider) {
      case 'yandex':
        results = await searchYandex(query)
        break
      case 'osm':
        results = await searchOSM(query)
        break
      case 'custom':
        // Custom search implementation can be added here
        results = []
        break
    }
    
    suggestions.value = results.slice(0, props.maxResults)
    emit('search-complete', suggestions.value)
    
  } catch (error) {
    console.error('Search error:', error)
    emit('search-error', error instanceof Error ? error.message : 'Ошибка поиска')
  } finally {
    isLoading.value = false
  }
}

const searchOSM = async (query: string): Promise<LocationSuggestion[]> => {
  const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=${props.maxResults}&addressdetails=1`
  
  const response = await fetch(url)
  if (!response.ok) throw new Error('Ошибка сети')
  
  const data = await response.json()
  
  return data.map((item: any) => ({
    id: item.place_id,
    title: item.display_name.split(',')[0],
    subtitle: item.display_name.split(',').slice(1, 3).join(',').trim(),
    coordinates: [parseFloat(item.lon), parseFloat(item.lat)] as [number, number],
    type: getOSMType(item.type),
    bounds: item.boundingbox ? [
      [parseFloat(item.boundingbox[2]), parseFloat(item.boundingbox[0])],
      [parseFloat(item.boundingbox[3]), parseFloat(item.boundingbox[1])]
    ] as [[number, number], [number, number]] : undefined
  }))
}

const searchYandex = async (query: string): Promise<LocationSuggestion[]> => {
  if (!props.apiKey) {
    throw new Error('API ключ Яндекс.Карт не предоставлен')
  }
  
  const url = `https://geocode-maps.yandex.ru/1.x/?apikey=${props.apiKey}&format=json&geocode=${encodeURIComponent(query)}&results=${props.maxResults}`
  
  const response = await fetch(url)
  if (!response.ok) throw new Error('Ошибка сети')
  
  const data = await response.json()
  const geoObjects = data.response?.GeoObjectCollection?.featureMember || []
  
  return geoObjects.map((item: any) => {
    const geoObject = item.GeoObject
    const coordinates = geoObject.Point.pos.split(' ').map(Number).reverse()
    
    return {
      id: geoObject.metaDataProperty.GeocoderMetaData.Address.country_code + '_' + Date.now(),
      title: geoObject.name,
      subtitle: geoObject.description,
      coordinates: coordinates as [number, number],
      type: 'address'
    }
  })
}

const getOSMType = (osmType: string): LocationSuggestion['type'] => {
  if (['house', 'residential'].includes(osmType)) return 'address'
  if (['city', 'town', 'village'].includes(osmType)) return 'city'
  if (['road', 'street'].includes(osmType)) return 'street'
  return 'poi'
}

const formatDistance = (distance: number): string => {
  if (distance < 1000) {
    return `${Math.round(distance)} м`
  }
  return `${(distance / 1000).toFixed(1)} км`
}

const calculateDistance = (coords1: [number, number], coords2: [number, number]): number => {
  const R = 6371000 // Earth's radius in meters
  const lat1Rad = coords1[1] * Math.PI / 180
  const lat2Rad = coords2[1] * Math.PI / 180
  const deltaLatRad = (coords2[1] - coords1[1]) * Math.PI / 180
  const deltaLonRad = (coords2[0] - coords1[0]) * Math.PI / 180

  const a = Math.sin(deltaLatRad / 2) * Math.sin(deltaLatRad / 2) +
    Math.cos(lat1Rad) * Math.cos(lat2Rad) *
    Math.sin(deltaLonRad / 2) * Math.sin(deltaLonRad / 2)
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a))

  return R * c
}

// Watchers
watch(suggestions, (newSuggestions) => {
  if (props.showDistance && props.distanceCenter && newSuggestions.length > 0) {
    newSuggestions.forEach(suggestion => {
      suggestion.distance = calculateDistance(props.distanceCenter!, suggestion.coordinates)
    })
    
    // Sort by distance
    suggestions.value = [...newSuggestions].sort((a, b) => 
      (a.distance || 0) - (b.distance || 0)
    )
  }
})

// Lifecycle
onMounted(() => {
  // Close dropdown on outside click
  document.addEventListener('click', (event) => {
    const target = event.target as HTMLElement
    if (!target.closest('.location-search-widget')) {
      showResults.value = false
    }
  })
})

onUnmounted(() => {
  if (debounceTimer) {
    clearTimeout(debounceTimer)
  }
})
</script>

<style scoped>
.location-search-widget {
  position: relative;
  width: 100%;
  max-width: 400px;
}

.search-container {
  position: relative;
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.search-input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.search-input {
  width: 100%;
  padding: 12px 40px 12px 40px;
  border: 2px solid #e0e0e0;
  border-radius: 8px;
  font-size: 14px;
  line-height: 1.4;
  outline: none;
  transition: border-color 0.2s, box-shadow 0.2s;
  background: white;
}

.search-input:focus {
  border-color: var(--ozon-primary, #005bff);
  box-shadow: 0 0 0 3px rgba(0, 91, 255, 0.1);
}

.search-input:disabled {
  background: #f5f5f5;
  color: #999;
  cursor: not-allowed;
}

.search-icon {
  position: absolute;
  left: 12px;
  color: #666;
  pointer-events: none;
}

.clear-button {
  position: absolute;
  right: 12px;
  background: none;
  border: none;
  color: #999;
  cursor: pointer;
  padding: 4px;
  border-radius: 4px;
  transition: color 0.2s, background-color 0.2s;
}

.clear-button:hover {
  color: #333;
  background-color: rgba(0, 0, 0, 0.05);
}

.search-results {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: white;
  border: 1px solid #e0e0e0;
  border-top: none;
  border-radius: 0 0 8px 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  max-height: 300px;
  overflow-y: auto;
  z-index: 1000;
}

.search-result-item {
  display: flex;
  align-items: center;
  padding: 12px 16px;
  cursor: pointer;
  border-bottom: 1px solid #f0f0f0;
  transition: background-color 0.2s;
}

.search-result-item:last-child {
  border-bottom: none;
}

.search-result-item:hover,
.search-result-item.highlighted {
  background-color: #f8f9fa;
}

.search-result-item.loading {
  justify-content: center;
  color: #666;
  cursor: default;
}

.search-result-item.no-results {
  justify-content: center;
  color: #999;
  cursor: default;
  font-style: italic;
}

.loading-icon {
  width: 16px;
  height: 16px;
  border: 2px solid #e0e0e0;
  border-top: 2px solid var(--ozon-primary, #005bff);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-right: 8px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.suggestion-icon {
  margin-right: 12px;
  color: #666;
  flex-shrink: 0;
}

.suggestion-content {
  flex: 1;
  min-width: 0;
}

.suggestion-title {
  font-weight: 500;
  color: #333;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.suggestion-subtitle {
  font-size: 12px;
  color: #666;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-top: 2px;
}

.suggestion-distance {
  font-size: 12px;
  color: #999;
  margin-left: 8px;
  flex-shrink: 0;
}

/* Mobile Optimizations */
@media (max-width: 768px) {
  .location-search-widget {
    max-width: none;
  }
  
  .search-input {
    padding: 14px 44px 14px 44px;
    font-size: 16px; /* Prevent zoom on iOS */
  }
  
  .search-icon {
    left: 14px;
  }
  
  .clear-button {
    right: 14px;
    padding: 8px;
    min-width: 32px;
    min-height: 32px;
  }
  
  .search-result-item {
    padding: 16px;
  }
  
  .suggestion-title {
    font-size: 15px;
  }
  
  .suggestion-subtitle {
    font-size: 13px;
  }
}

/* Dark Theme */
@media (prefers-color-scheme: dark) {
  .search-container {
    background: #2a2a2a;
  }
  
  .search-input {
    background: #2a2a2a;
    border-color: #444;
    color: white;
  }
  
  .search-input:focus {
    border-color: var(--ozon-primary, #005bff);
  }
  
  .search-results {
    background: #2a2a2a;
    border-color: #444;
  }
  
  .search-result-item {
    border-bottom-color: #333;
  }
  
  .search-result-item:hover,
  .search-result-item.highlighted {
    background-color: #333;
  }
  
  .suggestion-title {
    color: white;
  }
  
  .suggestion-subtitle {
    color: #ccc;
  }
}

/* Accessibility - Reduced Motion */
@media (prefers-reduced-motion: reduce) {
  .loading-icon {
    animation: none;
  }
  
  .search-input,
  .search-result-item,
  .clear-button {
    transition: none;
  }
}
</style>