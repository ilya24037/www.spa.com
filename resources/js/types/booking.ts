export interface Booking {
  id: number
  booking_number: string
  status: 'pending' | 'confirmed' | 'completed' | 'cancelled'
  booking_date: string
  booking_time: string
  service: {
    id: number
    name: string
    price: number
    duration?: number
  }
  service_price: number
  travel_fee: number
  discount_amount: number
  total_price: number
  payment_method: string
  payment_status: string
  notes?: string
  created_at: string
  updated_at: string
  confirmed_at?: string
  cancelled_at?: string
  master: {
    id: number
    name: string
    avatar?: string
    avatar_url?: string
    phone?: string
  }
  master_profile?: {
    id: number
    name: string
    avatar_url?: string
    phone?: string
    rating?: number
    reviews_count?: number
    user?: {
      id: number
      name: string
      avatar_url?: string
    }
  }
  master_profile_id?: number
  client: {
    id: number
    name: string
    phone?: string
    avatar_url?: string
  }
  client_name?: string
  client_phone?: string
  client_comment?: string
  address?: string
  service_location?: string
  cancellation_reason?: string
  cancelled_by?: number
}

export interface BookingStatus {
  value: string
  label: string
  color: string
  icon?: string
}