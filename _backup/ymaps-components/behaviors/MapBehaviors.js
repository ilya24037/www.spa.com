/**
 * Модуль управления поведениями карты Yandex Maps
 * Включает управление масштабированием, перетаскиванием и мультитач жестами
 * @module MapBehaviors
 * @version 1.0.0
 */

/**
 * Класс для управления поведениями карты
 * Позволяет включать/отключать различные интерактивные возможности
 */
class MapBehaviors {
  /**
   * Создает менеджер поведений карты
   * @param {Object} map - Экземпляр карты Yandex Maps
   * @param {Object} [options={}] - Опции поведений
   * @param {boolean} [options.drag=true] - Перетаскивание карты
   * @param {boolean} [options.dblClickZoom=true] - Масштабирование двойным кликом
   * @param {boolean} [options.multiTouch=true] - Мультитач жесты
   * @param {boolean} [options.rightMouseButtonMagnifier=true] - Лупа правой кнопкой мыши
   * @param {boolean} [options.leftMouseButtonMagnifier=false] - Лупа левой кнопкой мыши
   * @param {boolean} [options.ruler=false] - Измерение расстояний
   * @param {boolean} [options.routeEditor=false] - Редактирование маршрутов
   * @param {boolean} [options.scrollZoom=true] - Масштабирование колесом мыши
   * @param {Object} [options.dragOptions] - Опции перетаскивания
   * @param {Object} [options.zoomOptions] - Опции масштабирования
   * @param {Object} [options.multiTouchOptions] - Опции мультитач
   */
  constructor(map, options = {}) {
    if (!map) {
      throw new Error('Map instance is required')
    }

    this.map = map
    this.options = this._mergeOptions(options)
    this.behaviors = null
    this._isReady = false
    this._customBehaviors = new Map()
    this._listeners = new Map()
    this._state = this._createInitialState()
    
    this._init()
  }

  /**
   * Объединяет опции с настройками по умолчанию
   * @private
   */
  _mergeOptions(options) {
    const defaults = {
      // Основные поведения
      drag: true,
      dblClickZoom: true,
      multiTouch: true,
      rightMouseButtonMagnifier: true,
      leftMouseButtonMagnifier: false,
      ruler: false,
      routeEditor: false,
      scrollZoom: true,
      
      // Опции перетаскивания
      dragOptions: {
        inertia: true,
        inertiaDuration: 500,
        cursor: 'grab',
        cursorDragging: 'grabbing',
        useMapMargin: true
      },
      
      // Опции масштабирования
      zoomOptions: {
        smooth: true,
        duration: 300,
        centering: true,
        zoomRange: [0, 23],
        checkZoomRange: true
      },
      
      // Опции мультитач
      multiTouchOptions: {
        tremor: 4,
        preventDefaultAction: true
      },
      
      // Опции скролла
      scrollZoomOptions: {
        speed: 1.2,
        smooth: true,
        centering: true
      },
      
      // Ограничения
      restrictMapArea: null,
      restrictZoomRange: null,
      
      // Callbacks
      onBehaviorEnabled: null,
      onBehaviorDisabled: null,
      onDragStart: null,
      onDrag: null,
      onDragEnd: null,
      onZoomStart: null,
      onZoomChange: null,
      onZoomEnd: null
    }
    
    return { ...defaults, ...options }
  }

  /**
   * Создает начальное состояние
   * @private
   */
  _createInitialState() {
    return {
      isDragging: false,
      isZooming: false,
      isPanning: false,
      currentZoom: null,
      dragStartPosition: null,
      enabledBehaviors: new Set()
    }
  }

  /**
   * Инициализирует менеджер поведений
   * @private
   */
  async _init() {
    try {
      // Проверяем доступность ymaps
      if (typeof ymaps === 'undefined') {
        throw new Error('Yandex Maps API не загружен')
      }

      // Ждем готовности карты
      await this._waitForMap()
      
      // Получаем менеджер поведений
      this.behaviors = this.map.behaviors
      
      if (!this.behaviors) {
        throw new Error('Behaviors manager не доступен')
      }
      
      // Применяем начальные настройки
      this._applyInitialBehaviors()
      
      // Добавляем обработчики событий
      this._attachEventListeners()
      
      // Применяем ограничения
      this._applyRestrictions()
      
      this._isReady = true
      
    } catch (error) {
      console.error('Ошибка инициализации behaviors:', error)
      throw error
    }
  }

