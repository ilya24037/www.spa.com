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
import { ref, computed, watch } from 'vue'
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
  center: props.center || parseCoordinates(props.modelValue),
  zoom: props.zoom
}))

// Handlers
async function handleMapReady(map: any) {
  loading.value = false
  
  // Устанавливаем плагины
  const core = mapCoreRef.value
  if (!core) return

  // Markers плагин
  await core.use(new MarkersPlugin({
    mode: props.mode,
    draggable: props.draggable
  }))

  // Cluster плагин
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
  store.on('coordinates-change', (coords: Coordinates) => {
    emit('update:modelValue', formatCoordinates(coords))
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
}

function handleMapError(err: Error) {
  error.value = err.message
  loading.value = false
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
  searchAddress: async (address: string) => {
    // TODO: Implement via SearchPlugin
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