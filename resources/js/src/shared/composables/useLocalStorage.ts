import { watch, Ref, ref } from 'vue'
import { logger } from '../lib/logger'

/**
 * Composable для работы с localStorage с реактивностью
 * 
 * Использование:
 * const theme = useLocalStorage('theme', 'light')
 * theme.value = 'dark' // Автоматически сохранится в localStorage
 * 
 * const favorites = useLocalStorage<number[]>('favorites', [])
 * favorites.value.push(123) // Сохранится в localStorage
 */
export function useLocalStorage<T>(
  key: string,
  defaultValue: T,
  options?: {
    serializer?: (value: T) => string
    deserializer?: (value: string) => T
  }
): import("vue").Ref<T> {
  const serializer = options?.serializer || JSON.stringify
  const deserializer = options?.deserializer || JSON.parse
  
  // Функция чтения из localStorage
  const read = (): T => {
    try {
      const item = window.localStorage.getItem(key)
      if (item === null) {
        return defaultValue
      }
      return deserializer(item)
    } catch (error) {
      logger.error(`Error reading localStorage key "${key}"`, error)
      return defaultValue
    }
  }
  
  // Функция записи в localStorage
  const write = (value: T) => {
    try {
      window.localStorage.setItem(key, serializer(value))
    } catch (error) {
      logger.error(`Error writing localStorage key "${key}"`, error)
    }
  }
  
  // Инициализация значения
  const storedValue = read()
  const data = ref<T>(storedValue)
  
  // Синхронизация с localStorage при изменении
  watch(
    data,
    (newValue) => {
      write(newValue)
    },
    { deep: true }
  )
  
  // Слушаем изменения в других вкладках
  window.addEventListener('storage', (e) => {
    if (e.key === key && e.newValue !== null) {
      try {
        data.value = deserializer(e.newValue)
      } catch (error) {
        logger.error(`Error syncing localStorage key "${key}"`, error)
      }
    }
  })
  
  return data
}

/**
 * Composable для работы с sessionStorage
 */
export function useSessionStorage<T>(
  key: string,
  defaultValue: T,
  options?: {
    serializer?: (value: T) => string
    deserializer?: (value: string) => T
  }
): import("vue").Ref<T> {
  const serializer = options?.serializer || JSON.stringify
  const deserializer = options?.deserializer || JSON.parse
  
  const read = (): T => {
    try {
      const item = window.sessionStorage.getItem(key)
      if (item === null) {
        return defaultValue
      }
      return deserializer(item)
    } catch (error) {
      logger.error(`Error reading sessionStorage key "${key}"`, error)
      return defaultValue
    }
  }
  
  const write = (value: T) => {
    try {
      window.sessionStorage.setItem(key, serializer(value))
    } catch (error) {
      logger.error(`Error writing sessionStorage key "${key}"`, error)
    }
  }
  
  const storedValue = read()
  const data = ref<T>(storedValue)
  
  watch(
    data,
    (newValue) => {
      write(newValue)
    },
    { deep: true }
  )
  
  return data
}