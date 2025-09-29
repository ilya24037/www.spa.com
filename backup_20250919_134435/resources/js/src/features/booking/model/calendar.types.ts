/**
 * Типы для календаря бронирования
 */

// Статус доступности даты
export type DateAvailabilityStatus = 'available' | 'busy' | 'full' | 'unavailable'

// Информация о бронировании для конкретной даты
export interface DateBookingInfo {
  totalSlots: number
  bookedSlots: number
  availableSlots: number
  date: string
  status: DateAvailabilityStatus
}

// Конфигурация календаря
export interface CalendarConfig {
  minDate: Date | string
  maxDate: Date | string
  availableDates: string[]
  bookingData: Record<string, DateBookingInfo>
  weekDays: string[]
  monthNames: string[]
  locale: string
}

// Пропсы календаря
export interface BookingCalendarProps {
  modelValue: string | Date | null
  availableDates?: string[]
  minDate?: string | Date
  maxDate?: string | Date
  bookingData?: Record<string, DateBookingInfo>
  disabled?: boolean
  loading?: boolean
  locale?: string
}

// События календаря
export interface BookingCalendarEmits {
  'update:modelValue': [value: string | null]
  'monthChanged': [data: { month: number; year: number }]
  'dateSelected': [date: string]
  'dateHovered': [date: string | null]
}

// Внутреннее состояние календаря
export interface CalendarState {
  currentMonth: number
  currentYear: number
  selectedDate: string | null
  hoveredDate: string | null
  isLoading: boolean
}

// Данные для отображения дня в календаре
export interface CalendarDay {
  date: Date
  dateString: string
  day: number
  isToday: boolean
  isSelected: boolean
  isHovered: boolean
  isAvailable: boolean
  isPast: boolean
  isWeekend: boolean
  isCurrentMonth: boolean
  bookingInfo: DateBookingInfo | null
  status: DateAvailabilityStatus
}

// Данные для мобильного списка доступных дат
export interface AvailableDateItem {
  dateString: string
  displayText: string
  availableSlots: string
  isSelected: boolean
  date: Date
}

// Навигационные данные календаря
export interface CalendarNavigation {
  canGoPrevious: boolean
  canGoNext: boolean
  currentMonthName: string
  currentYear: number
  currentMonth: number
}

// Конфигурация легенды календаря
export interface CalendarLegendItem {
  id: string
  color: string
  label: string
  status: DateAvailabilityStatus
}

// Утилиты для работы с датами
export interface DateUtils {
  formatDateString: (date: Date) => string
  parseDate: (dateString: string) => Date
  isToday: (date: Date) => boolean
  isPast: (date: Date) => boolean
  isWeekend: (date: Date) => boolean
  isSameDate: (date1: Date, date2: Date) => boolean
  addDays: (date: Date, days: number) => Date
  addMonths: (date: Date, months: number) => Date
  getFirstDayOfMonth: (date: Date) => Date
  getLastDayOfMonth: (date: Date) => Date
  getDaysInMonth: (date: Date) => number
  getWeekDay: (date: Date) => number
}

// Опции для получения доступных дат
export interface AvailableDatesOptions {
  maxDates?: number
  startDate?: Date
  endDate?: Date
  excludeWeekends?: boolean
  onlyFuture?: boolean
}

// Результат валидации даты
export interface DateValidationResult {
  isValid: boolean
  reason?: string
  suggestions?: string[]
}

// Конфигурация отображения календаря
export interface CalendarDisplayConfig {
  showLegend: boolean
  showMobileList: boolean
  showBookingIndicators: boolean
  showWeekends: boolean
  highlightToday: boolean
  enableHover: boolean
  enableKeyboardNavigation: boolean
}

// Данные для анимаций календаря
export interface CalendarAnimation {
  direction: 'next' | 'previous' | null
  isAnimating: boolean
}

// Конфигурация доступности (accessibility)
export interface CalendarAccessibility {
  announceChanges: boolean
  announceSelection: boolean
  keyboardNavigation: boolean
  screenReaderSupport: boolean
  ariaLabels: {
    calendar: string
    navigation: string
    monthYear: string
    previousMonth: string
    nextMonth: string
    selectDate: string
    availableDate: string
    unavailableDate: string
    selectedDate: string
    today: string
  }
}

// Полная конфигурация календаря
export interface FullCalendarConfig extends CalendarConfig {
  display: CalendarDisplayConfig
  accessibility: CalendarAccessibility
  animation: CalendarAnimation
}