  /**
   * Ждет готовности карты
   * @private
   */
  _waitForMap() {
    return new Promise((resolve) => {
      if (this.map.behaviors) {
        resolve()
      } else {
        const checkInterval = setInterval(() => {
          if (this.map.behaviors) {
            clearInterval(checkInterval)
            resolve()
          }
        }, 100)
      }
    })
  }

  /**
   * Применяет начальные настройки поведений
   * @private
   */
  _applyInitialBehaviors() {
    // Отключаем все поведения
    this.behaviors.disable()
    
    // Включаем выбранные поведения
    const behaviorsList = []
    
    if (this.options.drag) {
      behaviorsList.push('drag')
      this._state.enabledBehaviors.add('drag')
    }
    
    if (this.options.dblClickZoom) {
      behaviorsList.push('dblClickZoom')
      this._state.enabledBehaviors.add('dblClickZoom')
    }
    
    if (this.options.multiTouch) {
      behaviorsList.push('multiTouch')
      this._state.enabledBehaviors.add('multiTouch')
    }
    
    if (this.options.rightMouseButtonMagnifier) {
      behaviorsList.push('rightMouseButtonMagnifier')
      this._state.enabledBehaviors.add('rightMouseButtonMagnifier')
    }
    
    if (this.options.leftMouseButtonMagnifier) {
      behaviorsList.push('leftMouseButtonMagnifier')
      this._state.enabledBehaviors.add('leftMouseButtonMagnifier')
    }
    
    if (this.options.ruler) {
      behaviorsList.push('ruler')
      this._state.enabledBehaviors.add('ruler')
    }
    
    if (this.options.routeEditor) {
      behaviorsList.push('routeEditor')
      this._state.enabledBehaviors.add('routeEditor')
    }
    
    if (this.options.scrollZoom) {
      behaviorsList.push('scrollZoom')
      this._state.enabledBehaviors.add('scrollZoom')
    }
    
    // Включаем поведения
    if (behaviorsList.length > 0) {
      this.behaviors.enable(behaviorsList)
    }
  }

  /**
   * Добавляет обработчики событий
   * @private
   */
  _attachEventListeners() {
    if (!this.map.events) return

    // Начало перетаскивания
    this.map.events.add('actionbegin', (e) => {
      const action = e.get('action')
      
      if (action.type === 'drag') {
        this._state.isDragging = true
        this._state.dragStartPosition = e.get('coords')
        
        if (this.options.onDragStart) {
          this.options.onDragStart(e)
        }
      } else if (action.type === 'zoom') {
        this._state.isZooming = true
        
        if (this.options.onZoomStart) {
          this.options.onZoomStart(e)
        }
      }
    })

    // Процесс перетаскивания
    this.map.events.add('actiontick', (e) => {
      const action = e.get('action')
      
      if (action.type === 'drag' && this._state.isDragging) {
        if (this.options.onDrag) {
          this.options.onDrag(e)
        }
      } else if (action.type === 'zoom' && this._state.isZooming) {
        if (this.options.onZoomChange) {
          this.options.onZoomChange(e)
        }
      }
    })

    // Конец перетаскивания
    this.map.events.add('actionend', (e) => {
      const action = e.get('action')
      
      if (action.type === 'drag') {
        this._state.isDragging = false
        this._state.dragStartPosition = null
        
        if (this.options.onDragEnd) {
          this.options.onDragEnd(e)
        }
      } else if (action.type === 'zoom') {
        this._state.isZooming = false
        
        if (this.options.onZoomEnd) {
          this.options.onZoomEnd(e)
        }
      }
    })

    // Изменение зума
    this.map.events.add('boundschange', (e) => {
      const newZoom = e.get('newZoom')
      const oldZoom = e.get('oldZoom')
      
      if (newZoom !== oldZoom) {
        this._state.currentZoom = newZoom
        this._checkZoomRestrictions(newZoom)
      }
    })

    // Клик по карте
    this.map.events.add('click', (e) => {
      this._onMapClick(e)
    })

    // Двойной клик
    this.map.events.add('dblclick', (e) => {
      this._onMapDblClick(e)
    })

    // Правый клик
    this.map.events.add('contextmenu', (e) => {
      this._onMapContextMenu(e)
    })

    // События мыши
    this.map.events.add('mouseenter', () => {
      if (this.options.drag && this.options.dragOptions.cursor) {
        this.map.container.getElement().style.cursor = this.options.dragOptions.cursor
      }
    })

    this.map.events.add('mouseleave', () => {
      if (!this._state.isDragging) {
        this.map.container.getElement().style.cursor = ''
      }
    })
  }

