<template>
  <button
    class="maplibregl-ctrl-geolocate geolocate-button"
    :class="buttonClass"
    type="button"
    :title="buttonTitle"
    :aria-label="buttonTitle"
    :disabled="disabled || !isGeolocationSupported"
    @click="handleClick"
  >
    <span class="maplibregl-ctrl-icon geolocate-icon" aria-hidden="true"></span>
  </button>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import type { Map as MapLibreMap } from 'maplibre-gl'
import { useGeolocation } from '../5-Logic/composables/useGeolocation'

export interface GeolocateButtonProps {
  /** Instance карты MapLibre */
  map?: MapLibreMap
  /** Кнопка отключена */
  disabled?: boolean
  /** Опции геолокации */
  options?: PositionOptions
  /** Отслеживать позицию пользователя */
  trackUserLocation?: boolean
  /** Показывать круг точности */
  showAccuracyCircle?: boolean
  /** Показывать пользовательскую позицию */
  showUserLocation?: boolean
}

export interface GeolocateButtonEmits {
  (e: 'location-found', position: GeolocationPosition): void
  (e: 'location-error', error: GeolocationPositionError): void
  (e: 'tracking-start'): void
  (e: 'tracking-stop'): void
}

const props = withDefaults(defineProps<GeolocateButtonProps>(), {
  disabled: false,
  trackUserLocation: false,
  showAccuracyCircle: true,
  showUserLocation: true,
  options: () => ({
    enableHighAccuracy: true,
    timeout: 6000,
    maximumAge: 0
  })
})

const emit = defineEmits<GeolocateButtonEmits>()

// State
const isActive = ref(false)
const isTracking = ref(false)
const hasError = ref(false)
const isWaiting = ref(false)

// Composables
const {
  isSupported: isGeolocationSupported,
  getCurrentPosition,
  watchPosition,
  stopWatching
} = useGeolocation()

let watchId: number | null = null

// Computed
const buttonClass = computed(() => ({
  'maplibregl-ctrl-geolocate-active': isActive.value && !hasError.value,
  'maplibregl-ctrl-geolocate-active-error': isActive.value && hasError.value,
  'maplibregl-ctrl-geolocate-background': isTracking.value && !hasError.value,
  'maplibregl-ctrl-geolocate-background-error': isTracking.value && hasError.value,
  'maplibregl-ctrl-geolocate-waiting': isWaiting.value
}))

const buttonTitle = computed(() => {
  if (!isGeolocationSupported.value) return 'Геолокация не поддерживается'
  if (isActive.value) return 'Отключить геолокацию'
  return 'Найти мое местоположение'
})

// Methods
const handleClick = async () => {
  if (props.disabled || !props.map || !isGeolocationSupported.value) return

  if (isActive.value) {
    // Отключить геолокацию
    stopGeolocation()
  } else {
    // Включить геолокацию
    await startGeolocation()
  }
}

const startGeolocation = async () => {
  if (!props.map) return

  hasError.value = false
  isWaiting.value = true

  try {
    if (props.trackUserLocation) {
      // Continuous tracking
      watchId = watchPosition(
        (position) => {
          handleLocationSuccess(position)
          if (!isTracking.value) {
            isTracking.value = true
            emit('tracking-start')
          }
        },
        (error) => {
          handleLocationError(error)
        },
        props.options
      )
    } else {
      // One-time location
      const position = await getCurrentPosition(props.options)
      handleLocationSuccess(position)
    }

    isActive.value = true
  } catch (error) {
    handleLocationError(error as GeolocationPositionError)
  } finally {
    isWaiting.value = false
  }
}

const stopGeolocation = () => {
  if (watchId !== null) {
    stopWatching(watchId)
    watchId = null
  }

  isActive.value = false
  isTracking.value = false
  hasError.value = false
  isWaiting.value = false

  // Remove user location from map
  if (props.map && props.map.getSource('user-location')) {
    const source = props.map.getSource('user-location') as maplibregl.GeoJSONSource
    source.setData({
      type: 'FeatureCollection',
      features: []
    })
  }

  emit('tracking-stop')
}

