/**
 * Константы для карты
 */

// API ключ (должен быть в .env)
export const DEFAULT_API_KEY = import.meta.env.VITE_YANDEX_MAPS_API_KEY || ''

// Центр по умолчанию (Пермь)
export const PERM_CENTER = { lat: 58.0105, lng: 56.2502 }

// Уровень зума по умолчанию
export const DEFAULT_ZOOM = 14

// Настройки карты по умолчанию
export const DEFAULT_MAP_CONFIG = {
  controls: [],
  behaviors: ['default', 'scrollZoom']
}

// Пресеты для маркеров
export const MARKER_PRESETS = {
  DEFAULT: 'islands#blueIcon',
  SELECTED: 'islands#redIcon',
  MASTER: 'islands#violetIcon',
  USER: 'islands#greenIcon'
}

// Цвета маркеров
export const MARKER_COLORS = {
  DEFAULT: '#0095b6',
  SELECTED: '#ff0000',
  MASTER: '#b51eff',
  USER: '#56db40'
}