  /**
   * Применяет ограничения
   * @private
   */
  _applyRestrictions() {
    // Ограничение области карты
    if (this.options.restrictMapArea) {
      this.map.options.set('restrictMapArea', this.options.restrictMapArea)
    }
    
    // Ограничение диапазона масштабирования
    if (this.options.restrictZoomRange) {
      const [minZoom, maxZoom] = this.options.restrictZoomRange
      this.map.options.set('minZoom', minZoom)
      this.map.options.set('maxZoom', maxZoom)
    }
  }

  /**
   * Проверяет ограничения зума
   * @private
   */
  _checkZoomRestrictions(zoom) {
    if (!this.options.restrictZoomRange) return
    
    const [minZoom, maxZoom] = this.options.restrictZoomRange
    
    if (zoom < minZoom) {
      this.map.setZoom(minZoom, { duration: 0 })
    } else if (zoom > maxZoom) {
      this.map.setZoom(maxZoom, { duration: 0 })
    }
  }

  /**
   * Обработчик клика по карте
   * @private
   */
  _onMapClick(event) {
    // Можно добавить кастомную логику
  }

  /**
   * Обработчик двойного клика
   * @private
   */
  _onMapDblClick(event) {
    // Если dblClickZoom отключен, предотвращаем зум
    if (!this.options.dblClickZoom) {
      event.preventDefault()
    }
  }

  /**
   * Обработчик правого клика
   * @private
   */
  _onMapContextMenu(event) {
    // Можно добавить контекстное меню
  }

  /**
   * Включает поведение
   * @param {string|Array} behavior - Название поведения или массив названий
   */
  enable(behavior) {
    if (!this._isReady || !this.behaviors) return
    
    if (Array.isArray(behavior)) {
      this.behaviors.enable(behavior)
      behavior.forEach(b => {
        this._state.enabledBehaviors.add(b)
        this._onBehaviorEnabled(b)
      })
    } else {
      this.behaviors.enable(behavior)
      this._state.enabledBehaviors.add(behavior)
      this._onBehaviorEnabled(behavior)
    }
  }

  /**
   * Отключает поведение
   * @param {string|Array} behavior - Название поведения или массив названий
   */
  disable(behavior) {
    if (!this._isReady || !this.behaviors) return
    
    if (Array.isArray(behavior)) {
      this.behaviors.disable(behavior)
      behavior.forEach(b => {
        this._state.enabledBehaviors.delete(b)
        this._onBehaviorDisabled(b)
      })
    } else {
      this.behaviors.disable(behavior)
      this._state.enabledBehaviors.delete(behavior)
      this._onBehaviorDisabled(behavior)
    }
  }

  /**
   * Проверяет, включено ли поведение
   * @param {string} behavior - Название поведения
   * @returns {boolean}
   */
  isEnabled(behavior) {
    if (!this._isReady || !this.behaviors) return false
    return this.behaviors.isEnabled(behavior)
  }

  /**
   * Получает список включенных поведений
   * @returns {Array}
   */
  getEnabled() {
    return Array.from(this._state.enabledBehaviors)
  }

  /**
   * Включает перетаскивание карты
   */
  enableDrag() {
    this.enable('drag')
    this.options.drag = true
  }

  /**
   * Отключает перетаскивание карты
   */
  disableDrag() {
    this.disable('drag')
    this.options.drag = false
  }

  /**
   * Включает масштабирование двойным кликом
   */
  enableDblClickZoom() {
    this.enable('dblClickZoom')
    this.options.dblClickZoom = true
  }

  /**
   * Отключает масштабирование двойным кликом
   */
  disableDblClickZoom() {
    this.disable('dblClickZoom')
    this.options.dblClickZoom = false
  }

  /**
   * Включает мультитач жесты
   */
  enableMultiTouch() {
    this.enable('multiTouch')
    this.options.multiTouch = true
  }

