/**
 * 🎈 BALLOON COMPONENT
 * Компонент всплывающих окон из Яндекс.Карт
 * Файл извлечен из: Яндекс.Карты bundle
 */

/**
 * Balloon - всплывающее окно на карте
 * Паттерн из Яндекс.Карт для отображения информации
 */
export class BalloonComponent {
  constructor(map, options = {}) {
    this.map = map
    this.options = {
      closeButton: true,
      autoPan: true,
      autoPanMargin: 34,
      maxWidth: 400,
      maxHeight: 400,
      minWidth: 85,
      minHeight: 30,
      ...options
    }
    
    this.isOpen = false
    this.position = null
    this.content = null
    this.listeners = new Map()
  }
  
  /**
   * Открытие balloon
   */
  open(position, content) {
    // Закрываем предыдущий если открыт
    if (this.isOpen) {
      this.close()
    }
    
    this.position = position
    this.content = content
    this.isOpen = true
    
    // Создаем DOM элемент
    this.element = this.createElement()
    this.map.container.appendChild(this.element)
    
    // Позиционируем
    this.updatePosition()
    
    // Автопанорамирование если нужно
    if (this.options.autoPan) {
      this.autoPan()
    }
    
    // Устанавливаем слушатели
    this.setupListeners()
    
    // Событие открытия
    this.emit('open')
    
    return Promise.resolve()
  }
  
  /**
   * Закрытие balloon
   */
  close() {
    if (!this.isOpen) return
    
    this.isOpen = false
    
    // Удаляем слушатели
    this.clearListeners()
    
    // Удаляем элемент
    if (this.element && this.element.parentNode) {
      this.element.parentNode.removeChild(this.element)
    }
    
    this.element = null
    this.position = null
    this.content = null
    
    // Событие закрытия
    this.emit('close')
    
    return Promise.resolve()
  }
  
  /**
   * Создание DOM элемента balloon
   */
  createElement() {
    const balloon = document.createElement('div')
    balloon.className = 'map-balloon'
    balloon.style.cssText = `
      position: absolute;
      background: white;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.15);
      padding: 12px;
      max-width: ${this.options.maxWidth}px;
      max-height: ${this.options.maxHeight}px;
      min-width: ${this.options.minWidth}px;
      min-height: ${this.options.minHeight}px;
      z-index: 1000;
    `
    
    // Контент
    const contentDiv = document.createElement('div')
    contentDiv.className = 'map-balloon__content'
    contentDiv.innerHTML = this.content
    balloon.appendChild(contentDiv)
    
    // Кнопка закрытия
    if (this.options.closeButton) {
      const closeBtn = document.createElement('button')
      closeBtn.className = 'map-balloon__close'
      closeBtn.innerHTML = '×'
      closeBtn.style.cssText = `
        position: absolute;
        top: 5px;
        right: 5px;
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #666;
      `
      closeBtn.onclick = () => this.close()
      balloon.appendChild(closeBtn)
    }
    
    // Стрелка указатель
    const tail = document.createElement('div')
    tail.className = 'map-balloon__tail'
    tail.style.cssText = `
      position: absolute;
      bottom: -10px;
      left: 50%;
      transform: translateX(-50%);
      width: 0;
      height: 0;
      border-left: 10px solid transparent;
      border-right: 10px solid transparent;
      border-top: 10px solid white;
    `
    balloon.appendChild(tail)
    
    return balloon
  }
  
  /**
   * Обновление позиции balloon
   */
  updatePosition() {
    if (!this.element || !this.position) return
    
    const pixel = this.map.converter.geoToPixel(this.position)
    
    // Центрируем balloon над точкой
    const width = this.element.offsetWidth
    const height = this.element.offsetHeight
    
    this.element.style.left = `${pixel[0] - width / 2}px`
    this.element.style.top = `${pixel[1] - height - 10}px` // 10px для стрелки
  }
  
  /**
   * Автопанорамирование карты
   */
  autoPan() {
    if (!this.element) return
    
    const bounds = this.element.getBoundingClientRect()
    const mapBounds = this.map.container.getBoundingClientRect()
    const margin = this.options.autoPanMargin
    
    let offsetX = 0
    let offsetY = 0
    
    // Проверяем выход за границы
    if (bounds.left < mapBounds.left + margin) {
      offsetX = bounds.left - mapBounds.left - margin
    } else if (bounds.right > mapBounds.right - margin) {
      offsetX = bounds.right - mapBounds.right + margin
    }
    
    if (bounds.top < mapBounds.top + margin) {
      offsetY = bounds.top - mapBounds.top - margin
    } else if (bounds.bottom > mapBounds.bottom - margin) {
      offsetY = bounds.bottom - mapBounds.bottom + margin
    }
    
    // Панорамируем если нужно
    if (offsetX || offsetY) {
      const center = this.map.getCenter()
      const pixelCenter = this.map.converter.geoToPixel(center)
      const newPixelCenter = [
        pixelCenter[0] + offsetX,
        pixelCenter[1] + offsetY
      ]
      const newCenter = this.map.converter.pixelToGeo(newPixelCenter)
      
      this.map.panTo(newCenter, {
        duration: this.options.autoPanDuration || 300
      })
    }
  }
  
  /**
   * Установка слушателей событий
   */
  setupListeners() {
    // Обновление позиции при изменении карты
    const updateHandler = () => this.updatePosition()
    this.map.events.add('boundschange', updateHandler)
    this.listeners.set('boundschange', updateHandler)
    
    // Закрытие по ESC
    const escHandler = (e) => {
      if (e.key === 'Escape') {
        this.close()
      }
    }
    document.addEventListener('keydown', escHandler)
    this.listeners.set('keydown', escHandler)
  }
  
  /**
   * Очистка слушателей
   */
  clearListeners() {
    this.listeners.forEach((handler, event) => {
      if (event === 'keydown') {
        document.removeEventListener(event, handler)
      } else {
        this.map.events.remove(event, handler)
      }
    })
    this.listeners.clear()
  }
  
  /**
   * Event emitter функционал
   */
  emit(event, data) {
    // Здесь должна быть реализация EventEmitter
    console.log(`Balloon event: ${event}`, data)
  }
}

/**
 * Фабрика для создания balloon
 */
export function createBalloon(map, options) {
  return new BalloonComponent(map, options)
}

/**
 * Пример использования
 */
export const balloonExample = `
// Создание balloon
const balloon = createBalloon(map, {
  closeButton: true,
  autoPan: true,
  maxWidth: 300
})

// Открытие с контентом
balloon.open([55.76, 37.64], \`
  <h3>Мастер Иван</h3>
  <p>Массажист с опытом 10 лет</p>
  <button>Записаться</button>
\`)

// Закрытие
balloon.close()
`