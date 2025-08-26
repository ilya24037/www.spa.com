// Интерфейс для маркера на карте
export interface MapMarker {
  id: string | number
  lat: number
  lng: number
  title?: string
  description?: string
  icon?: string
  data?: any // Дополнительные данные (например, мастер)
}

// Интерфейс для координат
export interface Coordinates {
  lat: number
  lng: number
}

// Интерфейс для границ карты
export interface MapBounds {
  sw_lat: number
  sw_lng: number
  ne_lat: number
  ne_lng: number
}

// Опции кластеризатора
export interface ClustererOptions {
  preset?: string
  groupByCoordinates?: boolean
  clusterDisableClickZoom?: boolean
  clusterHideIconOnBalloonOpen?: boolean
  geoObjectHideIconOnBalloonOpen?: boolean
  clusterBalloonContentLayout?: string
  clusterBalloonPanelMaxMapArea?: number
  clusterBalloonContentLayoutWidth?: number
  clusterBalloonContentLayoutHeight?: number
  clusterBalloonPagerSize?: number
  clusterNumbers?: number[]
  clusterMaxZoom?: number
  gridSize?: number
}

// Режимы работы карты
export type MapMode = 'single' | 'multiple'

// Конфигурация карты
export interface MapConfig {
  apiKey: string
  center: Coordinates
  zoom: number
  minZoom?: number
  maxZoom?: number
  controls?: string[]
  behaviors?: string[]
  mode?: MapMode
}