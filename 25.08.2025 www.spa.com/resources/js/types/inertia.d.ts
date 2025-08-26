import type { 
  User, 
  Master, 
  Ad, 
  Booking,
  Review,
  Notification,
  PaginatedResponse 
} from './models'

// Расширение типов Inertia для страниц
declare module '@inertiajs/core' {
  interface PageProps {
    user: User | null
    auth?: {
      user: User | null
    }
    flash?: {
      success?: string
      error?: string
      warning?: string
      info?: string
    }
    errors?: Record<string, string>
    ziggy?: {
      url: string
      port: number | null
      defaults: Record<string, any>
      routes: Record<string, any>
    }
  }
}

// Типы для конкретных страниц
export interface HomePageProps {
  masters: PaginatedResponse<Master>
  currentCity: string
  categories: Array<{
    id: number
    name: string
    slug: string
    count: number
  }>
}

export interface DashboardPageProps {
  ads: Ad[]
  counts: {
    total: number
    active: number
    draft: number
    archived: number
  }
  userStats: {
    views: number
    calls: number
    messages: number
    bookings: number
  }
}

export interface MasterShowPageProps {
  master: Master
  similarMasters?: Master[]
  reviews?: PaginatedResponse<Review>
  canBook: boolean
}

export interface AdShowPageProps {
  ad: Ad
  relatedAds?: Ad[]
  canEdit: boolean
}

export interface BookingsIndexPageProps {
  bookings: PaginatedResponse<Booking>
  filters: {
    status?: string
    date_from?: string
    date_to?: string
  }
}

export interface NotificationsIndexPageProps {
  notifications: PaginatedResponse<Notification>
  unreadCount: number
}

export interface PaymentCheckoutPageProps {
  ad: Ad
  payment: {
    amount: number
    formatted_amount: string
    currency: string
    plan: {
      id: number
      name: string
      features: string[]
    }
  }
  paymentMethods: Array<{
    id: string
    name: string
    icon: string
    available: boolean
  }>
}

export interface ProfileEditPageProps {
  user: User
  mustVerifyEmail: boolean
  status?: string
}

// Экспорт всех типов страниц
export type PageProps = 
  | HomePageProps
  | DashboardPageProps
  | MasterShowPageProps
  | AdShowPageProps
  | BookingsIndexPageProps
  | NotificationsIndexPageProps
  | PaymentCheckoutPageProps
  | ProfileEditPageProps