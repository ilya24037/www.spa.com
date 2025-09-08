<template>
  <div class="vue-yandex-map">
    <!-- Поисковая строка -->
    <div class="search-container">
      <input
        v-model="searchQuery"
        @input="handleSearchInput"
        @focus="showSuggestions = true"
        @blur="hideSuggestions"
        type="text"
        placeholder="Введите адрес для поиска..."
        class="search-input"
      />
      
      <!-- Список подсказок -->
      <div v-if="showSuggestions && suggestions.length > 0" class="suggestions-list">
        <div
          v-for="(suggestion, index) in suggestions"
          :key="index"
          @click="selectSuggestion(suggestion)"
          class="suggestion-item"
        >
          {{ suggestion.displayName }}
        </div>
      </div>
    </div>

    <!-- Карта -->
    <div class="map-container">
      <YandexMap
        :settings="mapSettings"
        :controls="controlsSettings"
        :readonly-settings="true"
        @input="handleMapInput"
      >
        <!-- Слой карты -->
        <YandexMapDefaultSchemeLayer />
        
        <!-- Элементы управления зумом -->
        <YandexMapControls :settings="controlsSettings">
          <YandexMapZoomControl />
        </YandexMapControls>
        
        <!-- Слушатель событий карты -->
        <YandexMapListener
          :settings="{
            onActionEnd: handleActionEnd,
            onClick: handleMapClick,
            onUpdate: handleMapUpdate
          }"
        />
      </YandexMap>
      
      <!-- Центральный маркер -->
      <div class="center-marker">
        <div class="marker-pin"></div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { YandexMap, YandexMapDefaultSchemeLayer, YandexMapControls, YandexMapZoomControl, YandexMapListener } from 'vue-yandex-maps'

// Интерфейсы
interface GeoData {
  address: string
  coordinates: {
    lat: number
    lng: number
  }
  precision: string
}

interface Props {
  initialAddress?: string
  initialCoordinates?: [number, number]
  height?: number
}

interface Emits {
  (e: 'addressSelected', data: GeoData): void
  (e: 'coordinatesChanged', data: { lat: number, lng: number }): void
}

// Props и Emits
const props = withDefaults(defineProps<Props>(), {
  initialAddress: 'Москва, Красная площадь, 1',
  initialCoordinates: () => [37.6176, 55.7558],
  height: 360
})

const emit = defineEmits<Emits>()

// Реактивные переменные
const searchQuery = ref(props.initialAddress)
const suggestions = ref<any[]>([])
const showSuggestions = ref(false)
const mapRef = ref<any>(null)

// Текущие координаты и зум карты (реактивные для обновления)
const currentCoordinates = ref<[number, number]>(props.initialCoordinates)
const currentZoom = ref(12)

// Флаг для предотвращения навигации при обновлении карты
const isNavigating = ref(false)

// Таймеры для debounce
let geocodeTimeout: NodeJS.Timeout | null = null
let updateTimeout: NodeJS.Timeout | null = null

// Настройки карты (реактивные для обновления)
const mapSettings = computed(() => ({
  location: {
    center: currentCoordinates.value,
    zoom: currentZoom.value
  },
  mode: 'raster', // Используем растровый режим для стабильности
  behaviors: ['default', 'scrollZoom', 'dblClickZoom', 'drag'], // Включаем все поведения
  controls: [] // Убираем стандартные контролы, используем свои
}))

// Настройки элементов управления
const controlsSettings = {
  position: 'right'
}

// Обработка ввода в поисковой строке
const handleSearchInput = () => {
  if (geocodeTimeout) {
    clearTimeout(geocodeTimeout)
  }
  
  geocodeTimeout = setTimeout(() => {
    if (searchQuery.value.trim().length > 2) {
      searchAddress(searchQuery.value)
    } else {
      suggestions.value = []
      showSuggestions.value = false
    }
  }, 300)
}

// Поиск адреса через Yandex Geocoder API
const searchAddress = async (query: string) => {
  try {
    const response = await fetch(
      `https://geocode-maps.yandex.ru/1.x/?apikey=23ff8acc-835f-4e99-8b19-d33c5d346e18&format=json&geocode=${encodeURIComponent(query)}&results=5`
    )
    
    const data = await response.json()
    
    if (data.response && data.response.GeoObjectCollection) {
      const geoObjects = data.response.GeoObjectCollection.featureMember || []
      
      suggestions.value = geoObjects.map((item: any) => {
        const geoObject = item.GeoObject
        const coords = geoObject.Point.pos.split(' ').map(Number)
        
        return {
          displayName: geoObject.name,
          coordinates: [coords[1], coords[0]], // [lat, lng]
          precision: geoObject.metaDataProperty?.GeocoderMetaData?.precision || 'unknown'
        }
      })
      
      showSuggestions.value = true
    }
  } catch (error) {
    console.error('Ошибка поиска адреса:', error)
  }
}

