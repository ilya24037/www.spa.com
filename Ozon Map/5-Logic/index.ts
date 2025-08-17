/**
 * OZON Map Logic
 * 
 * Логический слой для работы с картой:
 * - Composables для Vue компонентов
 * - Сервисы для работы с внешними API
 * - Утилиты и хелперы
 */

// Composables
export { useMapInit, useOzonMapStyle, mapPresets } from './composables/useMapInit'
export type { 
  MapInitOptions, 
  MapInitState, 
  MapInitMethods, 
  UseMapInitReturn 
} from './composables/useMapInit'

export { 
  useMapControls, 
  useMapKeyboardControls, 
  useMapInteraction 
} from './composables/useMapControls'
export type { 
  MapControlsState, 
  MapControlsOptions, 
  MapControlsMethods, 
  UseMapControlsReturn 
} from './composables/useMapControls'

export { 
  useGeolocation, 
  useDistanceCalculator, 
  useGeolocationPermissions 
} from './composables/useGeolocation'
export type { 
  GeolocationState, 
  GeolocationOptions, 
  GeolocationMethods, 
  UseGeolocationReturn 
} from './composables/useGeolocation'

// Services
export { 
  GeocodingService, 
  NominatimProvider, 
  YandexProvider, 
  geocodingService 
} from './services/GeocodingService'
export type { 
  GeocodingResult, 
  ReverseGeocodingResult, 
  GeocodingOptions, 
  GeocodingProvider 
} from './services/GeocodingService'

export { 
  RoutingService, 
  OSRMProvider, 
  GraphHopperProvider, 
  routingService 
} from './services/RoutingService'
export type { 
  Route, 
  RoutePoint, 
  RouteStep, 
  RouteOptions, 
  RoutingResult, 
  RoutingProvider 
} from './services/RoutingService'

// Utils
export * from './utils/mapUtils'
export * from './utils/coordinateUtils'