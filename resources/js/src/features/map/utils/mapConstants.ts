/**
 * Константы карты
 * Размер: 30 строк
 */

export const DEFAULT_API_KEY = ''

export const PERM_CENTER = {
  lat: 58.0105,
  lng: 56.2502
}

export const DEFAULT_ZOOM = 14

export const MOBILE_BREAKPOINT = 768

export const MAP_CONFIG_DEFAULTS = {
  controls: [],
  behaviors: ['default']
}

export const CLUSTER_DEFAULTS = {
  preset: 'islands#invertedVioletClusterIcons',
  clusterDisableClickZoom: true,
  gridSize: 64
}

export const MARKER_PRESETS = {
  default: 'islands#blueIcon',
  red: 'islands#redIcon',
  green: 'islands#greenIcon',
  yellow: 'islands#yellowIcon'
}

export const GEOLOCATION_OPTIONS = {
  enableHighAccuracy: true,
  timeout: 10000,
  maximumAge: 300000
}