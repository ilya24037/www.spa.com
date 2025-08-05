import { logger } from '@/src/shared/lib/logger'

import { ref, Ref } from 'vue'

export interface LoadingState<T = any> {
  isLoading: import("vue").Ref<boolean>
  error: import("vue").Ref<Error | null>
  data: import("vue").Ref<T | null>
  execute: (fn: () => Promise<T>) => Promise<T | undefined>
  reset: () => void
}

/**
 * Composable для управления состояниями загрузки
 * Использование:
 * const { isLoading, error, data, execute } = useLoadingState<User>()
 * await execute(() => api.getUser(id))
 */
export function useLoadingState<T = any>(initialData: T | null = null): LoadingState<T> {
  const isLoading = ref(false)
  const error = ref<Error | null>(null)
  const data = ref<T | null>(initialData)

  const execute = async (fn: () => Promise<T>): Promise<T | undefined> => {
    isLoading.value = true
    error.value = null
    
    try {
      const result = await fn()
      data.value = result
      return result
    } catch (e) {
      error.value = e instanceof Error ? e : new Error(String(e))
      logger.error('[useLoadingState] Error:', e)
      return undefined
    } finally {
      isLoading.value = false
    }
  }

  const reset = () => {
    isLoading.value = false
    error.value = null
    data.value = initialData
  }

  return {
    isLoading,
    error,
    data,
    execute,
    reset
  }
}