  /**
   * Отключает мультитач жесты
   */
  disableMultiTouch() {
    this.disable('multiTouch')
    this.options.multiTouch = false
  }

  /**
   * Включает масштабирование колесом мыши
   */
  enableScrollZoom() {
    this.enable('scrollZoom')
    this.options.scrollZoom = true
  }

  /**
   * Отключает масштабирование колесом мыши
   */
  disableScrollZoom() {
    this.disable('scrollZoom')
    this.options.scrollZoom = false
  }

  /**
   * Включает линейку
   */
  enableRuler() {
    this.enable('ruler')
    this.options.ruler = true
  }

  /**
   * Отключает линейку
   */
  disableRuler() {
    this.disable('ruler')
    this.options.ruler = false
  }

  /**
   * Устанавливает ограничение области карты
   * @param {Array} bounds - Границы [[minLat, minLng], [maxLat, maxLng]]
   */
  setRestrictMapArea(bounds) {
    this.options.restrictMapArea = bounds
    
    if (this.map) {
      this.map.options.set('restrictMapArea', bounds)
    }
  }

  /**
   * Снимает ограничение области карты
   */
  removeRestrictMapArea() {
    this.options.restrictMapArea = null
    
    if (this.map) {
      this.map.options.unset('restrictMapArea')
    }
  }

  /**
   * Устанавливает ограничение диапазона зума
   * @param {number} minZoom - Минимальный зум
   * @param {number} maxZoom - Максимальный зум
   */
  setZoomRange(minZoom, maxZoom) {
    this.options.restrictZoomRange = [minZoom, maxZoom]
    
    if (this.map) {
      this.map.options.set('minZoom', minZoom)
      this.map.options.set('maxZoom', maxZoom)
    }
  }

  /**
   * Снимает ограничение диапазона зума
   */
  removeZoomRange() {
    this.options.restrictZoomRange = null
    
    if (this.map) {
      this.map.options.unset('minZoom')
      this.map.options.unset('maxZoom')
    }
  }

  /**
   * Включает все поведения
   */
  enableAll() {
    const allBehaviors = [
      'drag',
      'dblClickZoom',
      'multiTouch',
      'rightMouseButtonMagnifier',
      'leftMouseButtonMagnifier',
      'ruler',
      'routeEditor',
      'scrollZoom'
    ]
    
    this.enable(allBehaviors)
  }

  /**
   * Отключает все поведения
   */
  disableAll() {
    if (this.behaviors) {
      this.behaviors.disable()
      this._state.enabledBehaviors.clear()
    }
  }

  /**
   * Сбрасывает к настройкам по умолчанию
   */
  reset() {
    this.disableAll()
    this._applyInitialBehaviors()
  }

  /**
   * Блокирует карту (отключает все интерактивные возможности)
   */
  lock() {
    this._state.lockedBehaviors = this.getEnabled()
    this.disableAll()
  }

  /**
   * Разблокирует карту (восстанавливает предыдущие поведения)
   */
  unlock() {
    if (this._state.lockedBehaviors) {
      this.enable(this._state.lockedBehaviors)
      this._state.lockedBehaviors = null
    }
  }

  /**
   * Проверяет, заблокирована ли карта
   * @returns {boolean}
   */
  isLocked() {
    return this._state.lockedBehaviors !== null && 
           this._state.lockedBehaviors !== undefined
  }

  /**
   * Обработчик включения поведения
   * @private
   */
  _onBehaviorEnabled(behavior) {
    if (this.options.onBehaviorEnabled) {
      this.options.onBehaviorEnabled(behavior)
    }
  }

  /**
   * Обработчик отключения поведения
   * @private
   */
  _onBehaviorDisabled(behavior) {
    if (this.options.onBehaviorDisabled) {
      this.options.onBehaviorDisabled(behavior)
    }
  }

  /**
   * Создает кастомное поведение
   * @param {string} name - Название поведения
   * @param {Object} config - Конфигурация поведения
   */
  createCustomBehavior(name, config) {
    if (this._customBehaviors.has(name)) {
      console.warn(`Поведение ${name} уже существует`)
      return
    }
    
    const behavior = {
      name,
      enabled: false,
      config,
      handlers: new Map()
    }
    
    // Добавляем обработчики
    if (config.onEnable) {
      behavior.handlers.set('enable', config.onEnable)
    }
    
    if (config.onDisable) {
      behavior.handlers.set('disable', config.onDisable)
    }
    
    this._customBehaviors.set(name, behavior)
  }

