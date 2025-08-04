// BookingModal.types.ts
export interface BookingModalProps {
  master: Master
  service?: Service | null
}

export interface BookingModalEmits {
  close: []
  success: [booking: BookingFormData]
}

// Данные мастера
export interface Master {
  id: number
  display_name: string
  avatar?: string
  district?: string
  home_service: boolean
  salon_service: boolean
  salon_address?: string
  services: Service[]
}

// Услуга
export interface Service {
  id: number
  name: string
  price: number
  duration: number
  description?: string
}

// Форма бронирования
export interface BookingFormData {
  master_profile_id: number
  service_id?: number | string
  booking_date: Date | null
  start_time: string
  is_home_service: boolean
  address: string
  client_name: string
  client_phone: string
  client_email: string
  client_comment: string
  payment_method: PaymentMethod
}

// Методы оплаты
export type PaymentMethod = 'cash' | 'card' | 'online'

export interface PaymentMethodOption {
  value: PaymentMethod
  label: string
}

// Состояние компонента
export interface BookingModalState {
  loading: boolean
  loadingSlots: boolean
  availableSlots: string[]
  disabledDates: Date[]
}

// Доступные слоты времени
export interface AvailableSlotsRequest {
  master_profile_id: number
  service_id: number | string
  date: Date | string
}

export interface AvailableSlotsResponse {
  slots: string[]
  disabled_dates?: string[]
}

// Ответ API при создании бронирования
export interface BookingResponse {
  id: number
  booking_date: string
  start_time: string
  status: BookingStatus
  total_price: number
  message?: string
}

export type BookingStatus = 'pending' | 'confirmed' | 'cancelled' | 'completed'

// Валидация формы
export interface BookingFormValidation {
  service_id: boolean
  booking_date: boolean
  start_time: boolean
  client_name: boolean
  client_phone: boolean
  address: boolean // только если is_home_service = true
}

export interface BookingFormErrors {
  service_id?: string
  booking_date?: string
  start_time?: string
  client_name?: string
  client_phone?: string
  client_email?: string
  address?: string
  general?: string
}

// Расчет стоимости
export interface PriceCalculation {
  servicePrice: number
  homeServiceFee: number
  totalPrice: number
}

// Error типы для обработки ошибок
export interface BookingModalError {
  type: 'validation' | 'api' | 'slots' | 'network'
  message: string
  field?: keyof BookingFormData
  originalError?: unknown
}

// Типы для событий
export interface BookingSubmitEvent {
  data: BookingFormData
  timestamp: string
  masterId: number
}

export interface SlotFetchEvent {
  date: Date
  masterId: number
  serviceId: number | string
}

// Телефонные страны
export interface PhoneCountry {
  code: string
  name: string
  flag: string
}

// VueDatePicker типы
export interface DatePickerModelValue {
  year: number
  month: number
  day: number
}

// Vue Tel Input типы  
export interface TelInputData {
  number: string
  isValid: boolean
  country: PhoneCountry
}