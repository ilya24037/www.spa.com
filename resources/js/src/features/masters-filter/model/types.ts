/**
 * TypeScript типы для фичи фильтрации мастеров
 * Полная типизация всех интерфейсов и енамов
 */

// =================== ОСНОВНЫЕ ТИПЫ ===================

export interface FilterState {
  services: number[]
  priceRange: PriceRange
  location: LocationFilter
  rating: RatingFilter
  workingHours: WorkingHoursFilter
  serviceLocation: ServiceLocationType[]
  availability: AvailabilityFilter
  sorting: SortingType
}

export interface PriceRange {
  min: number
  max: number
}

export interface LocationFilter {
  address: string
  lat: number | null
  lng: number | null
  radius: number // в километрах
}

export interface RatingFilter {
  min: number // от 0 до 5
  onlyWithReviews: boolean
}

export interface WorkingHoursFilter {
  day: string | null // 'today', 'tomorrow', или ISO дата
  timeFrom: string | null // 'HH:mm'
  timeTo: string | null // 'HH:mm'
}

export interface AvailabilityFilter {
  availableToday: boolean
  availableTomorrow: boolean
  availableThisWeek: boolean
}

// =================== ЕНАМЫ И КОНСТАНТЫ ===================

export type ServiceLocationType = 'home' | 'salon'

export type SortingType = 
  | 'relevance'     // По релевантности
  | 'rating'        // По рейтингу (убыв.)
  | 'price_asc'     // По цене (возр.)
  | 'price_desc'    // По цене (убыв.)
  | 'distance'      // По расстоянию
  | 'reviews_count' // По количеству отзывов
  | 'newest'        // Сначала новые

export const SORTING_OPTIONS: Array<{value: SortingType, label: string}> = [
  { value: 'relevance', label: 'По релевантности' },
  { value: 'rating', label: 'По рейтингу' },
  { value: 'price_asc', label: 'Сначала дешевле' },
  { value: 'price_desc', label: 'Сначала дороже' },
  { value: 'distance', label: 'По расстоянию' },
  { value: 'reviews_count', label: 'По отзывам' },
  { value: 'newest', label: 'Сначала новые' }
]

// =================== ОПЦИИ ДЛЯ СЕЛЕКТОВ ===================

export interface FilterOptions {
  services: ServiceOption[]
  priceRanges: PriceRangeOption[]
  districts: DistrictOption[]
  metros: MetroOption[]
}

export interface ServiceOption {
  id: number
  name: string
  category: string
  averagePrice: number
  mastersCount: number
}

export interface PriceRangeOption {
  min: number
  max: number | null
  label: string
}

export interface DistrictOption {
  id: number
  name: string
  city: string
  mastersCount: number
}

export interface MetroOption {
  id: number
  name: string
  line: string
  color: string
  mastersCount: number
}

// =================== РЕЗУЛЬТАТЫ ФИЛЬТРАЦИИ ===================

export interface FilterResult {
  masters: Master[]
  total: number
  pages: number
  currentPage: number
  perPage: number
  facets: FilterFacets
}

export interface FilterFacets {
  services: Array<{ id: number, name: string, count: number }>
  priceRanges: Array<{ min: number, max: number, count: number }>
  ratings: Array<{ rating: number, count: number }>
  serviceLocations: Array<{ type: ServiceLocationType, count: number }>
  availability: {
    today: number
    tomorrow: number
    thisWeek: number
  }
}

// =================== МАСТЕР ===================

export interface Master {
  id: number
  name: string
  avatar: string | null
  rating: number
  reviewsCount: number
  verified: boolean
  online: boolean
  
  // Профиль
  profile: MasterProfile
  
  // Услуги
  services: MasterService[]
  
  // Локация
  location: MasterLocation
  
  // Доступность
  availability: MasterAvailability
  
  // Статистика
  stats: MasterStats
}

export interface MasterProfile {
  id: number
  bio: string | null
  experience: number // лет
  education: string | null
  certificates: string[]
  languages: string[]
  workingHours: WorkingHours
}

export interface MasterService {
  id: number
  name: string
  description: string | null
  duration: number // минут
  price: number
  category: string
  isPopular: boolean
}

export interface MasterLocation {
  id: number
  address: string | null
  district: string | null
  metro: string | null
  lat: number | null
  lng: number | null
  serviceLocations: ServiceLocationType[]
  workRadius: number | null // км для выезда на дом
}

export interface MasterAvailability {
  isAvailable: boolean
  nextAvailableSlot: string | null // ISO datetime
  slotsToday: number
  slotsTomorrow: number
  slotsThisWeek: number
}

export interface MasterStats {
  totalBookings: number
  repeatedClients: number
  responseTime: number // минут
  cancellationRate: number // %
}

export interface WorkingHours {
  monday: DaySchedule | null
  tuesday: DaySchedule | null
  wednesday: DaySchedule | null
  thursday: DaySchedule | null
  friday: DaySchedule | null
  saturday: DaySchedule | null
  sunday: DaySchedule | null
}

export interface DaySchedule {
  from: string // 'HH:mm'
  to: string // 'HH:mm'
  breaks: TimeSlot[]
}

export interface TimeSlot {
  from: string // 'HH:mm'
  to: string // 'HH:mm'
}

// =================== API ЗАПРОСЫ ===================

export interface FiltersApiRequest {
  services?: string // ID через запятую
  price_min?: number
  price_max?: number
  lat?: number
  lng?: number
  radius?: number
  rating_min?: number
  only_with_reviews?: 0 | 1
  available_day?: string
  time_from?: string
  time_to?: string
  service_location?: string // 'home,salon'
  available_today?: 0 | 1
  available_tomorrow?: 0 | 1
  available_this_week?: 0 | 1
  sort?: SortingType
  page?: number
  per_page?: number
}

export interface FiltersApiResponse {
  success: boolean
  data: FilterResult
  message?: string
}

// =================== КОМПОНЕНТЫ ===================

export interface FilterPanelProps {
  modelValue: boolean // открыта ли панель
  loading?: boolean
  masterCount?: number
}

export interface FilterCategoryProps {
  title: string
  icon: string
  active?: boolean
  count?: number
}

export interface ServiceFilterProps {
  services: ServiceOption[]
  selectedServices: number[]
  loading?: boolean
}

export interface PriceFilterProps {
  min: number
  max: number
  range: [number, number]
  options: PriceRangeOption[]
}

export interface LocationFilterProps {
  address: string
  radius: number
  suggestions: LocationSuggestion[]
  loading?: boolean
}

export interface LocationSuggestion {
  address: string
  lat: number
  lng: number
  type: 'address' | 'metro' | 'district'
}

// =================== СОБЫТИЯ ===================

export interface FilterEvents {
  'update:modelValue': [value: boolean]
  'apply': [filters: FilterState]
  'reset': []
  'close': []
}

export interface ServiceFilterEvents {
  'update:selectedServices': [services: number[]]
  'select': [serviceId: number]
  'unselect': [serviceId: number]
}

export interface PriceFilterEvents {
  'update:range': [range: [number, number]]
  'preset': [option: PriceRangeOption]
}

export interface LocationFilterEvents {
  'update:address': [address: string]
  'update:radius': [radius: number]
  'select': [suggestion: LocationSuggestion]
  'current-location': []
}