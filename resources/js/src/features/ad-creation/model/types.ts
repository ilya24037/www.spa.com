/**
 * Типы для формы создания/редактирования объявления
 * FSD: entities/ad/model/types
 */

export interface AdForm {
  // Основная информация
  id: number | null
  user_id: number | null
  title: string
  category: 'relax' | 'escort' | 'massage' | 'other'
  description: string
  status: 'draft' | 'active' | 'inactive' | 'moderation'
  
  // Медиа
  photos: Photo[]
  video: Video[]
  
  // Цены и услуги
  prices: Record<string, number>
  services: string[]
  clients: string[]
  
  // Расписание
  schedule: Record<string, any>
  schedule_notes: string
  
  // Контакты
  phone: string
  whatsapp: string
  telegram: string
  vk: string
  instagram: string
  
  // Локация
  address: string
  geo: { lat: number; lng: number } | null
  radius: number | null
  is_remote: boolean
  
  // Параметры
  age: number | null
  height: number | null
  weight: number | null
  breast_size: number | null
  hair_color: string
  eye_color: string
  nationality: string
  appearance: string
  
  // Дополнительно
  additional_features: string[]
  discount: number | null
  gift: string
  new_client_discount: number | null
  has_girlfriend: boolean
  min_duration: number | null
  contacts_per_hour: number | null
  experience: number | null
  work_format: string
  specialty: string
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