// Выбор подсказки
const selectSuggestion = (suggestion: any) => {
  searchQuery.value = suggestion.displayName
  suggestions.value = []
  showSuggestions.value = false
  
  const [lat, lng] = suggestion.coordinates
  console.log('🎯 [VueYandexMap] Выбрана подсказка:', { address: suggestion.displayName, lat, lng })
  
  // Устанавливаем флаг навигации
  isNavigating.value = true
  
  // Обновляем реактивные переменные
  currentCoordinates.value = [lng, lat]
  currentZoom.value = 15
  
  // Навигируем карту к выбранному адресу с задержкой
  if (mapRef.value) {
    // Добавляем задержку для полной инициализации карты
    setTimeout(() => {
      try {
        if (mapRef.value && typeof mapRef.value.setLocation === 'function') {
          mapRef.value.setLocation({
            center: [lng, lat],
            zoom: 15,
            duration: 500 // Анимация 500мс
          })
          console.log('✅ [VueYandexMap] Карта навигирована к адресу через setLocation')
        } else {
          console.log('❌ [VueYandexMap] Метод setLocation недоступен, используем обновление настроек')
          // Альтернативный способ - обновляем настройки карты
          // Это должно сработать через реактивность Vue
        }
        
        // Сбрасываем флаг навигации после завершения анимации
        setTimeout(() => {
          isNavigating.value = false
        }, 600) // Немного больше чем duration
      } catch (error) {
        console.error('❌ [VueYandexMap] Ошибка навигации карты:', error)
        isNavigating.value = false
      }
    }, 200) // Задержка 200мс для инициализации
  } else {
    console.log('❌ [VueYandexMap] Экземпляр карты не доступен для навигации')
    isNavigating.value = false
  }
  
  emit('addressSelected', {
    address: suggestion.displayName,
    lat,
    lng,
    precision: suggestion.precision
  })
}

// Скрытие подсказок
const hideSuggestions = () => {
  setTimeout(() => {
    showSuggestions.value = false
  }, 200)
}

// Обработка получения экземпляра карты
const handleMapInput = (map: any) => {
  console.log('🗺️ [VueYandexMap] Получен экземпляр карты:', map)
  if (map) {
    mapRef.value = map
    console.log('✅ [VueYandexMap] Экземпляр карты сохранен')
    
    // Проверяем доступность методов карты
    if (typeof map.setLocation === 'function') {
      console.log('✅ [VueYandexMap] Метод setLocation доступен')
    } else {
      console.log('❌ [VueYandexMap] Метод setLocation недоступен')
    }
  }
}

// Обработка окончания действия на карте (перемещение, зум)
function handleActionEnd(event: any) {
  console.log('🗺️ [VueYandexMap] handleActionEnd вызван:', event)
  
  if (event && event.location) {
    const [lng, lat] = event.location.center
    const zoom = event.location.zoom
    
    console.log('📍 [VueYandexMap] Новые координаты:', { lat, lng, zoom })
    
    // Debounce обновления координат
    if (updateTimeout) {
      clearTimeout(updateTimeout)
    }
    
    updateTimeout = setTimeout(() => {
      currentCoordinates.value = [lng, lat] // Обновляем реактивный центр
      currentZoom.value = zoom // Обновляем реактивный зум
      emit('coordinatesChanged', { lat, lng })
      
      // Получаем адрес только если не происходит навигация
      if (!isNavigating.value) {
        getAddressFromCoordinates(lat, lng)
      }
    }, 100) // Задержка 100мс
  } else {
    console.log('❌ [VueYandexMap] Некорректное событие actionend:', event)
  }
}

// Обработка клика по карте
const handleMapClick = (event: any) => {
  console.log('🖱️ [VueYandexMap] handleMapClick вызван:', event)
  
  if (event && event.coordinates) {
    const [lng, lat] = event.coordinates
    console.log('📍 [VueYandexMap] Координаты клика:', { lat, lng })
    
    // Debounce обновления координат
    if (updateTimeout) {
      clearTimeout(updateTimeout)
    }
    
    updateTimeout = setTimeout(() => {
      currentCoordinates.value = [lng, lat] // Обновляем реактивный центр
      emit('coordinatesChanged', { lat, lng })
      
      // Получаем адрес только если не происходит навигация
      if (!isNavigating.value) {
        getAddressFromCoordinates(lat, lng)
      }
    }, 100) // Задержка 100мс
  } else {
    console.log('❌ [VueYandexMap] Некорректное событие click:', event)
  }
}

// Обработка обновления карты
const handleMapUpdate = (event: any) => {
  console.log('🔄 [VueYandexMap] handleMapUpdate вызван:', event)
  
  if (event && event.location) {
    const [lng, lat] = event.location.center
    const zoom = event.location.zoom
    
    console.log('📍 [VueYandexMap] Обновление карты:', { lat, lng, zoom })
    
    // Debounce обновления координат
    if (updateTimeout) {
      clearTimeout(updateTimeout)
    }
    
    updateTimeout = setTimeout(() => {
      currentCoordinates.value = [lng, lat] // Обновляем реактивный центр
      currentZoom.value = zoom // Обновляем реактивный зум
      emit('coordinatesChanged', { lat, lng })
      
      // Получаем адрес только если не происходит навигация
      if (!isNavigating.value) {
        getAddressFromCoordinates(lat, lng)
      }
    }, 100) // Задержка 100мс
  } else {
    console.log('❌ [VueYandexMap] Некорректное событие update:', event)
  }
}