  /**
   * Включает кастомное поведение
   * @param {string} name - Название поведения
   */
  enableCustomBehavior(name) {
    const behavior = this._customBehaviors.get(name)
    
    if (!behavior) {
      console.warn(`Поведение ${name} не найдено`)
      return
    }
    
    if (behavior.enabled) return
    
    behavior.enabled = true
    
    const enableHandler = behavior.handlers.get('enable')
    if (enableHandler) {
      enableHandler(this.map, this)
    }
  }

  /**
   * Отключает кастомное поведение
   * @param {string} name - Название поведения
   */
  disableCustomBehavior(name) {
    const behavior = this._customBehaviors.get(name)
    
    if (!behavior) {
      console.warn(`Поведение ${name} не найдено`)
      return
    }
    
    if (!behavior.enabled) return
    
    behavior.enabled = false
    
    const disableHandler = behavior.handlers.get('disable')
    if (disableHandler) {
      disableHandler(this.map, this)
    }
  }

  /**
   * Получает состояние поведений
   * @returns {Object}
   */
  getState() {
    return {
      ...this._state,
      enabledBehaviors: Array.from(this._state.enabledBehaviors)
    }
  }

  /**
   * Добавляет обработчик события
   * @param {string} event - Название события
   * @param {Function} handler - Обработчик
   */
  on(event, handler) {
    if (!this._listeners.has(event)) {
      this._listeners.set(event, [])
    }
    
    this._listeners.get(event).push(handler)
  }

  /**
   * Удаляет обработчик события
   * @param {string} event - Название события
   * @param {Function} [handler] - Обработчик
   */
  off(event, handler) {
    if (!this._listeners.has(event)) return
    
    if (handler) {
      const handlers = this._listeners.get(event)
      const index = handlers.indexOf(handler)
      
      if (index !== -1) {
        handlers.splice(index, 1)
      }
    } else {
      this._listeners.delete(event)
    }
  }

  /**
   * Проверяет готовность менеджера
   * @returns {boolean}
   */
  isReady() {
    return this._isReady
  }

  /**
   * Уничтожает экземпляр и освобождает ресурсы
   */
  destroy() {
    try {
      // Восстанавливаем стандартные поведения
      if (this.behaviors) {
        this.enableAll()
      }
      
      // Очищаем обработчики
      this._listeners.clear()
      
      // Очищаем кастомные поведения
      this._customBehaviors.clear()
      
      // Сбрасываем состояние
      this._state = this._createInitialState()
      
      // Сбрасываем флаги
      this._isReady = false
      
      // Очищаем ссылки
      this.map = null
      this.behaviors = null
      this.options = null
      
    } catch (error) {
      console.error('Ошибка при уничтожении behaviors manager:', error)
    }
  }
}

/**
 * Список доступных поведений
 */
export const BEHAVIORS = {
  DRAG: 'drag',
  DBL_CLICK_ZOOM: 'dblClickZoom',
  MULTI_TOUCH: 'multiTouch',
  RIGHT_MOUSE_MAGNIFIER: 'rightMouseButtonMagnifier',
  LEFT_MOUSE_MAGNIFIER: 'leftMouseButtonMagnifier',
  RULER: 'ruler',
  ROUTE_EDITOR: 'routeEditor',
  SCROLL_ZOOM: 'scrollZoom'
}

/**
 * Фабричная функция для создания менеджера поведений
 * @param {Object} map - Экземпляр карты
 * @param {Object} [options] - Опции
 * @returns {MapBehaviors}
 */
export function createMapBehaviors(map, options) {
  return new MapBehaviors(map, options)
}

/**
 * Проверяет поддержку behaviors в браузере
 * @returns {boolean}
 */
export function isBehaviorsSupported() {
  return typeof ymaps !== 'undefined' && 
         ymaps.Map !== undefined
}

/**
 * Версия модуля
 */
export const VERSION = '1.0.0'

/**
 * Опции по умолчанию
 */
export const DEFAULT_OPTIONS = {
  drag: true,
  dblClickZoom: true,
  multiTouch: true,
  scrollZoom: true,
  rightMouseButtonMagnifier: true
}

// Экспортируем класс по умолчанию
export default MapBehaviors