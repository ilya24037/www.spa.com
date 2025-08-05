// BookingSuccessModal.types.ts
export interface BookingSuccessModalProps {
  booking: Booking
}

export interface BookingSuccessModalEmits {
  close: []
}

// Основная модель бронирования
export interface Booking {
  id: number | string
  master_name: string
  master_id?: number
  service_name: string
  service_id?: number
  booking_date: string // ISO date string "2024-01-15"
  booking_time: string // time string "14:30"
  price: number
  status?: BookingStatus
  client_name?: string
  client_phone?: string
  client_email?: string
  is_home_service?: boolean
  address?: string
  payment_method?: PaymentMethod
  created_at?: string
  updated_at?: string
}

// Статусы бронирования
export type BookingStatus = 
  | 'pending'     // Ожидает подтверждения
  | 'confirmed'   // Подтверждено
  | 'cancelled'   // Отменено
  | 'completed'   // Завершено
  | 'no_show'     // Не явился

// Методы оплаты
export type PaymentMethod = 
  | 'cash'        // Наличные
  | 'card'        // Картой
  | 'online'      // Онлайн

// Форматирование даты и времени
export interface DateTimeFormatting {
  formatDateTime: (booking: Booking) => string
  formatDate: (date: string) => string
  formatTime: (time: string) => string
  getWeekday: (date: string) => string
}

// Форматирование цен
export interface PriceFormatting {
  formatPrice: (price: number) => string
  formatPriceShort: (price: number) => string
  getCurrencySymbol: () => string
}

// Конфигурация компонента
export interface BookingSuccessModalConfig {
  showBookingDetails: boolean
  showNextStepsInfo: boolean
  enableDetailsLink: boolean
  autoCloseTimeout?: number
  successMessageDuration?: number
}

// Детали отображения бронирования
export interface BookingDisplayDetails {
  bookingNumber: string
  masterName: string
  serviceName: string
  dateTime: string
  formattedPrice: string
  status: BookingStatus
  location?: string
}

// Информация о следующих шагах
export interface NextStepsInfo {
  title: string
  steps: NextStep[]
  estimatedTime?: string
  contactInfo?: string
}

export interface NextStep {
  id: string
  description: string
  completed?: boolean
  estimatedTime?: string
}

// События аналитики
export interface BookingSuccessAnalytics {
  bookingId: number | string
  action: 'view_success' | 'close_modal' | 'view_details' | 'share_booking'
  timestamp: Date
  sessionId?: string
  userId?: number
}

// Пропсы для дочерних компонентов
export interface BookingDetailsProps {
  booking: Booking
  showAllDetails?: boolean
  highlightChanges?: boolean
}

export interface NextStepsProps {
  booking: Booking
  customSteps?: NextStep[]
  showEstimatedTime?: boolean
}

export interface SuccessIconProps {
  size?: 'sm' | 'md' | 'lg'
  animated?: boolean
  color?: string
}

// Типы для действий
export interface BookingSuccessActions {
  onClose: () => void
  onViewDetails: (bookingId: number | string) => void
  onShareBooking?: (booking: Booking) => void
  onAddToCalendar?: (booking: Booking) => void
}

// Настройки уведомлений
export interface NotificationSettings {
  email: boolean
  sms: boolean
  push: boolean
  reminderTime?: number // минут до начала
}

// Информация о мастере
export interface MasterInfo {
  id: number
  name: string
  avatar?: string
  phone?: string
  rating?: number
  reviewsCount?: number
  specialization?: string
}

// Информация об услуге
export interface ServiceInfo {
  id: number
  name: string
  description?: string
  duration: number // минуты
  price: number
  category?: string
}

// Локация бронирования
export interface BookingLocation {
  type: 'home' | 'salon'
  address: string
  coordinates?: {
    lat: number
    lng: number
  }
  instructions?: string
}

// Состояние компонента
export interface BookingSuccessModalState {
  isClosing: boolean
  detailsLoaded: boolean
  error?: BookingSuccessError
}

// Ошибки компонента
export interface BookingSuccessError {
  type: 'display' | 'navigation' | 'formatting'
  message: string
  originalError?: unknown
}

// Валидация данных бронирования
export interface BookingValidation {
  isValid: boolean
  requiredFields: (keyof Booking)[]
  missingFields?: string[]
  invalidFields?: string[]
}

// Мета-информация компонента
export interface BookingSuccessModalMetadata {
  componentName: string
  version: string
  lastUpdated: Date
  dependencies: string[]
  features: string[]
}

// Типы для тестирования
export interface BookingSuccessModalTestProps {
  booking: Booking
  mockRouter?: boolean
  mockDateFormat?: boolean
  customConfig?: Partial<BookingSuccessModalConfig>
}

export interface BookingSuccessModalTestExpectations {
  shouldRenderCorrectly: boolean
  shouldDisplayBookingDetails: boolean
  shouldFormatDateTimeCorrectly: boolean
  shouldFormatPriceCorrectly: boolean
  shouldHandleCloseEvent: boolean
  shouldNavigateToDetails: boolean
  shouldShowNextSteps: boolean
}

// Константы компонента
export interface BookingSuccessModalConstants {
  DEFAULT_AUTO_CLOSE_TIME: number
  MIN_PRICE: number
  MAX_PRICE: number
  SUPPORTED_CURRENCIES: string[]
  DATE_FORMAT: string
  TIME_FORMAT: string
}

// Хелперы для работы с датами
export interface DateTimeHelpers {
  parseBookingDateTime: (booking: Booking) => Date | null
  isValidDate: (date: string) => boolean
  isValidTime: (time: string) => boolean
  formatForDisplay: (date: Date) => string
  getRelativeTime: (date: Date) => string
}

// Интеграции с внешними сервисами
export interface ExternalIntegrations {
  calendar?: {
    google: boolean
    outlook: boolean
    apple: boolean
  }
  notifications?: {
    email: boolean
    sms: boolean
    push: boolean
  }
  analytics?: {
    enabled: boolean
    trackingId?: string
  }
}

// Локализация
export interface BookingSuccessLocalization {
  locale: string
  messages: {
    successTitle: string
    successDescription: string
    bookingNumber: string
    masterLabel: string
    serviceLabel: string
    dateTimeLabel: string
    priceLabel: string
    nextStepsTitle: string
    viewDetailsButton: string
    closeButton: string
    [key: string]: string
  }
  dateFormat: string
  timeFormat: string
  currencyFormat: Intl.NumberFormatOptions
}