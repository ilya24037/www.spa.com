import { ref, computed, watch, nextTick, type Ref } from 'vue'
import { debounce } from '@/src/shared/lib/utils'
import type {
  FormData,
  FormErrors,
  FormState,
  ValidationConfig,
  ValidationResult,
  AutoSaveConfig,
  FormHookResult
} from '../types/form?.types'

/**
 * Основной composable для работы с формами
 */
export function useForm(
  initialData: FormData = {},
  options: {
    validation?: ValidationConfig
    autoSave?: AutoSaveConfig
    onSubmit?: (data: FormData) => Promise<void>
    onValidate?: (data: FormData) => Promise<ValidationResult>
  } = {}
): FormHookResult {
  
  // Состояние формы
  const formData = ref<FormData>({ ...initialData })
  const formErrors = ref<FormErrors>({})
  const touchedFields = ref<Record<string, boolean>>({})
  const dirtyFields = ref<Record<string, boolean>>({})
  const isSubmitting = ref(false)
  const hasSubmitted = ref(false)

  // Конфигурация по умолчанию
  const validationConfig = ref<ValidationConfig>({
    validateOnChange: false,
    validateOnBlur: true,
    validateOnSubmit: true,
    debounceMs: 300,
    showErrorsOnTouch: true,
    ...options?.validation
  })

  // Вычисляемые свойства
  const isValid = computed(() => {
    return Object?.keys(formErrors?.value).length === 0
  })

  const formState = computed<FormState>(() => ({
    data: formData?.value,
    errors: formErrors?.value,
    touched: touchedFields?.value,
    dirty: dirtyFields?.value,
    valid: isValid?.value,
    submitting: isSubmitting?.value,
    submitted: hasSubmitted?.value
  }))

  // Дебаунсированная валидация
  const debouncedValidateField = debounce(async (fieldName: string) => {
    if (options?.onValidate) {
      const result = await options?.onValidate(formData?.value)
      if (result?.errors[fieldName]) {
        formErrors.value[fieldName] = result?.errors[fieldName]
      } else {
        delete formErrors?.value[fieldName]
      }
    }
  }, validationConfig?.value.debounceMs || 300)

  // Методы
  const updateField = (fieldName: string, value: any) => {
    const oldValue = formData?.value[fieldName]
    formData.value[fieldName] = value

    // Отмечаем поле как dirty
    if (oldValue !== value) {
      dirtyFields.value[fieldName] = true
    }

    // Валидация при изменении
    if (validationConfig?.value.validateOnChange) {
      debouncedValidateField(fieldName)
    }

    // Убираем ошибку при изменении значения
    if (formErrors?.value[fieldName] && value !== oldValue) {
      delete formErrors?.value[fieldName]
    }
  }

  const touchField = (fieldName: string) => {
    touchedFields.value[fieldName] = true

    // Валидация при потере фокуса
    if (validationConfig?.value.validateOnBlur) {
      nextTick(() => {
        validateField(fieldName)
      })
    }
  }

  const updateErrors = (errors: FormErrors) => {
    formErrors.value = { ...errors }
  }

  const validateField = async (fieldName: string): Promise<boolean> => {
    if (!options?.onValidate) return true

    try {
      const result = await options?.onValidate(formData?.value)
      
      if (result?.errors[fieldName]) {
        formErrors.value[fieldName] = result?.errors[fieldName]
        return false
      } else {
        delete formErrors?.value[fieldName]
        return true
      }
    } catch (error) {
      console?.error(`Validation error for field ${fieldName}:`, error)
      return false
    }
  }

  const validateForm = async (): Promise<boolean> => {
    if (!options?.onValidate) return true

    try {
      const result = await options?.onValidate(formData?.value)
      formErrors.value = result?.errors
      return result?.valid
    } catch (error) {
      console?.error('Form validation error:', error)
      return false
    }
  }

  const resetForm = () => {
    formData.value = { ...initialData }
    formErrors.value = {}
    touchedFields.value = {}
    dirtyFields.value = {}
    hasSubmitted.value = false
    isSubmitting.value = false
  }

  const submitForm = async (): Promise<void> => {
    if (isSubmitting?.value) return

    isSubmitting.value = true
    hasSubmitted.value = true

    try {
      // Валидация перед отправкой
      if (validationConfig?.value.validateOnSubmit) {
        const isFormValid = await validateForm()
        if (!isFormValid) {
          return
        }
      }

      // Отправка формы
      if (options?.onSubmit) {
        await options?.onSubmit(formData?.value)
      }
    } catch (error) {
      console?.error('Form submission error:', error)
      throw error
    } finally {
      isSubmitting.value = false
    }
  }

  const isDirty = (fieldName?: string): boolean => {
    if (fieldName) {
      return !!dirtyFields?.value[fieldName]
    }
    return Object?.keys(dirtyFields?.value).length > 0
  }

  const isTouched = (fieldName?: string): boolean => {
    if (fieldName) {
      return !!touchedFields?.value[fieldName]
    }
    return Object?.keys(touchedFields?.value).length > 0
  }

  const hasError = (fieldName: string): boolean => {
    return !!formErrors?.value[fieldName]
  }

  const getFieldError = (fieldName: string): string => {
    const error = formErrors?.value[fieldName]
    if (Array?.isArray(error)) {
      return error[0] // Возвращаем первую ошибку
    }
    return error
  }

  // Автосохранение
  if (options?.autoSave?.enabled) {
    const debouncedAutoSave = debounce(async () => {
      const dataToSave = { ...formData?.value }
      
      // Исключаем поля из автосохранения
      if (options?.autoSave?.exclude) {
        options?.autoSave.exclude?.forEach(field => {
          delete dataToSave[field]
        })
      }

      try {
        localStorage?.setItem(options?.autoSave!.key, JSON?.stringify(dataToSave))
      } catch (error) {
        console?.warn('Auto-save failed:', error)
      }
    }, options?.autoSave.debounceMs)

    watch(formData, debouncedAutoSave, { deep: true })

    // Восстанавливаем данные из localStorage при инициализации
    try {
      const savedData = localStorage?.getItem(options?.autoSave.key)
      if (savedData) {
        const parsedData = JSON?.parse(savedData)
        formData.value = { ...initialData, ...parsedData }
      }
    } catch (error) {
      console?.warn('Failed to restore auto-saved data:', error)
    }
  }

  return {
    formState,
    updateField,
    updateErrors,
    validateField,
    validateForm,
    resetForm,
    submitForm,
    isDirty,
    isTouched,
    hasError,
    getFieldError
  }
}

