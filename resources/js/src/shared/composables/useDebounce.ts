import { ref, customRef, Ref } from 'vue'

/**
 * Создаёт debounced версию значения
 * 
 * Использование:
 * const searchQuery = ref('')
 * const debouncedQuery = useDebounce(searchQuery, 300)
 * 
 * watch(debouncedQuery, (value) => {
 *   // Выполнится через 300ms после последнего изменения
 *   searchAPI(value)
 * })
 */
export function useDebounce<T>(value: Ref<T>, delay = 300): Ref<T> {
  let timeout: NodeJS.Timeout
  
  return customRef((track, trigger) => {
    return {
      get() {
        track()
        return value.value
      },
      set(newValue: T) {
        clearTimeout(timeout)
        timeout = setTimeout(() => {
          value.value = newValue
          trigger()
        }, delay)
      }
    }
  })
}

/**
 * Создаёт debounced функцию
 * 
 * Использование:
 * const debouncedSearch = useDebounceFn((query: string) => {
 *   console.log('Searching:', query)
 * }, 500)
 * 
 * // В template:
 * <input @input="debouncedSearch($event.target.value)">
 */
export function useDebounceFn<T extends (...args: any[]) => any>(
  fn: T,
  delay = 300
): (...args: Parameters<T>) => void {
  let timeout: NodeJS.Timeout | null = null
  
  return (...args: Parameters<T>) => {
    if (timeout) {
      clearTimeout(timeout)
    }
    
    timeout = setTimeout(() => {
      fn(...args)
    }, delay)
  }
}

/**
 * Создаёт throttled функцию
 * 
 * Использование:
 * const throttledScroll = useThrottleFn(() => {
 *   console.log('Scrolling')
 * }, 100)
 * 
 * window.addEventListener('scroll', throttledScroll)
 */
export function useThrottleFn<T extends (...args: any[]) => any>(
  fn: T,
  delay = 300
): (...args: Parameters<T>) => void {
  let lastCall = 0
  let timeout: NodeJS.Timeout | null = null
  
  return (...args: Parameters<T>) => {
    const now = Date.now()
    
    if (now - lastCall >= delay) {
      fn(...args)
      lastCall = now
    } else {
      if (timeout) {
        clearTimeout(timeout)
      }
      
      timeout = setTimeout(() => {
        fn(...args)
        lastCall = Date.now()
      }, delay - (now - lastCall))
    }
  }
}