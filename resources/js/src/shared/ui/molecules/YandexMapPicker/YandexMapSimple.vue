<template>
  <div class="yandex-map-simple">
    <div 
      :id="mapId" 
      class="yandex-map-simple__container"
      :style="{ height: height + 'px' }"
    >
      <!-- Загрузка -->
      <div 
        v-if="loading" 
        class="yandex-map-simple__loading"
      >
        <div class="flex flex-col items-center justify-center h-full">
          <svg class="animate-spin h-8 w-8 text-blue-600 mb-2" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <p class="text-sm text-gray-600">{{ loadingText }}</p>
        </div>
      </div>
      
      <!-- Кнопка геолокации -->
      <button
        v-if="!loading && showGeolocationButton"
        @click="centerOnUserLocation"
        class="yandex-map-simple__geolocation-btn"
        :title="geolocationTooltip"
      >
        <svg 
          class="w-5 h-5" 
          :class="{ 'text-blue-600': userLocationActive, 'text-gray-600': !userLocationActive }"
          fill="none" 
          viewBox="0 0 24 24" 
          stroke="currentColor"
        >
          <path 
            stroke-linecap="round" 
            stroke-linejoin="round" 
            stroke-width="2" 
            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
          />
          <path 
            stroke-linecap="round" 
            stroke-linejoin="round" 
            stroke-width="2" 
            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
          />
        </svg>
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch, computed } from 'vue'

interface Props {
  modelValue?: string // "lat,lng"
  height?: number
  center?: { lat: number; lng: number }
  zoom?: number
  autoDetectLocation?: boolean
  showGeolocationButton?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: '',
  height: 360,
  center: () => ({ lat: 58.0105, lng: 56.2502 }), // Пермь (fallback)
  zoom: 12,
  autoDetectLocation: true,
  showGeolocationButton: true
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
  'address-found': [address: string, coordinates: { lat: number; lng: number }]
  'marker-moved': [coordinates: { lat: number; lng: number }]
  'search-error': [error: string]
}>()

// Состояние
const loading = ref(true)
const loadingText = ref('Загрузка карты...')
const mapId = `map-${Math.random().toString(36).substr(2, 9)}`
const userLocationActive = ref(false)
const userLocation = ref<{ lat: number; lng: number } | null>(null)

// Переменные для карты (не реактивные!)
let map: any = null
let placemark: any = null
let userLocationMarker: any = null
let ymapsLoaded = false

// Флаг готовности карты
const mapReady = ref(false)

// Очередь запросов поиска
let pendingSearchRequest: string | null = null

// Текущие координаты
const currentCoords = ref(props.center)

// Вычисляемые свойства
const geolocationTooltip = computed(() => 
  userLocationActive.value ? 'Вы здесь' : 'Моё местоположение'
)

// Загрузка Яндекс.Карт API
const loadYandexMaps = () => {
  return new Promise((resolve, reject) => {
    // Проверяем, загружен ли уже API
    if (window.ymaps) {
      if (ymapsLoaded) {
        resolve(window.ymaps)
      } else {
        window.ymaps.ready(() => {
          ymapsLoaded = true
          resolve(window.ymaps)
        })
      }
      return
    }

    // Проверяем, не загружается ли уже скрипт
    const existingScript = document.querySelector('script[src*="api-maps.yandex.ru"]')
    if (existingScript) {
      // Ждем загрузки существующего скрипта
      const checkInterval = setInterval(() => {
        if (window.ymaps) {
          clearInterval(checkInterval)
          window.ymaps.ready(() => {
            ymapsLoaded = true
            resolve(window.ymaps)
          })
        }
      }, 100)
      return
    }

    const script = document.createElement('script')
    script.src = 'https://api-maps.yandex.ru/2.1/?apikey=23ff8acc-835f-4e99-8b19-d33c5d346e18&lang=ru_RU'
    script.async = true
    
    script.onload = () => {
      if (window.ymaps) {
        window.ymaps.ready(() => {
          ymapsLoaded = true
          resolve(window.ymaps)
        })
      } else {
        reject(new Error('ymaps не загружен'))
      }
    }
    
    script.onerror = () => reject(new Error('Ошибка загрузки скрипта'))
    document.head.appendChild(script)
  })
}

// Автоопределение местоположения через Browser API
const detectLocationByBrowser = (): Promise<{ lat: number; lng: number } | null> => {
  return new Promise((resolve) => {
    if (!navigator.geolocation) {
      // Геолокация не поддерживается
      resolve(null)
      return
    }

    navigator.geolocation.getCurrentPosition(
      (position) => {
        const coords = {
          lat: position.coords.latitude,
          lng: position.coords.longitude
        }
        // Местоположение определено
        userLocation.value = coords
        resolve(coords)
      },
      (error) => {
        // Debug: Ошибка геолокации браузера
        resolve(null)
      },
      {
        enableHighAccuracy: true,
        timeout: 5000,
        maximumAge: 0
      }
    )
  })
}

