import { ref, onUnmounted, type Ref } from 'vue'
import { Master } from '@/src/entities/master/model/types'

export interface Location {
  address: string
  coordinates: [number, number]
  city?: string
  district?: string
}

export interface MapConfig {
  height?: number
  width?: string
  center?: [number, number]
  zoom?: number
  showSearch?: boolean
  showLocationInfo?: boolean
  showControls?: boolean
  enableMarkers?: boolean
  apiKey?: string
}

export interface MapEvents {
  onReady?: (map: any) => void
  onAddressSelect?: (location: Location) => void
  onMarkerClick?: (master: Master) => void
  onMapClick?: (event: any) => void
  onError?: (error: string) => void
}

/**
 * Composable для работы с Yandex Maps
 * Предоставляет удобный API для интеграции карт в компоненты
 */
export function useYandexMaps(config: MapConfig = {}, events: MapEvents = {}) {
  // Реактивное состояние
  const mapRef = ref<any>(null)
  const isMapReady = ref(false)
  const mapError = ref<string | null>(null)
  const selectedLocation = ref<Location | null>(null)
  const markers = ref<Master[]>([])

  // Конфигурация по умолчанию
  const defaultConfig: Required<MapConfig> = {
    height: 400,
    width: '100%',
    center: [55.7558, 37.6173], // Москва
    zoom: 10,
    showSearch: true,
    showLocationInfo: false,
    showControls: true,
    enableMarkers: true,
    apiKey: '23ff8acc-835f-4e99-8b19-d33c5d346e18'
  }

  const mapConfig = { ...defaultConfig, ...config }

  // Обработчики событий
  const handleMapReady = (map: any) => {
    mapRef.value = map
    isMapReady.value = true
    mapError.value = null
    events.onReady?.(map)
  }

  const handleAddressSelect = (location: Location) => {
    selectedLocation.value = location
    events.onAddressSelect?.(location)
  }

  const handleMarkerClick = (master: Master) => {
    events.onMarkerClick?.(master)
  }

  const handleMapClick = (event: any) => {
    events.onMapClick?.(event)
  }

  const handleMapError = (error: string) => {
    mapError.value = error
    isMapReady.value = false
    events.onError?.(error)
  }

  // Публичные методы для управления картой
  const setCenter = (coordinates: [number, number], zoom?: number) => {
    if (mapRef.value) {
      mapRef.value.setCenter(coordinates, zoom)
    }
  }

  const addMarker = (coordinates: [number, number], properties = {}, options = {}) => {
    if (mapRef.value) {
      return mapRef.value.addMarker(coordinates, properties, options)
    }
    return null
  }

  const clearSelectedLocation = () => {
    selectedLocation.value = null
    if (mapRef.value) {
      mapRef.value.clearSelectedLocation()
    }
  }

  const getUserLocation = async (): Promise<[number, number] | null> => {
    if (mapRef.value) {
      return await mapRef.value.getUserLocation()
    }
    return null
  }

  const searchAddress = async (query: string) => {
    if (mapRef.value && mapRef.value.searchControl) {
      return await mapRef.value.searchControl.search(query)
    }
    return []
  }

  // Вспомогательные функции
  const isValidCoordinates = (coordinates: any): coordinates is [number, number] => {
    return Array.isArray(coordinates) &&
           coordinates.length === 2 &&
           typeof coordinates[0] === 'number' &&
           typeof coordinates[1] === 'number' &&
           coordinates[0] >= -90 && coordinates[0] <= 90 &&
           coordinates[1] >= -180 && coordinates[1] <= 180
  }

  const formatCoordinates = (coordinates: [number, number], precision = 4): string => {
    if (!isValidCoordinates(coordinates)) return 'Н/Д'
    return `${coordinates[0].toFixed(precision)}, ${coordinates[1].toFixed(precision)}`
  }

  const calculateDistance = (coord1: [number, number], coord2: [number, number]): number => {
    if (!isValidCoordinates(coord1) || !isValidCoordinates(coord2)) return 0

    const R = 6371 // Радиус Земли в км
    const dLat = (coord2[0] - coord1[0]) * Math.PI / 180
    const dLon = (coord2[1] - coord1[1]) * Math.PI / 180
    const lat1 = coord1[0] * Math.PI / 180
    const lat2 = coord2[0] * Math.PI / 180

    const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
              Math.sin(dLon/2) * Math.sin(dLon/2) * Math.cos(lat1) * Math.cos(lat2)
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a))
    
    return R * c
  }

  const filterMastersByDistance = (masters: Master[], center: [number, number], maxDistance: number): Master[] => {
    return masters.filter(master => {
      const lat = master.lat || master.latitude
      const lng = master.lng || master.longitude
      
      if (!lat || !lng) return false
      
      const distance = calculateDistance(center, [lat, lng])
      return distance <= maxDistance
    })
  }

  // Геокодирование (обратное)
  const getAddressFromCoordinates = async (coordinates: [number, number]): Promise<string | null> => {
    if (!isValidCoordinates(coordinates) || !window.ymaps) return null

    try {
      const result = await window.ymaps.geocode(coordinates, { results: 1 })
      const firstGeoObject = result.geoObjects.get(0)
      return firstGeoObject ? firstGeoObject.getAddressLine() : null
    } catch (error) {
      console.error('Ошибка геокодирования:', error)
      return null
    }
  }

  // Очистка ресурсов
  onUnmounted(() => {
    mapRef.value = null
    isMapReady.value = false
    selectedLocation.value = null
    markers.value = []
  })

  return {
    // Состояние
    mapRef,
    isMapReady,
    mapError,
    selectedLocation,
    markers,
    mapConfig,

    // Обработчики событий для компонента
    handleMapReady,
    handleAddressSelect,
    handleMarkerClick,
    handleMapClick,
    handleMapError,

    // Методы управления картой
    setCenter,
    addMarker,
    clearSelectedLocation,
    getUserLocation,
    searchAddress,

    // Утилиты
    isValidCoordinates,
    formatCoordinates,
    calculateDistance,
    filterMastersByDistance,
    getAddressFromCoordinates,
  }
}

