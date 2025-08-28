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
 * Принцип KISS: минимум логики, максимум делегирования
 */
import { ref, computed, onMounted, watch } from 'vue'
import MapCore from '../core/MapCore.vue'
import MapStates from './MapStates.vue'
import MapControls from './MapControls.vue'

// Плагины
import { 
  ClusterPlugin,
  GeolocationPlugin,
  SearchPlugin,
  MarkersPlugin
} from '../plugins'

import type { MapMarker, Coordinates } from '../types'
import { formatCoordinates, parseCoordinates } from '../utils/mapHelpers'

interface Props {
  // Основные
  modelValue?: string
  height?: number
  center?: Coordinates
  zoom?: number
  
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
  center: props.center || parseCoordinates(props.modelValue) || undefined,
  zoom: props.zoom
}))

// Handlers
async function handleMapReady(map: any) {
  console.log('[MapContainer] Карта готова, устанавливаем плагины')
  loading.value = false
  
  const core = mapCoreRef.value
  if (!core) return

  // Markers плагин - всегда нужен
  await core.use(new MarkersPlugin({
    mode: props.mode,
    draggable: props.draggable
  }))

  // Cluster плагин - только для multiple mode
  if (props.clusterize && props.mode === 'multiple') {
    await core.use(new ClusterPlugin())
  }

  // Geolocation плагин
  if (props.showGeolocationButton || props.autoDetectLocation) {
    await core.use(new GeolocationPlugin({
      showButton: props.showGeolocationButton,
      autoDetect: props.autoDetectLocation
    }))
  }

  // Search плагин
  if (props.showSearchControl || props.reverseGeocode) {
    await core.use(new SearchPlugin({
      showSearchControl: props.showSearchControl,
      reverseGeocode: props.reverseGeocode
    }))
  }

  // Подписываемся на события store
  const store = core.store
  
  store.on('coordinates-change', (coords: Coordinates | null) => {
    if (coords) {
      emit('update:modelValue', formatCoordinates(coords.lat, coords.lng))
    }
  })
  
  store.on('marker-click', (marker: MapMarker) => {
    emit('marker-click', marker)
  })
  
  store.on('cluster-click', (markers: MapMarker[]) => {
    emit('cluster-click', markers)
  })
  
  store.on('address-found', (data: any) => {
    emit('address-found', data)
  })

  emit('ready', map)
  console.log('[MapContainer] Плагины установлены')
}

function handleMapError(err: Error) {
  error.value = err.message
  loading.value = false
  console.error('[MapContainer] Ошибка:', err)
}

function handleMapClick(coords: Coordinates) {
  if (props.mode === 'single') {
    emit('update:modelValue', formatCoordinates(coords.lat, coords.lng))
  }
}

function handleRetry() {
  error.value = null
  loading.value = true
  // Пересоздаем компонент через key
  location.reload()
}

function handleGeolocationClick() {
  const store = mapCoreRef.value?.store
  store?.emit('geolocation-detect')
}

function handleSearch(query: string) {
  const store = mapCoreRef.value?.store
  store?.emit('search-address', query)
}

// Watchers
watch(() => props.markers, (newMarkers) => {
  if (mapCoreRef.value && props.mode === 'multiple') {
    const store = mapCoreRef.value.store
    store.updateMarkers(newMarkers)
  }
}, { deep: true })

watch(() => props.modelValue, (newValue) => {
  if (newValue && mapCoreRef.value) {
    const coords = parseCoordinates(newValue)
    if (coords) {
      mapCoreRef.value.setCenter(coords)
    }
  }
})

// Public API
defineExpose({
  setCenter: (coords: Coordinates, zoom?: number) => {
    mapCoreRef.value?.setCenter(coords, zoom)
  },
  searchAddress: async (address: string) => {
    const store = mapCoreRef.value?.store
    store?.emit('search-address', address)
  },
  getCurrentAddress: () => {
    return mapCoreRef.value?.store.address || ''
  }
})
</script>

<style scoped>
.map-container {
  position: relative;
  width: 100%;
}

.map-container--loading {
  pointer-events: none;
}

.map-container--error .map-core {
  opacity: 0.5;
}
</style>