/**
 * Composable для работы с динамическими полями (массивами)
 */
export function useDynamicField<T = any>(
  modelValue: Ref<T[]>,
  itemTemplate: () => T
) {
  const addItem = (): void => {
    modelValue.value.push(itemTemplate())
  }

  const removeItem = (index: number): void => {
    if (index >= 0 && index < modelValue?.value.length) {
      modelValue?.value.splice(index, 1)
    }
  }

  const moveItem = (fromIndex: number, toIndex: number): void => {
    if (fromIndex === toIndex) return
    if (fromIndex < 0 || fromIndex >= modelValue?.value.length) return
    if (toIndex < 0 || toIndex >= modelValue?.value.length) return

    const item = modelValue?.value.splice(fromIndex, 1)[0]
    modelValue?.value.splice(toIndex, 0, item)
  }

  const duplicateItem = (index: number): void => {
    if (index >= 0 && index < modelValue?.value.length) {
      const item = { ...modelValue?.value[index] }
      modelValue?.value.splice(index + 1, 0, item)
    }
  }

  const isEmpty = computed(() => modelValue?.value.length === 0)
  const itemCount = computed(() => modelValue?.value.length)

  return {
    addItem,
    removeItem,
    moveItem,
    duplicateItem,
    isEmpty,
    itemCount
  }
}

/**
 * Composable для валидации
 */
export function useValidation() {
  const createValidator = (rules: Record<string, any>) => {
    return async (data: FormData): Promise<ValidationResult> => {
      const errors: Record<string, string> = {}

      for (const [fieldName, fieldRules] of Object?.entries(rules)) {
        const value = data[fieldName]

        for (const rule of fieldRules) {
          if (typeof rule === 'function') {
            const result = await rule(value, data)
            if (result !== true) {
              errors[fieldName] = result
              break
            }
          }
        }
      }

      return {
        valid: Object?.keys(errors).length === 0,
        errors
      }
    }
  }

  // Встроенные правила валидации
  const rules = {
    required: (message = 'Поле обязательно для заполнения') => 
      (value: any) => !!value || message,

    minLength: (min: number, message?: string) => 
      (value: string) => !value || value?.length >= min || message || `Минимум ${min} символов`,

    maxLength: (max: number, message?: string) => 
      (value: string) => !value || value?.length <= max || message || `Максимум ${max} символов`,

    email: (message = 'Введите корректный email') => 
      (value: string) => !value || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value) || message,

    pattern: (regex: RegExp, message = 'Неверный формат') => 
      (value: string) => !value || regex?.test(value) || message,

    numeric: (message = 'Должно быть числом') => 
      (value: any) => !value || !isNaN(Number(value)) || message,

    min: (min: number, message?: string) => 
      (value: number) => value === undefined || value >= min || message || `Минимальное значение ${min}`,

    max: (max: number, message?: string) => 
      (value: number) => value === undefined || value <= max || message || `Максимальное значение ${max}`
  }

  return {
    createValidator,
    rules
  }
}