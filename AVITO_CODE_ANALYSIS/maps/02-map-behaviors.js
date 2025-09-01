/**
 * 🗺️ MAP BEHAVIORS
 * Поведения карты из Яндекс.Карт
 * Файл извлечен из: Яндекс.Карты bundle
 */

/**
 * 1. DRAG BEHAVIOR
 * Перетаскивание карты мышью
 */
export class DragBehavior {
  constructor(map, options = {}) {
    this.map = map
    this.options = {
      cursor: 'grab',
      actionCursor: 'grabbing',
      inertia: true,
      inertiaDuration: 400,
      tremor: 2, // Порог дрожания в пикселях
      ...options
    }
    
    this.isDragging = false
    this.startPoint = null
    this.lastPoint = null
    this.velocity = { x: 0, y: 0 }
    this.timestamps = []
  }
  
  enable() {
    this.map.container.addEventListener('mousedown', this.onMouseDown)
    this.map.container.style.cursor = this.options.cursor
  }
  
  disable() {
    this.map.container.removeEventListener('mousedown', this.onMouseDown)
    this.map.container.style.cursor = 'default'
    this.stopDrag()
  }
  
  onMouseDown = (e) => {
    if (e.button !== 0) return // Только левая кнопка
    
    e.preventDefault()
    this.startDrag(e)
  }
  
  startDrag(e) {
    this.isDragging = true
    this.startPoint = { x: e.clientX, y: e.clientY }
    this.lastPoint = { ...this.startPoint }
    this.timestamps = [{ point: this.startPoint, time: Date.now() }]
    
    // Меняем курсор
    this.map.container.style.cursor = this.options.actionCursor
    
    // Добавляем глобальные слушатели
    document.addEventListener('mousemove', this.onMouseMove)
    document.addEventListener('mouseup', this.onMouseUp)
    
    // Останавливаем текущие анимации
    this.map.stopAnimation()
  }
  
  onMouseMove = (e) => {
    if (!this.isDragging) return
    
    const currentPoint = { x: e.clientX, y: e.clientY }
    const deltaX = currentPoint.x - this.lastPoint.x
    const deltaY = currentPoint.y - this.lastPoint.y
    
    // Проверка порога дрожания
    if (Math.abs(deltaX) < this.options.tremor && 
        Math.abs(deltaY) < this.options.tremor) {
      return
    }
    
    // Панорамируем карту
    this.map.panBy([-deltaX, -deltaY], { duration: 0 })
    
    // Сохраняем для инерции
    this.lastPoint = currentPoint
    this.timestamps.push({ 
      point: currentPoint, 
      time: Date.now() 
    })
    
    // Храним только последние 5 точек
    if (this.timestamps.length > 5) {
      this.timestamps.shift()
    }
  }
  
  onMouseUp = (e) => {
    this.stopDrag()
    
    // Применяем инерцию если включена
    if (this.options.inertia) {
      this.applyInertia()
    }
  }
  
  stopDrag() {
    if (!this.isDragging) return
    
    this.isDragging = false
    
    // Возвращаем курсор
    this.map.container.style.cursor = this.options.cursor
    
    // Удаляем слушатели
    document.removeEventListener('mousemove', this.onMouseMove)
    document.removeEventListener('mouseup', this.onMouseUp)
  }
  
  applyInertia() {
    if (this.timestamps.length < 2) return
    
    const now = Date.now()
    const recent = this.timestamps.filter(t => now - t.time < 100)
    
    if (recent.length < 2) return
    
    const first = recent[0]
    const last = recent[recent.length - 1]
    const dt = last.time - first.time
    
    if (dt === 0) return
    
    // Вычисляем скорость
    const velocity = {
      x: (last.point.x - first.point.x) / dt * 1000,
      y: (last.point.y - first.point.y) / dt * 1000
    }
    
    // Применяем инерцию
    const distance = {
      x: velocity.x * this.options.inertiaDuration / 1000 * 0.5,
      y: velocity.y * this.options.inertiaDuration / 1000 * 0.5
    }
    
    this.map.panBy([-distance.x, -distance.y], {
      duration: this.options.inertiaDuration,
      easing: 'ease-out'
    })
  }
}

/**
 * 2. DOUBLE CLICK ZOOM BEHAVIOR
 * Зум по двойному клику
 */
export class DblClickZoomBehavior {
  constructor(map, options = {}) {
    this.map = map
    this.options = {
      duration: 200,
      centering: true,
      useMapMargin: true,
      ...options
    }
    
    this.lastClickTime = 0
    this.lastClickPos = null
  }
  
  enable() {
    this.map.container.addEventListener('click', this.onClick)
    this.map.container.addEventListener('contextmenu', this.onRightClick)
  }
  
  disable() {
    this.map.container.removeEventListener('click', this.onClick)
    this.map.container.removeEventListener('contextmenu', this.onRightClick)
  }
  
  onClick = (e) => {
    const now = Date.now()
    const timeDiff = now - this.lastClickTime
    const pos = { x: e.clientX, y: e.clientY }
    
    // Проверяем двойной клик
    if (timeDiff < 300 && this.lastClickPos &&
        Math.abs(pos.x - this.lastClickPos.x) < 5 &&
        Math.abs(pos.y - this.lastClickPos.y) < 5) {
      
      e.preventDefault()
      this.zoomIn(pos)
      this.lastClickTime = 0
      this.lastClickPos = null
    } else {
      this.lastClickTime = now
      this.lastClickPos = pos
    }
  }
  
