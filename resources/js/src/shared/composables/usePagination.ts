import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'

export interface PaginationOptions {
  page?: number
  perPage?: number
  total?: number
  preserveScroll?: boolean
  preserveState?: boolean
}

export interface PaginationMeta {
  current_page: number
  from: number
  last_page: number
  per_page: number
  to: number
  total: number
}

/**
 * Composable для управления пагинацией
 * 
 * Использование:
 * const pagination = usePagination({
 *   page: props.masters.current_page,
 *   total: props.masters.total,
 *   perPage: props.masters.per_page
 * })
 * 
 * В template:
 * <button @click="pagination.prev()" :disabled="!pagination.hasPrev">Назад</button>
 * <span>Страница {{ pagination.currentPage }} из {{ pagination.totalPages }}</span>
 * <button @click="pagination.next()" :disabled="!pagination.hasNext">Вперед</button>
 */
export function usePagination(options: PaginationOptions = {}) {
  const currentPage = ref(options.page || 1)
  const perPage = ref(options.perPage || 15)
  const total = ref(options.total || 0)
  const preserveScroll = options.preserveScroll ?? true
  const preserveState = options.preserveState ?? true
  
  // Вычисляемые свойства
  const totalPages = computed(() => {
    return Math.ceil(total.value / perPage.value)
  })
  
  const hasPrev = computed(() => {
    return currentPage.value > 1
  })
  
  const hasNext = computed(() => {
    return currentPage.value < totalPages.value
  })
  
  const offset = computed(() => {
    return (currentPage.value - 1) * perPage.value
  })
  
  const from = computed(() => {
    if (total.value === 0) return 0
    return offset.value + 1
  })
  
  const to = computed(() => {
    const end = offset.value + perPage.value
    return end > total.value ? total.value : end
  })
  
  const pageNumbers = computed(() => {
    const pages: number[] = []
    const maxVisible = 5
    const halfVisible = Math.floor(maxVisible / 2)
    
    let start = Math.max(1, currentPage.value - halfVisible)
    let end = Math.min(totalPages.value, start + maxVisible - 1)
    
    if (end - start < maxVisible - 1) {
      start = Math.max(1, end - maxVisible + 1)
    }
    
    for (let i = start; i <= end; i++) {
      pages.push(i)
    }
    
    return pages
  })
  
  // Методы навигации
  const goToPage = (page: number) => {
    if (page < 1 || page > totalPages.value) return
    
    currentPage.value = page
    
    // Если используется Inertia
    if (router) {
      router.get(
        window.location.pathname,
        { page },
        { preserveScroll, preserveState }
      )
    }
  }
  
  const next = () => {
    if (hasNext.value) {
      goToPage(currentPage.value + 1)
    }
  }
  
  const prev = () => {
    if (hasPrev.value) {
      goToPage(currentPage.value - 1)
    }
  }
  
  const first = () => {
    goToPage(1)
  }
  
  const last = () => {
    goToPage(totalPages.value)
  }
  
  const setPerPage = (value: number) => {
    perPage.value = value
    currentPage.value = 1
    
    if (router) {
      router.get(
        window.location.pathname,
        { page: 1, per_page: value },
        { preserveScroll, preserveState }
      )
    }
  }
  
  // Обновление из props
  const updateFromMeta = (meta: PaginationMeta) => {
    currentPage.value = meta.current_page
    total.value = meta.total
    perPage.value = meta.per_page
  }
  
  return {
    // Состояние
    currentPage: computed(() => currentPage.value),
    perPage: computed(() => perPage.value),
    total: computed(() => total.value),
    totalPages,
    
    // Позиция
    from,
    to,
    offset,
    
    // Флаги
    hasPrev,
    hasNext,
    
    // Страницы для отображения
    pageNumbers,
    
    // Методы
    goToPage,
    next,
    prev,
    first,
    last,
    setPerPage,
    updateFromMeta
  }
}