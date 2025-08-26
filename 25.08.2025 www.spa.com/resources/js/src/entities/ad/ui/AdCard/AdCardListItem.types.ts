// AdCardListItem.types.ts
export interface AdCardListItemProps {
  ad: Ad
}

// Основная модель объявления
export interface Ad {
  id: number
  title?: string
  name?: string
  description?: string
  specialty?: string
  price?: number
  price_from?: number
  old_price?: number
  discount?: number
  rating?: number
  reviews_count?: number
  is_premium?: boolean
  is_favorite?: boolean
  district?: string
  location?: string
  phone?: string
  show_contacts?: boolean
  avatar?: string
  main_photo?: string
  images?: AdImage[]
  photos?: AdPhoto[]
  services?: AdService[]
}

// Изображения объявления
export interface AdImage {
  id: number
  url: string
  path?: string
  alt?: string
  thumb_url?: string
}

export interface AdPhoto {
  id: number
  url: string
  path?: string
  alt?: string
  thumb_url?: string
}

// Услуги объявления
export interface AdService {
  id: number
  name: string
  price?: number
  duration?: number
  description?: string
}

// Состояние компонента
export interface AdCardListItemState {
  imageError: boolean
  isProcessingFavorite: boolean
  isContactingMaster: boolean
  isOpeningBooking: boolean
}

// Computed свойства
export interface AdCardComputedProperties {
  isFavorite: boolean
  adPhoto: string
  displayServices: AdService[]
  hasMoreServices: boolean
  formattedPrice: string
  formattedOldPrice?: string
  adDescription: string
}

// События компонента
export interface AdCardListItemEvents {
  favoriteToggled: [adId: number, isFavorite: boolean]
  contactRequested: [adId: number, phone?: string]
  bookingRequested: [adId: number]
  adOpened: [adId: number]
  imageError: [adId: number]
}

// Методы действий
export interface AdCardActions {
  openAd: () => void
  toggleFavorite: () => Promise<void>
  contactMaster: () => void
  openBooking: () => void
  handleImageError: () => void
}

// Форматирование данных
export interface FormattingHelpers {
  formatPrice: (price?: number) => string
  getDescription: () => string
  getPhotoUrl: () => string
  truncateText: (text: string, maxLength: number) => string
}

// API ответы
export interface FavoriteToggleResponse {
  success: boolean
  is_favorite: boolean
  message?: string
}

// Константы стилей (CSS классы)
export interface StyleConstants {
  CARD_CLASSES: string
  CONTAINER_CLASSES: string
  PHOTO_CONTAINER_CLASSES: string
  PHOTO_CLASSES: string
  BADGES_CONTAINER_CLASSES: string
  PREMIUM_BADGE_CLASSES: string
  DISCOUNT_BADGE_CLASSES: string
  INFO_CONTAINER_CLASSES: string
  INFO_HEADER_CLASSES: string
  TITLE_CLASSES: string
  METADATA_CONTAINER_CLASSES: string
  RATING_WRAPPER_CLASSES: string
  STAR_ICON_CLASSES: string
  RATING_VALUE_CLASSES: string
  RATING_COUNT_CLASSES: string
  LOCATION_WRAPPER_CLASSES: string
  LOCATION_ICON_CLASSES: string
  PRICE_CONTAINER_CLASSES: string
  PRICE_WRAPPER_CLASSES: string
  PRICE_CLASSES: string
  OLD_PRICE_CLASSES: string
  PRICE_UNIT_CLASSES: string
  DESCRIPTION_CONTAINER_CLASSES: string
  DESCRIPTION_CLASSES: string
  SERVICES_CONTAINER_CLASSES: string
  SERVICE_TAG_CLASSES: string
  MORE_SERVICES_CLASSES: string
  ACTIONS_CONTAINER_CLASSES: string
  CONTACT_BUTTON_CLASSES: string
  CONTACT_ICON_CLASSES: string
  BOOKING_BUTTON_CLASSES: string
  BOOKING_ICON_CLASSES: string
  FAVORITE_BUTTON_CLASSES: string
  FAVORITE_ICON_CLASSES: string
}

// Ошибки компонента
export interface AdCardError {
  type: 'image_load' | 'favorite_toggle' | 'contact' | 'booking' | 'navigation'
  message: string
  adId: number
  originalError?: unknown
}

// Конфигурация компонента
export interface AdCardConfig {
  maxServicesDisplay: number
  fallbackImage: string
  fallbackPrice: string
  fallbackDescription: string
  priceUnit: string
  enableFavorites: boolean
  enableContact: boolean
  enableBooking: boolean
}

// Типы для аналитики
export interface AdCardAnalytics {
  adId: number
  action: 'view' | 'click' | 'favorite' | 'contact' | 'booking'
  timestamp: Date
  userAgent?: string
  referrer?: string
}

// Пропсы для дочерних компонентов
export interface AdBadgeProps {
  type: 'premium' | 'discount'
  value?: number | string
  className?: string
}

export interface AdRatingProps {
  rating: number
  reviewsCount: number
  showCount?: boolean
  size?: 'sm' | 'md' | 'lg'
}

export interface AdLocationProps {
  district?: string
  location?: string
  fullAddress?: string
  showIcon?: boolean
}

export interface AdPriceProps {
  price?: number
  oldPrice?: number
  priceFrom?: number
  unit?: string
  discount?: number
}

export interface AdServicesProps {
  services: AdService[]
  maxDisplay?: number
  showMore?: boolean
}

export interface AdActionsProps {
  adId: number
  phone?: string
  showContacts?: boolean
  isFavorite?: boolean
  onContact?: () => void
  onBooking?: () => void
  onFavoriteToggle?: () => void
}

// Типы для работы с изображениями
export interface ImageLoadOptions {
  fallbackUrl: string
  retryCount?: number
  lazyLoad?: boolean
  placeholder?: string
}

export interface ImageProcessingResult {
  url: string
  isLoaded: boolean
  hasError: boolean
  loadTime?: number
}

// Навигационные типы
export interface NavigationOptions {
  preserveScroll?: boolean
  preserveState?: boolean
  replace?: boolean
  onlyKeys?: string[]
}

export interface RouteParameters {
  adId: number
  booking?: boolean
  tab?: string
  modal?: string
}

// Типы для работы с контактами
export interface ContactOptions {
  method: 'phone' | 'message' | 'booking'
  requiresAuth?: boolean
  showContactsAfterBooking?: boolean
}

export interface PhoneContactInfo {
  phone: string
  cleanPhone: string
  isValid: boolean
  canCall: boolean
}

// Мета-информация компонента
export interface AdCardMetadata {
  componentName: string
  version: string
  lastUpdated: Date
  features: string[]
  dependencies: string[]
}

// Типы для тестирования
export interface AdCardTestProps {
  ad: Ad
  mockRouter?: boolean
  mockToast?: boolean
  enableAnalytics?: boolean
}

export interface AdCardTestExpectations {
  shouldRenderCorrectly: boolean
  shouldHandleImageErrors: boolean
  shouldFormatPriceCorrectly: boolean
  shouldDisplayServices: boolean
  shouldHandleFavoriteToggle: boolean
  shouldEnableContactActions: boolean
  shouldNavigateCorrectly: boolean
}