/**
 * OZON Map Widget
 * 
 * Полнофункциональная система карт для веб-приложений
 * Создана на основе компонентов OZON и MapLibre GL JS
 * 
 * @version 1.0.0
 * @author OZON Map Widget Team
 * @license MIT
 */

// Стили
export * from './1-Styles'

// Конфигурация
export * from './2-Config'

// Vue Компоненты
export * from './3-Components'

// Виджеты
export * from './4-Widgets'

// Логика (Composables, Services, Utils)
export * from './5-Logic'

// Иконки
export * from './6-Icons'

// Основные типы для удобства
export type {
  // Map types
  MapInitOptions,
  MapInitState,
  UseMapInitReturn,
  
  // Control types
  MapControlsState,
  MapControlsOptions,
  UseMapControlsReturn,
  
  // Geolocation types
  GeolocationState,
  GeolocationOptions,
  UseGeolocationReturn,
  
  // Geocoding types
  GeocodingResult,
  ReverseGeocodingResult,
  GeocodingOptions,
  
  // Routing types
  Route,
  RoutePoint,
  RouteStep,
  RouteOptions,
  RoutingResult,
  
  // Widget types
  LocationSuggestion,
  PickupPoint,
  LocationInfo,
  
  // Component types
  MapContainerProps,
  MapControlsProps,
  LocationSearchProps,
  PickupPointMarkersProps,
  MapPopupProps,
  LocationInfoProps
} from './5-Logic'

// Версия библиотеки
export const VERSION = '1.0.0'

// Константы
export const OZON_COLORS = {
  primary: '#005bff',
  primaryHover: '#0050e0',
  primaryDark: '#0046cc',
  secondary: '#f5f5f5',
  success: '#28a745',
  warning: '#ffc107',
  danger: '#dc3545',
  info: '#17a2b8'
} as const

export const MAP_DEFAULTS = {
  center: [37.6176, 55.7558] as [number, number], // Moscow
  zoom: 10,
  minZoom: 1,
  maxZoom: 18,
  style: 'osm'
} as const

// Утилиты для быстрого старта
export const MapQuickStart = {
  /**
   * Создание базовой карты
   */
  createBasicMap: (container: string | HTMLElement, options?: any) => {
    return import('./5-Logic/composables/useMapInit').then(({ useMapInit }) => {
      const { initMap } = useMapInit()
      return initMap(container, { ...MAP_DEFAULTS, ...options })
    })
  },

  /**
   * Создание карты с пунктами выдачи OZON
   */
  createPickupMap: (container: string | HTMLElement, pickupPoints: any[], options?: any) => {
    return Promise.all([
      import('./5-Logic/composables/useMapInit'),
      import('./4-Widgets/PickupPointMarkers.vue')
    ]).then(([{ useMapInit }]) => {
      const { initMap } = useMapInit()
      return initMap(container, { 
        ...MAP_DEFAULTS, 
        ...options,
        onLoad: (map: any) => {
          // Здесь можно добавить маркеры пунктов выдачи
          console.log('Map loaded with pickup points:', pickupPoints)
        }
      })
    })
  },

  /**
   * Создание карты с поиском
   */
  createSearchMap: (container: string | HTMLElement, options?: any) => {
    return Promise.all([
      import('./5-Logic/composables/useMapInit'),
      import('./5-Logic/services/GeocodingService')
    ]).then(([{ useMapInit }, { geocodingService }]) => {
      const { initMap } = useMapInit()
      return initMap(container, { 
        ...MAP_DEFAULTS, 
        ...options,
        onLoad: (map: any) => {
          console.log('Map loaded with search capability')
          // Здесь можно настроить поиск
        }
      })
    })
  }
}

// Экспорт готовых конфигураций
export const MapPresets = {
  /**
   * Карта для логистики
   */
  logistics: {
    center: [37.6176, 55.7558] as [number, number],
    zoom: 10,
    minZoom: 8,
    maxZoom: 16,
    showControls: true,
    showPickupPoints: true,
    enableRouting: true
  },

  /**
   * Карта для выбора адреса
   */
  addressPicker: {
    center: [37.6176, 55.7558] as [number, number],
    zoom: 15,
    minZoom: 10,
    maxZoom: 18,
    showControls: true,
    enableSearch: true,
    enableGeolocation: true
  },

  /**
   * Карта для отображения магазинов
   */
  storeLocator: {
    center: [37.6176, 55.7558] as [number, number],
    zoom: 12,
    minZoom: 8,
    maxZoom: 16,
    showControls: true,
    showStores: true,
    enableSearch: true,
    enableFiltering: true
  },

  /**
   * Миникарта
   */
  minimap: {
    center: [37.6176, 55.7558] as [number, number],
    zoom: 10,
    minZoom: 8,
    maxZoom: 14,
    showControls: false,
    interactive: false,
    attributionControl: false
  }
} as const

// Проверка совместимости
export const BrowserSupport = {
  /**
   * Проверка поддержки WebGL
   */
  isWebGLSupported: (): boolean => {
    try {
      const canvas = document.createElement('canvas')
      const context = canvas.getContext('webgl') || canvas.getContext('experimental-webgl')
      return !!context
    } catch (e) {
      return false
    }
  },

  /**
   * Проверка поддержки геолокации
   */
  isGeolocationSupported: (): boolean => {
    return 'geolocation' in navigator
  },

  /**
   * Проверка поддержки полноэкранного режима
   */
  isFullscreenSupported: (): boolean => {
    return !!(
      document.fullscreenEnabled ||
      (document as any).webkitFullscreenEnabled ||
      (document as any).mozFullScreenEnabled ||
      (document as any).msFullscreenEnabled
    )
  },

  /**
   * Получение информации о браузере
   */
  getBrowserInfo: () => {
    const ua = navigator.userAgent
    const isChrome = /Chrome/.test(ua) && /Google Inc/.test(navigator.vendor)
    const isFirefox = /Firefox/.test(ua)
    const isSafari = /Safari/.test(ua) && /Apple Computer/.test(navigator.vendor)
    const isEdge = /Edg/.test(ua)
    const isMobile = /Mobile|Android|iPhone|iPad/.test(ua)

    return {
      isChrome,
      isFirefox,
      isSafari,
      isEdge,
      isMobile,
      userAgent: ua,
      supportsWebGL: BrowserSupport.isWebGLSupported(),
      supportsGeolocation: BrowserSupport.isGeolocationSupported(),
      supportsFullscreen: BrowserSupport.isFullscreenSupported()
    }
  }
}

// Информация о библиотеке
export const LibraryInfo = {
  name: 'OZON Map Widget',
  version: VERSION,
  description: 'Полнофункциональная система карт для веб-приложений',
  author: 'OZON Map Widget Team',
  license: 'MIT',
  repository: 'https://github.com/ozon/map-widget',
  dependencies: {
    'maplibre-gl': '^3.6.2',
    'vue': '^3.0.0'
  },
  buildDate: new Date().toISOString(),
  
  /**
   * Получение полной информации о библиотеке
   */
  getInfo: () => ({
    ...LibraryInfo,
    browserSupport: BrowserSupport.getBrowserInfo(),
    timestamp: Date.now()
  })
}

// Лог инициализации (только в development)
if (process.env.NODE_ENV === 'development') {
  console.log('🗺️ OZON Map Widget v' + VERSION + ' loaded')
  console.log('📊 Browser support:', BrowserSupport.getBrowserInfo())
}

// Default export для удобства
export default {
  VERSION,
  OZON_COLORS,
  MAP_DEFAULTS,
  MapQuickStart,
  MapPresets,
  BrowserSupport,
  LibraryInfo
}