// Автоопределение местоположения через IP (Яндекс)
const detectLocationByIP = async (): Promise<{ lat: number; lng: number } | null> => {
  try {
    if (!window.ymaps) {
      await loadYandexMaps()
    }
    
    // Используем встроенную геолокацию Яндекс.Карт
    const result = await window.ymaps.geolocation.get({
      provider: 'yandex',
      mapStateAutoApply: false
    })
    
    const coords = result.geoObjects.position
    if (coords) {
      const location = { lat: coords[0], lng: coords[1] }
      // Debug: Местоположение определено через IP (Яндекс)
      return location
    }
    
    return null
  } catch (error) {
    // Debug: Ошибка определения по IP
    
    // Fallback на внешний сервис
    try {
      const response = await fetch('https://ipapi.co/json/')
      const data = await response.json()
      
      if (data.latitude && data.longitude) {
        const location = {
          lat: data.latitude,
          lng: data.longitude
        }
        // Debug: Местоположение определено через ipapi.co
        return location
      }
    } catch (err) {
      // Debug: Ошибка ipapi.co
    }
    
    return null
  }
}

// Определение начальных координат
const detectInitialLocation = async (): Promise<{ lat: number; lng: number }> => {
  // Если есть сохраненные координаты из props - используем их
  if (props.modelValue) {
    const [lat, lng] = props.modelValue.split(',').map(Number)
    if (!isNaN(lat) && !isNaN(lng)) {
      // Debug: Используем сохраненные координаты
      return { lat, lng }
    }
  }
  
  // Если включено автоопределение
  if (props.autoDetectLocation) {
    loadingText.value = 'Определение местоположения...'
    
    // Сначала пробуем через браузер
    const browserLocation = await detectLocationByBrowser()
    if (browserLocation) {
      return browserLocation
    }
    
    // Затем через IP
    const ipLocation = await detectLocationByIP()
    if (ipLocation) {
      return ipLocation
    }
  }
  
  // Fallback на дефолтные координаты
  // Debug: Используем координаты по умолчанию (Пермь)
  return props.center
}

// Инициализация карты
const initMap = async () => {
  try {
    loadingText.value = 'Загрузка карты...'
    await loadYandexMaps()
    
    // Определяем начальные координаты
    const initialCoords = await detectInitialLocation()
    currentCoords.value = initialCoords
    
    // Ждем рендеринга DOM
    await new Promise(resolve => setTimeout(resolve, 100))
    
    const container = document.getElementById(mapId)
    if (!container) {
      console.error('Контейнер карты не найден')
      return
    }

    // Создаем карту
    map = new window.ymaps.Map(mapId, {
      center: [currentCoords.value.lat, currentCoords.value.lng],
      zoom: props.zoom,
      controls: ['zoomControl', 'typeSelector']
    })

    // Создаем маркер
    placemark = new window.ymaps.Placemark(
      [currentCoords.value.lat, currentCoords.value.lng],
      {
        balloonContent: 'Перетащите для изменения адреса'
      },
      {
        preset: 'islands#blueIcon',
        draggable: true
      }
    )

    // Обработчик перетаскивания
    placemark.events.add('dragend', function() {
      const coords = placemark.geometry.getCoordinates()
      handleCoordsUpdate(coords)
    })

    // Добавляем маркер
    map.geoObjects.add(placemark)

    // Клик по карте
    map.events.add('click', function(e: any) {
      const coords = e.get('coords')
      placemark.geometry.setCoordinates(coords)
      handleCoordsUpdate(coords)
    })

    loading.value = false
    mapReady.value = true
    
    // Если есть отложенный запрос поиска - выполняем его
    if (pendingSearchRequest) {
      const searchQuery = pendingSearchRequest
      pendingSearchRequest = null
      setTimeout(() => searchAddress(searchQuery), 100)
    }
  } catch (error) {
    console.error('Ошибка инициализации карты:', error)
    loading.value = false
    mapReady.value = false
  }
}

// Обновление координат
const handleCoordsUpdate = (coords: number[]) => {
  if (!coords || coords.length !== 2) return
  
  currentCoords.value = { lat: coords[0], lng: coords[1] }
  emit('update:modelValue', coords.join(','))
  emit('marker-moved', currentCoords.value)
}

