import type { DirectiveBinding } from 'vue'

/**
 * Директива v-ripple
 * Добавляет Material Design ripple эффект при клике
 * 
 * Использование:
 * <button v-ripple>Click me</button> - стандартный ripple
 * <button v-ripple="'#ff0000'">Click me</button> - кастомный цвет
 * <button v-ripple="{ color: '#ff0000', duration: 800 }">Click me</button>
 */

interface RippleOptions {
  color?: string // Цвет ripple эффекта
  duration?: number // Длительность анимации
  opacity?: number // Прозрачность
  centered?: boolean // Центрировать ripple или от точки клика
}

export const ripple = {
  mounted(el: HTMLElement, binding: DirectiveBinding<string | RippleOptions>) {
    // Парсим опции
    let options: RippleOptions = {
      color: 'currentColor',
      duration: 600,
      opacity: 0.25,
      centered: false
    }
    
    if (typeof binding.value === 'string') {
      options.color = binding.value
    } else if (typeof binding.value === 'object') {
      options = { ...options, ...binding.value }
    }
    
    // Устанавливаем позиционирование для контейнера
    if (getComputedStyle(el).position === 'static') {
      el.style.position = 'relative'
    }
    el.style.overflow = 'hidden'
    
    // Обработчик клика
    const handleClick = (event: MouseEvent) => {
      // Создаем ripple элемент
      const rippleElement = document.createElement('span')
      rippleElement.className = 'ripple-effect'
      
      // Вычисляем размер ripple (должен покрыть весь элемент)
      const rect = el.getBoundingClientRect()
      const size = Math.max(rect.width, rect.height) * 2
      
      // Позиционирование ripple
      let x, y
      if (options.centered) {
        x = rect.width / 2 - size / 2
        y = rect.height / 2 - size / 2
      } else {
        x = event.clientX - rect.left - size / 2
        y = event.clientY - rect.top - size / 2
      }
      
      // Стили для ripple
      rippleElement.style.cssText = `
        position: absolute;
        left: ${x}px;
        top: ${y}px;
        width: ${size}px;
        height: ${size}px;
        border-radius: 50%;
        background-color: ${options.color};
        opacity: 0;
        transform: scale(0);
        animation: ripple-animation ${options.duration}ms ease-out;
        pointer-events: none;
        z-index: 999;
      `
      
      // Добавляем элемент
      el.appendChild(rippleElement)
      
      // Удаляем после анимации
      setTimeout(() => {
        rippleElement.remove()
      }, options.duration)
    }
    
    // Добавляем слушатель
    el.addEventListener('click', handleClick)
    
    // Добавляем стили анимации если их еще нет
    if (!document.querySelector('#ripple-styles')) {
      const style = document.createElement('style')
      style.id = 'ripple-styles'
      style.textContent = `
        @keyframes ripple-animation {
          0% {
            opacity: ${options.opacity};
            transform: scale(0);
          }
          100% {
            opacity: 0;
            transform: scale(1);
          }
        }
      `
      document.head.appendChild(style)
    }
    
    // Сохраняем обработчик для очистки
    ;(el as any)._rippleHandler = handleClick
  },
  
  unmounted(el: HTMLElement) {
    // Очищаем слушатель
    if ((el as any)._rippleHandler) {
      el.removeEventListener('click', (el as any)._rippleHandler)
      delete (el as any)._rippleHandler
    }
  }
}