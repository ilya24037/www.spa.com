<template>
  <div class="yandex-map" role="region" :aria-label="ariaLabel">
    <MapStates v-bind="stateProps" @retry="handleRetry">
      <YandexMapBase
        ref="mapBaseRef"
        v-bind="mapProps"
        @ready="handleMapReady"
        @bounds-change="emit('bounds-change', $event)"
        @center-change="handleCenterChange"
        @click="handleMapClick"
        @error="handleMapError"
      >
        <template #controls>
          <MapControls v-bind="controlsProps" @geolocation-click="handleGeolocationClick" />
        </template>
        <template #overlays>
          <MapCenterMarker v-if="showCenterMarker" :visible="!!hasAddress" @marker-hover="handleMarkerHover" />
          <MapAddressTooltip v-bind="tooltipProps" />
        </template>
      </YandexMapBase>
      <MapMarkersManager
        v-if="showMarkers"
        v-bind="markersProps"
        @marker-click="emit('marker-click', $event)"
        @cluster-click="emit('cluster-click', $event)"
      />
    </MapStates>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { MapMarker, Coordinates } from '@/src/features/map/types'
import { DEFAULT_API_KEY, PERM_CENTER, DEFAULT_ZOOM } from '@/src/features/map/lib/mapConstants'
import YandexMapBase from '@/src/features/map/ui/YandexMapBase/YandexMapBase.vue'
import MapStates from '@/src/features/map/ui/MapStates/MapStates.vue'
import MapControls from '@/src/features/map/ui/MapControls/MapControls.vue'
import MapCenterMarker from '@/src/features/map/ui/MapCenterMarker/MapCenterMarker.vue'
import MapAddressTooltip from '@/src/features/map/ui/MapAddressTooltip/MapAddressTooltip.vue'
import MapMarkersManager from '@/src/features/map/ui/MapMarkersManager/MapMarkersManager.vue'
import { useMapController } from '@/src/features/map/composables/useMapController'

export type { MapMarker } from '@/src/features/map/types'

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

const {
  mapBaseRef,
  mapInstance,
  mapState,
  geolocation,
  tooltip,
  handleMapReady,
  handleCenterChange,
  handleMapClick,
  handleMapError,
  handleRetry,
  handleGeolocationClick,
  handleMarkerHover,
  searchAddress,
  setCoordinates
} = useMapController(props, emit)

const stateProps = computed(() => ({
  isLoading: mapState.isLoading.value,
  error: mapState.error.value,
  errorDetails: mapState.errorDetails.value,
  isEmpty: props.mode === 'multiple' && props.markers.length === 0,
  height: props.height,
  loadingText: props.loadingText,
  errorTitle: props.errorTitle,
  emptyTitle: props.emptyTitle,
  emptyMessage: props.emptyMessage
}))

const mapProps = computed(() => ({
  height: props.height,
  center: props.center,
      zoom: props.zoom,
  apiKey: props.apiKey,
  mode: props.mode,
  autoDetectLocation: props.autoDetectLocation
}))

const controlsProps = computed(() => ({
  showGeolocation: props.showGeolocationButton,
  locationActive: geolocation.locationActive.value,
  geolocationLoading: geolocation.isLoading.value
}))

const tooltipProps = computed(() => ({
  visible: tooltip.visible.value,
  address: tooltip.address.value,
  position: tooltip.position.value
}))

const markersProps = computed(() => ({
  map: mapInstance.value,
  markers: props.markers,
  clusterize: props.clusterize
}))

const showCenterMarker = computed(() => props.mode === 'single' && props.showSingleMarker)
const showMarkers = computed(() => props.mode === 'multiple' && mapInstance.value)
const hasAddress = computed(() => props.mode === 'single' && props.currentAddress && props.modelValue?.includes(','))

defineExpose({ searchAddress, setCoordinates, forceInit: () => {} })
</script>

<style scoped>
.yandex-map { @apply relative w-full; }
@media (max-width: 640px) { .yandex-map { @apply rounded-none; } }
</style>