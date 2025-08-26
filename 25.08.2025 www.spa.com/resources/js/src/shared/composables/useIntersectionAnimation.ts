import { ref, onMounted, onUnmounted, type Ref } from 'vue'

/**
 * Composable для анимации элементов при появлении в viewport
 * Использует Intersection Observer API для оптимальной производительности
 */

interface AnimationOptions {
  threshold?: number // Процент видимости для срабатывания (0-1)
  rootMargin?: string // Отступы от viewport
  once?: boolean // Анимировать только один раз
  delay?: number // Задержка перед анимацией
  stagger?: number // Задержка между элементами при групповой анимации
  animationClass?: string // CSS класс для анимации
}

export function useIntersectionAnimation(
  elementRefs: Ref<HTMLElement | HTMLElement[] | null>,
  options: AnimationOptions = {}
) {
  const {
    threshold = 0.1,
    rootMargin = '0px',
    once = true,
    delay = 0,
    stagger = 100,
    animationClass = 'animate-in'
  } = options
  
  const isVisible = ref(false)
  const observer = ref<IntersectionObserver | null>(null)
  
  // Функция для добавления анимации к элементу
  const animateElement = (element: HTMLElement, index: number = 0) => {
    const animationDelay = delay + (index * stagger)
    
    setTimeout(() => {
      element.classList.add(animationClass)
      element.style.opacity = '1'
      element.style.transform = 'translateY(0)'
    }, animationDelay)
  }
  
  // Функция для сброса анимации
  const resetElement = (element: HTMLElement) => {
    element.classList.remove(animationClass)
    element.style.opacity = '0'
    element.style.transform = 'translateY(20px)'
  }
  
  // Инициализация observer
  const initObserver = () => {
    observer.value = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry, index) => {
          if (entry.isIntersecting) {
            isVisible.value = true
            animateElement(entry.target as HTMLElement, index)
            
            if (once) {
              observer.value?.unobserve(entry.target)
            }
          } else if (!once) {
            isVisible.value = false
            resetElement(entry.target as HTMLElement)
          }
        })
      },
      {
        threshold,
        rootMargin
      }
    )
    
    // Наблюдаем за элементами
    if (elementRefs.value) {
      const elements = Array.isArray(elementRefs.value) 
        ? elementRefs.value 
        : [elementRefs.value]
      
      elements.forEach(element => {
        if (element) {
          // Устанавливаем начальное состояние
          element.style.opacity = '0'
          element.style.transform = 'translateY(20px)'
          element.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)'
          
          // Начинаем наблюдение
          observer.value?.observe(element)
        }
      })
    }
  }
  
  // Очистка observer
  const cleanup = () => {
    if (observer.value) {
      observer.value.disconnect()
      observer.value = null
    }
  }
  
  onMounted(() => {
    initObserver()
  })
  
  onUnmounted(() => {
    cleanup()
  })
  
  return {
    isVisible,
    restart: () => {
      cleanup()
      initObserver()
    }
  }
}

/**
 * Хук для группы элементов с stagger анимацией
 */
export function useStaggerAnimation(
  containerRef: Ref<HTMLElement | null>,
  itemSelector: string = '.stagger-item',
  options: AnimationOptions = {}
) {
  const elements = ref<HTMLElement[]>([])
  
  onMounted(() => {
    if (containerRef.value) {
      elements.value = Array.from(
        containerRef.value.querySelectorAll(itemSelector)
      )
    }
  })
  
  return useIntersectionAnimation(elements as any, {
    ...options,
    stagger: options.stagger || 50
  })
}