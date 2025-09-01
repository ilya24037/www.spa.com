<template>
  <div class="map-container" :class="containerClasses">
    <MapStates 
      :loading="loading"
      :error="error"
      @retry="handleRetry"
    >
      <MapCore
        ref="mapCoreRef"
        v-bind="coreProps"
        @ready="handleMapReady"
        @error="handleMapError"
        @click="handleMapClick"
        @center-change="$emit('center-change', $event)"
        @zoom-change="$emit('zoom-change', $event)"
      >
        <template #controls>
          <MapControls 
            v-if="showControls"
            :show-geolocation="showGeolocationButton"
            :show-search="showSearchControl"
            @geolocation-click="handleGeolocationClick"
            @search="handleSearch"
          />
        </template>
        
        <template #overlays>
          <slot name="overlays" />
        </template>
      </MapCore>
    </MapStates>
  </div>
</template>

<script setup lang="ts">
/**
 * MapContainer - главный контейнер карты
 * Объединяет ядро, плагины и UI
 * Размер: 100 строк
 */
import { ref, computed, watch, onMounted } from 'vue'
import MapCore from '../core/MapCore.vue'
import MapStates from './MapStates.vue'
import MapControls from './MapControls.vue'

// Плагины
import { ClusterPlugin } from '../plugins/ClusterPlugin'
import { GeolocationPlugin } from '../plugins/GeolocationPlugin'
import { SearchPlugin } from '../plugins/SearchPlugin'
import { MarkersPlugin } from '../plugins/MarkersPlugin'

import type { MapMarker, Coordinates } from '../core/MapStore'
import { parseCoordinates, formatCoordinates } from '../utils/mapHelpers'

interface Props {
  // Основные
  modelValue?: string
  height?: number
  center?: Coordinates
  zoom?: number
  apiKey?: string
  
  // Режимы
  mode?: 'single' | 'multiple'
  markers?: MapMarker[]
  
  // UI
  showControls?: boolean
  showGeolocationButton?: boolean
  showSearchControl?: boolean
  
  // Опции
  clusterize?: boolean
  draggable?: boolean
  autoDetectLocation?: boolean
  reverseGeocode?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  height: 400,
  zoom: 14,
  mode: 'single',
  markers: () => [],
  showControls: true,
  showGeolocationButton: false,
  showSearchControl: false,
  clusterize: false,
  draggable: true,
  autoDetectLocation: false,
  reverseGeocode: true
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
  'ready': [map: any]
  'marker-click': [marker: MapMarker]
  'cluster-click': [markers: MapMarker[]]
  'address-found': [data: { address: string, coords: Coordinates }]
  'center-change': [center: Coordinates]
  'zoom-change': [zoom: number]
}>()

// Логирование при монтировании
onMounted(() => {
  console.log('[MapContainer] 🚀 MapContainer компонент смонтирован')
  console.log('[MapContainer] 📋 Полученные props:', {
    modelValue: props.modelValue,
    height: props.height,
    center: props.center,
    zoom: props.zoom,
    apiKey: props.apiKey,
    mode: props.mode,
    markers: props.markers?.length || 0,
    showControls: props.showControls,
    clusterize: props.clusterize,
    draggable: props.draggable
  })
  console.log('[MapContainer] 📦 MapCore ref существует:', !!mapCoreRef.value)
})

// Refs
const mapCoreRef = ref<InstanceType<typeof MapCore>>()
const loading = ref(true)
const error = ref<string | null>(null)

// Computed
const containerClasses = computed(() => ({
  'map-container--loading': loading.value,
  'map-container--error': !!error.value
}))

const coreProps = computed(() => ({
  height: props.height,
  center: props.center || parseCoordinates(props.modelValue),
  zoom: props.zoom,
  apiKey: props.apiKey,
  showCenterMarker: props.mode === 'single'
}))

