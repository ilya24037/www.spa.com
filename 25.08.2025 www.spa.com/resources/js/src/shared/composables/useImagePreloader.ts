import { ref, onMounted, onUnmounted, type Ref } from 'vue'
import ImageCacheService from '@/src/shared/services/ImageCacheService'

/**
 * Composable для предзагрузки изображений
 * Оптимизирует загрузку изображений с помощью Intersection Observer
 */

interface ImagePreloaderOptions {
  rootMargin?: string // Отступ от viewport для начала загрузки
  threshold?: number // Порог видимости для загрузки
  priority?: 'high' | 'low' | 'auto' // Приоритет загрузки
  batchSize?: number // Размер батча для загрузки
  delay?: number // Задержка между батчами
}

interface PreloadableImage {
  url: string
  priority?: 'high' | 'low'
  element?: HTMLElement
}

export function useImagePreloader(options: ImagePreloaderOptions = {}) {
  const {
    rootMargin = '50px',
    threshold = 0.01,
    priority = 'auto',
    batchSize = 5,
    delay = 100
  } = options
  
  const isPreloading = ref(false)
  const preloadedUrls = ref(new Set<string>())
  const preloadQueue = ref<PreloadableImage[]>([])
  const observer = ref<IntersectionObserver | null>(null)
  
  /**
   * Предзагрузка одного изображения
   */
  const preloadImage = async (url: string): Promise<void> => {
    if (preloadedUrls.value.has(url)) return
    
    try {
      await ImageCacheService.getImage(url)
      preloadedUrls.value.add(url)
    } catch (error) {
      console.error(`Failed to preload image: ${url}`, error)
    }
  }
  
  /**
   * Предзагрузка батча изображений
   */
  const preloadBatch = async (images: PreloadableImage[]): Promise<void> => {
    const promises = images.map(img => preloadImage(img.url))
    await Promise.all(promises)
  }
  
  /**
   * Обработка очереди предзагрузки
   */
  const processQueue = async () => {
    if (isPreloading.value || preloadQueue.value.length === 0) return
    
    isPreloading.value = true
    
    // Сортируем по приоритету
    const sortedQueue = [...preloadQueue.value].sort((a, b) => {
      if (a.priority === 'high' && b.priority !== 'high') return -1
      if (b.priority === 'high' && a.priority !== 'high') return 1
      return 0
    })
    
    // Обрабатываем батчами
    while (sortedQueue.length > 0) {
      const batch = sortedQueue.splice(0, batchSize)
      await preloadBatch(batch)
      
      // Удаляем обработанные из очереди
      preloadQueue.value = preloadQueue.value.filter(
        img => !batch.some(b => b.url === img.url)
      )
      
      // Задержка между батчами
      if (sortedQueue.length > 0) {
        await new Promise(resolve => setTimeout(resolve, delay))
      }
    }
    
    isPreloading.value = false
  }
  
  /**
   * Добавление изображений в очередь предзагрузки
   */
  const addToQueue = (images: PreloadableImage | PreloadableImage[]) => {
    const imagesToAdd = Array.isArray(images) ? images : [images]
    
    imagesToAdd.forEach(img => {
      if (!preloadedUrls.value.has(img.url)) {
        preloadQueue.value.push(img)
      }
    })
    
    // Запускаем обработку очереди
    processQueue()
  }
  
  /**
   * Наблюдение за элементами для ленивой предзагрузки
   */
  const observeElements = (elements: HTMLElement[]) => {
    if (!observer.value) {
      observer.value = new IntersectionObserver(
        (entries) => {
          entries.forEach(entry => {
            if (entry.isIntersecting) {
              const img = entry.target as HTMLElement
              const url = img.dataset.preloadUrl || img.getAttribute('data-src')
              
              if (url) {
                addToQueue({
                  url,
                  priority: priority === 'auto' 
                    ? (entry.intersectionRatio > 0.5 ? 'high' : 'low')
                    : priority === 'high' ? 'high' : 'low',
                  element: img
                })
                
                // Прекращаем наблюдение за загруженным элементом
                observer.value?.unobserve(img)
              }
            }
          })
        },
        {
          rootMargin,
          threshold
        }
      )
    }
    
    elements.forEach(el => observer.value?.observe(el))
  }
  
  /**
   * Прекращение наблюдения
   */
  const unobserveElements = (elements: HTMLElement[]) => {
    elements.forEach(el => observer.value?.unobserve(el))
  }
  
  /**
   * Очистка
   */
  const cleanup = () => {
    if (observer.value) {
      observer.value.disconnect()
      observer.value = null
    }
    preloadQueue.value = []
    preloadedUrls.value.clear()
  }
  
  onUnmounted(() => {
    cleanup()
  })
  
  return {
    isPreloading,
    preloadedUrls,
    preloadQueue,
    preloadImage,
    addToQueue,
    observeElements,
    unobserveElements,
    processQueue,
    cleanup
  }
}

/**
 * Хук для предзагрузки изображений при наведении
 */
export function useHoverPreloader() {
  const { addToQueue } = useImagePreloader({
    priority: 'high',
    batchSize: 3
  })
  
  const handleMouseEnter = (event: MouseEvent) => {
    const target = event.currentTarget as HTMLElement
    const images = target.querySelectorAll<HTMLElement>('[data-hover-preload]')
    
    const urls = Array.from(images).map(img => ({
      url: img.dataset.hoverPreload || img.getAttribute('src') || '',
      priority: 'high' as const
    })).filter(item => item.url)
    
    if (urls.length > 0) {
      addToQueue(urls)
    }
  }
  
  const attachHoverListeners = (elements: HTMLElement[]) => {
    elements.forEach(el => {
      el.addEventListener('mouseenter', handleMouseEnter)
    })
  }
  
  const removeHoverListeners = (elements: HTMLElement[]) => {
    elements.forEach(el => {
      el.removeEventListener('mouseenter', handleMouseEnter)
    })
  }
  
  onUnmounted(() => {
    // Очистка слушателей при размонтировании
    const elements = document.querySelectorAll<HTMLElement>('[data-hover-preload]')
    removeHoverListeners(Array.from(elements))
  })
  
  return {
    attachHoverListeners,
    removeHoverListeners
  }
}

/**
 * Хук для предзагрузки критических изображений
 */
export function useCriticalImages(imageUrls: Ref<string[]>) {
  const loaded = ref(false)
  const progress = ref(0)
  
  const loadCriticalImages = async () => {
    const total = imageUrls.value.length
    let loadedCount = 0
    
    for (const url of imageUrls.value) {
      try {
        await ImageCacheService.getImage(url)
        loadedCount++
        progress.value = (loadedCount / total) * 100
      } catch (error) {
        console.error(`Failed to load critical image: ${url}`)
      }
    }
    
    loaded.value = true
  }
  
  onMounted(() => {
    loadCriticalImages()
  })
  
  return {
    loaded,
    progress
  }
}