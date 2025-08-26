// ItemCard.types.ts
export interface Item {
  id: number
  title?: string
  name?: string
  display_name?: string
  description?: string
  price?: number
  price_from?: number
  status: 'draft' | 'active' | 'archived' | 'pending' | 'rejected'
  rating?: number
  reviews_count?: number
  is_premium?: boolean
  is_verified?: boolean
  is_favorite?: boolean
  show_contacts?: boolean
  phone?: string
  district?: string
  location?: string
  metro_station?: string
  images?: ItemImage[]
  photos?: ItemImage[]
  created_at?: string
  updated_at?: string
  expires_at?: string
  views_count?: number
  favorites_count?: number
}

export interface ItemImage {
  id?: number
  url?: string
  path?: string
  thumb_url?: string
  alt?: string
}

export interface ItemCardProps {
  item: Item
}

export interface ItemCardEmits {
  'item-updated': [item: Item]
  'item-deleted': [itemId: number]
}

// Состояние компонента
export interface ItemCardState {
  showDeleteModal: boolean
}

// Вычисляемые свойства
export interface ItemCardComputedProperties {
  itemUrl: string
}

// API Response типы
export interface ItemActionResponse {
  success: boolean
  message?: string
  item?: Item
}

export interface ApiError {
  message: string
  errors?: Record<string, string[]>
}

// Типы для событий
export interface ClickEvent extends Omit<Event, 'preventDefault'> {
  preventDefault: () => void
}

// Роуты для действий
export interface ItemRoutes {
  view: string
  edit: string
  delete: string
  deactivate: string 
  restore: string
  payment: string
  promotion: string
}

// Типы для статусов объявлений
export type ItemStatus = 'draft' | 'active' | 'archived' | 'pending' | 'rejected'

// Типы для действий с объявлениями
export type ItemAction = 'pay' | 'promote' | 'edit' | 'deactivate' | 'restore' | 'delete'