/**
 * Forms Module - Экспорт всех компонентов и утилит для работы с формами
 */

// Основные компоненты
export { default as FormSection } from './FormSection.vue'
export { default as FormFieldGroup } from './components/FormFieldGroup.vue'
export { default as DynamicFieldList } from './components/DynamicFieldList.vue'

// Специализированные формы
export { default as EducationForm } from './features/EducationForm.vue'
export { default as MediaForm } from './features/MediaForm.vue'

// Composables
export { useForm, useDynamicField, useValidation } from './composables/useForm'

// Типы
export type {
  // Базовые типы
  FormData,
  FormErrors,
  FormState,
  FormField,
  BaseFormField,
  FormFieldType,
  ValidationRule,
  
  // Специфичные типы полей
  TextFormField,
  SelectFormField,
  CheckboxFormField,
  RadioFormField,
  TextareaFormField,
  FileUploadFormField,
  
  // Опции
  SelectOption,
  RadioOption,
  
  // Структуры формы
  FormSection as FormSectionType,
  FormFieldGroup as FormFieldGroupType,
  DynamicFormField,
  
  // Конфигурация
  ValidationConfig,
  AutoSaveConfig,
  FormComponentConfig,
  
  // События
  FormEvents,
  
  // Пропсы компонентов
  FormSectionProps,
  FormFieldGroupProps,
  DynamicFieldProps,
  
  // Специфичные данные
  EducationFormData,
  Course,
  MediaFormData,
  
  // Результаты
  ValidationResult,
  FormHookResult
} from './types/form.types'