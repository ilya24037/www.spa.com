/**
 * Генерирует уникальный ID с префиксом
 */
export function generateUniqueId(prefix: string = 'id'): string {
  const timestamp = Date.now().toString(36)
  const randomPart = Math.random().toString(36).substr(2, 5)
  return `${prefix}-${timestamp}-${randomPart}`
}

/**
 * Debounce функция для ограничения частоты вызовов
 */
export function debounce<T extends (...args: any[]) => void>(
  func: T,
  wait: number
): (...args: Parameters<T>) => void {
  let timeout: ReturnType<typeof setTimeout>
  
  return (...args: Parameters<T>) => {
    const later = () => {
      clearTimeout(timeout)
      func(...args)
    }
    
    clearTimeout(timeout)
    timeout = setTimeout(later, wait)
  }
}

/**
 * Throttle функция для ограничения частоты выполнения
 */
export function throttle<T extends (...args: any[]) => void>(
  func: T,
  limit: number
): (...args: Parameters<T>) => void {
  let inThrottle: boolean
  
  return (...args: Parameters<T>) => {
    if (!inThrottle) {
      func(...args)
      inThrottle = true
      setTimeout(() => (inThrottle = false), limit)
    }
  }
}

/**
 * Форматирует дату в читаемый формат
 */
export function formatDate(date: Date | string, locale: string = 'ru-RU'): string {
  const dateObj = typeof date === 'string' ? new Date(date) : date
  
  return dateObj.toLocaleDateString(locale, {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  })
}

/**
 * Обрезает текст до указанной длины
 */
export function truncateText(text: string, maxLength: number): string {
  if (text.length <= maxLength) return text
  return text.substr(0, maxLength).trim() + '...'
}

/**
 * Проверяет является ли значение пустым
 */
export function isEmpty(value: any): boolean {
  if (value === null || value === undefined) return true
  if (typeof value === 'string') return value.trim().length === 0
  if (Array.isArray(value)) return value.length === 0
  if (typeof value === 'object') return Object.keys(value).length === 0
  return false
}

/**
 * Создает задержку (для async функций)
 */
export function sleep(ms: number): Promise<void> {
  return new Promise(resolve => setTimeout(resolve, ms))
}

/**
 * Безопасное парсение JSON
 */
export function safeJsonParse<T = any>(jsonString: string, defaultValue: T): T {
  try {
    return JSON.parse(jsonString)
  } catch {
    return defaultValue
  }
}

/**
 * Копирует текст в буфер обмена
 */
export async function copyToClipboard(text: string): Promise<boolean> {
  try {
    if (navigator.clipboard && window.isSecureContext) {
      await navigator.clipboard.writeText(text)
      return true
    } else {
      // Fallback для старых браузеров
      const textArea = document.createElement('textarea')
      textArea.value = text
      textArea.style.position = 'fixed'
      textArea.style.left = '-999999px'
      textArea.style.top = '-999999px'
      document.body.appendChild(textArea)
      textArea.focus()
      textArea.select()
      
      const result = document.execCommand('copy')
      document.body.removeChild(textArea)
      return result
    }
  } catch {
    return false
  }
}

/**
 * Проверяет поддерживает ли браузер определенную функцию
 */
export function supportsFeature(feature: string): boolean {
  switch (feature) {
    case 'clipboard':
      return 'clipboard' in navigator
    case 'webp':
      const canvas = document.createElement('canvas')
      return canvas.toDataURL('image/webp').indexOf('webp') !== -1
    case 'localStorage':
      try {
        localStorage.setItem('test', 'test')
        localStorage.removeItem('test')
        return true
      } catch {
        return false
      }
    default:
      return false
  }
}

/**
 * Скролл к элементу с плавной анимацией
 */
export function scrollToElement(
  element: Element | string,
  options: ScrollIntoViewOptions = { behavior: 'smooth', block: 'center' }
): void {
  const el = typeof element === 'string' 
    ? document.querySelector(element) 
    : element
    
  if (el) {
    el.scrollIntoView(options)
  }
}

/**
 * Проверяет находится ли элемент в области видимости
 */
export function isElementInViewport(element: Element): boolean {
  const rect = element.getBoundingClientRect()
  const windowHeight = window.innerHeight || document.documentElement.clientHeight
  const windowWidth = window.innerWidth || document.documentElement.clientWidth
  
  return (
    rect.top >= 0 &&
    rect.left >= 0 &&
    rect.bottom <= windowHeight &&
    rect.right <= windowWidth
  )
}

/**
 * Конвертирует camelCase в kebab-case
 */
export function camelToKebab(str: string): string {
  return str.replace(/([a-z0-9]|(?=[A-Z]))([A-Z])/g, '$1-$2').toLowerCase()
}

/**
 * Конвертирует kebab-case в camelCase
 */
export function kebabToCamel(str: string): string {
  return str.replace(/-([a-z])/g, (_, letter) => letter.toUpperCase())
}