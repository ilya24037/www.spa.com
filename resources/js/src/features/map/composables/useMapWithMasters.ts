/**
 * useMapWithMasters - заглушка для обратной совместимости
 * Реальная функциональность карты в новой архитектуре Core + Plugins
 */

import { ref, computed } from 'vue'
import type { Ref, ComputedRef } from 'vue'

// Минимальные типы для совместимости
export interface Coordinates {
  lat: number
  lng: number
}

export interface MapMarker {
  id: string
  coordinates: Coordinates
  title?: string
  description?: string
  data?: any
}

interface Master {
  id: number
  latitude: number
  longitude: number
  name: string
  [key: string]: any
}

/**
 * Минимальная заглушка для совместимости со старым кодом
 * Все методы возвращают пустые значения или noop функции
 */
export function useMapWithMasters() {
  // Состояние
  const mapIsReady = ref(true)
  const mapIsLoading = ref(false)
  const mapError = ref<string | null>(null)
  const mapMarkers = computed<MapMarker[]>(() => [])
  const selectedMarker = ref<MapMarker | null>(null)
  const mapCenter = ref<Coordinates>({ lat: 58.0105, lng: 56.2502 })
  const mapZoom = ref(14)
  
  // Пустые методы для совместимости
  const initializeMap = async () => {}
  const updateMapMarkers = (masters: Master[]) => {}
  const handleMarkerClick = (marker: MapMarker) => {}
  const handleClusterClick = (markers: MapMarker[]) => {}
  const centerOnMaster = (master: Master) => {}
  const clearMapMarkers = () => {}
  
  return {
    // Состояние
    mapIsReady,
    mapIsLoading,
    mapError,
    mapMarkers,
    selectedMarker,
    mapCenter,
    mapZoom,
    
    // Методы
    initializeMap,
    updateMapMarkers,
    handleMarkerClick,
    handleClusterClick,
    centerOnMaster,
    clearMapMarkers
  }
}