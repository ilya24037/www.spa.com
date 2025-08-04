import { ref } from 'vue'

let idCounter = 0

/**
 * Генерирует уникальный ID для элементов формы
 * Важно для доступности (ARIA)
 */
export function useId(prefix = 'id'): string {
  const id = ref(`${prefix}-${++idCounter}-${Math.random().toString(36).substr(2, 9)}`)
  return id.value
}