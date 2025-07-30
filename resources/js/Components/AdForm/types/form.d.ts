/**
 * TypeScript типы для формы объявления
 * Обеспечивают типобезопасность и лучшую разработку
 */

// === БАЗОВЫЕ ТИПЫ ===

export type WorkFormat = 'individual' | 'salon'
export type ServiceProvider = 'woman' | 'man'
export type ClientType = 'women' | 'men' | 'couples' | 'groups'
export type ServiceLocation = 'incall' | 'outcall' | 'both'
export type ContactMethod = 'any' | 'calls' | 'messages'
export type PriceUnit = 'service' | 'hour' | 'night'

export type HairColor = 'blonde' | 'brunette' | 'brown' | 'red' | 'gray' | 'colored'
export type EyeColor = 'blue' | 'green' | 'brown' | 'gray' | 'hazel'
export type Appearance = 'slavic' | 'european' | 'asian' | 'caucasian' | 'mixed'
export type Nationality = 'russian' | 'ukrainian' | 'belarusian' | 'kazakh' | 'azerbaijani' | 'armenian' | 'georgian' | 'other'

export type Experience = '3260137' | '3260142' | '3260146' | '3260149' | '3260152'
export type EducationLevel = '2' | '3' | '4' | '5' | '6' | '7'

// === ИНТЕРФЕЙСЫ ДАННЫХ ===

export interface MediaFile {
  id: string
  filename: string
  url: string
  size: number
  name: string
  serverPath?: string
  preview?: string
}

export interface PhotoData extends MediaFile {
  isMain?: boolean
  order?: number
}

export interface VideoData extends MediaFile {
  duration?: number
  thumbnail?: string
}

export interface PricingData {
  [key: string]: {
    price: number
    duration?: string
    description?: string
  }
}

export interface ScheduleDay {
  isWorking: boolean
  from?: string
  to?: string
  notes?: string
}

export interface Schedule {
  monday?: ScheduleDay
  tuesday?: ScheduleDay
  wednesday?: ScheduleDay
  thursday?: ScheduleDay
  friday?: ScheduleDay
  saturday?: ScheduleDay
  sunday?: ScheduleDay
}

export interface Features {
  [key: string]: boolean
}

export interface Services {
  [categoryId: string]: {
    [serviceId: string]: {
      enabled: boolean
      price: string
      notes?: string
    }
  }
}

export interface GeoData {
  address?: string
  city?: string
  district?: string
  metro?: string
  coordinates?: {
    lat: number
    lng: number
  }
}

// === МОДУЛИ ФОРМЫ ===

export interface BasicInfoData {
  work_format: WorkFormat | ''
  has_girlfriend: boolean
  service_provider: ServiceProvider[]
  clients: ClientType[]
  description: string
}

export interface PersonalInfoData {
  age: string
  height: string
  weight: string
  breast_size: string
  hair_color: HairColor | ''
  eye_color: EyeColor | ''
  appearance: Appearance | ''
  nationality: Nationality | ''
  features: Features
  additional_features: string
  experience: Experience | ''
  education_level: EducationLevel | ''
}

export interface BusinessInfoData {
  price: string
  price_unit: PriceUnit
  is_starting_price: boolean
  pricing_data: PricingData
  contacts_per_hour: string
  new_client_discount: string
  gift: string
  services: Services
  services_additional_info: string
  schedule: Schedule
  schedule_notes: string
}

export interface LocationInfoData {
  service_location: ServiceLocation[]
  outcall_locations: string[]
  taxi_option: string
  geo: GeoData
  phone: string
  contact_method: ContactMethod
  whatsapp: string
  telegram: string
}

export interface MediaInfoData {
  photos: PhotoData[]
  video: VideoData | null
}

// === ГЛАВНЫЙ ИНТЕРФЕЙС ФОРМЫ ===

export interface AdFormData 
  extends BasicInfoData, 
          PersonalInfoData, 
          BusinessInfoData, 
          LocationInfoData, 
          MediaInfoData {
  // Мета-информация
  category: string
  specialty: string
}

// === ТИПЫ ДЛЯ ВАЛИДАЦИИ ===

