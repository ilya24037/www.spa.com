/**
 * TypeScript типы для Yandex Maps интеграции
 */

// Базовые типы для координат и географических данных
export type Coordinates = [number, number] // [latitude, longitude]

export interface GeoBounds {
  southwest: Coordinates
  northeast: Coordinates
}

export interface GeoLocation {
  address: string
  coordinates: Coordinates
  city?: string
  district?: string
  country?: string
  postalCode?: string
  precision?: string
  kind?: string
}

// Типы для мастеров и бизнес-сущностей
export interface Master {
  id: number | string
  name: string
  description?: string
  photo?: string
  
  // Координаты (поддерживаем разные варианты)
  lat?: number
  lng?: number
  latitude?: number
  longitude?: number
  coordinates?: Coordinates
  
  // Дополнительные данные
  rating?: number
  services?: string[]
  workingHours?: string
  phone?: string
  email?: string
  
  // Адресные данные
  address?: string
  city?: string
  district?: string
}

export interface Ad {
  id: number | string
  title: string
  description?: string
  
  // Местоположение
  location?: GeoLocation
  address?: string
  
  // Фото и медиа
  photos?: string[]
  
  // Цена и условия
  price?: number
  currency?: string
  
  // Дополнительные данные
  category?: string
  subcategory?: string
  tags?: string[]
  
  // Метаданные
  createdAt?: string
  updatedAt?: string
  status?: string
}

// Конфигурация карты
export interface MapConfig {
  height?: number
  width?: string
  center?: Coordinates
  zoom?: number
  minZoom?: number
  maxZoom?: number
  
  // Функциональность
  showSearch?: boolean
  showLocationInfo?: boolean
  showControls?: boolean
  enableMarkers?: boolean
  enableClustering?: boolean
  
  // Стили и внешний вид
  theme?: 'light' | 'dark'
  customStyles?: any
  
  // API и безопасность
  apiKey?: string
}

export interface MapMarkerConfig {
  coordinates: Coordinates
  properties?: {
    balloonContent?: string
    hintContent?: string
    iconContent?: string
    [key: string]: any
  }
  options?: {
    preset?: string
    iconColor?: string
    draggable?: boolean
    visible?: boolean
    zIndex?: number
    [key: string]: any
  }
}

export interface SearchConfig {
  placeholder?: string
  maxResults?: number
  searchDelay?: number
  enableAutoComplete?: boolean
  fitResultBounds?: boolean
  addResultMarker?: boolean
  searchTypes?: string[]
}

export interface ClusterConfig {
  gridSize?: number
  minClusterSize?: number
  maxZoom?: number
  styles?: Array<{
    textColor?: string
    textSize?: number
    backgroundColor?: string
    iconUrl?: string
  }>
}

// События карты
export interface MapEvents {
  onReady?: (map: any) => void
  onAddressSelect?: (location: GeoLocation) => void
  onMarkerClick?: (master: Master, marker?: any) => void
  onMapClick?: (event: { coordinates: Coordinates, [key: string]: any }) => void
  onZoomChange?: (zoom: number) => void
  onBoundsChange?: (bounds: GeoBounds) => void
  onError?: (error: string | Error) => void
}

// Результаты поиска
export interface SearchResult {
  displayName: string
  address: string
  coordinates: Coordinates
  bounds?: GeoBounds
  kind?: string
  precision?: string
  description?: string
  
  // Дополнительные данные от провайдера
  geoObject?: any
  data?: Record<string, any>
}

export interface GeocodeResult extends SearchResult {
  components?: {
    country?: string
    province?: string
    area?: string
    locality?: string
    district?: string
    street?: string
    house?: string
  }
}

// Состояние карты
export interface MapState {
  isLoading: boolean
  isReady: boolean
  error: string | null
  center: Coordinates
  zoom: number
  bounds?: GeoBounds
}

// Конфигурации для различных сценариев использования
export interface MapPresets {
  adCreation: Partial<MapConfig>
  mastersCatalog: Partial<MapConfig>
  profileCompact: Partial<MapConfig>
  fullFeatured: Partial<MapConfig>
}

// Фильтры для поиска мастеров
export interface MasterFilters {
  location?: {
    center: Coordinates
    radius: number // в километрах
  }
  services?: string[]
  rating?: {
    min: number
    max: number
  }
  priceRange?: {
    min: number
    max: number
  }
  availability?: {
    date?: string
    timeSlots?: string[]
  }
}

// Результат фильтрации
export interface FilteredMasters {
  masters: Master[]
  total: number
  center?: Coordinates
  bounds?: GeoBounds
}

// Утилиты и хелперы
export interface MapUtils {
  isValidCoordinates: (coords: any) => coords is Coordinates
  formatCoordinates: (coords: Coordinates, precision?: number) => string
  calculateDistance: (coord1: Coordinates, coord2: Coordinates) => number
  calculateBounds: (coordinates: Coordinates[], padding?: number) => GeoBounds
  filterByDistance: (items: Master[], center: Coordinates, maxDistance: number) => Master[]
}

// Контекст для провайдера
export interface YandexMapsContext {
  isApiLoaded: boolean
  apiVersion: string
  config: MapConfig
  utils: MapUtils
  createMap: (containerId: string, config: MapConfig) => Promise<any>
  geocode: (query: string) => Promise<GeocodeResult[]>
  reverseGeocode: (coordinates: Coordinates) => Promise<string>
}

// Ошибки
export class YandexMapsError extends Error {
  constructor(
    message: string,
    public code?: string,
    public details?: any
  ) {
    super(message)
    this.name = 'YandexMapsError'
  }
}

export class YandexMapsApiError extends YandexMapsError {
  constructor(message: string, details?: any) {
    super(message, 'API_ERROR', details)
  }
}

export class YandexMapsGeocodeError extends YandexMapsError {
  constructor(message: string, details?: any) {
    super(message, 'GEOCODE_ERROR', details)
  }
}

// Экспорт всех типов для удобства
// export * from './YandexMapsTypes' - удален вместе с YandexMapNative

// Дополнительные утилиты типов
export type MapEventHandler<T = any> = (event: T) => void
export type AsyncMapEventHandler<T = any> = (event: T) => Promise<void>

export type OptionalMapConfig = Partial<MapConfig>
export type RequiredMapConfig = Required<MapConfig>

export type MasterWithCoordinates = Master & {
  coordinates: Coordinates
}

export type LocationSearchMode = 'geocode' | 'suggest' | 'combined'

// Тип-гарды для проверки типов
export function isMaster(item: any): item is Master {
  return item && 
         typeof item === 'object' && 
         (typeof item.id === 'string' || typeof item.id === 'number') &&
         typeof item.name === 'string'
}

export function isValidCoordinates(coords: any): coords is Coordinates {
  return Array.isArray(coords) &&
         coords.length === 2 &&
         typeof coords[0] === 'number' &&
         typeof coords[1] === 'number' &&
         coords[0] >= -90 && coords[0] <= 90 &&
         coords[1] >= -180 && coords[1] <= 180
}

export function hasCoordinates(master: Master): master is MasterWithCoordinates {
  const lat = master.lat || master.latitude
  const lng = master.lng || master.longitude
  
  return typeof lat === 'number' && 
         typeof lng === 'number' && 
         isValidCoordinates([lat, lng])
}