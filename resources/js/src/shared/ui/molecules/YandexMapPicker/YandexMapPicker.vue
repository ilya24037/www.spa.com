<template>
  <div class="yandex-map-picker">
    <!-- Debug info (только в development) -->
    <div v-if="false" style="font-size: 10px; color: #999; margin-bottom: 4px; display: flex; gap: 8px; align-items: center;">
      MapID: {{ mapId }} | Loading: {{ loading }}
      <button 
        @click="safeInitMap" 
        style="background: #ccc; border: 1px solid #999; padding: 2px 6px; font-size: 10px;"
      >
        Force Init
      </button>
    </div>
    
    <div 
      ref="mapContainer"
      :id="mapId" 
      class="yandex-map-picker__container"
      :style="{ height: height + 'px' }"
    >
      <!-- Загрузка -->
      <div 
        v-if="loading" 
        class="yandex-map-picker__loading"
      >
        <div class="flex flex-col items-center justify-center h-full">
          <svg class="animate-spin h-8 w-8 text-blue-600 mb-2" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <p class="text-sm text-gray-600">Загрузка карты...</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { useId } from '@/src/shared/composables/useId'

// Интерфейс для маркера на карте
export interface MapMarker {
  id: string | number
  lat: number
  lng: number
  title?: string
  description?: string
  icon?: string
  data?: any // Дополнительные данные (например, мастер)
}

interface Props {
  modelValue?: string // "lat,lng"
  height?: number
  center?: { lat: number; lng: number }
  zoom?: number
  apiKey?: string
  // Новые props для множественных меток
  multiple?: boolean // Режим множественных меток
  markers?: MapMarker[] // Массив маркеров для отображения
  clusterize?: boolean // Включить кластеризацию
  showSingleMarker?: boolean // Показывать одиночный маркер в режиме выбора
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: '',
  height: 360,
  center: () => ({ lat: 58.0105, lng: 56.2502 }), // Пермь
  zoom: 16,
  apiKey: '1220b4af-ae8e-4506-ab3a-c6329234066f', // Enterprise ключ из Avito
  multiple: false,
  markers: () => [],
  clusterize: true,
  showSingleMarker: true
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
  'address-found': [address: string, coordinates: { lat: number; lng: number }]
  'search-error': [error: string]
  'marker-moved': [coordinates: { lat: number; lng: number }]
  // Новые события для множественных меток
  'marker-click': [marker: MapMarker]
  'cluster-click': [markers: MapMarker[]]
  'bounds-change': [bounds: any]
}>()

// Состояние
const loading = ref(true)
const mapId = useId('yandex-map')
const map = ref<any>(null)
const placemark = ref<any>(null)
const mapContainer = ref<HTMLElement>()
// Новые переменные для множественных меток
const clusterer = ref<any>(null)
const placemarks = ref<any[]>([]) // Массив всех меток на карте

// Логируем ID для отладки (только в development)
if (import.meta.env.DEV) {
  console.log('YandexMapPicker mapId:', mapId)
}

// Парсинг координат из modelValue
const parseCoordinates = (value: string) => {
  if (value && value.includes(',')) {
    const [lat, lng] = value.split(',').map(Number)
    if (!isNaN(lat) && !isNaN(lng)) {
      return { lat, lng }
    }
  }
  return props.center
}

// Текущие координаты с проверкой
const currentCoords = ref(parseCoordinates(props.modelValue))
console.log('Начальные координаты компонента:', currentCoords.value)

// Загрузка Яндекс.Карт API
const loadYandexMapsAPI = (): Promise<any> => {
  return new Promise((resolve, reject) => {
    console.log('Загрузка Яндекс.Карт API с ключом:', props.apiKey)
    
    // Проверяем, загружен ли уже API
    if (window.ymaps) {
      console.log('API уже загружен')
      resolve(window.ymaps)
      return
    }

    // Создаем скрипт
    const script = document.createElement('script')
    const apiUrl = `https://api-maps.yandex.ru/2.1/?apikey=${props.apiKey}&lang=ru_RU`
    console.log('URL API:', apiUrl)
    script.src = apiUrl
    script.async = true
    
    script.onload = () => {
      console.log('Яндекс.Карты API скрипт загружен')
      
      // Добавляем небольшую задержку для инициализации API
      setTimeout(() => {
        if (window.ymaps) {
          console.log('window.ymaps доступен, ожидание ready...')
          
          // Устанавливаем таймаут на ready
          const readyTimeout = setTimeout(() => {
            console.error('Таймаут ожидания ready() Яндекс.Карт')
            reject(new Error('Таймаут инициализации Яндекс.Карт'))
          }, 10000) // 10 секунд
          
          window.ymaps.ready(() => {
            clearTimeout(readyTimeout)
            console.log('Яндекс.Карты готовы к использованию')
            resolve(window.ymaps)
          })
        } else {
          console.error('window.ymaps не доступен после загрузки скрипта')
          reject(new Error('window.ymaps не доступен'))
        }
      }, 100)
    }
    
    script.onerror = (error) => {
      console.error('Ошибка загрузки скрипта Яндекс.Карт:', error)
      reject(new Error('Не удалось загрузить Яндекс.Карты'))
    }
    
    document.head.appendChild(script)
  })
}