const handleLocationSuccess = (position: GeolocationPosition) => {
  if (!props.map) return

  const { longitude, latitude, accuracy } = position.coords

  // Center map on user location
  props.map.setCenter([longitude, latitude])

  if (props.showUserLocation) {
    // Add/update user location marker
    const features = []

    // User position point
    features.push({
      type: 'Feature',
      geometry: {
        type: 'Point',
        coordinates: [longitude, latitude]
      },
      properties: {
        type: 'user-location'
      }
    })

    // Accuracy circle
    if (props.showAccuracyCircle && accuracy) {
      features.push({
        type: 'Feature',
        geometry: {
          type: 'Point',
          coordinates: [longitude, latitude]
        },
        properties: {
          type: 'user-location-accuracy',
          accuracy: accuracy
        }
      })
    }

    // Update map source
    if (props.map.getSource('user-location')) {
      const source = props.map.getSource('user-location') as maplibregl.GeoJSONSource
      source.setData({
        type: 'FeatureCollection',
        features: features
      })
    }
  }

  hasError.value = false
  emit('location-found', position)
}

const handleLocationError = (error: GeolocationPositionError) => {
  console.error('Geolocation error:', error)
  hasError.value = true
  isWaiting.value = false
  emit('location-error', error)
}

// Lifecycle
onUnmounted(() => {
  stopGeolocation()
})
</script>

<style scoped>
.geolocate-button {
  background-color: transparent;
  border: 0;
  box-sizing: border-box;
  cursor: pointer;
  display: block;
  height: var(--map-control-size, 29px);
  outline: none;
  padding: 0;
  width: var(--map-control-size, 29px);
  transition: background-color var(--transition, 0.2s);
}

.geolocate-button:disabled {
  cursor: not-allowed;
  opacity: 0.25;
}

.geolocate-button:not(:disabled):hover {
  background-color: var(--map-control-hover, rgba(0, 0, 0, 0.05));
}