// Handlers с логированием
function handleMapReady(map: any) {
  console.log('[MapContainer] 🎉 Получен сигнал ready от MapCore')
  console.log('[MapContainer] 🗺️ Объект карты:', map)
  
  loading.value = false
  console.log('[MapContainer] ⏳ Убран статус loading')
  
  // Устанавливаем плагины (синхронно, как в архиве)
  const core = mapCoreRef.value
  if (!core) {
    console.error('[MapContainer] ❌ MapCore ref недоступен')
    return
  }
  console.log('[MapContainer] ✅ MapCore найден, подключаем плагины')

  // Markers плагин - только для multiple mode
  if (props.mode === 'multiple') {
    console.log(`[MapContainer] 🔌 Подключаем MarkersPlugin для multiple mode`)
    core.use(new MarkersPlugin({
      mode: props.mode,
      draggable: props.draggable
    }))
  } else {
    console.log('[MapContainer] ⏭️ MarkersPlugin пропущен для single mode (используется статический маркер)')
  }

  // Cluster плагин
  if (props.clusterize && props.mode === 'multiple') {
    console.log('[MapContainer] 🔌 Подключаем ClusterPlugin')
    core.use(new ClusterPlugin())
  } else {
    console.log('[MapContainer] ⏭️ ClusterPlugin пропущен (clusterize:', props.clusterize, ', mode:', props.mode, ')')
  }

  // Geolocation плагин
  if (props.showGeolocationButton || props.autoDetectLocation) {
    console.log(`[MapContainer] 🔌 Подключаем GeolocationPlugin (button: ${props.showGeolocationButton}, auto: ${props.autoDetectLocation})`)
    core.use(new GeolocationPlugin({
      showButton: props.showGeolocationButton,
      autoDetect: props.autoDetectLocation
    }))
  } else {
    console.log('[MapContainer] ⏭️ GeolocationPlugin пропущен')
  }

  // Search плагин
  if (props.showSearchControl || props.reverseGeocode) {
    console.log(`[MapContainer] 🔌 Подключаем SearchPlugin (control: ${props.showSearchControl}, geocode: ${props.reverseGeocode})`)
    core.use(new SearchPlugin({
      showSearchControl: props.showSearchControl,
      reverseGeocode: props.reverseGeocode
    }))
  } else {
    console.log('[MapContainer] ⏭️ SearchPlugin пропущен')
  }

  // Подписываемся на события store
  const store = core.store
  console.log('[MapContainer] 📡 Подписываемся на события store')
  
  store.on('coordinates-change', (coords: Coordinates) => {
    console.log('[MapContainer] 📍 coordinates-change:', coords)
    emit('update:modelValue', formatCoordinates(coords))
  })
  
  store.on('marker-click', (marker: MapMarker) => {
    console.log('[MapContainer] 🎯 marker-click:', marker)
    emit('marker-click', marker)
  })
  
  store.on('cluster-click', (markers: MapMarker[]) => {
    console.log('[MapContainer] 🎯 cluster-click:', markers.length, 'маркеров')
    emit('cluster-click', markers)
  })
  
  store.on('address-found', (data: any) => {
    console.log('[MapContainer] 📍 address-found:', data)
    emit('address-found', data)
  })

  console.log('[MapContainer] ✅ Все плагины подключены, эмитируем ready')
  emit('ready', map)
}

function handleMapError(err: Error) {
  console.error('[MapContainer] ❌ Получена ошибка от MapCore:', err)
  error.value = err.message
  loading.value = false
  console.log('[MapContainer] ⏳ Убран статус loading из-за ошибки')
}

function handleMapClick(coords: Coordinates) {
  if (props.mode === 'single') {
    emit('update:modelValue', formatCoordinates(coords))
  }
}

function handleRetry() {
  error.value = null
  loading.value = true
  // Restart map initialization
  mapCoreRef.value?.initMap()
}

function handleGeolocationClick() {
}

function handleSearch(query: string) {
}

// Watchers
watch(() => props.markers, (newMarkers) => {
  if (mapCoreRef.value && props.mode === 'multiple') {
    const store = mapCoreRef.value.store
    store.emit('markers-change', newMarkers)
  }
}, { deep: true })

