/**
 * Balloon - Модуль всплывающих окон для Yandex Maps
 * 
 * @module Balloon
 * @version 1.0.0
 * @author SPA Platform Team
 * 
 * Класс для создания и управления всплывающими информационными окнами на карте.
 * Поддерживает различные режимы отображения, автопанорамирование и кастомизацию.
 */

import YMapsCore from '../../core/YMapsCore.js'

/**
 * Опции по умолчанию для всплывающего окна
 */
const DEFAULT_OPTIONS = {
  // Визуальные настройки
  closeButton: true,              // Показывать кнопку закрытия
  maxWidth: 400,                   // Максимальная ширина
  maxHeight: 400,                  // Максимальная высота
  minWidth: 85,                    // Минимальная ширина
  minHeight: 30,                   // Минимальная высота
  offset: [0, 0],                  // Смещение от точки привязки
  
  // Поведение
  autoPan: true,                   // Автопанорамирование при открытии
  autoPanMargin: 34,              // Отступ от краев при автопанорамировании
  autoPanDuration: 500,           // Длительность автопанорамирования
  autoPanCheckZoomRange: false,   // Проверять диапазон зума
  autoPanUseMapMargin: true,      // Использовать margin карты
  
  // Режимы отображения
  panelMaxMapArea: 160000,        // Площадь для режима панели (px²)
  panelMaxHeightRatio: 0.5,       // Максимальная высота панели (% от карты)
  
  // Интерактивность
  interactivityModel: 'default',  // Модель интерактивности
  hideIconOnBalloonOpen: true,    // Скрывать иконку при открытии
  
  // Макеты
  layout: 'islands#balloon',       // Макет всплывающего окна
  contentLayout: null,             // Макет содержимого
  panelLayout: 'islands#balloonPanel', // Макет панели
  
  // Z-индекс
  zIndex: 0,                       // Приоритет отображения
  
  // События
  openTimeout: 0,                  // Задержка открытия (мс)
  closeTimeout: 0                  // Задержка закрытия (мс)
}

/**
 * Класс всплывающего окна
 * @class
 */
class Balloon {
  /**
   * Конструктор
   * @param {Object} map - экземпляр карты Yandex
   * @param {Object} options - опции всплывающего окна
   */
  constructor(map, options = {}) {
    if (!map) {
      throw new Error('Карта не передана в конструктор Balloon')
    }
    
    // Сохраняем ссылку на карту
    this._map = map
    
    // Объединяем опции с дефолтными
    this._options = { ...DEFAULT_OPTIONS, ...options }
    
    // Внутреннее состояние
    this._isOpen = false
    this._isPanelMode = false
    this._position = null
    this._content = null
    this._balloonInstance = null
    this._overlay = null
    this._shape = null
    this._listeners = []
    
    // Таймеры для отложенных действий
    this._openTimer = null
    this._closeTimer = null
    this._autoPanTimer = null
    
    // Bind методов для правильного контекста
    this.open = this.open.bind(this)
    this.close = this.close.bind(this)
    this.setPosition = this.setPosition.bind(this)
    this.setContent = this.setContent.bind(this)
    this.autoPan = this.autoPan.bind(this)
    
    // Инициализация
    this._init()
  }
  
  /**
   * Инициализация всплывающего окна
   * @private
   */
  _init() {
    try {
      // Проверяем доступность ymaps
      const ymaps = window.ymaps || window.YMaps
      if (!ymaps) {
        throw new Error('Yandex Maps API не загружено')
      }
      
      // Создаем экземпляр balloon через API карты
      this._balloonInstance = this._map.balloon
      
      // Настраиваем опции
      this._applyOptions()
      
      // Подписываемся на события карты
      this._setupMapListeners()
      
      console.log('✅ Balloon инициализирован')
      
    } catch (error) {
      console.error('❌ Ошибка инициализации Balloon:', error)
      throw error
    }
  }
  
  /**
   * Открывает всплывающее окно
   * @param {Array|Object} position - позиция [lat, lng] или объект с координатами
   * @param {string|HTMLElement|Object} content - содержимое окна
   * @param {Object} options - дополнительные опции
   * @returns {Promise} промис открытия
   */
  async open(position, content, options = {}) {
    try {
      // Отменяем предыдущие таймеры
      this._clearTimers()
      
      // Если окно уже открыто, закрываем
      if (this._isOpen) {
        await this.close()
      }
      
      // Валидация позиции
      const validPosition = this._validatePosition(position)
      if (!validPosition) {
        throw new Error('Некорректная позиция для balloon')
      }
      
      // Сохраняем позицию и контент
      this._position = validPosition
      this._content = content
      
      // Объединяем опции
      const finalOptions = { ...this._options, ...options }
      
      // Определяем режим отображения
      this._isPanelMode = this._checkPanelMode(finalOptions)
      
      // Задержка открытия если указана
      if (finalOptions.openTimeout > 0) {
        return new Promise((resolve, reject) => {
          this._openTimer = setTimeout(async () => {
            try {
              await this._performOpen(finalOptions)
              resolve()
            } catch (error) {
              reject(error)
            }
          }, finalOptions.openTimeout)
        })
      } else {
        return this._performOpen(finalOptions)
      }
      
    } catch (error) {
      console.error('❌ Ошибка открытия balloon:', error)
      throw error
    }
  }
  
