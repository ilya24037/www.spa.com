/**
 * Композабл для работы с Breadcrumbs компонентом
 * 
 * Использование:
 * const { breadcrumbs, addItem, updateItem, generateFromRoute } = useBreadcrumbs()
 */

import { ref, computed, reactive, readonly } from 'vue'
import type { 
  BreadcrumbItem, 
  BreadcrumbsOptions, 
  RouteBreadcrumbConfig,
  BreadcrumbPresets 
} from './Breadcrumbs.types'

export function useBreadcrumbs(initialOptions?: BreadcrumbsOptions) {
  const items = ref<BreadcrumbItem[]>([])
  const options = reactive<BreadcrumbsOptions & { formatTitle: (title: string) => string; generateH: (route: any) => string }>({
    autoHome: true,
    maxTitleLength: 50,
    formatTitle: (title: string) => title,
    generateH: (route: any) => route.path || route.href || '',
    ...initialOptions
  })

  /**
   * Добавить элемент в конец цепочки
   */
  const addItem = (item: BreadcrumbItem): void => {
    const formattedItem = {
      ...item,
      title: formatTitle(item.title)
    }
    items.value.push(formattedItem)
  }

  /**
   * Добавить элемент в начало цепочки
   */
  const prependItem = (item: BreadcrumbItem): void => {
    const formattedItem = {
      ...item,
      title: formatTitle(item.title)
    }
    items.value.unshift(formattedItem)
  }

  /**
   * Обновить элемент по индексу
   */
  const updateItem = (index: number, updates: Partial<BreadcrumbItem>): void => {
    if (index >= 0 && index < items.value.length) {
      const updatedItem = {
        ...items.value[index],
        ...updates
      }
      
      if (updates.title) {
        updatedItem.title = formatTitle(updates.title)
      }
      
      items.value[index] = updatedItem
    }
  }

  /**
   * Удалить элемент по индексу
   */
  const removeItem = (index: number) => {
    if (index >= 0 && index < items.value.length) {
      items.value.splice(index, 1)
    }
  }

  /**
   * Очистить все элементы
   */
  const clearItems = () => {
    items.value = []
  }

  /**
   * Заменить все элементы
   */
  const setItems = (newItems: BreadcrumbItem[]): void => {
    items.value = newItems.map(item => ({
      ...item,
      title: formatTitle(item.title)
    }))
  }

  /**
   * Найти элемент по h или to
   */
  const findItem = (h: string): BreadcrumbItem | undefined => {
    return items.value.find(item => 
      item.href === h || 
      (typeof item.to === 'string' && item.to === h)
    )
  }

  /**
   * Форматирование заголовка
   */
  const formatTitle = (title: string): string => {
    let formatted = options.formatTitle(title)
    
    // Обрезаем длинные заголовки
    if (options.maxTitleLength > 0 && formatted.length > options.maxTitleLength) {
      formatted = formatted.substring(0, options.maxTitleLength - 3) + '...'
    }
    
    return formatted
  }

  /**
   * Генерация breadcrumbs из Vue Router
   */
  const generateFromRoute = (route: any, config?: RouteBreadcrumbConfig): BreadcrumbItem[] => {
    const breadcrumbs: BreadcrumbItem[] = []
    
    // Если включено автоматическое добавление домашней страницы
    if (options.autoHome) {
      breadcrumbs.push({
        title: 'Главная',
        h: '/',
        isHome: true,
        icon: config?.icons?.['/']
      })
    }
    
    // Разбираем path на части
    const pathParts = route.path.split('/').filter((part: string) => part !== '')
    let currentPath = ''
    
    pathParts.forEach((part: string, index: number) => {
      currentPath += `/${part}`
      
      // Проверяем не исключен ли роут
      if (config?.exclude?.includes(currentPath)) {
        return
      }
      
      // Кастомная трансформация
      if (config?.transform) {
        const customItem = config.transform({ path: currentPath, params: route.params })
        if (customItem) {
          breadcrumbs.push(customItem)
        }
        return
      }
      
      // Определяем title
      let title = config?.titles?.[currentPath] || 
                  route.meta?.breadcrumb || 
                  route.name || 
                  part.charAt(0).toUpperCase() + part.slice(1)
      
      // Подставляем параметры роута в title
      if (route.params && typeof title === 'string') {
        Object.keys(route.params).forEach(param => {
          title = title.replace(`:${param}`, route.params[param])
        })
      }
      
      breadcrumbs.push({
        title: formatTitle(title),
        h: options.generateH({ path: currentPath }),
        to: currentPath,
        icon: config?.icons?.[currentPath],
        key: currentPath
      })
    })
    
    return breadcrumbs
  }

  /**
   * Автоматическое обновление breadcrumbs при изменении роута
   */
  const syncWithRouter = (router: any, config?: RouteBreadcrumbConfig) => {
    const updateBreadcrumbs = () => {
      const newItems = generateFromRoute(router.currentRoute.value, config)
      setItems(newItems)
    }
    
    // Обновляем при изменении роута
    router.afterEach(updateBreadcrumbs)
    
    // Инициализируем
    updateBreadcrumbs()
    
    return () => {
      // Функция отписки (если нужна)
    }
  }

  /**
   * Получить полный путь до определенного элемента
   */
  const getPathTo = (targetIndex: number): BreadcrumbItem[] => {
    return items.value.slice(0, targetIndex + 1)
  }

  /**
   * Проверить является ли элемент текущим (последним)
   */
  const isCurrent = (index: number): boolean => {
    return index === items.value.length - 1
  }

  return {
    // Состояние
    items: readonly(items),
    options: readonly(options),
    
    // Вычисляемые
    isEmpty: computed(() => items.value.length === 0),
    currentItem: computed(() => items.value[items.value.length - 1]),
    breadcrumbs: computed(() => items.value),
    
    // Методы управления
    addItem,
    prependItem,
    updateItem,
    removeItem,
    clearItems,
    setItems,
    
    // Поиск и навигация
    findItem,
    getPathTo,
    isCurrent,
    
    // Интеграция с Router
    generateFromRoute,
    syncWithRouter,
    
    // Утилиты
    formatTitle
  }
}