// Поиск адреса
const searchAddress = async (address: string) => {
  if (!address) return
  
  // Проверяем готовность карты
  if (!mapReady.value || !window.ymaps || !map || !placemark) {
    // Debug: Карта еще не готова, запрос добавлен в очередь
    pendingSearchRequest = address
    emit('search-error', 'Карта загружается, попробуйте через секунду')
    return
  }
  
  // Проверяем наличие метода geocode
  if (!window.ymaps.geocode) {
    console.error('Метод geocode недоступен')
    emit('search-error', 'Сервис поиска временно недоступен')
    return
  }

  try {
    const searchQuery = address.includes('Пермь') ? address : `Пермь, ${address}`
    const result = await window.ymaps.geocode(searchQuery, { results: 1 })
    const firstGeoObject = result.geoObjects.get(0)
    
    if (firstGeoObject) {
      const coords = firstGeoObject.geometry.getCoordinates()
      
      // Перемещаем карту и маркер
      map.setCenter(coords, 16)
      placemark.geometry.setCoordinates(coords)
      handleCoordsUpdate(coords)
      
      const fullAddress = firstGeoObject.getAddressLine()
      emit('address-found', fullAddress, { lat: coords[0], lng: coords[1] })
    } else {
      emit('search-error', 'Адрес не найден')
    }
  } catch (error) {
    console.error('Ошибка поиска:', error)
    emit('search-error', 'Ошибка при поиске адреса')
  }
}

// Центрирование на местоположении пользователя
const centerOnUserLocation = async () => {
  userLocationActive.value = false
  loadingText.value = 'Определение местоположения...'
  loading.value = true
  
  try {
    // Определяем местоположение
    const location = await detectLocationByBrowser() || await detectLocationByIP()
    
    if (location && map && placemark) {
      // Перемещаем карту и маркер
      map.setCenter([location.lat, location.lng], 15)
      placemark.geometry.setCoordinates([location.lat, location.lng])
      
      // Обновляем координаты
      currentCoords.value = location
      handleCoordsUpdate([location.lat, location.lng])
      
      // Добавляем или обновляем маркер местоположения пользователя
      if (userLocationMarker) {
        userLocationMarker.geometry.setCoordinates([location.lat, location.lng])
      } else {
        // Создаем синий круг для обозначения текущего местоположения
        userLocationMarker = new window.ymaps.Circle(
          [[location.lat, location.lng], 100], // центр и радиус в метрах
          {
            hintContent: 'Вы здесь'
          },
          {
            fillColor: '#3B82F677',
            strokeColor: '#3B82F6',
            strokeOpacity: 0.8,
            strokeWidth: 2
          }
        )
        map.geoObjects.add(userLocationMarker)
      }
      
      userLocationActive.value = true
      userLocation.value = location
      
      // Геокодируем для получения адреса
      const result = await window.ymaps.geocode([location.lat, location.lng], { results: 1 })
      const firstGeoObject = result.geoObjects.get(0)
      if (firstGeoObject) {
        const address = firstGeoObject.getAddressLine()
        emit('address-found', address, location)
      }
    } else {
      // Debug: Не удалось определить местоположение
    }
  } catch (error) {
    console.error('Ошибка при определении местоположения:', error)
  } finally {
    loading.value = false
  }
}

// Экспорт методов
defineExpose({
  searchAddress,
  centerOnUserLocation
})

// Lifecycle
onMounted(() => {
  initMap()
})

onUnmounted(() => {
  if (map && typeof map.destroy === 'function') {
    try {
      map.destroy()
      map = null
      placemark = null
    } catch (e) {
      console.error('Ошибка при уничтожении карты:', e)
    }
  }
})

// Типы для window.ymaps
declare global {
  interface Window {
    ymaps: any
  }
}
</script>

<style scoped>
.yandex-map-simple {
  width: 100%;
  border-radius: 8px;
  overflow: hidden;
  border: 1px solid #e5e7eb;
}

.yandex-map-simple__container {
  width: 100%;
  min-height: 200px;
  background: #f0f0f0;
  position: relative;
}

.yandex-map-simple__loading {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.9);
  z-index: 10;
}

.yandex-map-simple__geolocation-btn {
  position: absolute;
  bottom: 20px;
  right: 20px;
  width: 40px;
  height: 40px;
  background: white;
  border: none;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
  z-index: 5;
}

.yandex-map-simple__geolocation-btn:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
  transform: translateY(-2px);
}

.yandex-map-simple__geolocation-btn:active {
  transform: translateY(0);
}

/* Стили для Яндекс.Карт */
:deep([class*="ymaps-"]) {
  font-family: inherit !important;
}
</style>