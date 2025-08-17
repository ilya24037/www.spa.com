/**
 * Composable для инициализации и управления картой MapLibre
 */

import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import type { Ref } from 'vue'
import type { Map as MapLibreMap, MapOptions, LngLatBoundsLike } from 'maplibre-gl'

export interface MapInitOptions extends Partial<MapOptions> {
  /** Контейнер для карты */
  container?: string | HTMLElement
  /** Стиль карты */
  style?: string | object
  /** Центр карты */
  center?: [number, number]
  /** Уровень зума */
  zoom?: number
  /** Минимальный зум */
  minZoom?: number
  /** Максимальный зум */
  maxZoom?: number
  /** Границы карты */
  maxBounds?: LngLatBoundsLike
  /** Показать элементы управления */
  showControls?: boolean
  /** Включить навигацию */
  interactive?: boolean
  /** Показать атрибуцию */
  attributionControl?: boolean
}

export interface MapInitState {
  /** Instance карты */
  map: Ref<MapLibreMap | null>
  /** Карта загружена */
  isLoaded: Ref<boolean>
  /** Карта инициализирована */
  isInitialized: Ref<boolean>
  /** Ошибка инициализации */
  error: Ref<string | null>
  /** Состояние загрузки */
  isLoading: Ref<boolean>
  /** Текущий зум */
  currentZoom: Ref<number>
  /** Текущий центр */
  currentCenter: Ref<[number, number]>
  /** Текущие границы */
  currentBounds: Ref<LngLatBoundsLike | null>
}

export interface MapInitMethods {
  /** Инициализировать карту */
  initMap: (container: string | HTMLElement, options?: MapInitOptions) => Promise<void>
  /** Уничтожить карту */
  destroyMap: () => void
  /** Переместить карту */
  flyTo: (center: [number, number], zoom?: number) => void
  /** Установить границы */
  fitBounds: (bounds: LngLatBoundsLike, options?: any) => void
  /** Изменить размер */
  resize: () => void
  /** Получить screenshot */
  getCanvas: () => HTMLCanvasElement | null
}

export interface UseMapInitReturn extends MapInitState, MapInitMethods {}

/**
 * Composable для работы с MapLibre
 */
export function useMapInit(initialOptions: MapInitOptions = {}): UseMapInitReturn {
  // State
  const map = ref<MapLibreMap | null>(null)
  const isLoaded = ref(false)
  const isInitialized = ref(false)
  const error = ref<string | null>(null)
  const isLoading = ref(false)
  const currentZoom = ref(initialOptions.zoom || 10)
  const currentCenter = ref<[number, number]>(initialOptions.center || [37.6176, 55.7558]) // Moscow
  const currentBounds = ref<LngLatBoundsLike | null>(null)

  // Default options
  const defaultOptions: MapInitOptions = {
    center: [37.6176, 55.7558], // Moscow
    zoom: 10,
    minZoom: 1,
    maxZoom: 18,
    interactive: true,
    attributionControl: true,
    showControls: false,
    style: {
      version: 8,
      sources: {
        'osm': {
          type: 'raster',
          tiles: ['https://tile.openstreetmap.org/{z}/{x}/{y}.png'],
          tileSize: 256,
          attribution: '© OpenStreetMap contributors'
        }
      },
      layers: [
        {
          id: 'osm',
          type: 'raster',
          source: 'osm'
        }
      ]
    }
  }

  // Methods
  const initMap = async (container: string | HTMLElement, options: MapInitOptions = {}) => {
    try {
      isLoading.value = true
      error.value = null

      // Динамический импорт MapLibre
      const { Map } = await import('maplibre-gl')

      // Объединение опций
      const mapOptions = {
        ...defaultOptions,
        ...initialOptions,
        ...options,
        container
      }

      // Создание карты
      const mapInstance = new Map(mapOptions)

      // Обработчики событий
      mapInstance.on('load', () => {
        isLoaded.value = true
        isInitialized.value = true
        isLoading.value = false
        updateMapState()
      })

      mapInstance.on('error', (e) => {
        console.error('Map error:', e)
        error.value = e.error?.message || 'Ошибка загрузки карты'
        isLoading.value = false
      })

      mapInstance.on('moveend', updateMapState)
      mapInstance.on('zoomend', updateMapState)

      map.value = mapInstance

    } catch (err) {
      console.error('Failed to initialize map:', err)
      error.value = err instanceof Error ? err.message : 'Ошибка инициализации карты'
      isLoading.value = false
    }
  }

  const destroyMap = () => {
    if (map.value) {
      map.value.remove()
      map.value = null
    }
    
    isLoaded.value = false
    isInitialized.value = false
    error.value = null
    isLoading.value = false
  }

  const updateMapState = () => {
    if (!map.value) return

    currentZoom.value = map.value.getZoom()
    const center = map.value.getCenter()
    currentCenter.value = [center.lng, center.lat]
    currentBounds.value = map.value.getBounds()
  }

  const flyTo = (center: [number, number], zoom?: number) => {
    if (!map.value) return

    map.value.flyTo({
      center,
      zoom: zoom || currentZoom.value,
      duration: 1000
    })
  }

  const fitBounds = (bounds: LngLatBoundsLike, options: any = {}) => {
    if (!map.value) return

    const defaultFitOptions = {
      padding: 50,
      duration: 1000,
      ...options
    }

    map.value.fitBounds(bounds, defaultFitOptions)
  }

  const resize = () => {
    if (!map.value) return
    
    nextTick(() => {
      map.value!.resize()
    })
  }

  const getCanvas = (): HTMLCanvasElement | null => {
    return map.value?.getCanvas() || null
  }

  // Cleanup
  onUnmounted(() => {
    destroyMap()
  })

  return {
    // State
    map,
    isLoaded,
    isInitialized,
    error,
    isLoading,
    currentZoom,
    currentCenter,
    currentBounds,
    
    // Methods
    initMap,
    destroyMap,
    flyTo,
    fitBounds,
    resize,
    getCanvas
  }
}

