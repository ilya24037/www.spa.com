/**
 * Composable для управления клавиатурной навигацией в поиске
 * Обрабатывает стрелки, Enter, Escape и Tab
 */

import { ref, onMounted, onUnmounted, type Ref } from 'vue'
import type { 
  UseSearchKeyboardOptions, 
  UseSearchKeyboardReturn,
  NAVIGATION_KEYS 
} from '../model/search.types'

/**
 * Клавиши для навигации
 */
const KEYS = {
  ARROW_UP: 'ArrowUp',
  ARROW_DOWN: 'ArrowDown',
  ENTER: 'Enter',
  ESCAPE: 'Escape',
  TAB: 'Tab',
  HOME: 'Home',
  END: 'End',
  PAGE_UP: 'PageUp',
  PAGE_DOWN: 'PageDown'
} as const

/**
 * Composable для клавиатурной навигации
 */
export function useSearchKeyboard(
  options: UseSearchKeyboardOptions
): UseSearchKeyboardReturn {
  const {
    itemsCount,
    onSelect,
    onEnter,
    onEscape,
    enabled = true
  } = options

  // State
  const selectedIndex = ref(-1)
  const isNavigating = ref(false)

  /**
   * Сброс выделения
   */
  const resetSelection = (): void => {
    selectedIndex.value = -1
    isNavigating.value = false
  }

  /**
   * Выбор следующего элемента
   */
  const selectNext = (): void => {
    if (itemsCount === 0) return

    isNavigating.value = true
    
    if (selectedIndex.value < itemsCount - 1) {
      selectedIndex.value++
    } else {
      // Циклический переход к началу
      selectedIndex.value = 0
    }
  }

  /**
   * Выбор предыдущего элемента
   */
  const selectPrevious = (): void => {
    if (itemsCount === 0) return

    isNavigating.value = true
    
    if (selectedIndex.value > 0) {
      selectedIndex.value--
    } else if (selectedIndex.value === 0) {
      // Циклический переход к концу
      selectedIndex.value = itemsCount - 1
    } else {
      // Если ничего не выбрано, выбираем последний
      selectedIndex.value = itemsCount - 1
    }
  }

  /**
   * Выбор первого элемента
   */
  const selectFirst = (): void => {
    if (itemsCount === 0) return
    
    isNavigating.value = true
    selectedIndex.value = 0
  }

  /**
   * Выбор последнего элемента
   */
  const selectLast = (): void => {
    if (itemsCount === 0) return
    
    isNavigating.value = true
    selectedIndex.value = itemsCount - 1
  }

  /**
   * Переход на страницу вверх (5 элементов)
   */
  const pageUp = (): void => {
    if (itemsCount === 0) return
    
    isNavigating.value = true
    const newIndex = Math.max(0, selectedIndex.value - 5)
    selectedIndex.value = newIndex
  }

  /**
   * Переход на страницу вниз (5 элементов)
   */
  const pageDown = (): void => {
    if (itemsCount === 0) return
    
    isNavigating.value = true
    const newIndex = Math.min(itemsCount - 1, selectedIndex.value + 5)
    selectedIndex.value = newIndex
  }

  /**
   * Обработчик нажатия клавиш
   */
  const handleKeyDown = (event: KeyboardEvent): void => {
    if (!enabled) return

    const key = event.key

    switch (key) {
      case KEYS.ARROW_DOWN:
        event.preventDefault()
        selectNext()
        break

      case KEYS.ARROW_UP:
        event.preventDefault()
        selectPrevious()
        break

      case KEYS.ENTER:
        event.preventDefault()
        if (selectedIndex.value >= 0) {
          onSelect(selectedIndex.value)
        } else {
          onEnter()
        }
        break

      case KEYS.ESCAPE:
        event.preventDefault()
        resetSelection()
        onEscape()
        break

      case KEYS.TAB:
        // Позволяем Tab работать по умолчанию, но сбрасываем выделение
        resetSelection()
        break

      case KEYS.HOME:
        if (event.ctrlKey || event.metaKey) {
          event.preventDefault()
          selectFirst()
        }
        break

      case KEYS.END:
        if (event.ctrlKey || event.metaKey) {
          event.preventDefault()
          selectLast()
        }
        break

      case KEYS.PAGE_UP:
        event.preventDefault()
        pageUp()
        break

      case KEYS.PAGE_DOWN:
        event.preventDefault()
        pageDown()
        break

      default:
        // Сбрасываем навигацию при вводе текста
        if (key.length === 1 && !event.ctrlKey && !event.metaKey) {
          resetSelection()
        }
    }
  }

  /**
   * Установка индекса напрямую
   */
  const setSelectedIndex = (index: number): void => {
    if (index >= -1 && index < itemsCount) {
      selectedIndex.value = index
      isNavigating.value = index >= 0
    }
  }

  /**
   * Проверка, выбран ли элемент
   */
  const isSelected = (index: number): boolean => {
    return selectedIndex.value === index && isNavigating.value
  }

  /**
   * Получение выбранного элемента для скролла
   */
  const scrollToSelected = (containerRef: Ref<HTMLElement | null>): void => {
    if (!containerRef.value || selectedIndex.value < 0) return

    const container = containerRef.value
    const items = container.querySelectorAll('[data-search-item]')
    const selectedItem = items[selectedIndex.value] as HTMLElement

    if (selectedItem) {
      const containerRect = container.getBoundingClientRect()
      const itemRect = selectedItem.getBoundingClientRect()

      // Проверяем, видим ли элемент
      const isAbove = itemRect.top < containerRect.top
      const isBelow = itemRect.bottom > containerRect.bottom

      if (isAbove) {
        // Скроллим вверх
        selectedItem.scrollIntoView({ block: 'start', behavior: 'smooth' })
      } else if (isBelow) {
        // Скроллим вниз
        selectedItem.scrollIntoView({ block: 'end', behavior: 'smooth' })
      }
    }
  }

  /**
   * Подсветка выбранного элемента
   */
  const highlightSelected = (containerRef: Ref<HTMLElement | null>): void => {
    if (!containerRef.value) return

    const container = containerRef.value
    const items = container.querySelectorAll('[data-search-item]')

    items.forEach((item, index) => {
      if (index === selectedIndex.value && isNavigating.value) {
        item.classList.add('bg-gray-100', 'outline', 'outline-2', 'outline-blue-500')
        item.setAttribute('aria-selected', 'true')
      } else {
        item.classList.remove('bg-gray-100', 'outline', 'outline-2', 'outline-blue-500')
        item.setAttribute('aria-selected', 'false')
      }
    })
  }

  return {
    selectedIndex,
    handleKeyDown,
    resetSelection,
    selectNext,
    selectPrevious,
    selectFirst,
    selectLast,
    pageUp,
    pageDown,
    setSelectedIndex,
    isSelected,
    isNavigating,
    scrollToSelected,
    highlightSelected
  }
}

