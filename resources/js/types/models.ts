// Основные модели данных для SPA Platform

// ===== USER =====
export interface User {
  id: number
  name: string
  email: string
  email_verified_at?: string | null
  phone?: string | null
  role?: UserRole
  status?: UserStatus
  created_at: string
  updated_at: string
  
  // Relations
  profile?: UserProfile
  settings?: UserSettings
  master_profile?: MasterProfile
  bookings?: Booking[]
}

export interface UserProfile {
  id: number
  user_id: number
  first_name?: string
  last_name?: string
  phone?: string
  avatar?: string
  bio?: string
  birth_date?: string
  gender?: 'male' | 'female' | 'other'
  city?: string
  address?: string
}

export interface UserSettings {
  id: number
  user_id: number
  notifications_email: boolean
  notifications_sms: boolean
  notifications_push: boolean
  language: string
  timezone: string
  theme: 'light' | 'dark' | 'auto'
}

// ===== MASTER =====
export interface Master {
  id: number
  user_id: number
  name: string
  display_name?: string
  slug?: string
  phone?: string
  email?: string
  
  // Основная информация
  description?: string
  specialty?: string
  experience?: string
  education?: string
  
  // Локация
  city?: string
  district?: string
  metro_station?: string
  address?: string
  
  // Услуги и цены
  services?: MasterService[]
  price_from?: number
  price_to?: number
  price_per_hour?: number
  
  // Внешность
  age?: number
  height?: number
  weight?: number
  bust_size?: string
  hair_color?: string
  eye_color?: string
  appearance?: string
  
  // Медиа
  avatar?: string
  main_photo?: string
  photo?: string
  photos?: Photo[]
  videos?: Video[]
  
  // Статусы
  is_verified?: boolean
  is_premium?: boolean
  is_online?: boolean
  is_available_now?: boolean
  is_favorite?: boolean
  show_contacts?: boolean
  
  // Статистика
  rating?: number | string
  reviews_count?: number
  views_count?: number
  bookings_count?: number
  
  // Расписание
  schedule?: Schedule
  work_zones?: WorkZone[]
  
  // Метаданные
  created_at: string
  updated_at: string
}

export interface MasterProfile extends Master {
  // Дополнительные поля профиля мастера
  subscription?: MasterSubscription
  balance?: number
  documents?: Document[]
}

export interface MasterService {
  id: number
  name: string
  description?: string
  price: number
  duration: number // в минутах
  category?: string
}

export interface Schedule {
  [key: string]: string | null // день недели: время работы
}

export interface WorkZone {
  id: number
  name: string
  districts: string[]
}

// ===== AD (ОБЪЯВЛЕНИЕ) =====
export interface Ad {
  id: number
  user_id: number
  
  // Основная информация
  title?: string
  description?: string
  category?: string
  
  // Локация
  city?: string
  district?: string
  metro?: string
  address?: string
  geo?: string
  
  // Услуги и цены
  services?: AdService[]
  price_from?: number
  price_to?: number
  price_per_hour?: number
  price_30min?: number
  price_2hours?: number
  price_night?: number
  
  // Контакты
  phone?: string
  whatsapp?: string
  telegram?: string
  show_phone?: boolean
  
  // Медиа
  photos?: Photo[] | string[]
  videos?: Video[]
  main_photo?: string
  
  // Статусы
  status: AdStatus
  is_active?: boolean
  is_premium?: boolean
  is_verified?: boolean
  
  // Расписание
  schedule?: Schedule
  work_format?: WorkFormat
  
  // Статистика
  views?: number
  calls?: number
  messages?: number
  
  // Метаданные
  published_at?: string
  expires_at?: string
  created_at: string
  updated_at: string
}

export interface AdService {
  id?: number
  name: string
  price?: number
  duration?: number
}

// ===== BOOKING =====
export interface Booking {
  id: number
  master_id: number
  client_id?: number
  
  // Информация о бронировании
  date: string
  time: string
  duration_minutes: number
  service_id?: number
  service_name?: string
  
  // Клиент
  client_name?: string
  client_phone?: string
  client_email?: string
  client_comment?: string
  
  // Статус и цена
  status: BookingStatus
  price?: number
  prepayment?: number
  
  // Метаданные
  confirmed_at?: string
  cancelled_at?: string
  completed_at?: string
  reminder_sent_at?: string
  created_at: string
  updated_at: string
  
  // Relations
  master?: Master
  client?: User
  service?: MasterService
  payment?: Payment
}

// ===== PAYMENT =====
export interface Payment {
  id: number
  user_id: number
  
  // Информация о платеже
  amount: number
  currency: Currency
  status: PaymentStatus
  type: PaymentType
  method?: PaymentMethod
  