// Инициализация карты
const initMap = async () => {
  try {
    const ymaps = await loadYandexMapsAPI()
    
    // Ждем, пока DOM элемент будет доступен
    await new Promise(resolve => setTimeout(resolve, 100))
    
    // Проверяем существование контейнера
    if (!mapContainer.value) {
      throw new Error(`Контейнер карты не найден через ref`)
    }
    
    // Проверяем размеры контейнера
    const rect = mapContainer.value.getBoundingClientRect()
    console.log('Container dimensions:', rect.width, 'x', rect.height)
    if (rect.width === 0 || rect.height === 0) {
      throw new Error(`Контейнер карты имеет нулевые размеры: ${rect.width}x${rect.height}`)
    }
    
    // Создаем карту - используем ID строку вместо DOM элемента
    map.value = new ymaps.Map(mapId, {
      center: [currentCoords.value.lat, currentCoords.value.lng],
      zoom: props.zoom,
      controls: ['zoomControl', 'searchControl', 'typeSelector'],
      behaviors: ['drag', 'multiTouch', 'scrollZoom']
    })

    // Проверяем режим работы: множественные метки или одиночная
    if (props.multiple && props.markers.length > 0) {
      // РЕЖИМ МНОЖЕСТВЕННЫХ МЕТОК
      console.log('Инициализация в режиме множественных меток:', props.markers.length)
      
      // Создаем кластеризатор если включен
      if (props.clusterize) {
        clusterer.value = new ymaps.Clusterer({
          preset: 'islands#invertedYellowClusterIcons',
          groupByCoordinates: false,
          clusterDisableClickZoom: true,
          clusterHideIconOnBalloonOpen: false,
          geoObjectHideIconOnBalloonOpen: false,
          clusterBalloonContentLayout: 'cluster#balloonCarousel',
          clusterBalloonPanelMaxMapArea: 0,
          clusterBalloonContentLayoutWidth: 300,
          clusterBalloonContentLayoutHeight: 200,
          clusterBalloonPagerSize: 5
        })
        
        // Обработчик клика по кластеру
        clusterer.value.events.add('click', (e: any) => {
          const cluster = e.get('target')
          const geoObjects = cluster.getGeoObjects()
          const markers = geoObjects.map((obj: any) => obj.properties.get('markerData'))
          emit('cluster-click', markers)
        })
      }
      
      // Создаем метки для всех маркеров
      props.markers.forEach(marker => {
        const placemark = new ymaps.Placemark(
          [marker.lat, marker.lng],
          {
            balloonContentHeader: marker.title || '',
            balloonContentBody: marker.description || '',
            markerData: marker // Сохраняем данные маркера
          },
          {
            preset: marker.icon || 'islands#blueIcon',
            draggable: false
          }
        )
        
        // Обработчик клика по метке
        placemark.events.add('click', () => {
          emit('marker-click', marker)
        })
        
        placemarks.value.push(placemark)
        
        // Добавляем в кластеризатор или напрямую на карту
        if (props.clusterize && clusterer.value) {
          clusterer.value.add(placemark)
        } else {
          map.value.geoObjects.add(placemark)
        }
      })
      
      // Добавляем кластеризатор на карту
      if (props.clusterize && clusterer.value) {
        map.value.geoObjects.add(clusterer.value)
      }
      
      // Автоматически устанавливаем границы карты по меткам
      if (props.markers.length > 1) {
        map.value.setBounds(map.value.geoObjects.getBounds(), {
          checkZoomRange: true,
          zoomMargin: 50
        })
      }
      
    } else if (props.showSingleMarker) {
      // РЕЖИМ ОДИНОЧНОЙ МЕТКИ (как было раньше)
      const markerCoords = [currentCoords.value.lat, currentCoords.value.lng]
      console.log('Создаем одиночный маркер с координатами:', markerCoords)
      
      placemark.value = new ymaps.Placemark(markerCoords, {}, { 
        draggable: true
      })

      // Обработчик перетаскивания маркера
      placemark.value.events.add('dragend', () => {
        try {
          const coords = placemark.value.geometry.getCoordinates()
          const newCoords = { lat: coords[0], lng: coords[1] }
          
          console.log('Маркер перетащен, новые координаты:', newCoords)
          currentCoords.value = newCoords
          updateModelValue(coords.join(','))
          
          emit('marker-moved', newCoords)
        } catch (error) {
          console.error('Ошибка при обработке перетаскивания:', error)
        }
      })

      // Добавляем маркер на карту
      try {
        if (placemark.value && map.value && map.value.geoObjects) {
          map.value.geoObjects.add(placemark.value)
          console.log('Маркер успешно добавлен на карту')
        }
      } catch (markerError) {
        console.error('Ошибка при добавлении маркера:', markerError)
      }
    }
    
    // Обработчик клика по карте
    map.value.events.add('click', (e: any) => {
      try {
        const coords = e.get('coords')
        if (coords && coords.length === 2) {
          // В режиме одиночного маркера - перемещаем его
          if (!props.multiple && placemark.value) {
            placemark.value.geometry.setCoordinates(coords)
            
            const newCoords = { lat: coords[0], lng: coords[1] }
            currentCoords.value = newCoords
            updateModelValue(coords.join(','))
            
            console.log('Клик по карте, новые координаты:', newCoords)
            emit('marker-moved', newCoords)
          }
        }
      } catch (error) {
        console.error('Ошибка при клике по карте:', error)
      }
    })
    
    // Обработчик изменения границ карты
    map.value.events.add('boundschange', () => {
      const bounds = getBounds()
      if (bounds) {
        emit('bounds-change', bounds)
      }
    })
    
    loading.value = false
    
  } catch (error) {
    console.error('Ошибка инициализации карты:', error)
    console.error('MapID:', mapId)
    console.error('Container ref exists:', !!mapContainer.value)
    emit('search-error', `Не удалось загрузить карту: ${error.message}`)
    loading.value = false
  }
}