  /**
   * Выполняет открытие окна
   * @private
   */
  async _performOpen(options) {
    return new Promise((resolve, reject) => {
      try {
        // Подготавливаем содержимое
        const balloonContent = this._prepareContent(this._content)
        
        // Подготавливаем опции для API
        const apiOptions = this._prepareApiOptions(options)
        
        // Открываем через API карты
        this._balloonInstance.open(this._position, balloonContent, apiOptions)
          .then(() => {
            // Обновляем состояние
            this._isOpen = true
            
            // Получаем overlay и shape
            this._overlay = this._balloonInstance.getOverlay()
            if (this._overlay) {
              this._shape = this._overlay.getShape()
            }
            
            // Настраиваем обработчики событий окна
            this._setupBalloonListeners()
            
            // Автопанорамирование если нужно
            if (options.autoPan && !this._isPanelMode) {
              // Задержка чтобы окно успело отрендериться
              setTimeout(() => this.autoPan(), 50)
            }
            
            console.log('✅ Balloon открыт')
            
            // Генерируем событие
            this._emit('open', {
              position: this._position,
              content: this._content
            })
            
            resolve()
          })
          .catch(reject)
          
      } catch (error) {
        reject(error)
      }
    })
  }
  
  /**
   * Закрывает всплывающее окно
   * @param {boolean} force - принудительное закрытие без задержки
   * @returns {Promise} промис закрытия
   */
  async close(force = false) {
    try {
      // Отменяем таймеры
      this._clearTimers()
      
      // Если окно не открыто
      if (!this._isOpen) {
        return Promise.resolve()
      }
      
      // Задержка закрытия если указана и не принудительное
      if (!force && this._options.closeTimeout > 0) {
        return new Promise((resolve, reject) => {
          this._closeTimer = setTimeout(async () => {
            try {
              await this._performClose()
              resolve()
            } catch (error) {
              reject(error)
            }
          }, this._options.closeTimeout)
        })
      } else {
        return this._performClose()
      }
      
    } catch (error) {
      console.error('❌ Ошибка закрытия balloon:', error)
      throw error
    }
  }
  
  /**
   * Выполняет закрытие окна
   * @private
   */
  async _performClose() {
    return new Promise((resolve, reject) => {
      try {
        // Удаляем обработчики
        this._clearBalloonListeners()
        
        // Закрываем через API
        this._balloonInstance.close()
          .then(() => {
            // Обновляем состояние
            this._isOpen = false
            this._isPanelMode = false
            this._overlay = null
            this._shape = null
            
            console.log('✅ Balloon закрыт')
            
            // Генерируем событие
            this._emit('close', {
              position: this._position,
              content: this._content
            })
            
            // Очищаем данные
            this._position = null
            this._content = null
            
            resolve()
          })
          .catch(reject)
          
      } catch (error) {
        reject(error)
      }
    })
  }
  
  /**
   * Устанавливает позицию окна
   * @param {Array|Object} position - новая позиция
   */
  setPosition(position) {
    const validPosition = this._validatePosition(position)
    if (!validPosition) {
      console.error('Некорректная позиция для balloon')
      return
    }
    
    this._position = validPosition
    
    // Если окно открыто, обновляем позицию
    if (this._isOpen && this._balloonInstance) {
      this._balloonInstance.setPosition(validPosition)
      
      // Автопанорамирование к новой позиции
      if (this._options.autoPan) {
        this.autoPan()
      }
    }
  }
  
  /**
   * Устанавливает содержимое окна
   * @param {string|HTMLElement|Object} content - новое содержимое
   */
  setContent(content) {
    this._content = content
    
    // Если окно открыто, обновляем содержимое
    if (this._isOpen && this._balloonInstance) {
      const balloonContent = this._prepareContent(content)
      this._balloonInstance.setContent(balloonContent)
    }
  }
  
