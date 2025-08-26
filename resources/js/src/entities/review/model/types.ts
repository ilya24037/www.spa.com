// Типы для сущности Review
export interface Review {
  id: number
  user_id: number
  reviewer_id: number
  rating: number
  comment: string
  is_anonymous: boolean
  status: ReviewStatus
  created_at: string
  updated_at: string
  // Relations
  user?: User
  reviewer?: User
  service?: Service
}

export interface User {
  id: number
  name: string
  email: string
  avatar?: string
}

export interface Service {
  id: number
  name: string
  category: string
}

export enum ReviewStatus {
  PENDING = 'pending',
  APPROVED = 'approved',
  REJECTED = 'rejected'
}

export interface CreateReviewDTO {
  user_id: number
  rating: number
  comment: string
  is_anonymous?: boolean
  service_id?: number
}

export interface UpdateReviewDTO {
  rating?: number
  comment?: string
  is_anonymous?: boolean
}

export interface ReviewFilters {
  user_id?: number
  rating?: number
  status?: ReviewStatus
  date_from?: string
  date_to?: string
}

export interface ReviewsResponse {
  data: Review[]
  meta: {
    current_page: number
    from: number
    last_page: number
    per_page: number
    to: number
    total: number
  }
}