// Поиск адреса
const searchAddress = async (address: string): Promise<void> => {
  console.log('Начинаем поиск адреса:', address)
  
  if (!window.ymaps) {
    console.error('ymaps не доступен для поиска')
    emit('search-error', 'Карты не готовы')
    return
  }
  
  if (!address) {
    console.error('Адрес пустой')
    return
  }

  try {
    // Добавляем префикс города если его нет
    let searchQuery = address
    if (searchQuery.toLowerCase().indexOf('пермь') === -1) {
      searchQuery = `Пермь, ${address}`
    }

    console.log('Поисковый запрос:', searchQuery)
    
    const result = await window.ymaps.geocode(searchQuery, { results: 1 })
    console.log('Результат геокодинга:', result)
    
    const firstGeoObject = result.geoObjects.get(0)
    
    if (!firstGeoObject) {
      console.log('Адрес не найден')
      emit('search-error', 'Адрес не найден')
      return
    }

    const coords = firstGeoObject.geometry.getCoordinates()
    const newCoords = { lat: coords[0], lng: coords[1] }
    
    console.log('Найденные координаты:', newCoords)
    
    // Обновляем позицию карты и маркера
    if (map.value && placemark.value) {
      map.value.panTo(coords)
      placemark.value.geometry.setCoordinates(coords)
    }
    
    currentCoords.value = newCoords
    updateModelValue(coords.join(','))
    
    // Получаем точный адрес
    const geoAddress = firstGeoObject.getAddressLine()
    console.log('Точный адрес:', geoAddress)
    emit('address-found', geoAddress, newCoords)
    
  } catch (error) {
    console.error('Ошибка поиска адреса:', error)
    emit('search-error', 'Ошибка поиска адреса')
  }
}

// Обновление modelValue
const updateModelValue = (value: string) => {
  emit('update:modelValue', value)
}

// Установка координат извне
const setCoordinates = (coords: { lat: number; lng: number }) => {
  if (!map.value || !placemark.value) return
  
  const ymapsCoords = [coords.lat, coords.lng]
  
  map.value.panTo(ymapsCoords)
  placemark.value.geometry.setCoordinates(ymapsCoords)
  
  currentCoords.value = coords
  updateModelValue(ymapsCoords.join(','))
}

// Следим за изменениями modelValue
watch(() => props.modelValue, (newValue) => {
  const coords = parseCoordinates(newValue)
  if (coords.lat !== currentCoords.value.lat || coords.lng !== currentCoords.value.lng) {
    setCoordinates(coords)
  }
})

// Следим за изменениями маркеров для обновления карты
watch(() => props.markers, (newMarkers) => {
  if (props.multiple && map.value) {
    updateMarkers(newMarkers)
  }
}, { deep: true })