  onRightClick = (e) => {
    e.preventDefault()
    const pos = { x: e.clientX, y: e.clientY }
    this.zoomOut(pos)
  }
  
  zoomIn(pixelPos) {
    const currentZoom = this.map.getZoom()
    const newZoom = Math.min(currentZoom + 1, this.map.options.maxZoom)
    
    if (this.options.centering) {
      // Зум с центрированием на точке клика
      const geoPos = this.map.pixelToGeo(pixelPos)
      this.map.setCenter(geoPos, newZoom, {
        duration: this.options.duration
      })
    } else {
      // Просто увеличиваем зум
      this.map.setZoom(newZoom, {
        duration: this.options.duration
      })
    }
  }
  
  zoomOut(pixelPos) {
    const currentZoom = this.map.getZoom()
    const newZoom = Math.max(currentZoom - 1, this.map.options.minZoom)
    
    this.map.setZoom(newZoom, {
      duration: this.options.duration
    })
  }
}

/**
 * 3. MULTITOUCH BEHAVIOR
 * Мультитач жесты для мобильных
 */
export class MultiTouchBehavior {
  constructor(map, options = {}) {
    this.map = map
    this.options = {
      scaleTremor: 0.1,
      actionsPerSecond: 20,
      ...options
    }
    
    this.touches = new Map()
    this.lastDistance = 0
    this.lastCenter = null
  }
  
  enable() {
    this.map.container.addEventListener('touchstart', this.onTouchStart, { passive: false })
    this.map.container.addEventListener('touchmove', this.onTouchMove, { passive: false })
    this.map.container.addEventListener('touchend', this.onTouchEnd, { passive: false })
  }
  
  disable() {
    this.map.container.removeEventListener('touchstart', this.onTouchStart)
    this.map.container.removeEventListener('touchmove', this.onTouchMove)
    this.map.container.removeEventListener('touchend', this.onTouchEnd)
  }
  
  onTouchStart = (e) => {
    e.preventDefault()
    
    // Сохраняем все касания
    for (const touch of e.changedTouches) {
      this.touches.set(touch.identifier, {
        x: touch.clientX,
        y: touch.clientY
      })
    }
    
    if (this.touches.size === 2) {
      // Начинаем мультитач
      const points = Array.from(this.touches.values())
      this.lastDistance = this.getDistance(points[0], points[1])
      this.lastCenter = this.getCenter(points[0], points[1])
    }
  }
  
  onTouchMove = (e) => {
    e.preventDefault()
    
    // Обновляем позиции касаний
    for (const touch of e.changedTouches) {
      if (this.touches.has(touch.identifier)) {
        this.touches.set(touch.identifier, {
          x: touch.clientX,
          y: touch.clientY
        })
      }
    }
    
    if (this.touches.size === 2) {
      // Обрабатываем pinch zoom и pan
      const points = Array.from(this.touches.values())
      const distance = this.getDistance(points[0], points[1])
      const center = this.getCenter(points[0], points[1])
      
      // Зум
      if (this.lastDistance) {
        const scale = distance / this.lastDistance
        
        if (Math.abs(1 - scale) > this.options.scaleTremor) {
          const currentZoom = this.map.getZoom()
          const zoomDelta = Math.log2(scale)
          const newZoom = Math.max(
            this.map.options.minZoom,
            Math.min(this.map.options.maxZoom, currentZoom + zoomDelta)
          )
          
          this.map.setZoom(newZoom, {
            duration: 0,
            center: this.map.pixelToGeo(center)
          })
        }
      }
      
      // Панорамирование
      if (this.lastCenter) {
        const deltaX = center.x - this.lastCenter.x
        const deltaY = center.y - this.lastCenter.y
        
        if (Math.abs(deltaX) > 2 || Math.abs(deltaY) > 2) {
          this.map.panBy([-deltaX, -deltaY], { duration: 0 })
        }
      }
      
      this.lastDistance = distance
      this.lastCenter = center
    }
  }
  
  onTouchEnd = (e) => {
    e.preventDefault()
    
    // Удаляем завершенные касания
    for (const touch of e.changedTouches) {
      this.touches.delete(touch.identifier)
    }
    
    // Сбрасываем состояние если меньше 2 касаний
    if (this.touches.size < 2) {
      this.lastDistance = 0
      this.lastCenter = null
    }
  }
  
  getDistance(p1, p2) {
    const dx = p2.x - p1.x
    const dy = p2.y - p1.y
    return Math.sqrt(dx * dx + dy * dy)
  }
  
  getCenter(p1, p2) {
    return {
      x: (p1.x + p2.x) / 2,
      y: (p1.y + p2.y) / 2
    }
  }
}

/**
 * Фабрика для создания behaviors
 */
export class BehaviorManager {
  constructor(map) {
    this.map = map
    this.behaviors = new Map()
  }
  
  add(name, BehaviorClass, options) {
    if (this.behaviors.has(name)) {
      this.remove(name)
    }
    
    const behavior = new BehaviorClass(this.map, options)
    behavior.enable()
    this.behaviors.set(name, behavior)
    
    return behavior
  }
  
  remove(name) {
    const behavior = this.behaviors.get(name)
    if (behavior) {
      behavior.disable()
      this.behaviors.delete(name)
    }
  }
  
  get(name) {
    return this.behaviors.get(name)
  }
  
  enableAll() {
    this.behaviors.forEach(behavior => behavior.enable())
  }
  
  disableAll() {
    this.behaviors.forEach(behavior => behavior.disable())
  }
}