import type { DirectiveBinding } from 'vue'

/**
 * Директива v-hover-lift
 * Добавляет эффект поднятия элемента при наведении
 * 
 * Использование:
 * <div v-hover-lift>...</div> - стандартное поднятие
 * <div v-hover-lift="8">...</div> - кастомная высота поднятия
 * <div v-hover-lift="{ lift: 8, scale: 1.02 }">...</div> - расширенные настройки
 */

interface HoverLiftOptions {
  lift?: number // Высота поднятия в пикселях
  scale?: number // Масштабирование при наведении
  duration?: number // Длительность анимации в мс
  shadow?: boolean // Добавлять ли тень
}

export const hoverLift = {
  mounted(el: HTMLElement, binding: DirectiveBinding<number | HoverLiftOptions>) {
    // Парсим опции
    let options: HoverLiftOptions = {
      lift: 4,
      scale: 1,
      duration: 200,
      shadow: true
    }
    
    if (typeof binding.value === 'number') {
      options.lift = binding.value
    } else if (typeof binding.value === 'object') {
      options = { ...options, ...binding.value }
    }
    
    // Сохраняем оригинальные стили
    const originalTransform = el.style.transform || ''
    const originalTransition = el.style.transition || ''
    const originalBoxShadow = el.style.boxShadow || ''
    
    // Добавляем базовую transition
    el.style.transition = `all ${options.duration}ms cubic-bezier(0.4, 0, 0.2, 1)`
    el.style.cursor = 'pointer'
    
    // Обработчики событий
    const handleMouseEnter = () => {
      const transforms = []
      
      if (options.lift && options.lift > 0) {
        transforms.push(`translateY(-${options.lift}px)`)
      }
      
      if (options.scale && options.scale !== 1) {
        transforms.push(`scale(${options.scale})`)
      }
      
      if (transforms.length > 0) {
        el.style.transform = transforms.join(' ')
      }
      
      if (options.shadow) {
        el.style.boxShadow = '0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)'
      }
    }
    
    const handleMouseLeave = () => {
      el.style.transform = originalTransform
      el.style.boxShadow = originalBoxShadow
    }
    
    // Добавляем слушатели
    el.addEventListener('mouseenter', handleMouseEnter)
    el.addEventListener('mouseleave', handleMouseLeave)
    
    // Сохраняем в элементе для unmounted
    ;(el as any)._hoverLiftCleanup = () => {
      el.removeEventListener('mouseenter', handleMouseEnter)
      el.removeEventListener('mouseleave', handleMouseLeave)
      el.style.transition = originalTransition
      el.style.transform = originalTransform
      el.style.boxShadow = originalBoxShadow
    }
  },
  
  unmounted(el: HTMLElement) {
    // Очищаем слушатели
    if ((el as any)._hoverLiftCleanup) {
      (el as any)._hoverLiftCleanup()
      delete (el as any)._hoverLiftCleanup
    }
  }
}