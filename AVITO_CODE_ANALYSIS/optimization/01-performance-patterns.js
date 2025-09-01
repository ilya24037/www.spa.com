/**
 * ⚡ PERFORMANCE OPTIMIZATION PATTERNS
 * Паттерны оптимизации производительности из Avito
 * Файл извлечен из: avito-favorite-collections-integration.js
 */

import { debounce, throttle } from 'lodash-es'

/**
 * 1. DEBOUNCE PATTERN
 * Используется для поиска и фильтров (из Avito)
 */
export const searchDebounce = {
  // Дебаунс для поискового запроса
  createSearchDebounce: (searchFn, delay = 300) => {
    return debounce(searchFn, delay, {
      leading: false,
      trailing: true,
      maxWait: 1000 // Максимальное время ожидания
    })
  },
  
  // Пример использования в компоненте
  useSearch: () => {
    const debouncedSearch = searchDebounce.createSearchDebounce(
      async (query) => {
        const response = await fetch(`/api/search?q=${query}`)
        return response.json()
      },
      300
    )
    
    return debouncedSearch
  }
}

/**
 * 2. THROTTLE PATTERN
 * Для scroll и resize событий (из Avito)
 */
export const scrollThrottle = {
  // Throttle для скролла
  createScrollThrottle: (handler, delay = 100) => {
    return throttle(handler, delay, {
      leading: true,
      trailing: true
    })
  },
  
  // Infinite scroll паттерн
  setupInfiniteScroll: (loadMoreFn, threshold = 200) => {
    const throttledCheck = scrollThrottle.createScrollThrottle(() => {
      const scrollHeight = document.documentElement.scrollHeight
      const scrollTop = document.documentElement.scrollTop
      const clientHeight = document.documentElement.clientHeight
      
      if (scrollTop + clientHeight >= scrollHeight - threshold) {
        loadMoreFn()
      }
    }, 100)
    
    window.addEventListener('scroll', throttledCheck)
    
    return () => window.removeEventListener('scroll', throttledCheck)
  }
}

/**
 * 3. LAZY LOADING IMAGES
 * Паттерн ленивой загрузки изображений (из Avito)
 */
export class LazyImageLoader {
  constructor(options = {}) {
    this.options = {
      root: null,
      rootMargin: '50px',
      threshold: 0.01,
      ...options
    }
    
    this.observer = null
    this.init()
  }
  
  init() {
    this.observer = new IntersectionObserver(
      this.handleIntersection.bind(this),
      this.options
    )
  }
  
  handleIntersection(entries) {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const img = entry.target
        const src = img.dataset.src
        
        if (src) {
          // Предзагрузка изображения
          const tempImg = new Image()
          tempImg.onload = () => {
            img.src = src
            img.classList.add('loaded')
            this.observer.unobserve(img)
          }
          tempImg.src = src
        }
      }
    })
  }
  
  observe(element) {
    if (element && this.observer) {
      this.observer.observe(element)
    }
  }
  
  disconnect() {
    if (this.observer) {
      this.observer.disconnect()
    }
  }
}

/**
 * 4. VIRTUAL SCROLLING
 * Виртуальный скроллинг для больших списков (паттерн Avito)
 */
export class VirtualScroller {
  constructor(container, items, itemHeight) {
    this.container = container
    this.items = items
    this.itemHeight = itemHeight
    this.visibleItems = []
    this.scrollTop = 0
    
    this.init()
  }
  
  init() {
    this.containerHeight = this.container.clientHeight
    this.totalHeight = this.items.length * this.itemHeight
    
    // Создаем виртуальный контейнер
    this.virtualContainer = document.createElement('div')
    this.virtualContainer.style.height = `${this.totalHeight}px`
    this.virtualContainer.style.position = 'relative'
    
    this.container.appendChild(this.virtualContainer)
    this.container.addEventListener('scroll', this.handleScroll.bind(this))
    
    this.render()
  }
  
  handleScroll() {
    this.scrollTop = this.container.scrollTop
    this.render()
  }
  
  render() {
    const startIndex = Math.floor(this.scrollTop / this.itemHeight)
    const endIndex = Math.ceil(
      (this.scrollTop + this.containerHeight) / this.itemHeight
    )
    
    // Очищаем предыдущие элементы
    this.visibleItems.forEach(item => item.remove())
    this.visibleItems = []
    
    // Рендерим только видимые элементы
    for (let i = startIndex; i < endIndex && i < this.items.length; i++) {
      const item = this.createItemElement(this.items[i], i)
      item.style.position = 'absolute'
      item.style.top = `${i * this.itemHeight}px`
      item.style.height = `${this.itemHeight}px`
      
      this.virtualContainer.appendChild(item)
      this.visibleItems.push(item)
    }
  }
  
  createItemElement(data, index) {
    const div = document.createElement('div')
    div.className = 'virtual-item'
    div.textContent = `Item ${index}: ${JSON.stringify(data)}`
    return div
  }
}

/**
 * 5. MEMOIZATION PATTERN
 * Кеширование вычислений (из Avito)
 */
export class Memoizer {
  constructor(fn, options = {}) {
    this.fn = fn
    this.cache = new Map()
    this.maxSize = options.maxSize || 100
    this.ttl = options.ttl || null // Time to live в миллисекундах
  }
  
  execute(...args) {
    const key = JSON.stringify(args)
    
    if (this.cache.has(key)) {
      const cached = this.cache.get(key)
      
      // Проверяем TTL
      if (this.ttl && Date.now() - cached.timestamp > this.ttl) {
        this.cache.delete(key)
      } else {
        return cached.value
      }
    }
    
    // Вычисляем значение
    const value = this.fn(...args)
    
    // Сохраняем в кеш
    this.cache.set(key, {
      value,
      timestamp: Date.now()
    })
    
    // Очищаем старые записи если превышен лимит
    if (this.cache.size > this.maxSize) {
      const firstKey = this.cache.keys().next().value
      this.cache.delete(firstKey)
    }
    
    return value
  }
  
  clear() {
    this.cache.clear()
  }
}

/**
 * 6. REQUEST ANIMATION FRAME PATTERN
 * Оптимизация анимаций (из Avito)
 */
export class RAFScheduler {
  constructor() {
    this.callbacks = []
    this.rafId = null
  }
  
  schedule(callback) {
    this.callbacks.push(callback)
    
    if (!this.rafId) {
      this.rafId = requestAnimationFrame(() => {
        const callbacks = this.callbacks.slice()
        this.callbacks = []
        this.rafId = null
        
        callbacks.forEach(cb => cb())
      })
    }
  }
  
  cancel() {
    if (this.rafId) {
      cancelAnimationFrame(this.rafId)
      this.rafId = null
    }
    this.callbacks = []
  }
}