/**
 * Хук для добавления клавиатурной навигации к элементу
 */
export function useKeyboardNavigation(
  elementRef: Ref<HTMLElement | null>,
  options: UseSearchKeyboardOptions
) {
  const keyboard = useSearchKeyboard(options)

  const handleKeyDown = (event: KeyboardEvent) => {
    keyboard.handleKeyDown(event)
  }

  onMounted(() => {
    if (elementRef.value) {
      elementRef.value.addEventListener('keydown', handleKeyDown)
      
      // Добавляем ARIA атрибуты
      elementRef.value.setAttribute('role', 'combobox')
      elementRef.value.setAttribute('aria-expanded', 'true')
      elementRef.value.setAttribute('aria-haspopup', 'listbox')
    }
  })

  onUnmounted(() => {
    if (elementRef.value) {
      elementRef.value.removeEventListener('keydown', handleKeyDown)
    }
  })

  return keyboard
}

/**
 * Хелпер для создания навигации по списку
 */
export function createListNavigation(
  items: Ref<any[]>,
  onSelect: (item: any, index: number) => void
) {
  const currentIndex = ref(-1)

  const navigate = (direction: 'up' | 'down' | 'first' | 'last') => {
    const count = items.value.length
    if (count === 0) return

    switch (direction) {
      case 'up':
        currentIndex.value = currentIndex.value > 0 
          ? currentIndex.value - 1 
          : count - 1
        break
        
      case 'down':
        currentIndex.value = currentIndex.value < count - 1
          ? currentIndex.value + 1
          : 0
        break
        
      case 'first':
        currentIndex.value = 0
        break
        
      case 'last':
        currentIndex.value = count - 1
        break
    }
  }

  const selectCurrent = () => {
    if (currentIndex.value >= 0 && currentIndex.value < items.value.length) {
      onSelect(items.value[currentIndex.value], currentIndex.value)
    }
  }

  const reset = () => {
    currentIndex.value = -1
  }

  return {
    currentIndex,
    navigate,
    selectCurrent,
    reset
  }
}

/**
 * Хелпер для отслеживания фокуса внутри контейнера
 */
export function useFocusTrap(containerRef: Ref<HTMLElement | null>) {
  const focusableSelectors = [
    'a[href]',
    'button:not([disabled])',
    'input:not([disabled])',
    'select:not([disabled])',
    'textarea:not([disabled])',
    '[tabindex]:not([tabindex="-1"])'
  ]

  const getFocusableElements = (): HTMLElement[] => {
    if (!containerRef.value) return []
    
    const elements = containerRef.value.querySelectorAll<HTMLElement>(
      focusableSelectors.join(',')
    )
    
    return Array.from(elements)
  }

  const trapFocus = (event: KeyboardEvent) => {
    if (event.key !== 'Tab') return

    const focusableElements = getFocusableElements()
    if (focusableElements.length === 0) return

    const firstElement = focusableElements[0]
    const lastElement = focusableElements[focusableElements.length - 1]
    const activeElement = document.activeElement as HTMLElement

    if (event.shiftKey) {
      // Shift + Tab
      if (activeElement === firstElement) {
        event.preventDefault()
        lastElement.focus()
      }
    } else {
      // Tab
      if (activeElement === lastElement) {
        event.preventDefault()
        firstElement.focus()
      }
    }
  }

  const activate = () => {
    if (containerRef.value) {
      containerRef.value.addEventListener('keydown', trapFocus)
      
      // Фокус на первый элемент
      const focusableElements = getFocusableElements()
      if (focusableElements.length > 0) {
        focusableElements[0].focus()
      }
    }
  }

  const deactivate = () => {
    if (containerRef.value) {
      containerRef.value.removeEventListener('keydown', trapFocus)
    }
  }

  return {
    activate,
    deactivate,
    getFocusableElements
  }
}