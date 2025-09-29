export interface Ad {
  id: number
  title: string
  name?: string
  display_name?: string
  description?: string
  price?: number
  price_from?: number
  city?: string
  district?: string
  address?: string
  phone?: string
  show_contacts?: boolean
  photos?: Array<{
    id: number
    url: string
    thumbnail?: string
  }>
  services?: Array<{
    id: number
    name: string
    price: number
    duration?: number
  }>
  status?: 'active' | 'inactive' | 'draft' | 'moderation'
  created_at?: string
  updated_at?: string
  user?: {
    id: number
    name: string
    avatar?: string
  }
  category?: {
    id: number
    name: string
    slug: string
  }
  views_count?: number
  favorites_count?: number
  is_favorite?: boolean
  is_promoted?: boolean
  schedule?: Record<string, string>
  education?: string
  experience?: string
  rating?: number
  reviews_count?: number
  reviews?: Array<{
    id: number
    rating: number
    comment: string
    client_name: string
    created_at: string
  }>
}

export interface Item extends Ad {
  // Item is an alias for Ad with same properties
}