// Получение адреса по координатам (обратное геокодирование)
const getAddressFromCoordinates = async (lat: number, lng: number) => {
  console.log('🔍 [VueYandexMap] Получение адреса для координат:', { lat, lng })
  
  try {
    const url = `https://geocode-maps.yandex.ru/1.x/?apikey=23ff8acc-835f-4e99-8b19-d33c5d346e18&format=json&geocode=${lng},${lat}&results=1`
    console.log('🌐 [VueYandexMap] URL запроса:', url)
    
    const response = await fetch(url)
    console.log('📡 [VueYandexMap] Ответ сервера:', response.status)
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }
    
    const data = await response.json()
    console.log('📄 [VueYandexMap] Данные ответа:', data)
    
    if (data.response && data.response.GeoObjectCollection) {
      const geoObjects = data.response.GeoObjectCollection.featureMember || []
      console.log('🏠 [VueYandexMap] Найдено объектов:', geoObjects.length)
      
      if (geoObjects.length > 0) {
        const geoObject = geoObjects[0].GeoObject
        const address = geoObject.name
        console.log('✅ [VueYandexMap] Найден адрес:', address)
        
        searchQuery.value = address
        
        emit('addressSelected', {
          address,
          lat,
          lng,
          precision: geoObject.metaDataProperty?.GeocoderMetaData?.precision || 'unknown'
        })
      } else {
        console.log('❌ [VueYandexMap] Адрес не найден для координат:', { lat, lng })
        searchQuery.value = `${lat.toFixed(6)}, ${lng.toFixed(6)}`
      }
    } else {
      console.log('❌ [VueYandexMap] Некорректный ответ API:', data)
    }
  } catch (error) {
    console.error('❌ [VueYandexMap] Ошибка получения адреса:', error)
    searchQuery.value = `${lat.toFixed(6)}, ${lng.toFixed(6)}`
  }
}

// Отслеживание изменений начальных координат
watch(() => props.initialCoordinates, (newCoords) => {
  if (newCoords) {
    currentCoordinates.value = newCoords
  }
})

// Отслеживание изменений координат для принудительного обновления
watch(currentCoordinates, (newCoords) => {
  console.log('🔄 [VueYandexMap] Координаты изменились:', newCoords)
}, { deep: true })

// Отслеживание изменений зума для принудительного обновления
watch(currentZoom, (newZoom) => {
  console.log('🔍 [VueYandexMap] Зум изменился:', newZoom)
})

// Инициализация при монтировании
onMounted(() => {
  console.log('🚀 [VueYandexMap] Компонент смонтирован')
  console.log('📍 [VueYandexMap] Начальные координаты:', props.initialCoordinates)
  console.log('🏠 [VueYandexMap] Начальный адрес:', props.initialAddress)
  
  // Устанавливаем начальный адрес
  searchQuery.value = props.initialAddress
  
  // Если есть начальные координаты, получаем адрес с задержкой
  if (props.initialCoordinates) {
    setTimeout(() => {
      const [lng, lat] = props.initialCoordinates
      getAddressFromCoordinates(lat, lng)
    }, 1000) // Задержка для инициализации карты
  }
})

// Очистка при размонтировании
onBeforeUnmount(() => {
  if (geocodeTimeout) {
    clearTimeout(geocodeTimeout)
  }
  if (updateTimeout) {
    clearTimeout(updateTimeout)
  }
})
</script>

<style scoped>
.vue-yandex-map {
  width: 100%;
  height: 100%;
  position: relative;
}

.search-container {
  position: relative;
  margin-bottom: 12px;
}

.search-input {
  width: 100%;
  padding: 12px 16px;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  font-size: 14px;
  outline: none;
  transition: border-color 0.2s ease;
}

.search-input:focus {
  border-color: #007bff;
  box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.1);
}

.suggestions-list {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: white;
  border: 1px solid #e0e0e0;
  border-top: none;
  border-radius: 0 0 8px 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  z-index: 1000;
  max-height: 200px;
  overflow-y: auto;
}

.suggestion-item {
  padding: 12px 16px;
  cursor: pointer;
  border-bottom: 1px solid #f0f0f0;
  font-size: 14px;
  transition: background-color 0.2s ease;
}

.suggestion-item:hover {
  background-color: #f8f9fa;
}

.suggestion-item:last-child {
  border-bottom: none;
}

.map-container {
  position: relative;
  width: 100%;
  height: v-bind(height + 'px');
  border-radius: 8px;
  overflow: hidden;
  border: 1px solid #e0e0e0;
}

.center-marker {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -100%);
  z-index: 10;
  pointer-events: none;
}

.marker-pin {
  width: 24px;
  height: 24px;
  background: #ff4444;
  border: 3px solid white;
  border-radius: 50% 50% 50% 0;
  transform: rotate(-45deg);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

.marker-pin::after {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%) rotate(45deg);
  width: 8px;
  height: 8px;
  background: white;
  border-radius: 50%;
}
</style>