/**
 * Композабл с предустановленными конфигурациями
 */
export function useBreadcrumbPresets(): BreadcrumbPresets {
  return {
    default: {
      size: 'medium',
      separator: 'chevron',
      showIcons: false,
      showHome: true,
      enableJsonLd: false
    },
    
    compact: {
      size: 'small',
      separator: 'slash',
      showIcons: false,
      showHome: false,
      maxItems: 3,
      enableJsonLd: false
    },
    
    withIcons: {
      size: 'medium',
      separator: 'chevron',
      showIcons: true,
      showHome: true,
      enableJsonLd: false
    },
    
    seo: {
      size: 'medium',
      separator: 'chevron',
      showIcons: false,
      showHome: true,
      enableJsonLd: true,
      ariaLabel: 'Навигационная цепочка по сайту'
    }
  }
}

/**
 * Утилиты для работы с breadcrumbs
 */
export const breadcrumbUtils = {
  /**
   * Создать breadcrumb из URL
   */
  fromUrl: (url: string, titles?: Record<string, string>): BreadcrumbItem[] => {
    const path = new URL(url, window.location.origin).pathname
    const parts = path.split('/').filter(part => part !== '')
    const breadcrumbs: BreadcrumbItem[] = []
    
    let currentPath = ''
    parts.forEach((part, index) => {
      currentPath += `/${part}`
      breadcrumbs.push({
        title: titles?.[currentPath] || part.charAt(0).toUpperCase() + part.slice(1),
        h: currentPath,
        key: currentPath
      })
    })
    
    return breadcrumbs
  },
  
  /**
   * Создать breadcrumb из массива строк
   */
  fromStrings: (paths: string[], baseUrl = ''): BreadcrumbItem[] => {
    return paths.map((path, index) => {
      const h = baseUrl + '/' + paths.slice(0, index + 1).join('/')
      return {
        title: path,
        h,
        key: h
      }
    })
  },
  
  /**
   * Объединить несколько цепочек breadcrumbs
   */
  merge: (...breadcrumbs: BreadcrumbItem[][]): BreadcrumbItem[] => {
    return breadcrumbs.flat()
  }
}