/**
 * Предустановленные конфигурации для различных случаев использования
 */
export const mapPresets = {
  // Для подачи объявления (с поиском)
  adCreation: {
    height: 400,
    showSearch: true,
    showLocationInfo: true,
    enableMarkers: false,
    zoom: 12
  },

  // Для каталога мастеров (с метками)
  mastersCatalog: {
    height: 500,
    showSearch: true,
    showLocationInfo: false,
    enableMarkers: true,
    zoom: 11
  },

  // Компактная карта для профиля
  profileCompact: {
    height: 300,
    width: '100%',
    showSearch: false,
    showLocationInfo: false,
    showControls: false,
    enableMarkers: true,
    zoom: 14
  },

  // Полнофункциональная карта
  fullFeatured: {
    height: 600,
    showSearch: true,
    showLocationInfo: true,
    enableMarkers: true,
    showControls: true,
    zoom: 10
  }
}

/**
 * Хук для простого использования предустановок
 */
export function useYandexMapsPreset(presetName: keyof typeof mapPresets, events: MapEvents = {}) {
  const preset = mapPresets[presetName]
  return useYandexMaps(preset, events)
}

/**
 * Хук для работы только с поиском (без карты)
 */
export function useYandexSearch() {
  const isLoading = ref(false)
  const searchResults = ref<Location[]>([])
  const searchError = ref<string | null>(null)

  const search = async (query: string): Promise<Location[]> => {
    if (!query.trim() || isLoading.value || !window.ymaps) return []

    try {
      isLoading.value = true
      searchError.value = null

      const result = await window.ymaps.geocode(query, { results: 10 })
      const results: Location[] = []

      result.geoObjects.each((geoObject: any) => {
        results.push({
          address: geoObject.getAddressLine(),
          coordinates: geoObject.geometry.getCoordinates()
        })
      })

      searchResults.value = results
      return results

    } catch (error) {
      searchError.value = error instanceof Error ? error.message : 'Ошибка поиска'
      console.error('Ошибка поиска:', error)
      return []
    } finally {
      isLoading.value = false
    }
  }

  const clearResults = () => {
    searchResults.value = []
    searchError.value = null
  }

  return {
    isLoading,
    searchResults,
    searchError,
    search,
    clearResults
  }
}