import { ref } from 'vue'
import { useErrorHandler } from './useErrorHandler'
import { useToast } from './useToast'

export interface AsyncActionOptions {
  successMessage?: string
  errorMessage?: string
  showToast?: boolean
  onSuccess?: (data: any) => void
  onError?: (error: Error) => void
}

/**
 * Composable для выполнения асинхронных действий с обработкой состояний
 * 
 * Использование:
 * const { execute, loading, error } = useAsyncAction()
 * 
 * const handleSubmit = () => {
 *   execute(
 *     () => api.saveData(formData),
 *     {
 *       successMessage: 'Данные сохранены',
 *       onSuccess: (result) => router.push('/success')
 *     }
 *   )
 * }
 */
export function useAsyncAction<T = any>() {
  const loading = ref(false)
  const error = ref<Error | null>(null)
  const result = ref<T | null>(null)
  
  const { handleError } = useErrorHandler()
  const toast = useToast()
  
  const execute = async (
    action: () => Promise<T>,
    options: AsyncActionOptions = {}
  ): Promise<T | null> => {
    const {
      successMessage,
      // errorMessage,
      showToast = true,
      onSuccess,
      onError
    } = options
    
    loading.value = true
    error.value = null
    result.value = null
    
    try {
      const data = await action()
      result.value = data
      
      if (successMessage && showToast) {
        toast.success(successMessage)
      }
      
      if (onSuccess) {
        onSuccess(data)
      }
      
      return data
    } catch (e) {
      error.value = e instanceof Error ? e : new Error(String(e))
      
      if (showToast) {
        // const message = errorMessage || error.value.message || 'Произошла ошибка'
        handleError(e, 'network')
      }
      
      if (onError) {
        onError(error.value)
      }
      
      return null
    } finally {
      loading.value = false
    }
  }
  
  const reset = () => {
    loading.value = false
    error.value = null
    result.value = null
  }
  
  return {
    loading,
    error,
    result,
    execute,
    reset
  }
}