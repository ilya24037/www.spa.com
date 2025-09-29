/**
 * Композабл для работы со Skeleton компонентами
 * 
 * Использование:
 * const { showSkeleton, hideSkeleton, isLoading } = useSkeleton()
 * const { userCardSkeleton, articleSkeleton } = useSkeletonPresets()
 */

import { ref, computed, reactive, readonly } from 'vue'
import type { SkeletonOptions, SkeletonPresets } from './Skeleton.types'

export function useSkeleton(initialOptions?: SkeletonOptions) {
  const isLoading = ref(false)
  const options = reactive({
    variant: initialOptions?.variant ?? 'text',
    animated: initialOptions?.animated ?? true,
    duration: initialOptions?.duration ?? 0
  })

  /**
   * Показать скелетон
   */
  const showSkeleton = (duration?: number) => {
    isLoading.value = true
    
    const timeoutDuration = duration ?? options.duration
    if (timeoutDuration > 0) {
      setTimeout(() => {
        hideSkeleton()
      }, timeoutDuration)
    }
  }

  /**
   * Скрыть скелетон
   */
  const hideSkeleton = () => {
    isLoading.value = false
  }

  /**
   * Переключить состояние скелетона
   */
  const toggleSkeleton = (loading?: boolean) => {
    isLoading.value = loading ?? !isLoading.value
  }

  /**
   * Асинхронное действие с автоматическим управлением скелетоном
   */
  const withSkeleton = async <T>(action: () => Promise<T>): Promise<T> => {
    try {
      showSkeleton()
      return await action()
    } finally {
      hideSkeleton()
    }
  }

  /**
   * Установить новые опции
   */
  const setOptions = (newOptions: Partial<SkeletonOptions>) => {
    Object.assign(options, newOptions)
  }

  return {
    // Состояние
    isLoading: readonly(isLoading),
    options: readonly(options),
    
    // Методы
    showSkeleton,
    hideSkeleton,
    toggleSkeleton,
    withSkeleton,
    setOptions
  }
}

/**
 * Композабл с предустановленными скелетонами
 */
export function useSkeletonPresets(): SkeletonPresets {
  return {
    // Скелетон карточки пользователя
    userCard: {
      avatar: {
        variant: 'avatar',
        size: 'medium',
        animated: true
      },
      name: {
        variant: 'heading',
        size: 'medium',
        width: '60%',
        animated: true
      },
      description: {
        variant: 'text',
        size: 'small',
        width: '80%',
        animated: true
      }
    },

    // Скелетон статьи
    article: {
      title: {
        variant: 'heading',
        size: 'large',
        width: '75%',
        animated: true
      },
      image: {
        variant: 'image',
        animated: true
      },
      paragraph: [
        {
          variant: 'paragraph',
          size: 'medium',
          width: '100%',
          animated: true
        },
        {
          variant: 'paragraph',
          size: 'medium', 
          width: '95%',
          animated: true
        },
        {
          variant: 'paragraph',
          size: 'medium',
          width: '87%',
          animated: true
        },
        {
          variant: 'paragraph',
          size: 'medium',
          width: '92%',
          animated: true
        },
        {
          variant: 'paragraph',
          size: 'medium',
          width: '68%', // Последний абзац короче
          animated: true
        }
      ]
    },

    // Скелетон элемента списка
    listItem: {
      icon: {
        variant: 'circular',
        width: '20px',
        height: '20px',
        animated: true
      },
      title: {
        variant: 'text',
        size: 'medium',
        width: '40%',
        animated: true
      },
      subtitle: {
        variant: 'text',
        size: 'small',
        width: '60%',
        animated: true
      }
    },

    // Скелетон строки таблицы
    tableRow: {
      cells: [
        {
          variant: 'text',
          size: 'small',
          width: '80%',
          animated: true
        },
        {
          variant: 'text',
          size: 'small',
          width: '60%',
          animated: true
        },
        {
          variant: 'text',
          size: 'small',
          width: '90%',
          animated: true
        },
        {
          variant: 'button',
          size: 'small',
          width: '80px',
          animated: true
        }
      ]
    }
  }
}

/**
 * Композабл для коллекции скелетонов (например, список элементов)
 */
export function useSkeletonCollection(count: number = 5) {
  const loading = ref(false)
  const itemsCount = ref(count)

  /**
   * Показать скелетоны для коллекции
   */
  const showCollection = (newCount?: number) => {
    if (newCount) {
      itemsCount.value = newCount
    }
    loading.value = true
  }

  /**
   * Скрыть скелетоны коллекции
   */
  const hideCollection = () => {
    loading.value = false
  }

  /**
   * Массив индексов для v-for
   */
  const skeletonItems = computed(() => 
    Array.from({ length: itemsCount.value }, (_, i) => i)
  )

  /**
   * Загрузка коллекции с скелетонами
   */
  const loadCollection = async <T>(
    loader: () => Promise<T[]>,
    options?: { minDisplayTime?: number }
  ): Promise<T[]> => {
    const minTime = options?.minDisplayTime ?? 500
    
    showCollection()
    
    const startTime = Date.now()
    
    try {
      const result = await loader()
      
      // Минимальное время показа скелетона для лучшего UX
      const elapsed = Date.now() - startTime
      if (elapsed < minTime) {
        await new Promise(resolve => setTimeout(resolve, minTime - elapsed))
      }
      
      return result
    } finally {
      hideCollection()
    }
  }

  return {
    // Состояние
    loading: readonly(loading),
    itemsCount: readonly(itemsCount),
    skeletonItems,
    
    // Методы
    showCollection,
    hideCollection,
    loadCollection
  }
}