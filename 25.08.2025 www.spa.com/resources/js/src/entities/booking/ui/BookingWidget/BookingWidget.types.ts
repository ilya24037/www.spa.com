// BookingWidget.types.ts
export interface BookingWidgetProps {
  master: Master
  selectedService?: Service | null
  isOpen?: boolean
}

export interface BookingWidgetEmits {
  'booking-created': [booking: CreatedBooking]
  close: []
}

// Мастер
export interface Master {
  id: number
  name: string
  display_name?: string
  avatar?: string
  rating?: number
  reviews_count?: number
  services?: Service[]
}

// Услуга
export interface Service {
  id: number
  name: string
  price: number
  duration: number
  description?: string
}

// Данные бронирования на разных этапах
export interface BookingData {
  date: Date | null
  time: string | null
  datetime: Date | null
  service: Service | null
}

// Созданное бронирование
export interface CreatedBooking {
  id: number
  bookingNumber: string
  masterId: number
  serviceId?: number
  date: Date
  time: string
  clientName: string
  clientPhone: string
  clientEmail?: string
  status: BookingStatus
  totalPrice?: number
}

export type BookingStatus = 'pending' | 'confirmed' | 'cancelled' | 'completed'

// Типы шагов
export type StepNumber = 1 | 2 | 3
export type StepType = StepNumber | 'error'

// Состояние компонента
export interface BookingWidgetState {
  currentStep: StepType
  bookingData: BookingData
  createdBooking: CreatedBooking | null
  submitting: boolean
  errorMessage: string | null
}

// Данные выбора времени (от BookingCalendar)
export interface TimeSelection {
  date: Date
  time: string
  datetime: Date
  availableSlots?: string[]
  serviceId?: number
}

// Данные формы (от BookingForm)
export interface BookingFormData {
  clientName: string
  clientPhone: string
  clientEmail?: string
  clientComment?: string
  serviceType: 'home' | 'salon'
  address?: string
  paymentMethod: PaymentMethod
  agreeToTerms: boolean
}

export type PaymentMethod = 'cash' | 'card' | 'online'

// Шаги виджета
export interface Step {
  number: StepNumber
  title: string
  description: string
  isComplete: boolean
  isActive: boolean
}

// Конфигурация этапов
export interface StepConfig {
  1: {
    title: 'Время'
    description: 'Выберите удобное время'
    component: 'BookingCalendar'
  }
  2: {
    title: 'Данные'
    description: 'Заполните контактные данные'
    component: 'BookingForm'
  }
  3: {
    title: 'Готово'
    description: 'Запись создана'
    component: 'SuccessScreen'
  }
}

// Результат API создания бронирования
export interface BookingApiResponse {
  success: boolean
  booking?: CreatedBooking
  error?: string
  validationErrors?: Record<string, string>
}

// Ошибки виджета
export interface BookingWidgetError {
  type: 'api' | 'validation' | 'network' | 'calendar' | 'form'
  message: string
  step?: StepType
  field?: string
  originalError?: unknown
}

// События календаря
export interface CalendarSelectionEvent {
  date: Date
  time: string
  datetime: Date
  service: Service | null
}

// События формы
export interface FormSubmitEvent {
  formData: BookingFormData
  bookingData: BookingData
}

// Конфигурация анимаций
export interface AnimationConfig {
  duration: number
  easing: 'ease-in-out' | 'ease-in' | 'ease-out' | 'linear'
  delay?: number
}

// Компонент шага
export interface StepComponent {
  name: string
  props: Record<string, unknown>
  events: Record<string, Function>
}

// Навигация по шагам
export interface StepNavigation {
  canGoNext: boolean
  canGoPrev: boolean
  nextStep: () => void
  prevStep: () => void
  goToStep: (step: StepNumber) => void
  reset: () => void
}

// Валидация шагов
export interface StepValidation {
  1: boolean // Время выбрано
  2: boolean // Форма заполнена корректно
}

// Форматирование данных
export interface FormattedBookingData {
  dateTime: string
  serviceName: string
  masterName: string
  bookingNumber: string
  status: string
}

// Конфигурация виджета
export interface WidgetConfig {
  autoAdvance?: boolean
  showProgress?: boolean
  allowBackNavigation?: boolean
  resetOnClose?: boolean
  animationsEnabled?: boolean
}

// Метаданные шага
export interface StepMetadata {
  step: StepNumber
  title: string
  isValid: boolean
  data: Record<string, unknown>
  timestamp: Date
}

// История навигации
export interface NavigationHistory {
  steps: StepMetadata[]
  currentIndex: number
}

// Внешние зависимости
export interface ExternalDependencies {
  dayjs: typeof import('dayjs')
  bookingApi: {
    createBooking: (data: BookingFormData & BookingData) => Promise<BookingApiResponse>
    getAvailableSlots: (masterId: number, date: Date) => Promise<string[]>
  }
}

// Пользовательские события
export interface UserInteractionEvent {
  type: 'step_change' | 'form_submit' | 'time_select' | 'widget_reset' | 'widget_close'
  step: StepType
  data?: Record<string, unknown>
  timestamp: Date
}