/**
 * Хук для создания стиля карты OZON
 */
export function useOzonMapStyle() {
  return {
    version: 8,
    name: 'OZON Map Style',
    metadata: {
      'mapbox:autocomposite': false,
      'mapbox:type': 'template'
    },
    sources: {
      'openmaptiles': {
        type: 'vector',
        url: 'https://api.maptiler.com/maps/streets-v2/style.json?key=YOUR_API_KEY'
      }
    },
    sprite: 'https://api.maptiler.com/maps/streets-v2/sprite',
    glyphs: 'https://api.maptiler.com/fonts/{fontstack}/{range}.pbf?key=YOUR_API_KEY',
    layers: [
      {
        id: 'background',
        type: 'background',
        paint: {
          'background-color': 'hsl(212, 88%, 95%)'
        }
      },
      {
        id: 'water',
        type: 'fill',
        source: 'openmaptiles',
        'source-layer': 'water',
        paint: {
          'fill-color': 'hsl(212, 88%, 81%)',
          'fill-opacity': 0.8
        }
      },
      {
        id: 'park',
        type: 'fill',
        source: 'openmaptiles',
        'source-layer': 'park',
        paint: {
          'fill-color': 'hsl(93, 62%, 80%)',
          'fill-opacity': 0.8
        }
      },
      {
        id: 'roads',
        type: 'line',
        source: 'openmaptiles',
        'source-layer': 'transportation',
        paint: {
          'line-color': 'hsl(0, 0%, 100%)',
          'line-width': {
            base: 1.55,
            stops: [[4, 0.25], [20, 30]]
          }
        }
      },
      {
        id: 'buildings',
        type: 'fill-extrusion',
        source: 'openmaptiles',
        'source-layer': 'building',
        paint: {
          'fill-extrusion-color': 'hsl(0, 0%, 85%)',
          'fill-extrusion-height': {
            property: 'render_height',
            type: 'identity'
          },
          'fill-extrusion-base': {
            property: 'render_min_height',
            type: 'identity'
          },
          'fill-extrusion-opacity': 0.6
        }
      },
      {
        id: 'place-labels',
        type: 'symbol',
        source: 'openmaptiles',
        'source-layer': 'place',
        layout: {
          'text-field': '{name}',
          'text-font': ['Open Sans Bold', 'Arial Unicode MS Bold'],
          'text-size': {
            stops: [[3, 12], [8, 16]]
          }
        },
        paint: {
          'text-color': 'hsl(0, 0%, 20%)',
          'text-halo-color': 'hsl(0, 0%, 100%)',
          'text-halo-width': 1
        }
      }
    ]
  }
}

/**
 * Предустановленные опции для разных типов карт
 */
export const mapPresets = {
  /** Карта для логистики */
  logistics: {
    center: [37.6176, 55.7558] as [number, number],
    zoom: 10,
    minZoom: 8,
    maxZoom: 16,
    interactive: true
  },

  /** Карта для выбора адреса */
  addressPicker: {
    center: [37.6176, 55.7558] as [number, number],
    zoom: 15,
    minZoom: 10,
    maxZoom: 18,
    interactive: true
  },

  /** Карта для просмотра маршрута */
  routeViewer: {
    center: [37.6176, 55.7558] as [number, number],
    zoom: 12,
    minZoom: 8,
    maxZoom: 16,
    interactive: false
  },

  /** Миникарта */
  minimap: {
    center: [37.6176, 55.7558] as [number, number],
    zoom: 8,
    minZoom: 6,
    maxZoom: 12,
    interactive: false,
    attributionControl: false,
    showControls: false
  }
} as const