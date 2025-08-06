/**
 * Типы для системы форм
 */

// Базовые типы полей формы
export type FormFieldType = 'text' | 'email' | 'password' | 'number' | 'tel' | 'url' | 'date' | 'datetime-local' | 'time'

export type ValidationRule = 
  | 'required'
  | 'email' 
  | 'min'
  | 'max'
  | 'minLength'
  | 'maxLength'
  | 'pattern'
  | 'custom'

// Базовый интерфейс поля формы
export interface BaseFormField {
  name: string
  label: string
  placeholder?: string
  hint?: string
  disabled?: boolean
  readonly?: boolean
  required?: boolean
  rules?: ValidationRule[]
  error?: string
  value?: any
}

// Специфичные типы полей
export interface TextFormField extends BaseFormField {
  type: FormFieldType
  minLength?: number
  maxLength?: number
  pattern?: string
}

export interface SelectFormField extends BaseFormField {
  type: 'select'
  options: SelectOption[]
  multiple?: boolean
  searchable?: boolean
}

export interface CheckboxFormField extends BaseFormField {
  type: 'checkbox'
  checked?: boolean
}

export interface RadioFormField extends BaseFormField {
  type: 'radio'
  options: RadioOption[]
}

export interface TextareaFormField extends BaseFormField {
  type: 'textarea'
  rows?: number
  minLength?: number
  maxLength?: number
  autoResize?: boolean
}

export interface FileUploadFormField extends BaseFormField {
  type: 'file'
  accept?: string
  multiple?: boolean
  maxSize?: number
  maxFiles?: number
}

// Опции для select и radio
export interface SelectOption {
  value: string | number
  label: string
  disabled?: boolean
  description?: string
}

export interface RadioOption {
  value: string | number
  label: string
  disabled?: boolean
  description?: string
}

// Объединенный тип поля формы
export type FormField = 
  | TextFormField
  | SelectFormField
  | CheckboxFormField
  | RadioFormField
  | TextareaFormField
  | FileUploadFormField

// Секция формы
export interface FormSection {
  id: string
  title: string
  description?: string
  fields: FormField[]
  collapsible?: boolean
  collapsed?: boolean
  required?: boolean
  visible?: boolean
  order?: number
}

// Группа полей
export interface FormFieldGroup {
  id: string
  label?: string
  description?: string
  fields: FormField[]
  layout?: 'row' | 'column'
  responsive?: boolean
}

// Динамическое поле (для списков)
export interface DynamicFormField {
  id: string
  template: FormField[]
  minItems?: number
  maxItems?: number
  addButtonText?: string
  removeButtonText?: string
  emptyStateText?: string
}

// Данные формы
export type FormData = Record<string, any>

// Ошибки формы
export type FormErrors = Record<string, string | string[]>

// Состояние формы
export interface FormState {
  data: FormData
  errors: FormErrors
  touched: Record<string, boolean>
  dirty: Record<string, boolean>
  valid: boolean
  submitting: boolean
  submitted: boolean
}

// Настройки валидации
export interface ValidationConfig {
  validateOnChange?: boolean
  validateOnBlur?: boolean
  validateOnSubmit?: boolean
  debounceMs?: number
  showErrorsOnTouch?: boolean
}

// События формы
export interface FormEvents {
  'field-change': [fieldName: string, value: any]
  'field-blur': [fieldName: string]
  'field-focus': [fieldName: string]
  'section-toggle': [sectionId: string, collapsed: boolean]
  'form-submit': [data: FormData]
  'form-reset': []
  'form-valid': [valid: boolean]
}

// Пропсы компонентов формы
export interface FormSectionProps {
  section: FormSection
  modelValue: FormData
  errors?: FormErrors
  disabled?: boolean
  readonly?: boolean
  validationConfig?: ValidationConfig
}

export interface FormFieldGroupProps {
  group: FormFieldGroup
  modelValue: FormData
  errors?: FormErrors
  disabled?: boolean
  readonly?: boolean
}

export interface DynamicFieldProps {
  field: DynamicFormField
  modelValue: any[]
  errors?: FormErrors
  disabled?: boolean
  readonly?: boolean
}

// Конфигурация компонентов
export interface FormComponentConfig {
  showRequiredIndicator?: boolean
  requiredIndicatorText?: string
  errorDisplayMode?: 'inline' | 'tooltip' | 'summary'
  fieldSpacing?: 'tight' | 'normal' | 'loose'
  labelPosition?: 'top' | 'left' | 'floating'
  submitButtonText?: string
  resetButtonText?: string
  showResetButton?: boolean
}

// Специфичные типы для образования
export interface EducationFormData {
  education_level: string
  university: string
  specialization: string
  graduation_year: number
  courses: Course[]
  has_certificates: boolean
  certificate_photos: File[]
  experience_years: string
  work_history: string
}

export interface Course {
  id: string | number
  name: string
  organization: string
  year: number
  duration: string
  description: string
  certificate_number?: string
}

// Специфичные типы для медиа
export interface MediaFormData {
  photos: File[]
  video?: File
  media_settings: string[]
}

// Результат валидации
export interface ValidationResult {
  valid: boolean
  errors: Record<string, string>
  warnings?: Record<string, string>
}

// Конфигурация автосохранения
export interface AutoSaveConfig {
  enabled: boolean
  debounceMs: number
  key: string
  exclude?: string[]
}

// Хук форм
export interface FormHookResult {
  formState: FormState
  updateField: (fieldName: string, value: any) => void
  updateErrors: (errors: FormErrors) => void
  validateField: (fieldName: string) => Promise<boolean>
  validateForm: () => Promise<boolean>
  resetForm: () => void
  submitForm: () => Promise<void>
  isDirty: (fieldName?: string) => boolean
  isTouched: (fieldName?: string) => boolean
  hasError: (fieldName: string) => boolean
  getFieldError: (fieldName: string) => string | undefined
}