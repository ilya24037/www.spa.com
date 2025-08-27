/**
 * Типы для формы создания/редактирования объявления
 * FSD: entities/ad/model/types
 */

export interface AdForm {
  // Системные поля
  id?: number | null
  user_id?: number | null  
  status?: string
  category?: string
  title?: string
  
  // Основная информация (из оригинального AdFormData)
  specialty: string
  clients: string[]
  service_location: string[]
  work_format: string
  service_provider: string[]
  experience: string
  description: string
  
  // Услуги и возможности
  services: any
  services_additional_info: string
  features: string[]
  additional_features: string
  
  // Расписание и бронирование
  schedule: any
  schedule_notes: string
  online_booking: boolean
  
  // Цены
  price: number | null
  price_unit: string
  is_starting_price: boolean
  prices?: {
    apartments_express?: number | null
    apartments_1h?: number | null
    apartments_2h?: number | null
    apartments_night?: number | null
    outcall_1h?: number | null
    outcall_2h?: number | null
    outcall_night?: number | null
    taxi_included?: boolean
  }
  
  // Параметры (объект как в оригинале)
  parameters: {
    title: string
    age: string | number
    height: string
    weight: string
    breast_size: string
    hair_color: string
    eye_color: string
    nationality: string
    bikini_zone: string
  }
  
  // Скидки и подарки
  discount?: number | null
  new_client_discount: string
  gift: string
  has_girlfriend?: boolean
  
  // Дополнительные поля
  min_duration?: number | null
  contacts_per_hour?: number | null
  
  // Медиа
  photos: any[]
  video: any[]
  
  // Геолокация и путешествия
  geo: any
  address: string
  travel_area: string
  custom_travel_areas: string[]
  travel_radius: string | number
  travel_price: number | null
  travel_price_type: string
  
  // Контакты (объект как в оригинале)
  contacts: {
    phone: string
    contact_method: string
    whatsapp: string
    telegram: string
  }
  
  // FAQ
  faq?: Record<string, any>
  
  // Поля верификации
  verification_photo: string | null
  verification_video: string | null
  verification_status: string
  verification_comment: string | null
  verification_expires_at: string | null
}

export interface Photo {
  id?: number
  url: string
  is_verification?: boolean
  order?: number
}

export interface Video {
  id?: number
  url: string
  thumbnail?: string
  duration?: number
  order?: number
}

export interface ValidationRule {
  field: string
  rules: Array<'required' | 'numeric' | 'min' | 'max' | 'email' | 'phone'>
  min?: number
  max?: number
  message?: string
}

export interface SubmissionResult {
  success: boolean
  data?: AdForm
  errors?: Record<string, string[]>
  message?: string
}