export interface ValidationRule {
  type: 'required' | 'minLength' | 'maxLength' | 'min' | 'max' | 'email' | 'phone' | 'url' | 'custom'
  value?: any
  message?: string
  validator?: (value: any) => boolean
}

export interface ValidationErrors {
  [fieldName: string]: string
}

export interface FieldValidationConfig {
  value: any
  rules: (string | ValidationRule | ((value: any) => string | null))[]
}

// === ТИПЫ ДЛЯ КОМПОНЕНТОВ ===

export interface FormSectionProps {
  title?: string
  hint?: string
  required?: boolean
  errors?: ValidationErrors
  errorKeys?: string[]
}

export interface FormFieldProps {
  label?: string
  hint?: string
  error?: string
  required?: boolean
  fieldId?: string
}

export interface ActionButtonProps {
  variant?: 'primary' | 'secondary' | 'success' | 'danger' | 'ghost'
  size?: 'small' | 'medium' | 'large'
  type?: string
  disabled?: boolean
  loading?: boolean
  text?: string
  fullWidth?: boolean
}

// === ТИПЫ ДЛЯ STORE ===

export interface AdFormStoreState {
  formData: AdFormData
  isLoading: boolean
  isSaving: boolean
  hasUnsavedChanges: boolean
  lastSavedAt: Date | null
  adId: string | number | null
  isEditMode: boolean
  currentStep: number
  totalSteps: number
  errors: ValidationErrors
  hasErrors: boolean
}

export interface ModuleData {
  basicInfo: BasicInfoData
  personalInfo: PersonalInfoData
  businessInfo: BusinessInfoData
  locationInfo: LocationInfoData
  mediaInfo: MediaInfoData
}

export interface ModuleCompletionPercentage {
  basicInfo: number
  personalInfo: number
  businessInfo: number
  locationInfo: number
  mediaInfo: number
}

// === ТИПЫ ДЛЯ API ===

export interface ApiResponse<T = any> {
  success: boolean
  data?: T
  message?: string
  errors?: ValidationErrors
}

export interface AdFormApiResponse extends ApiResponse {
  data?: {
    ad?: {
      id: string | number
      [key: string]: any
    }
  }
}

// === ТИПЫ ДЛЯ КОМПОЗАБЛОВ ===

export interface UseFormModuleOptions {
  trackChanges?: boolean
  validateOnChange?: boolean
  debounceMs?: number
}

export interface UseFormModuleReturn {
  hasChanges: Ref<boolean>
  isValid: ComputedRef<boolean>
  completionPercentage: ComputedRef<number>
  createLocalState: (propsList: string[]) => Record<string, Ref<any>>
  watchProps: (propsList: string[], localState: Record<string, Ref<any>>) => void
  createUpdateFunctions: (propsList: string[], localState: Record<string, Ref<any>>) => Record<string, () => void>
  debounce: (func: Function, wait: number) => Function
  validateField: (fieldName: string, value: any, rules?: any) => string[]
  resetForm: () => void
  extractFormData: (data: any) => any
  checkForChanges: () => void
}

export interface UseFieldValidationReturn {
  errors: Ref<ValidationErrors>
  touchedFields: Ref<Set<string>>
  errorCount: ComputedRef<number>
  hasErrors: ComputedRef<boolean>
  allErrors: ComputedRef<string[]>
  validateField: (fieldName: string, value: any, rules?: (string | ValidationRule | Function)[]) => boolean
  validateAll: (fieldsConfig: Record<string, FieldValidationConfig>) => boolean
  touchField: (fieldName: string) => void
  clearFieldError: (fieldName: string) => void
  clearAllErrors: () => void
  hasFieldError: (fieldName: string) => boolean
  getFieldError: (fieldName: string) => string | null
  isFieldTouched: (fieldName: string) => boolean
  createFieldValidator: (fieldName: string, rules: (string | ValidationRule | Function)[]) => any
  validationRules: Record<string, Function>
}

// === ГЛОБАЛЬНЫЕ ТИПЫ ===

declare global {
  interface Window {
    // Можно добавить глобальные типы при необходимости
  }
}

// Экспорт для использования в других файлах
export * from './form.d.ts'