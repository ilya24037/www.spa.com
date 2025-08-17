<template>
  <div class="map-controls-group" :class="`position-${position}`">
    <div class="maplibregl-ctrl-group ozon-map-controls">
      <!-- Zoom Controls -->
      <ZoomControls 
        v-if="showZoom"
        :map="map"
        :disabled="!isMapLoaded"
      />
      
      <!-- Compass -->
      <CompassButton
        v-if="showCompass"
        :map="map"
        :disabled="!isMapLoaded"
      />
      
      <!-- Geolocation -->
      <GeolocateButton
        v-if="showGeolocate"
        :map="map"
        :disabled="!isMapLoaded"
        :options="geolocateOptions"
        @location-found="$emit('location-found', $event)"
        @location-error="$emit('location-error', $event)"
      />
      
      <!-- Fullscreen -->
      <FullscreenButton
        v-if="showFullscreen"
        :map="map"
        :disabled="!isMapLoaded"
        @fullscreen-change="$emit('fullscreen-change', $event)"
      />
      
      <!-- Custom Controls Slot -->
      <slot name="custom-controls" :map="map" :isLoaded="isMapLoaded" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { type Map as MapLibreMap } from 'maplibre-gl'
import ZoomControls from './ZoomControls.vue'
import CompassButton from './CompassButton.vue'
import GeolocateButton from './GeolocateButton.vue'
import FullscreenButton from './FullscreenButton.vue'

export interface MapControlsProps {
  /** Instance карты MapLibre */
  map?: MapLibreMap
  /** Карта загружена */
  isMapLoaded?: boolean
  /** Позиция элементов управления */
  position?: 'top-left' | 'top-right' | 'bottom-left' | 'bottom-right'
  /** Показывать zoom кнопки */
  showZoom?: boolean
  /** Показывать компас */
  showCompass?: boolean
  /** Показывать геолокацию */
  showGeolocate?: boolean
  /** Показывать fullscreen */
  showFullscreen?: boolean
  /** Опции геолокации */
  geolocateOptions?: PositionOptions
}

export interface MapControlsEmits {
  (e: 'location-found', location: GeolocationPosition): void
  (e: 'location-error', error: GeolocationPositionError): void
  (e: 'fullscreen-change', isFullscreen: boolean): void
}

withDefaults(defineProps<MapControlsProps>(), {
  position: 'top-right',
  showZoom: true,
  showCompass: false,
  showGeolocate: true,
  showFullscreen: true,
  geolocateOptions: () => ({
    enableHighAccuracy: true,
    timeout: 6000,
    maximumAge: 0
  })
})

defineEmits<MapControlsEmits>()
</script>

<style scoped>
.map-controls-group {
  position: absolute;
  z-index: var(--z-index-map-controls, 2);
  pointer-events: none;
}

.map-controls-group.position-top-left {
  top: var(--map-control-margin, 10px);
  left: var(--map-control-margin, 10px);
}

.map-controls-group.position-top-right {
  top: var(--map-control-margin, 10px);
  right: var(--map-control-margin, 10px);
}

.map-controls-group.position-bottom-left {
  bottom: var(--map-control-margin, 10px);
  left: var(--map-control-margin, 10px);
}

.map-controls-group.position-bottom-right {
  bottom: var(--map-control-margin, 10px);
  right: var(--map-control-margin, 10px);
}

.maplibregl-ctrl-group {
  background: var(--map-control-bg, #ffffff);
  border-radius: var(--map-control-border-radius, 4px);
  box-shadow: 0 0 0 2px var(--map-control-border, rgba(0, 0, 0, 0.1));
  pointer-events: auto;
  display: flex;
  flex-direction: column;
}

.maplibregl-ctrl-group:not(:empty) {
  margin: 0;
}

/* Адаптивность для мобильных */
@media (max-width: 768px) {
  .map-controls-group.position-top-left,
  .map-controls-group.position-bottom-left {
    left: 16px;
  }
  
  .map-controls-group.position-top-right,
  .map-controls-group.position-bottom-right {
    right: 16px;
  }
  
  .map-controls-group.position-top-left,
  .map-controls-group.position-top-right {
    top: 16px;
  }
  
  .map-controls-group.position-bottom-left,
  .map-controls-group.position-bottom-right {
    bottom: 16px;
  }
}

/* Forced Colors Mode */
@media (forced-colors: active) {
  .maplibregl-ctrl-group:not(:empty) {
    box-shadow: 0 0 0 2px ButtonText;
  }
}

/* Темная тема */
@media (prefers-color-scheme: dark) {
  .maplibregl-ctrl-group {
    background: #2a2a2a;
    box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.1);
  }
}
</style>