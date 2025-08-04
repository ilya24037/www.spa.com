// AdCard.types.ts
export interface AdImage {
  id?: number
  url?: string
  path?: string
  thumb_url?: string
  alt?: string
}

export interface Ad {
  id: number
  title?: string
  name?: string
  display_name?: string
  description?: string
  specialty?: string
  price?: number
  price_from?: number
  old_price?: number
  discount?: number
  discount_percent?: number
  rating?: number
  reviews_count?: number
  is_premium?: boolean
  is_verified?: boolean
  is_favorite?: boolean
  show_contacts?: boolean
  phone?: string
  district?: string
  location?: string
  metro_station?: string
  images?: AdImage[]
  photos?: AdImage[]
}

export interface AdCardProps {
  ad: Ad
}

export interface AdCardEmits {
  favoriteToggled: [adId: number, isFavorite: boolean]
  contactRequested: [adId: number]
  bookingRequested: [adId: number]
  adOpened: [adId: number]
}

// Состояние компонента
export interface AdCardState {
  currentImage: number
}

// Вычисляемые свойства
export interface AdCardComputedProperties {
  isFavorite: boolean
  allImages: AdImage[]
  currentImageUrl: string
}

// CSS классы типизированы для лучшей поддержки в IDE
export interface AdCardStyles {
  CARD_CLASSES: string
  BADGES_CONTAINER_CLASSES: string
  SALE_BADGE_CLASSES: string
  PREMIUM_BADGE_CLASSES: string
  VERIFIED_BADGE_CLASSES: string
  VERIFIED_ICON_CLASSES: string
  FAVORITE_BUTTON_CLASSES: string
  FAVORITE_ICON_CLASSES: string
  IMAGE_CONTAINER_CLASSES: string
  IMAGE_CLASSES: string
  PLACEHOLDER_CLASSES: string
  PLACEHOLDER_ICON_CLASSES: string
  INDICATORS_CONTAINER_CLASSES: string
  INDICATOR_CLASSES: string
  DISCOUNT_BADGE_CLASSES: string
  CONTENT_CLASSES: string
  PRICE_CONTAINER_CLASSES: string
  PRICE_WRAPPER_CLASSES: string
  PRICE_CLASSES: string
  OLD_PRICE_CLASSES: string
  DISCOUNT_TEXT_CLASSES: string
  PRICE_UNIT_CLASSES: string
  TITLE_CLASSES: string
  DESCRIPTION_CLASSES: string
  METRICS_CONTAINER_CLASSES: string
  RATING_WRAPPER_CLASSES: string
  STAR_ICON_CLASSES: string
  RATING_VALUE_CLASSES: string
  RATING_COUNT_CLASSES: string
  LOCATION_WRAPPER_CLASSES: string
  LOCATION_ICON_CLASSES: string
  ACTIONS_CONTAINER_CLASSES: string
  CONTACT_BUTTON_CLASSES: string
  CONTACT_ICON_CLASSES: string
  BOOKING_BUTTON_CLASSES: string
  BOOKING_ICON_CLASSES: string
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

// Типы для событий мыши
export interface MouseMoveEvent extends MouseEvent {
  currentTarget: HTMLElement
}