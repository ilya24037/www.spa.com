import { ref, computed, onMounted, onUnmounted, type Ref } from 'vue'

/**
 * Composable для виртуального скроллинга
 * Оптимизирует рендеринг больших списков
 */

interface VirtualScrollOptions {
  itemHeight: number // Высота одного элемента
  containerHeight?: number // Высота контейнера
  buffer?: number // Буфер элементов сверху/снизу
  threshold?: number // Порог для подгрузки
}

interface VirtualScrollReturn<T> {
  visibleItems: Ref<T[]>
  totalHeight: Ref<number>
  offsetY: Ref<number>
  scrollTop: Ref<number>
  containerRef: Ref<HTMLElement | null>
  handleScroll: (event: Event) => void
  scrollToIndex: (index: number) => void
  scrollToTop: () => void
  updateContainerHeight: () => void
}

export function useVirtualScroll<T = any>(
  items: Ref<T[]>,
  options: VirtualScrollOptions
): VirtualScrollReturn<T> {
  const {
    itemHeight,
    containerHeight = 600,
    buffer = 3,
    threshold = 100
  } = options
  
  // Refs
  const containerRef = ref<HTMLElement | null>(null)
  const scrollTop = ref(0)
  const containerHeightValue = ref(containerHeight)
  
  // Вычисления
  const totalHeight = computed(() => items.value.length * itemHeight)
  
  const visibleRange = computed(() => {
    const visibleCount = Math.ceil(containerHeightValue.value / itemHeight)
    const startIndex = Math.floor(scrollTop.value / itemHeight) - buffer
    const endIndex = startIndex + visibleCount + buffer * 2
    
    return {
      start: Math.max(0, startIndex),
      end: Math.min(items.value.length, endIndex)
    }
  })
  
  const visibleItems = computed(() => {
    const { start, end } = visibleRange.value
    return items.value.slice(start, end)
  })
  
  const offsetY = computed(() => {
    return visibleRange.value.start * itemHeight
  })
  
  // Методы
  const handleScroll = (event: Event) => {
    const target = event.target as HTMLElement
    scrollTop.value = target.scrollTop
  }
  
  const scrollToIndex = (index: number) => {
    if (containerRef.value) {
      const position = index * itemHeight
      containerRef.value.scrollTop = position
      scrollTop.value = position
    }
  }
  
  const scrollToTop = () => {
    if (containerRef.value) {
      containerRef.value.scrollTop = 0
      scrollTop.value = 0
    }
  }
  
  const updateContainerHeight = () => {
    if (containerRef.value) {
      containerHeightValue.value = containerRef.value.clientHeight
    }
  }
  
  // Автоматическое обновление высоты при ресайзе
  onMounted(() => {
    updateContainerHeight()
    window.addEventListener('resize', updateContainerHeight)
  })
  
  onUnmounted(() => {
    window.removeEventListener('resize', updateContainerHeight)
  })
  
  return {
    visibleItems,
    totalHeight,
    offsetY,
    scrollTop,
    containerRef,
    handleScroll,
    scrollToIndex,
    scrollToTop,
    updateContainerHeight
  }
}

/**
 * Хук для бесконечного скролла с виртуализацией
 */
export function useInfiniteVirtualScroll<T = any>(
  loadMore: () => Promise<T[]>,
  options: VirtualScrollOptions & { 
    initialItems?: T[]
    hasMore?: Ref<boolean>
  }
) {
  const items = ref<T[]>(options.initialItems || [])
  const loading = ref(false)
  const hasMore = options.hasMore || ref(true)
  
  const virtualScroll = useVirtualScroll(items, options)
  
  // Проверка необходимости загрузки
  const checkLoadMore = async () => {
    if (loading.value || !hasMore.value) return
    
    const { scrollTop, totalHeight, containerRef } = virtualScroll
    
    if (!containerRef.value) return
    
    const scrollBottom = totalHeight.value - scrollTop.value - containerRef.value.clientHeight
    
    if (scrollBottom < (options.threshold || 100)) {
      loading.value = true
      
      try {
        const newItems = await loadMore()
        items.value = [...items.value, ...newItems]
        
        if (newItems.length === 0) {
          hasMore.value = false
        }
      } catch (error) {
        console.error('Error loading more items:', error)
      } finally {
        loading.value = false
      }
    }
  }
  
  // Переопределяем handleScroll
  const handleScroll = (event: Event) => {
    virtualScroll.handleScroll(event)
    checkLoadMore()
  }
  
  return {
    ...virtualScroll,
    items,
    loading,
    hasMore,
    handleScroll
  }
}