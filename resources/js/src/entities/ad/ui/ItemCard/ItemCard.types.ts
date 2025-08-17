// Типы для ItemCard компонента
export interface ItemPhoto {
  id?: number
  url?: string
  src?: string
  path?: string
  preview?: string
}

export interface ItemLocation {
  city?: string
  street?: string
  metro?: string
  district?: string
}

export interface ItemPrice {
  amount: number
  currency?: string
  period?: string
}

export interface AdItem {
  id: number
  name: string
  title?: string
  description?: string
  status: 'active' | 'draft' | 'inactive' | 'pending' | 'archive' | 'old'
  photos?: ItemPhoto[] | string[]
  photo?: string
  price?: number | ItemPrice
  location?: ItemLocation | string
  views?: number
  favorites?: number
  messages?: number
  calls?: number
  created_at?: string
  updated_at?: string
  slug?: string
  waiting_payment?: boolean
  category?: {
    id: number
    name: string
  }
}

export interface ItemCardEmits {
  (e: 'item-updated', itemId: number, data: any): void
  (e: 'item-deleted', itemId: number): void
  (e: 'pay', itemId: number): void
  (e: 'promote', itemId: number): void
  (e: 'edit', itemId: number): void
  (e: 'deactivate', itemId: number): void
  (e: 'delete', itemId: number): void
  (e: 'mark-irrelevant', itemId: number): void
  (e: 'book', itemId: number): void
}