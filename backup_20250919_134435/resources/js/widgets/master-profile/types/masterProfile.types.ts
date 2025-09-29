/**
 * Типы для изолированного виджета MasterProfile
 */

export interface MasterProfile {
  id: number
  name: string
  description?: string
  avatar?: string
  rating?: number
  reviewsCount?: number
  isOnline?: boolean
  services?: MasterService[]
  photos?: MasterPhoto[]
  location?: MasterLocation
  workingHours?: WorkingHours
  contacts?: MasterContacts
}

export interface MasterService {
  id: number
  name: string
  description?: string
  price: number
  duration: number // в минутах
  category?: string
}

export interface MasterPhoto {
  id: number
  url: string
  alt?: string
  isMain?: boolean
}

export interface MasterLocation {
  address: string
  city: string
  coordinates?: {
    lat: number
    lng: number
  }
}

export interface WorkingHours {
  [key: string]: {
    isWorking: boolean
    start?: string
    end?: string
  }
}

export interface MasterContacts {
  phone?: string
  whatsapp?: string
  telegram?: string
  instagram?: string
}

// Props виджета
export interface MasterProfileWidgetProps {
  masterId: number
  compact?: boolean
  showBooking?: boolean
  showReviews?: boolean
}

// Events виджета
export interface MasterProfileWidgetEmits {
  'service-selected': (service: MasterService) => void
  'photo-clicked': (photo: MasterPhoto) => void
  'contact-clicked': (type: string, value: string) => void
  'booking-requested': (masterId: number) => void
}

// Состояние виджета
export interface MasterProfileWidgetState {
  master: MasterProfile | null
  isLoading: boolean
  error: string | null
  selectedService: MasterService | null
}

// API фильтры
export interface MasterProfileFilters {
  includeServices?: boolean
  includePhotos?: boolean
  includeReviews?: boolean
}