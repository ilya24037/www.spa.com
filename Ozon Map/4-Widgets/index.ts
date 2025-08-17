/**
 * OZON Map Widgets
 * 
 * Коллекция готовых виджетов для работы с картой:
 * - LocationSearch - поиск адресов и мест
 * - PickupPointMarkers - отображение пунктов выдачи на карте
 * - MapPopup - всплывающие окна на карте
 * - LocationInfo - детальная информация о локации
 */

export { default as LocationSearch } from './LocationSearch.vue'
export { default as PickupPointMarkers } from './PickupPointMarkers.vue'
export { default as MapPopup } from './MapPopup.vue'
export { default as LocationInfo } from './LocationInfo.vue'

// Types
export type {
  LocationSuggestion,
  LocationSearchProps,
  LocationSearchEmits
} from './LocationSearch.vue'

export type {
  PickupPoint,
  PickupFilter,
  PickupPointMarkersProps,
  PickupPointMarkersEmits
} from './PickupPointMarkers.vue'

export type {
  MapPopupProps,
  MapPopupEmits
} from './MapPopup.vue'

export type {
  LocationInfo,
  CustomSection,
  LocationInfoProps,
  LocationInfoEmits
} from './LocationInfo.vue'