// Метод для обновления маркеров на карте
const updateMarkers = (newMarkers: MapMarker[]) => {
  if (!map.value || !window.ymaps) return
  
  console.log('Обновление маркеров:', newMarkers.length)
  
  // Очищаем старые метки
  if (clusterer.value) {
    clusterer.value.removeAll()
  } else {
    placemarks.value.forEach(placemark => {
      map.value.geoObjects.remove(placemark)
    })
  }
  placemarks.value = []
  
  // Добавляем новые метки
  newMarkers.forEach(marker => {
    const placemark = new window.ymaps.Placemark(
      [marker.lat, marker.lng],
      {
        balloonContentHeader: marker.title || '',
        balloonContentBody: marker.description || '',
        markerData: marker
      },
      {
        preset: marker.icon || 'islands#blueIcon',
        draggable: false
      }
    )
    
    placemark.events.add('click', () => {
      emit('marker-click', marker)
    })
    
    placemarks.value.push(placemark)
    
    if (props.clusterize && clusterer.value) {
      clusterer.value.add(placemark)
    } else {
      map.value.geoObjects.add(placemark)
    }
  })
  
  // Автоматически устанавливаем границы карты
  if (newMarkers.length > 1 && map.value.geoObjects.getBounds()) {
    map.value.setBounds(map.value.geoObjects.getBounds(), {
      checkZoomRange: true,
      zoomMargin: 50
    })
  } else if (newMarkers.length === 1) {
    map.value.setCenter([newMarkers[0].lat, newMarkers[0].lng], props.zoom)
  }
}

// Метод для обновления центра карты
const updateCenter = (center: { lat: number; lng: number }, zoom?: number) => {
  if (!map.value) return
  
  map.value.setCenter([center.lat, center.lng], zoom || map.value.getZoom())
}

// Метод для получения текущих границ карты
const getBounds = () => {
  if (!map.value) return null
  
  const bounds = map.value.getBounds()
  if (bounds) {
    return {
      northEast: { lat: bounds[1][0], lng: bounds[1][1] },
      southWest: { lat: bounds[0][0], lng: bounds[0][1] }
    }
  }
  return null
}

// Метод для принудительной инициализации
const forceInit = async () => {
  if (!map.value) {
    await safeInitMap()
  }
}

// Экспорт методов для родительского компонента
defineExpose({
  searchAddress,
  setCoordinates,
  forceInit,
  updateMarkers,
  updateCenter,
  getBounds
})

// Функция проверки видимости контейнера
const waitForVisible = async (): Promise<void> => {
  return new Promise((resolve) => {
    let attempts = 0
    const maxAttempts = 50 // 5 секунд максимум
    
    const checkVisible = () => {
      attempts++
      
      if (mapContainer.value) {
        const rect = mapContainer.value.getBoundingClientRect()
        console.log(`Попытка ${attempts}: размеры контейнера ${rect.width}x${rect.height}`)
        
        if (rect.width > 0 && rect.height > 0) {
          console.log('Контейнер стал видимым, начинаем инициализацию')
          resolve()
          return
        }
      } else {
        console.log(`Попытка ${attempts}: ref контейнера не найден`)
      }
      
      if (attempts >= maxAttempts) {
        console.warn('Достигнуто максимальное количество попыток ожидания видимости контейнера')
        resolve() // Все равно пытаемся инициализировать
        return
      }
      
      // Проверяем снова через 100ms
      setTimeout(checkVisible, 100)
    }
    
    checkVisible()
  })
}

// Улучшенная инициализация
const safeInitMap = async () => {
  try {
    // Ждем пока контейнер станет видимым
    await waitForVisible()
    await initMap()
  } catch (error) {
    console.error('Безопасная инициализация карты не удалась:', error)
  }
}

// Lifecycle
onMounted(() => {
  // Используем nextTick и ожидание видимости
  nextTick(() => {
    safeInitMap()
  })
})

onUnmounted(() => {
  if (map.value) {
    map.value.destroy()
  }
})

// Добавляем типы для window.ymaps
declare global {
  interface Window {
    ymaps: any
  }
}
</script>

<style scoped>
.yandex-map-picker {
  width: 100%;
  border-radius: 8px;
  overflow: hidden;
  border: 1px solid #e5e7eb;
}

.yandex-map-picker__container {
  width: 100%;
  min-width: 200px;
  background: #f0f0f0;
  position: relative;
  display: block;
}

.yandex-map-picker__loading {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.9);
  z-index: 10;
}

/* Стили для элементов Яндекс.Карт */
:deep(.ymaps-2-1-79-map) {
  width: 100% !important;
  height: 100% !important;
}

:deep(.ymaps-2-1-79-balloon-content) {
  max-width: 200px !important;
}
</style>