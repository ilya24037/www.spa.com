// Единый экспорт всех типов проекта

// Модели
export * from './models'

// Inertia страницы
export type * from './inertia'

// Дополнительные утилитарные типы
export type Nullable<T> = T | null
export type Optional<T> = T | undefined
export type MaybeNull<T> = T | null | undefined

// Типы для форм
export interface FormData<T = any> {
  values: T
  errors: Partial<Record<keyof T, string>>
  touched: Partial<Record<keyof T, boolean>>
  loading: boolean
  isDirty: boolean
}

// Типы для API
export interface ApiConfig {
  baseURL?: string
  headers?: Record<string, string>
  timeout?: number
}

export interface ApiError {
  message: string
  code?: string | number
  field?: string
  details?: any
}

// Типы для фильтров
export interface FilterOptions {
  search?: string
  category?: string
  city?: string
  district?: string
  price_from?: number
  price_to?: number
  rating?: number
  sort?: 'price_asc' | 'price_desc' | 'rating' | 'created_at'
  page?: number
  per_page?: number
}

// Типы для медиа
export interface UploadedFile {
  id: string
  file: File
  url?: string
  preview?: string
  progress?: number
  error?: string
  uploaded?: boolean
}

// Типы для уведомлений
export interface Toast {
  id: string
  type: 'success' | 'error' | 'info' | 'warning'
  message: string
  duration?: number
  action?: {
    label: string
    handler: () => void
  }
}

// Типы для модальных окон
export interface ModalConfig {
  title?: string
  message?: string
  confirmText?: string
  cancelText?: string
  type?: 'info' | 'warning' | 'error' | 'success'
  closable?: boolean
}

// Типы для навигации
export interface MenuItem {
  label: string
  href?: string
  route?: string
  icon?: string
  active?: boolean
  children?: MenuItem[]
  badge?: string | number
}

// Типы для таблиц
export interface TableColumn<T = any> {
  key: keyof T | string
  label: string
  sortable?: boolean
  width?: string | number
  align?: 'left' | 'center' | 'right'
  render?: (value: any, row: T) => string | number
}

// Типы для графиков
export interface ChartData {
  labels: string[]
  datasets: Array<{
    label: string
    data: number[]
    backgroundColor?: string | string[]
    borderColor?: string | string[]
    borderWidth?: number
  }>
}

// Типы для карт
export interface MapCoordinates {
  lat: number
  lng: number
}

export interface MapMarker {
  id: string | number
  position: MapCoordinates
  title?: string
  icon?: string
  data?: any
}

// Типы для календаря
export interface CalendarEvent {
  id: string | number
  title: string
  start: Date | string
  end?: Date | string
  allDay?: boolean
  color?: string
  data?: any
}

// Типы для чата
export interface ChatMessage {
  id: string | number
  sender_id: number
  recipient_id: number
  message: string
  attachments?: Array<{
    type: 'image' | 'file'
    url: string
    name?: string
  }>
  read_at?: string
  created_at: string
}

// Типы для настроек
export interface AppSettings {
  theme: 'light' | 'dark' | 'auto'
  language: 'ru' | 'en'
  notifications: {
    email: boolean
    sms: boolean
    push: boolean
  }
  privacy: {
    showPhone: boolean
    showEmail: boolean
    showLocation: boolean
  }
}