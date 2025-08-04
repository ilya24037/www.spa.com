// MasterCard.types.ts
export interface Service {
  id: number
  name: string
  description?: string
  price?: number
  duration?: number
}

export interface Master {
  id: number
  name: string
  display_name?: string
  avatar?: string
  main_photo?: string
  photo?: string
  specialty?: string
  price_from?: number
  rating?: number
  reviews_count?: number
  is_premium?: boolean
  is_verified?: boolean
  is_online?: boolean
  is_available_now?: boolean
  is_favorite?: boolean
  show_contacts?: boolean
  phone?: string
  district?: string
  metro_station?: string
  services?: Service[]
}

export interface MasterCardProps {
  master: Master
}

export interface MasterCardEmits {
  favoriteToggled: [masterId: number, isFavorite: boolean]
  phoneRequested: [masterId: number]
  bookingRequested: [masterId: number]
  profileVisited: [masterId: number]
}

// CSS классы типизированы для лучшей поддержки в IDE
export interface MasterCardStyles {
  CARD_CLASSES: string
  BADGES_CONTAINER_CLASSES: string
  PREMIUM_BADGE_CLASSES: string
  VERIFIED_BADGE_CLASSES: string
  VERIFIED_ICON_CLASSES: string
  FAVORITE_BUTTON_CLASSES: string
  FAVORITE_ICON_CLASSES: string
  IMAGE_CONTAINER_CLASSES: string
  IMAGE_CLASSES: string
  ONLINE_STATUS_CLASSES: string
  ONLINE_INDICATOR_CLASSES: string
  CONTENT_CLASSES: string
  PRICE_RATING_CONTAINER_CLASSES: string
  PRICE_CLASSES: string
  PRICE_UNIT_CLASSES: string
  RATING_CONTAINER_CLASSES: string
  STAR_ICON_CLASSES: string
  RATING_VALUE_CLASSES: string
  RATING_COUNT_CLASSES: string
  NAME_CLASSES: string
  SERVICES_CLASSES: string
  LOCATION_CONTAINER_CLASSES: string
  LOCATION_ICON_CLASSES: string
  ACTIONS_CONTAINER_CLASSES: string
  PHONE_BUTTON_CLASSES: string
  PHONE_ICON_CLASSES: string
  BOOKING_BUTTON_CLASSES: string
  BOOKING_ICON_CLASSES: string
}

// Типы для внутренней логики компонента
export interface MasterCardState {
  imageError: boolean
}

export interface MasterCardComputedProperties {
  isFavorite: boolean
  masterPhoto: string
}

// API Response типы
export interface FavoriteToggleResponse {
  success: boolean
  is_favorite: boolean
  message?: string
}

export interface ApiError {
  message: string
  errors?: Record<string, string[]>
}