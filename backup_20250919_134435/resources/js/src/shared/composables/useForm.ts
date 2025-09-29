import { computed, ref } from 'vue'
import { useErrorHandler } from './useErrorHandler'
import { useAsyncAction } from './useAsyncAction'

export interface FormField {
  value: any
  error: string | null
  touched: boolean
  dirty: boolean
}

export interface FormOptions<T> {
  initialValues: T
  validate?: (values: T) => Record<string, string> | null
  onSubmit: (values: T) => Promise<any>
}

/**
 * Composable для управления формами с валидацией
 * 
 * Использование:
 * const form = useForm({
 *   initialValues: { email: '', password: '' },
 *   validate: (values) => {
 *     if (!values.email) return { email: 'Email обязателен' }
 *     return null
 *   },
 *   onSubmit: async (values) => {
 *     await api.login(values)
 *   }
 * })
 * 
 * В template:
 * <input v-model="form.values.email" @blur="form.touch('email')">
 * <span v-if="form.errors.email">{{ form.errors.email }}</span>
 * <button @click="form.submit()" :disabled={false}="form.loading">Отправить</button>
 */
export function useForm<T extends Record<string, any>>(options: FormOptions<T>) {
  const { initialValues, validate, onSubmit } = options
  
  // Состояние формы
  const values = ref<T>({ ...initialValues })
  const touched = ref<Record<string, boolean>>({})
  const errors = ref<Record<string, string>>({})
  
  // Async action для submit
  const { execute, loading } = useAsyncAction()
  const { validationErrors, clearError } = useErrorHandler()
  
  // Вычисляемые свойства
  const isDirty = computed(() => {
    return JSON.stringify(values.value) !== JSON.stringify(initialValues)
  })
  
  const isValid = computed(() => {
    return Object.keys(errors.value).length === 0
  })
  
  const hasErrors = computed(() => {
    return !isValid.value
  })
  
  // Методы
  const validateField = (field: keyof T) => {
    if (!validate) return
    
    const validationResult = validate(values.value)
    if (validationResult && validationResult[field as string]) {
      errors.value[field as string] = validationResult[field as string] || ''
    } else {
      delete errors.value[field as string]
    }
  }
  
  const validateForm = (): boolean => {
    if (!validate) return true
    
    const validationResult = validate(values.value)
    
    // Очистить старые ошибки
    Object.keys(errors.value).forEach(key => delete errors.value[key])
    
    if (validationResult) {
      Object.assign(errors.value, validationResult)
      return false
    }
    
    return true
  }
  
  const touch = (field: keyof T) => {
    touched.value[field as string] = true
    validateField(field)
  }
  
  const touchAll = () => {
    Object.keys(values.value).forEach(key => {
      touched.value[key] = true
    })
  }
  
  const setFieldValue = (field: keyof T, value: any) => {
    values.value[field] = value
    if (touched.value[field as string]) {
      validateField(field)
    }
  }
  
  const setFieldError = (field: keyof T, error: string) => {
    errors.value[field as string] = error
    touched.value[field as string] = true
  }
  
  const clearFieldError = (field: keyof T) => {
    delete errors.value[field as string]
  }
  
  const reset = () => {
    Object.assign(values.value, initialValues)
    Object.keys(touched.value).forEach(key => delete touched.value[key])
    Object.keys(errors.value).forEach(key => delete errors.value[key])
    clearError()
  }
  
  const submit = async () => {
    touchAll()
    
    if (!validateForm()) {
      return
    }
    
    await execute(
      () => onSubmit(values.value),
      {
        onError: () => {
          // Обработка ошибок валидации от сервера
          Object.entries(validationErrors.value).forEach(([field, messages]) => {
            if (messages && messages[0]) {
              errors.value[field] = messages[0]
            }
          })
        }
      }
    )
  }
  
  const getFieldProps = (field: keyof T) => ({
    value: values.value[field],
    'onUpdate:modelValue': (value: any) => setFieldValue(field, value),
    onBlur: () => touch(field),
    error: errors.value[field as string] || null,
    'aria-invalid': !!errors.value[field as string],
    'aria-describedby': errors.value[field as string] ? `${field as string}-error` : undefined
  })
  
  return {
    // Состояние
    values,
    errors,
    touched,
    loading,
    
    // Вычисляемые
    isDirty,
    isValid,
    hasErrors,
    
    // Методы
    touch,
    touchAll,
    validateField,
    validateForm,
    setFieldValue,
    setFieldError,
    clearFieldError,
    reset,
    submit,
    getFieldProps
  }
}