  /**
   * Выполняет автопанорамирование карты к окну
   * @returns {Promise} промис панорамирования
   */
  async autoPan() {
    if (!this._isOpen || !this._map || this._isPanelMode) {
      return Promise.resolve()
    }
    
    return new Promise((resolve) => {
      try {
        // Получаем текущие границы окна
        if (!this._shape) {
          resolve()
          return
        }
        
        const bounds = this._shape.getBounds()
        const mapContainer = this._map.container.getSize()
        const margin = this._options.autoPanMargin
        
        // Вычисляем необходимое смещение
        const offset = this._calculateAutoPanOffset(bounds, mapContainer, margin)
        
        if (offset && (Math.abs(offset[0]) > 1 || Math.abs(offset[1]) > 1)) {
          // Получаем текущий центр
          const currentCenter = this._map.getGlobalPixelCenter()
          const zoom = this._map.getZoom()
          
          // Новый центр
          const newCenter = [
            currentCenter[0] - offset[0],
            currentCenter[1] - offset[1]
          ]
          
          // Панорамируем
          this._map.setGlobalPixelCenter(newCenter, zoom, {
            duration: this._options.autoPanDuration,
            useMapMargin: this._options.autoPanUseMapMargin
          }).then(() => {
            console.log('✅ Автопанорамирование выполнено')
            this._emit('autopancomplete')
            resolve()
          })
          
          this._emit('autopanstart')
        } else {
          resolve()
        }
        
      } catch (error) {
        console.error('Ошибка автопанорамирования:', error)
        resolve()
      }
    })
  }
  
  /**
   * Проверяет, открыто ли окно
   * @returns {boolean}
   */
  isOpen() {
    return this._isOpen
  }
  
  /**
   * Получает текущую позицию
   * @returns {Array|null}
   */
  getPosition() {
    return this._position
  }
  
  /**
   * Получает текущее содержимое
   * @returns {*}
   */
  getContent() {
    return this._content
  }
  
  /**
   * Получает опции
   * @returns {Object}
   */
  getOptions() {
    return { ...this._options }
  }
  
  /**
   * Устанавливает опции
   * @param {Object} options - новые опции
   */
  setOptions(options) {
    this._options = { ...this._options, ...options }
    this._applyOptions()
  }
  
  /**
   * Уничтожает экземпляр и освобождает ресурсы
   */
  destroy() {
    try {
      // Закрываем окно
      if (this._isOpen) {
        this.close(true)
      }
      
      // Очищаем таймеры
      this._clearTimers()
      
      // Удаляем обработчики
      this._clearMapListeners()
      this._clearBalloonListeners()
      
      // Очищаем ссылки
      this._map = null
      this._balloonInstance = null
      this._overlay = null
      this._shape = null
      this._position = null
      this._content = null
      this._options = null
      
      console.log('✅ Balloon уничтожен')
      
    } catch (error) {
      console.error('❌ Ошибка уничтожения Balloon:', error)
    }
  }
  
  // =====================
  // Приватные методы
  // =====================
  
  /**
   * Применяет опции к balloon
   * @private
   */
  _applyOptions() {
    if (!this._balloonInstance) return
    
    // Устанавливаем опции через API
    Object.keys(this._options).forEach(key => {
      if (this._balloonInstance.options) {
        this._balloonInstance.options.set(key, this._options[key])
      }
    })
  }
  
  /**
   * Валидация позиции
   * @private
   */
  _validatePosition(position) {
    if (!position) return null
    
    // Если массив координат
    if (Array.isArray(position)) {
      if (position.length === 2 && 
          typeof position[0] === 'number' && 
          typeof position[1] === 'number') {
        return position
      }
    }
    
    // Если объект с координатами
    if (typeof position === 'object') {
      if (position.lat && position.lng) {
        return [position.lat, position.lng]
      }
      if (position.latitude && position.longitude) {
        return [position.latitude, position.longitude]
      }
    }
    
    return null
  }
  
  /**
   * Проверяет необходимость режима панели
   * @private
   */
  _checkPanelMode(options) {
    const mapSize = this._map.container.getSize()
    const mapArea = mapSize[0] * mapSize[1]
    
    return options.panelMaxMapArea && mapArea >= options.panelMaxMapArea
  }
  
  /**
   * Подготавливает содержимое для отображения
   * @private
   */
  _prepareContent(content) {
    if (!content) return ''
    
    // Если строка HTML
    if (typeof content === 'string') {
      return content
    }
    
    // Если DOM элемент
    if (content instanceof HTMLElement) {
      return content.outerHTML
    }
    
    // Если объект с полями
    if (typeof content === 'object') {
      return this._buildContentFromObject(content)
    }
    
    return String(content)
  }
  
