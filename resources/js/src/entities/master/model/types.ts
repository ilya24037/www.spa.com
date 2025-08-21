// Master entity types

export interface Master {
  id: number
  name: string
  slug?: string
  display_name?: string
  avatar?: string
  photo?: string
  age?: number
  experience_years?: number
  rating?: number
  reviews_count?: number
  views_count?: number
  is_premium?: boolean
  is_verified?: boolean
  is_available_now?: boolean
  is_online?: boolean
  work_on_site?: boolean
  description?: string
  city?: string
  district?: string
  metro_station?: string
  price_from?: number
  price_to?: number
  phone?: string
  whatsapp?: string
  telegram?: string
  schedule?: MasterSchedule
  schedule_description?: string
  primary_category_id?: number
  services?: MasterService[]
  photos?: MasterPhoto[]
  reviews?: MasterReview[]
  // Поля для связи с профилем мастера
  master_profile_id?: number
  master_slug?: string
  has_master_profile?: boolean
  // Дополнительные поля для галереи
  gallery?: MasterPhoto[]
  photo_placeholder?: string
  avatar_placeholder?: string
}

export interface MasterSchedule {
  [key: string]: {
    start: string
    end: string
    is_working: boolean
  }
}

export interface MasterService {
  id: number
  name: string
  description?: string
  price: number
  duration: number
  category?: ServiceCategory
  is_home_service?: boolean
}

export interface ServiceCategory {
  id: number
  name: string
  slug: string
}

export interface MasterPhoto {
  id: number
  url: string
  thumbnail?: string
  caption?: string
  is_main?: boolean
  order?: number
}

export interface MasterReview {
  id: number
  rating: number
  text: string
  author_name: string
  author_avatar?: string
  created_at: string
  reply?: string
  reply_at?: string
}

export interface MasterFilters {
  category?: number
  district?: string
  metro?: string
  price_min?: number
  price_max?: number
  rating_min?: number
  has_reviews?: boolean
  is_verified?: boolean
  is_premium?: boolean
  work_on_site?: boolean
  available_now?: boolean
}

export interface MasterListResponse {
  data: Master[]
  meta: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}