/**
 * Композабл для работы с Card компонентом
 * 
 * Использование:
 * const { cardState, toggleLoading, toggleDisabled } = useCard()
 */

import { ref, computed, readonly } from 'vue'
import type { CardVariant, CardSize, CardOptions } from './Card.types'

export interface CardState {
  loading: boolean
  disabled: boolean
  variant: CardVariant
  size: CardSize
  hoverable: boolean
}

export function useCard(initialOptions?: CardOptions) {
  const state = ref<CardState>({
    loading: false,
    disabled: false,
    variant: initialOptions?.variant ?? 'default',
    size: initialOptions?.size ?? 'medium',
    hoverable: initialOptions?.hoverable ?? false
  })

  /**
   * Переключить состояние загрузки
   */
  const toggleLoading = (loading?: boolean) => {
    state.value.loading = loading ?? !state.value.loading
  }

  /**
   * Переключить заблокированное состояние
   */
  const toggleDisabled = (disabled?: boolean) => {
    state.value.disabled = disabled ?? !state.value.disabled
  }

  /**
   * Установить вариант стиля
   */
  const setVariant = (variant: CardVariant) => {
    state.value.variant = variant
  }

  /**
   * Установить размер
   */
  const setSize = (size: CardSize) => {
    state.value.size = size
  }

  /**
   * Переключить интерактивность
   */
  const toggleHoverable = (hoverable?: boolean) => {
    state.value.hoverable = hoverable ?? !state.value.hoverable
  }

  /**
   * Проверка на интерактивность
   */
  const isInteractive = computed(() => 
    state.value.hoverable && !state.value.disabled && !state.value.loading
  )

  /**
   * Проверка на доступность
   */
  const isClickable = computed(() => 
    !state.value.disabled && !state.value.loading
  )

  /**
   * Сброс состояния к начальному
   */
  const reset = () => {
    state.value.loading = false
    state.value.disabled = false
    state.value.variant = initialOptions?.variant ?? 'default'
    state.value.size = initialOptions?.size ?? 'medium'
    state.value.hoverable = initialOptions?.hoverable ?? false
  }

  /**
   * Асинхронное действие с автоматическим управлением loading
   */
  const withLoading = async <T>(action: () => Promise<T>): Promise<T> => {
    try {
      toggleLoading(true)
      return await action()
    } finally {
      toggleLoading(false)
    }
  }

  /**
   * Временно заблокировать карточку
   */
  const temporaryDisable = (duration: number = 2000) => {
    toggleDisabled(true)
    setTimeout(() => {
      toggleDisabled(false)
    }, duration)
  }

  return {
    // Состояние
    state: readonly(state),
    
    // Геттеры
    isInteractive,
    isClickable,
    
    // Действия
    toggleLoading,
    toggleDisabled,
    setVariant,
    setSize,
    toggleHoverable,
    reset,
    
    // Утилиты
    withLoading,
    temporaryDisable
  }
}

/**
 * Композабл для коллекции карточек
 */
export function useCardCollection<T = any>(items: T[] = []) {
  const selectedIds = ref<Set<string | number>>(new Set())
  const loadingIds = ref<Set<string | number>>(new Set())

  /**
   * Выбрать/снять выбор карточки
   */
  const toggleSelection = (id: string | number) => {
    if (selectedIds.value.has(id)) {
      selectedIds.value.delete(id)
    } else {
      selectedIds.value.add(id)
    }
  }

  /**
   * Выбрать все карточки
   */
  const selectAll = (getIdFn: (item: T) => string | number) => {
    items.forEach(item => {
      selectedIds.value.add(getIdFn(item))
    })
  }

  /**
   * Снять выбор со всех карточек
   */
  const clearSelection = () => {
    selectedIds.value.clear()
  }

  /**
   * Проверить выбрана ли карточка
   */
  const isSelected = (id: string | number) => {
    return selectedIds.value.has(id)
  }

  /**
   * Установить состояние загрузки для карточки
   */
  const setLoading = (id: string | number, loading: boolean) => {
    if (loading) {
      loadingIds.value.add(id)
    } else {
      loadingIds.value.delete(id)
    }
  }

  /**
   * Проверить загружается ли карточка
   */
  const isLoading = (id: string | number) => {
    return loadingIds.value.has(id)
  }

  /**
   * Выполнить действие для выбранных карточек
   */
  const executeForSelected = async <R>(
    action: (id: string | number) => Promise<R>
  ): Promise<R[]> => {
    const selectedArray = Array.from(selectedIds.value)
    
    return Promise.all(
      selectedArray.map(id => action(id))
    )
  }

  return {
    // Состояние
    selectedIds: readonly(selectedIds),
    loadingIds: readonly(loadingIds),
    
    // Вычисляемые свойства
    selectedCount: computed(() => selectedIds.value.size),
    hasSelected: computed(() => selectedIds.value.size > 0),
    
    // Методы выбора
    toggleSelection,
    selectAll,
    clearSelection,
    isSelected,
    
    // Методы загрузки
    setLoading,
    isLoading,
    
    // Действия
    executeForSelected
  }
}