// BookingForm.types.ts
export interface Service {
  id: number
  name: string
  price: number
  duration: number
  description?: string
}

export interface Master {
  id: number
  name: string
  display_name: string
  services: Service[]
  home_service?: boolean
  salon_service?: boolean
  salon_address?: string
  avatar?: string
  rating?: number
  reviewsCount?: number
}

export interface BookingFormProps {
  master: Master
}

export interface BookingFormEmits {
  close: []
  success: [result: BookingResult]
}

export interface Step {
  label: string
}

export interface FormData {
  service: Service | null
  date: string | null
  time: string | null
  locationType: 'salon' | 'home'
  name: string
  phone: string
  email: string
  address: string
  comment: string
  paymentMethod: 'cash' | 'card' | 'online'
  agreement: boolean
}

export interface FormErrors {
  name?: string
  phone?: string
  address?: string
  service?: string
  date?: string
  time?: string
  agreement?: string
  [key: string]: string | undefined
}

export interface TimeSlot {
  time: string
  available: boolean
}

export interface BookingData {
  masterId: number
  serviceId: number
  date: string
  time: string
  locationType: 'salon' | 'home'
  clientName: string
  clientPhone: string
  clientEmail?: string
  address?: string
  comment?: string
  paymentMethod: 'cash' | 'card' | 'online'
}

export interface BookingResult {
  id: number
  status: string
  masterId: number
  serviceId: number
  date: string
  time: string
  totalPrice: number
}

export interface ApiError {
  response?: {
    status: number
    data: {
      errors: FormErrors
      message?: string
    }
  }
  message: string
}

export type PaymentMethod = 'cash' | 'card' | 'online'
export type LocationType = 'salon' | 'home'
export type StepIndex = 0 | 1 | 2 | 3