.geolocate-button:focus {
  box-shadow: 0 0 2px 2px var(--map-control-focus, #0096ff);
}

.geolocate-button:focus:not(:focus-visible) {
  box-shadow: none;
}

/* Icons */
.geolocate-icon {
  background-position: 50%;
  background-repeat: no-repeat;
  display: block;
  height: 100%;
  width: 100%;
  background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='29' height='29' fill='%23333' viewBox='0 0 20 20'%3E%3Cpath d='M10 4C9 4 9 5 9 5v.1A5 5 0 0 0 5.1 9H5s-1 0-1 1 1 1 1 1h.1A5 5 0 0 0 9 14.9v.1s0 1 1 1 1-1 1-1v-.1a5 5 0 0 0 3.9-3.9h.1s1 0 1-1-1-1-1-1h-.1A5 5 0 0 0 11 5.1V5s0-1-1-1m0 2.5a3.5 3.5 0 1 1 0 7 3.5 3.5 0 1 1 0-7'/%3E%3Ccircle cx='10' cy='10' r='2'/%3E%3C/svg%3E");
}

/* State-specific icon colors */
.maplibregl-ctrl-geolocate-active .geolocate-icon {
  background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='29' height='29' fill='%2333b5e5' viewBox='0 0 20 20'%3E%3Cpath d='M10 4C9 4 9 5 9 5v.1A5 5 0 0 0 5.1 9H5s-1 0-1 1 1 1 1 1h.1A5 5 0 0 0 9 14.9v.1s0 1 1 1 1-1 1-1v-.1a5 5 0 0 0 3.9-3.9h.1s1 0 1-1-1-1-1-1h-.1A5 5 0 0 0 11 5.1V5s0-1-1-1m0 2.5a3.5 3.5 0 1 1 0 7 3.5 3.5 0 1 1 0-7'/%3E%3Ccircle cx='10' cy='10' r='2'/%3E%3C/svg%3E");
}

.maplibregl-ctrl-geolocate-active-error .geolocate-icon {
  background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='29' height='29' fill='%23e58978' viewBox='0 0 20 20'%3E%3Cpath d='M10 4C9 4 9 5 9 5v.1A5 5 0 0 0 5.1 9H5s-1 0-1 1 1 1 1 1h.1A5 5 0 0 0 9 14.9v.1s0 1 1 1 1-1 1-1v-.1a5 5 0 0 0 3.9-3.9h.1s1 0 1-1-1-1-1-1h-.1A5 5 0 0 0 11 5.1V5s0-1-1-1m0 2.5a3.5 3.5 0 1 1 0 7 3.5 3.5 0 1 1 0-7'/%3E%3Ccircle cx='10' cy='10' r='2'/%3E%3C/svg%3E");
}

.maplibregl-ctrl-geolocate-background .geolocate-icon {
  background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='29' height='29' fill='%2333b5e5' viewBox='0 0 20 20'%3E%3Cpath d='M10 4C9 4 9 5 9 5v.1A5 5 0 0 0 5.1 9H5s-1 0-1 1 1 1 1 1h.1A5 5 0 0 0 9 14.9v.1s0 1 1 1 1-1 1-1v-.1a5 5 0 0 0 3.9-3.9h.1s1 0 1-1-1-1-1-1h-.1A5 5 0 0 0 11 5.1V5s0-1-1-1m0 2.5a3.5 3.5 0 1 1 0 7 3.5 3.5 0 1 1 0-7'/%3E%3C/svg%3E");
}

.maplibregl-ctrl-geolocate-background-error .geolocate-icon {
  background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='29' height='29' fill='%23e54e33' viewBox='0 0 20 20'%3E%3Cpath d='M10 4C9 4 9 5 9 5v.1A5 5 0 0 0 5.1 9H5s-1 0-1 1 1 1 1 1h.1A5 5 0 0 0 9 14.9v.1s0 1 1 1 1-1 1-1v-.1a5 5 0 0 0 3.9-3.9h.1s1 0 1-1-1-1-1-1h-.1A5 5 0 0 0 11 5.1V5s0-1-1-1m0 2.5a3.5 3.5 0 1 1 0 7 3.5 3.5 0 1 1 0-7'/%3E%3C/svg%3E");
}

.maplibregl-ctrl-geolocate-waiting .geolocate-icon {
  animation: maplibregl-spin 2s linear infinite;
}

/* Disabled state */
.geolocate-button:disabled .geolocate-icon {
  background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='29' height='29' fill='%23aaa' viewBox='0 0 20 20'%3E%3Cpath d='M10 4C9 4 9 5 9 5v.1A5 5 0 0 0 5.1 9H5s-1 0-1 1 1 1 1 1h.1A5 5 0 0 0 9 14.9v.1s0 1 1 1 1-1 1-1v-.1a5 5 0 0 0 3.9-3.9h.1s1 0 1-1-1-1-1-1h-.1A5 5 0 0 0 11 5.1V5s0-1-1-1m0 2.5a3.5 3.5 0 1 1 0 7 3.5 3.5 0 1 1 0-7'/%3E%3Ccircle cx='10' cy='10' r='2'/%3E%3Cpath fill='red' d='m14 5 1 1-9 9-1-1z'/%3E%3C/svg%3E");
}

/* Spin animation for waiting state */
@keyframes maplibregl-spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Mobile Optimizations */
@media (max-width: 768px) {
  .geolocate-button {
    min-height: 44px;
    min-width: 44px;
    padding: 8px;
  }
}

/* Accessibility - Reduced Motion */
@media (prefers-reduced-motion: reduce) {
  .maplibregl-ctrl-geolocate-waiting .geolocate-icon {
    animation: none;
  }
}
</style>