import type { DirectiveBinding } from 'vue'

/**
 * Директива v-fade-in
 * Добавляет анимацию появления элемента с эффектом fade и движения
 * 
 * Использование:
 * <div v-fade-in>...</div> - стандартная анимация
 * <div v-fade-in="200">...</div> - с задержкой 200мс
 * <div v-fade-in="{ delay: 200, duration: 500, direction: 'up' }">...</div>
 */

interface FadeInOptions {
  delay?: number // Задержка перед анимацией
  duration?: number // Длительность анимации
  direction?: 'up' | 'down' | 'left' | 'right' | 'none' // Направление появления
  distance?: number // Расстояние движения
  once?: boolean // Анимировать только один раз
}

export const fadeIn = {
  mounted(el: HTMLElement, binding: DirectiveBinding<number | FadeInOptions>) {
    // Парсим опции
    let options: FadeInOptions = {
      delay: 0,
      duration: 600,
      direction: 'up',
      distance: 30,
      once: true
    }
    
    if (typeof binding.value === 'number') {
      options.delay = binding.value
    } else if (typeof binding.value === 'object') {
      options = { ...options, ...binding.value }
    }
    
    // Начальное состояние
    el.style.opacity = '0'
    el.style.transition = `opacity ${options.duration}ms ease-out, transform ${options.duration}ms ease-out`
    
    // Устанавливаем начальную позицию в зависимости от направления
    switch (options.direction) {
      case 'up':
        el.style.transform = `translateY(${options.distance}px)`
        break
      case 'down':
        el.style.transform = `translateY(-${options.distance}px)`
        break
      case 'left':
        el.style.transform = `translateX(${options.distance}px)`
        break
      case 'right':
        el.style.transform = `translateX(-${options.distance}px)`
        break
      case 'none':
        el.style.transform = 'none'
        break
    }
    
    // Создаем observer для отслеживания появления элемента в viewport
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            // Запускаем анимацию с задержкой
            setTimeout(() => {
              el.style.opacity = '1'
              el.style.transform = 'translate(0, 0)'
              
              // Отключаем observer если анимация одноразовая
              if (options.once) {
                observer.unobserve(el)
              }
            }, options.delay)
          } else if (!options.once) {
            // Сбрасываем состояние для повторной анимации
            el.style.opacity = '0'
            switch (options.direction) {
              case 'up':
                el.style.transform = `translateY(${options.distance}px)`
                break
              case 'down':
                el.style.transform = `translateY(-${options.distance}px)`
                break
              case 'left':
                el.style.transform = `translateX(${options.distance}px)`
                break
              case 'right':
                el.style.transform = `translateX(-${options.distance}px)`
                break
            }
          }
        })
      },
      {
        threshold: 0.1 // Срабатывает когда 10% элемента видно
      }
    )
    
    observer.observe(el)
    
    // Сохраняем observer для очистки
    ;(el as any)._fadeInObserver = observer
  },
  
  unmounted(el: HTMLElement) {
    // Очищаем observer
    if ((el as any)._fadeInObserver) {
      (el as any)._fadeInObserver.disconnect()
      delete (el as any)._fadeInObserver
    }
  }
}