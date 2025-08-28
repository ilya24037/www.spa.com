<template>
  <div class="yandex-map" role="region" :aria-label="ariaLabel">
    <!-- Используем новый MapContainer из рефакторинга -->
    <MapContainer
      ref="mapRef"
      :model-value="modelValue"
      :height="height"
      :center="center"
      :zoom="zoom"
      :mode="mode"
      :markers="markers"
      :show-geolocation-button="showGeolocationButton"
      :show-search-control="false"
      :clusterize="clusterize"
      :draggable="draggable"
      :auto-detect-location="autoDetectLocation"
      :reverse-geocode="showAddressTooltip"
      @update:modelValue="$emit('update:modelValue', $event)"
      @marker-click="$emit('marker-click', $event)"
      @cluster-click="$emit('cluster-click', $event)"
      @address-found="handleAddressFound"
      @center-change="$emit('bounds-change', { center: $event })"
    >
      <template #overlays>
        <slot name="overlays" />
      </template>
    </MapContainer>
  </div>
</template>

<script setup lang="ts">
/**
 * YandexMap - адаптер для обратной совместимости
 * Преобразует старое API к новому MapContainer
 */
import { ref, computed } from 'vue'
import MapContainer from '@/src/features/map/components/MapContainer.vue'
import { DEFAULT_API_KEY, PERM_CENTER, DEFAULT_ZOOM } from '@/src/features/map/utils/mapConstants'
import type { Coordinates, MapMarker } from '@/src/features/map/core/MapStore'

export type { MapMarker, Coordinates } from '@/src/features/map/core/MapStore'

interface Props {
  modelValue?: string
  height?: number
  center?: Coordinates
  zoom?: number
  apiKey?: string
  mode?: 'single' | 'multiple'
  markers?: MapMarker[]
  showGeolocationButton?: boolean
  autoDetectLocation?: boolean
  clusterize?: boolean
  draggable?: boolean
  showSingleMarker?: boolean
  showAddressTooltip?: boolean
  currentAddress?: string
  loadingText?: string
  errorTitle?: string
  emptyTitle?: string
  emptyMessage?: string
  ariaLabel?: string
}

const props = withDefaults(defineProps<Props>(), {
  height: 400,
  center: () => PERM_CENTER,
  zoom: DEFAULT_ZOOM,
  apiKey: DEFAULT_API_KEY,
  mode: 'single',
  markers: () => [],
  showGeolocationButton: false,
  autoDetectLocation: false,
  clusterize: false,
  draggable: true,
  showSingleMarker: true,
  showAddressTooltip: true,
  currentAddress: '',
  ariaLabel: 'Интерактивная карта'
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
  'marker-moved': [coords: Coordinates]
  'marker-click': [marker: MapMarker]
  'cluster-click': [markers: MapMarker[]]
  'address-found': [address: string, coords: Coordinates]
  'search-error': [error: string]
  'marker-address-hover': [address: string]
  'bounds-change': [bounds: any]
}>()

const mapRef = ref<InstanceType<typeof MapContainer>>()

// Обработчик для совместимости событий
function handleAddressFound(data: { address: string, coords: Coordinates }) {
  emit('address-found', data.address, data.coords)
  emit('marker-moved', data.coords)
}

// Expose старые методы для совместимости
defineExpose({
  // Методы для совместимости
  searchAddress: async (address: string) => {
    // TODO: Implement search via MapContainer
    console.log('searchAddress:', address)
  },
  setCoordinates: (coords: Coordinates) => {
    mapRef.value?.setCenter?.(coords)
  },
  getCurrentAddress: () => {
    return props.currentAddress || ''
  },
  forceInit: () => {
    // Заглушка для совместимости
    console.log('forceInit called (deprecated)')
  }
})
</script>

<style scoped>
.yandex-map { @apply relative w-full; }
@media (max-width: 640px) { .yandex-map { @apply rounded-none; } }
</style>