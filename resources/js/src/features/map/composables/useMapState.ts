import { ref, computed, type Ref } from 'vue'

export interface MapStateOptions {
  initialLoading?: boolean
  initialError?: string | null
}

export function useMapState(options: MapStateOptions = {}) {
  // State refs
  const isLoading = ref(options.initialLoading ?? true)
  const error = ref<string | null>(options.initialError ?? null)
  const errorDetails = ref<string | null>(null)
  const isInitialized = ref(false)

  // Computed properties
  const hasError = computed(() => !!error.value)
  const isReady = computed(() => !isLoading.value && !error.value && isInitialized.value)

  // Methods
  const setLoading = (loading: boolean) => {
    isLoading.value = loading
  }

  const setError = (errorMsg: string | null, details?: string | null) => {
    error.value = errorMsg
    errorDetails.value = details ?? null
    isLoading.value = false
  }

  const clearError = () => {
    error.value = null
    errorDetails.value = null
  }

  const setReady = () => {
    isLoading.value = false
    error.value = null
    errorDetails.value = null
    isInitialized.value = true
  }

  const reset = () => {
    isLoading.value = true
    error.value = null
    errorDetails.value = null
    isInitialized.value = false
  }

  return {
    // State
    isLoading: isLoading as Readonly<Ref<boolean>>,
    error: error as Readonly<Ref<string | null>>,
    errorDetails: errorDetails as Readonly<Ref<string | null>>,
    isInitialized: isInitialized as Readonly<Ref<boolean>>,
    
    // Computed
    hasError,
    isReady,
    
    // Methods
    setLoading,
    setError,
    clearError,
    setReady,
    reset
  }
}