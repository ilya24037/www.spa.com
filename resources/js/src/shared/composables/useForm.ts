import { ref, reactive, computed } from 'vue'
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
 * <button @click="form.submit()" :disabled="form.loading">Отправить</button>
 */
export function useForm<T extends Record<string, any>>(options: FormOptions<T>) {
  const { initialValues, validate, onSubmit } = options
  
  // Состояние формы
  const values = reactive<T>({ ...initialValues })
  const touched = reactive<Record<string, boolean>>({})
  const errors = reactive<Record<string, string>>({})
  
  // Async action для submit
  const { execute, loading } = useAsyncAction()
  const { validationErrors, clearError } = useErrorHandler()
  
  // Вычисляемые свойства
  const isDirty = computed(() => {
    return JSON.stringify(values) !== JSON.stringify(initialValues)
  })
  
  const isValid = computed(() => {
    return Object.keys(errors).length === 0
  })
  
  const hasErrors = computed(() => {
    return !isValid.value
  })
  
  // Методы
  const validateField = (field: keyof T) => {
    if (!validate) return
    
    const validationResult = validate(values)
    if (validationResult && validationResult[field as string]) {
      errors[field as string] = validationResult[field as string]
    } else {
      delete errors[field as string]
    }
  }
  
  const validateForm = (): boolean => {
    if (!validate) return true
    
    const validationResult = validate(values)
    
    // Очистить старые ошибки
    Object.keys(errors).forEach(key => delete errors[key])
    
    if (validationResult) {
      Object.assign(errors, validationResult)
      return false
    }
    
    return true
  }
  
  const touch = (field: keyof T) => {
    touched[field as string] = true
    validateField(field)
  }
  
  const touchAll = () => {
    Object.keys(values).forEach(key => {
      touched[key] = true
    })
  }
  
  const setFieldValue = (field: keyof T, value: any) => {
    values[field] = value
    if (touched[field as string]) {
      validateField(field)
    }
  }
  
  const setFieldError = (field: keyof T, error: string) => {
    errors[field as string] = error
    touched[field as string] = true
  }
  
  const clearFieldError = (field: keyof T) => {
    delete errors[field as string]
  }
  
  const reset = () => {
    Object.assign(values, initialValues)
    Object.keys(touched).forEach(key => delete touched[key])
    Object.keys(errors).forEach(key => delete errors[key])
    clearError()
  }
  
  const submit = async () => {
    touchAll()
    
    if (!validateForm()) {
      return
    }
    
    await execute(
      () => onSubmit(values),
      {
        onError: () => {
          // Обработка ошибок валидации от сервера
          Object.entries(validationErrors.value).forEach(([field, messages]) => {
            errors[field] = messages[0]
          })
        }
      }
    )
  }
  
  const getFieldProps = (field: keyof T) => ({
    value: values[field],
    'onUpdate:modelValue': (value: any) => setFieldValue(field, value),
    onBlur: () => touch(field),
    error: errors[field as string] || null,
    'aria-invalid': !!errors[field as string],
    'aria-describedby': errors[field as string] ? `${field}-error` : undefined
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