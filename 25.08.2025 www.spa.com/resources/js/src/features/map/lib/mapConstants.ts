/**
 * Константы для карты
 */

// Координаты Перми по умолчанию
export const PERM_CENTER = { lat: 58.0105, lng: 56.2502 }

// Настройки зума
export const DEFAULT_ZOOM = 15
export const MIN_ZOOM = 10
export const MAX_ZOOM = 18

// API ключ по умолчанию
export const DEFAULT_API_KEY = '23ff8acc-835f-4e99-8b19-d33c5d346e18'

// Таймауты
export const BOUNDS_CHANGE_DEBOUNCE = 500
export const ADDRESS_UPDATE_DEBOUNCE = 1000
export const GEOLOCATION_TIMEOUT = 5000

// Размеры
export const DEFAULT_MAP_HEIGHT = 400
export const CLUSTER_GRID_SIZE = 80
export const MIN_MARKERS_FOR_CLUSTER = 2

// Стили маркеров
export const MARKER_PRESETS = {
  gold: 'islands#goldIcon',
  green: 'islands#greenIcon', 
  blue: 'islands#blueIcon',
  gray: 'islands#grayIcon',
  red: 'islands#redIcon'
} as const

// Контролы карты
export const MAP_CONTROLS = {
  single: ['zoomControl', 'typeSelector'],
  multiple: ['zoomControl', 'searchControl']
} as const