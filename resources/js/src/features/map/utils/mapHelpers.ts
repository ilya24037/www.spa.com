/**
 * Утилиты карты
 * Размер: 50 строк
 */
import { MOBILE_BREAKPOINT } from './mapConstants'
import type { Coordinates } from '../core/MapStore'

/**
 * Определение мобильного устройства
 */
export function isMobileDevice(): boolean {
  if (typeof window === 'undefined') return false
  
  const userAgent = window.navigator.userAgent
  const mobileRegex = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i
  
  return mobileRegex.test(userAgent) || window.innerWidth < MOBILE_BREAKPOINT
}

/**
 * Парсинг координат из строки
 */
export function parseCoordinates(value: string): Coordinates | null {
  if (!value) return null
  
  const parts = value.split(',')
  if (parts.length !== 2) return null
  
  const lat = parseFloat(parts[0].trim())
  const lng = parseFloat(parts[1].trim())
  
  if (isNaN(lat) || isNaN(lng)) return null
  
  return { lat, lng }
}

/**
 * Форматирование координат в строку
 */
export function formatCoordinates(coords: Coordinates): string {
  return `${coords.lat},${coords.lng}`
}

/**
 * Валидация координат
 */
export function isValidCoordinates(coords: Coordinates): boolean {
  return !isNaN(coords.lat) && 
         !isNaN(coords.lng) && 
         coords.lat >= -90 && 
         coords.lat <= 90 &&
         coords.lng >= -180 && 
         coords.lng <= 180
}

/**
 * Генерация уникального ID для карты
 */
export function generateMapId(): string {
  return `map-${Math.random().toString(36).substr(2, 9)}`
}

/**
 * Debounce функция
 */
export function debounce<T extends (...args: any[]) => any>(
  func: T,
  delay: number
): T {
  let timeoutId: NodeJS.Timeout
  
  return ((...args: Parameters<T>) => {
    clearTimeout(timeoutId)
    timeoutId = setTimeout(() => func.apply(null, args), delay)
  }) as T
}