  /**
   * Строит HTML из объекта
   * @private
   */
  _buildContentFromObject(obj) {
    let html = '<div class="balloon-content">'
    
    if (obj.header) {
      html += `<div class="balloon-header">${obj.header}</div>`
    }
    
    if (obj.body) {
      html += `<div class="balloon-body">${obj.body}</div>`
    }
    
    if (obj.footer) {
      html += `<div class="balloon-footer">${obj.footer}</div>`
    }
    
    html += '</div>'
    
    return html
  }
  
  /**
   * Подготавливает опции для API
   * @private
   */
  _prepareApiOptions(options) {
    const apiOptions = {}
    
    // Копируем только поддерживаемые опции
    const supportedOptions = [
      'closeButton', 'offset', 'autoPan', 'autoPanMargin',
      'autoPanDuration', 'autoPanCheckZoomRange', 'autoPanUseMapMargin',
      'panelMaxMapArea', 'panelMaxHeightRatio', 'interactivityModel',
      'layout', 'contentLayout', 'zIndex', 'maxWidth', 'maxHeight',
      'minWidth', 'minHeight'
    ]
    
    supportedOptions.forEach(key => {
      if (key in options) {
        apiOptions[key] = options[key]
      }
    })
    
    return apiOptions
  }
  
  /**
   * Вычисляет смещение для автопанорамирования
   * @private
   */
  _calculateAutoPanOffset(bounds, containerSize, margin) {
    const offset = [0, 0]
    
    // Левая граница
    if (bounds[0][0] < margin) {
      offset[0] = bounds[0][0] - margin
    }
    // Правая граница
    else if (bounds[1][0] > containerSize[0] - margin) {
      offset[0] = bounds[1][0] - containerSize[0] + margin
    }
    
    // Верхняя граница
    if (bounds[0][1] < margin) {
      offset[1] = bounds[0][1] - margin
    }
    // Нижняя граница
    else if (bounds[1][1] > containerSize[1] - margin) {
      offset[1] = bounds[1][1] - containerSize[1] + margin
    }
    
    return offset
  }
  
  /**
   * Настраивает обработчики событий карты
   * @private
   */
  _setupMapListeners() {
    if (!this._map) return
    
    // Закрытие при изменении масштаба
    const onZoomChange = () => {
      if (this._isOpen && !this._options.stayOpenOnZoom) {
        this.close()
      }
    }
    
    // Обновление позиции при перемещении карты
    const onBoundsChange = () => {
      if (this._isOpen && !this._isPanelMode) {
        // Обновляем shape для правильного автопанорамирования
        if (this._overlay) {
          this._shape = this._overlay.getShape()
        }
      }
    }
    
    this._map.events.add('boundschange', onBoundsChange)
    this._map.events.add('sizechange', onZoomChange)
    
    // Сохраняем ссылки для удаления
    this._listeners.push(
      { event: 'boundschange', handler: onBoundsChange },
      { event: 'sizechange', handler: onZoomChange }
    )
  }
  
  /**
   * Удаляет обработчики событий карты
   * @private
   */
  _clearMapListeners() {
    if (!this._map) return
    
    this._listeners.forEach(({ event, handler }) => {
      this._map.events.remove(event, handler)
    })
    
    this._listeners = []
  }
  
  /**
   * Настраивает обработчики событий balloon
   * @private
   */
  _setupBalloonListeners() {
    if (!this._balloonInstance) return
    
    // Обработка закрытия пользователем
    const onUserClose = () => {
      this._isOpen = false
      this._emit('userclose')
      console.log('✅ Balloon закрыт пользователем')
    }
    
    this._balloonInstance.events.add('userclose', onUserClose)
  }
  
  /**
   * Удаляет обработчики событий balloon
   * @private
   */
  _clearBalloonListeners() {
    if (!this._balloonInstance) return
    
    this._balloonInstance.events.removeAll()
  }
  
  /**
   * Очищает все таймеры
   * @private
   */
  _clearTimers() {
    if (this._openTimer) {
      clearTimeout(this._openTimer)
      this._openTimer = null
    }
    
    if (this._closeTimer) {
      clearTimeout(this._closeTimer)
      this._closeTimer = null
    }
    
    if (this._autoPanTimer) {
      clearTimeout(this._autoPanTimer)
      this._autoPanTimer = null
    }
  }
  
  /**
   * Генерирует событие
   * @private
   */
  _emit(eventName, data = {}) {
    // Здесь можно добавить EventEmitter или callback систему
    if (typeof this._options[`on${eventName}`] === 'function') {
      this._options[`on${eventName}`](data)
    }
  }
}

// Экспорт класса
export default Balloon

// Для использования без ES6 модулей
if (typeof module !== 'undefined' && module.exports) {
  module.exports = Balloon
}

// Для использования в браузере
if (typeof window !== 'undefined') {
  window.YMapsBalloon = Balloon
}