// Public API
defineExpose({
  setCenter: (coords: Coordinates, zoom?: number) => {
    mapCoreRef.value?.setCenter(coords, zoom)
  },
  searchAddress: async (address: string): Promise<boolean> => {
    if (!address) {
      console.log('[MapContainer] ❌ Пустой адрес')
      return false
    }
    
    // Если API еще не загружен
    if (!window.ymaps) {
      console.log('[MapContainer] ⚠️ Yandex Maps API еще не загружен')
      return false
    }
    
    // Если geocode недоступен, ждём готовности API
    if (!window.ymaps.geocode) {
      console.log('[MapContainer] ⚠️ Функция geocode недоступна, ждём инициализации...')
      
      return new Promise<boolean>((resolve) => {
        // Используем ymaps.ready для ожидания полной загрузки
        window.ymaps.ready(async () => {
          console.log('[MapContainer] ✅ API готов, выполняем поиск')
          
          // После готовности API проверяем еще раз
          if (!window.ymaps.geocode) {
            console.error('[MapContainer] ❌ geocode все еще недоступен после ready()')
            resolve(false)
            return
          }
          
          try {
            const result = await window.ymaps.geocode(address, { results: 1 })
            const firstGeoObject = result.geoObjects.get(0)
            
            if (firstGeoObject) {
              const coords = firstGeoObject.geometry.getCoordinates()
              const fullAddress = firstGeoObject.getAddressLine()
              
              const coordinates: Coordinates = {
                lat: coords[0], 
                lng: coords[1]
              }
              
              console.log('[MapContainer] 📍 Адрес найден:', fullAddress, coordinates)
              
              // Центрируем карту
              mapCoreRef.value?.setCenter(coordinates, 15)
              
              // Обновляем store для single mode
              if (props.mode === 'single') {
                const store = mapCoreRef.value?.store
                if (store) {
                  store.setCoordinates(coordinates)
                  store.setAddress(fullAddress)
                  emit('update:modelValue', formatCoordinates(coordinates))
                  emit('address-found', { address: fullAddress, coords: coordinates })
                }
              }
              
              resolve(true)
            } else {
              console.log('[MapContainer] ❌ Адрес не найден')
              resolve(false)
            }
          } catch (error) {
            console.error('[MapContainer] ❌ Ошибка поиска:', error)
            resolve(false)
          }
        })
      })
    }
    
    console.log('[MapContainer] 🔍 searchAddress вызван для:', address)
    
    try {
      // Используем Yandex geocode API
      const result = await window.ymaps.geocode(address, { results: 1 })
      const firstGeoObject = result.geoObjects.get(0)
      
      if (firstGeoObject) {
        const coords = firstGeoObject.geometry.getCoordinates()
        const fullAddress = firstGeoObject.getAddressLine()
        
        const coordinates: Coordinates = {
          lat: coords[0],
          lng: coords[1]
        }
        
        console.log('[MapContainer] 📍 Адрес найден:', fullAddress, coordinates)
        
        // Центрируем карту на найденных координатах
        mapCoreRef.value?.setCenter(coordinates, 15)
        
        // Дополнительно форсируем обновление через малую задержку
        setTimeout(() => {
          const mapInstance = mapCoreRef.value?.store?.getMapInstance()
          if (mapInstance) {
            // Проверяем что карта действительно переместилась
            const currentCenter = mapInstance.getCenter()
            console.log('[MapContainer] 🔍 Проверка центра после перемещения:', currentCenter)
            
            // Если центр не совпадает, повторяем
            if (Math.abs(currentCenter[0] - coordinates.lat) > 0.001 || Math.abs(currentCenter[1] - coordinates.lng) > 0.001) {
              console.log('[MapContainer] ⚠️ Центр не обновился, повторяем...')
              mapInstance.setCenter([coordinates.lat, coordinates.lng], 15)
            }
          }
        }, 200)
        
        // Обновляем store для single mode (маркер переместится автоматически)
        if (props.mode === 'single') {
          const store = mapCoreRef.value?.store
          if (store) {
            store.setCoordinates(coordinates)
            store.setAddress(fullAddress)
            
            // Эмитируем события
            emit('update:modelValue', formatCoordinates(coordinates))
            emit('address-found', { address: fullAddress, coords: coordinates })
          }
        }
        
        console.log('[MapContainer] ✅ Поиск успешен, возвращаем true')
        return true
      }
      
      console.log('[MapContainer] ❌ Адрес не найден')
      return false
    } catch (error) {
      console.error('[MapContainer] ❌ Ошибка поиска адреса:', error)
      return false
    }
  }
})
</script>

<style lang="scss">
.map-container {
  position: relative;
  width: 100%;
  
  &--loading {
    pointer-events: none;
  }
  
  &--error {
    .map-core {
      opacity: 0.5;
    }
  }
}
</style>