  // Связи
  payable_type?: string
  payable_id?: number
  
  // Детали транзакции
  transaction_id?: string
  gateway_response?: any
  
  // Метаданные
  paid_at?: string
  failed_at?: string
  refunded_at?: string
  created_at: string
  updated_at: string
}

// ===== MEDIA =====
export interface Photo {
  id: number
  url: string
  thumbnail?: string
  title?: string
  description?: string
  order?: number
  is_main?: boolean
  is_verified?: boolean
  created_at?: string
}

export interface Video {
  id: number
  url: string
  thumbnail?: string
  title?: string
  description?: string
  duration?: number
  order?: number
  created_at?: string
}

// ===== REVIEW =====
export interface Review {
  id: number
  master_id: number
  client_id?: number
  
  // Отзыв
  rating: number
  comment?: string
  
  // Клиент
  client_name?: string
  is_verified?: boolean
  
  // Ответ мастера
  reply?: string
  replied_at?: string
  
  // Метаданные
  created_at: string
  updated_at: string
  
  // Relations
  master?: Master
  client?: User
}

// ===== NOTIFICATION =====
export interface Notification {
  id: number
  user_id: number
  
  type: NotificationType
  title: string
  message: string
  data?: any
  
  read_at?: string
  created_at: string
  updated_at: string
}

// ===== SUBSCRIPTION =====
export interface MasterSubscription {
  id: number
  master_profile_id: number
  
  plan: SubscriptionPlan
  status: SubscriptionStatus
  
  started_at: string
  expires_at: string
  cancelled_at?: string
  
  auto_renew: boolean
  
  created_at: string
  updated_at: string
}

// ===== ENUMS =====
export enum UserRole {
  CLIENT = 'client',
  MASTER = 'master',
  ADMIN = 'admin',
  MODERATOR = 'moderator'
}

export enum UserStatus {
  ACTIVE = 'active',
  INACTIVE = 'inactive',
  BLOCKED = 'blocked',
  DELETED = 'deleted'
}

export enum AdStatus {
  DRAFT = 'draft',
  PENDING = 'pending',
  ACTIVE = 'active',
  INACTIVE = 'inactive',
  REJECTED = 'rejected',
  ARCHIVED = 'archived'
}

export enum BookingStatus {
  PENDING = 'pending',
  CONFIRMED = 'confirmed',
  CANCELLED = 'cancelled',
  COMPLETED = 'completed',
  NO_SHOW = 'no_show'
}

export enum PaymentStatus {
  PENDING = 'pending',
  PROCESSING = 'processing',
  COMPLETED = 'completed',
  FAILED = 'failed',
  REFUNDED = 'refunded'
}

export enum PaymentType {
  BOOKING = 'booking',
  SUBSCRIPTION = 'subscription',
  PROMOTION = 'promotion',
  BALANCE = 'balance'
}

export enum PaymentMethod {
  CARD = 'card',
  SBP = 'sbp',
  WALLET = 'wallet',
  CASH = 'cash'
}

export enum Currency {
  RUB = 'RUB',
  USD = 'USD',
  EUR = 'EUR'
}

export enum WorkFormat {
  INCALL = 'incall',     // У себя
  OUTCALL = 'outcall',   // Выезд
  BOTH = 'both'          // Оба варианта
}

export enum NotificationType {
  BOOKING_CREATED = 'booking_created',
  BOOKING_CONFIRMED = 'booking_confirmed',
  BOOKING_CANCELLED = 'booking_cancelled',
  BOOKING_REMINDER = 'booking_reminder',
  PAYMENT_RECEIVED = 'payment_received',
  REVIEW_RECEIVED = 'review_received',
  MESSAGE_RECEIVED = 'message_received',
  SYSTEM = 'system'
}

export enum SubscriptionPlan {
  FREE = 'free',
  BASIC = 'basic',
  PREMIUM = 'premium',
  VIP = 'vip'
}

export enum SubscriptionStatus {
  ACTIVE = 'active',
  EXPIRED = 'expired',
  CANCELLED = 'cancelled',
  SUSPENDED = 'suspended'
}

// ===== PAGINATION =====
export interface PaginatedResponse<T> {
  data: T[]
  current_page: number
  first_page_url: string
  from: number
  last_page: number
  last_page_url: string
  links: PaginationLink[]
  next_page_url: string | null
  path: string
  per_page: number
  prev_page_url: string | null
  to: number
  total: number
}

export interface PaginationLink {
  url: string | null
  label: string
  active: boolean
}

// ===== API RESPONSES =====
export interface ApiResponse<T = any> {
  success: boolean
  data?: T
  message?: string
  errors?: Record<string, string[]>
}

export interface ValidationError {
  message: string
  errors: Record<string, string[]>
}