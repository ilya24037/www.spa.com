/**
 * Базовый класс для всех Controls компонентов Yandex Maps
 * Обеспечивает общую функциональность и соблюдение принципов SOLID
 * 
 * @class ControlBase
 * @version 1.0.0
 * @author SPA Platform
 */

/**
 * Базовый класс для всех элементов управления карты
 * Реализует общие методы и события для всех контролов
 */
export default class ControlBase {
  /**
   * Конструктор базового контрола
   * @param {Object} options - Опции контрола
   * @param {string} [options.position='topRight'] - Позиция на карте
   * @param {number} [options.zIndex=1000] - Z-index элемента
   * @param {Object} [options.margin] - Отступы от края карты
   * @param {boolean} [options.float='none'] - Позиционирование
   */
  constructor(options = {}) {
    // Валидация входных параметров (Production-ready)
    if (typeof options !== 'object' || options === null) {
      throw new TypeError('ControlBase: options должны быть объектом')
    }

    /**
     * Опции контрола с значениями по умолчанию
     * @type {Object}
     * @private
     */
    this._options = {
      position: 'topRight',
      zIndex: 1000,
      margin: { top: 10, right: 10, bottom: 10, left: 10 },
      float: 'none',
      visible: true,
      enabled: true,
      ...options
    }

    /**
     * Ссылка на карту
     * @type {ymaps.Map|null}
     * @private
     */
    this._map = null

    /**
     * DOM элемент контрола
     * @type {HTMLElement|null}
     * @private
     */
    this._element = null

    /**
     * Контейнер для размещения контрола на карте
     * @type {HTMLElement|null}
     * @private
     */
    this._container = null

    /**
     * Обработчики событий
     * @type {Map}
     * @private
     */
    this._eventListeners = new Map()

    /**
     * Флаг инициализации
     * @type {boolean}
     * @private
     */
    this._initialized = false

    /**
     * Флаг активного состояния
     * @type {boolean}
     * @private
     */
    this._active = false

    // Привязка контекста методов
    this._onMapClick = this._onMapClick.bind(this)
    this._onResize = this._onResize.bind(this)
    this._onDestroy = this._onDestroy.bind(this)
  }

  /**
   * Добавление контрола на карту
   * @param {ymaps.Map} map - Экземпляр карты
   * @returns {Promise<void>}
   */
  async addToMap(map) {
    try {
      if (!map || typeof map.container === 'undefined') {
        throw new Error('ControlBase: Некорректный экземпляр карты')
      }

      if (this._map) {
        throw new Error('ControlBase: Контрол уже добавлен на карту')
      }

      this._map = map
      
      // Создаем DOM структуру
      await this._createElement()
      await this._createContainer()
      
      // Добавляем на карту
      this._addToMapContainer()
      
      // Настраиваем обработчики событий
      this._setupEventListeners()
      
      // Применяем стили позиционирования
      this._applyPositioning()
      
      this._initialized = true
      this._emitEvent('add', { control: this, map: this._map })

    } catch (error) {
      console.error('ControlBase.addToMap:', error)
      throw error
    }
  }

  /**
   * Удаление контрола с карты
   * @returns {Promise<void>}
   */
  async removeFromMap() {
    try {
      if (!this._map) {
        return // Контрол не был добавлен
      }

      // Удаляем обработчики событий
      this._removeEventListeners()
      
      // Удаляем DOM элементы
      if (this._element && this._element.parentNode) {
        this._element.parentNode.removeChild(this._element)
      }
      
      if (this._container && this._container.parentNode) {
        this._container.parentNode.removeChild(this._container)
      }

      this._emitEvent('remove', { control: this, map: this._map })
      
      // Очищаем ссылки
      this._map = null
      this._element = null
      this._container = null
      this._initialized = false
      this._active = false

    } catch (error) {
      console.error('ControlBase.removeFromMap:', error)
      throw error
    }
  }

  /**
   * Получить карту, к которой привязан контрол
   * @returns {ymaps.Map|null}
   */
  getMap() {
    return this._map
  }

  /**
   * Получить опции контрола
   * @returns {Object}
   */
  getOptions() {
    return { ...this._options }
  }

  /**
   * Установить опцию
   * @param {string} key - Ключ опции
   * @param {*} value - Значение
   */
  setOption(key, value) {
    if (typeof key !== 'string') {
      throw new TypeError('ControlBase: ключ опции должен быть строкой')
    }

    const oldValue = this._options[key]
    this._options[key] = value

    this._emitEvent('optionChange', { 
      key, 
      oldValue, 
      newValue: value 
    })

    // Применяем изменения если контрол инициализирован
    if (this._initialized) {
      this._onOptionChange(key, value, oldValue)
    }
  }

  /**
   * Показать контрол
   */
  show() {
    if (this._element) {
      this._element.style.display = 'block'
      this._options.visible = true
      this._emitEvent('show')
    }
  }

  /**
   * Скрыть контрол
   */
  hide() {
    if (this._element) {
      this._element.style.display = 'none'
      this._options.visible = false
      this._emitEvent('hide')
    }
  }

  /**
   * Включить контрол
   */
  enable() {
    this._options.enabled = true
    if (this._element) {
      this._element.classList.remove('disabled')
    }
    this._emitEvent('enable')
  }

  /**
   * Отключить контрол
   */
  disable() {
    this._options.enabled = false
    if (this._element) {
      this._element.classList.add('disabled')
    }
    this._emitEvent('disable')
  }

  /**
   * Проверить, видим ли контрол
   * @returns {boolean}
   */
  isVisible() {
    return this._options.visible
  }

  /**
   * Проверить, включен ли контрол
   * @returns {boolean}
   */
  isEnabled() {
    return this._options.enabled
  }

  /**
   * Добавить обработчик события
   * @param {string} event - Тип события
   * @param {Function} handler - Обработчик
   */
  on(event, handler) {
    if (typeof event !== 'string' || typeof handler !== 'function') {
      throw new TypeError('ControlBase: некорректные параметры события')
    }

    if (!this._eventListeners.has(event)) {
      this._eventListeners.set(event, [])
    }
    
    this._eventListeners.get(event).push(handler)
  }

  /**
   * Удалить обработчик события
   * @param {string} event - Тип события
   * @param {Function} handler - Обработчик
   */
  off(event, handler) {
    const handlers = this._eventListeners.get(event)
    if (handlers) {
      const index = handlers.indexOf(handler)
      if (index !== -1) {
        handlers.splice(index, 1)
      }
    }
  }

  /**
   * Получить DOM элемент контрола
   * @returns {HTMLElement|null}
   */
  getElement() {
    return this._element
  }

  /**
   * Уничтожить контрол полностью
   */
  destroy() {
    this.removeFromMap()
    this._eventListeners.clear()
    this._onDestroy()
  }

  // PROTECTED методы для наследников

  /**
   * Создание DOM элемента контрола (переопределяется в наследниках)
   * @returns {Promise<void>}
   * @protected
   */
  async _createElement() {
    // Базовая реализация - создаем простой div
    this._element = document.createElement('div')
    this._element.className = 'ymaps-control-base'
  }

  /**
   * Создание контейнера для контрола
   * @returns {Promise<void>}
   * @protected
   */
  async _createContainer() {
    this._container = document.createElement('div')
    this._container.className = 'ymaps-control-container'
    this._container.appendChild(this._element)
  }

  /**
   * Добавление контейнера в DOM карты
   * @protected
   */
  _addToMapContainer() {
    const mapContainer = this._map.container.getElement()
    mapContainer.appendChild(this._container)
  }

  /**
   * Настройка обработчиков событий
   * @protected
   */
  _setupEventListeners() {
    if (this._map && this._map.events) {
      this._map.events.add('click', this._onMapClick)
      
      // Слушаем изменение размера карты
      if (this._map.container && this._map.container.events) {
        this._map.container.events.add('sizechange', this._onResize)
      }
    }
  }

  /**
   * Удаление обработчиков событий
   * @protected
   */
  _removeEventListeners() {
    if (this._map && this._map.events) {
      this._map.events.remove('click', this._onMapClick)
      
      if (this._map.container && this._map.container.events) {
        this._map.container.events.remove('sizechange', this._onResize)
      }
    }
  }

  /**
   * Применение стилей позиционирования
   * @protected
   */
  _applyPositioning() {
    if (!this._container) return

    const position = this._options.position
    const margin = this._options.margin
    const zIndex = this._options.zIndex

    // Базовые стили
    Object.assign(this._container.style, {
      position: 'absolute',
      zIndex: zIndex.toString(),
      pointerEvents: 'auto'
    })

    // Позиционирование
    switch (position) {
      case 'topLeft':
        this._container.style.top = margin.top + 'px'
        this._container.style.left = margin.left + 'px'
        break
      case 'topCenter':
        this._container.style.top = margin.top + 'px'
        this._container.style.left = '50%'
        this._container.style.transform = 'translateX(-50%)'
        break
      case 'topRight':
        this._container.style.top = margin.top + 'px'
        this._container.style.right = margin.right + 'px'
        break
      case 'centerLeft':
        this._container.style.top = '50%'
        this._container.style.left = margin.left + 'px'
        this._container.style.transform = 'translateY(-50%)'
        break
      case 'center':
        this._container.style.top = '50%'
        this._container.style.left = '50%'
        this._container.style.transform = 'translate(-50%, -50%)'
        break
      case 'centerRight':
        this._container.style.top = '50%'
        this._container.style.right = margin.right + 'px'
        this._container.style.transform = 'translateY(-50%)'
        break
      case 'bottomLeft':
        this._container.style.bottom = margin.bottom + 'px'
        this._container.style.left = margin.left + 'px'
        break
      case 'bottomCenter':
        this._container.style.bottom = margin.bottom + 'px'
        this._container.style.left = '50%'
        this._container.style.transform = 'translateX(-50%)'
        break
      case 'bottomRight':
        this._container.style.bottom = margin.bottom + 'px'
        this._container.style.right = margin.right + 'px'
        break
      default:
        // Использовать topRight как по умолчанию
        this._container.style.top = margin.top + 'px'
        this._container.style.right = margin.right + 'px'
    }
  }

  /**
   * Эмиссия событий
   * @param {string} eventType - Тип события
   * @param {Object} data - Данные события
   * @protected
   */
  _emitEvent(eventType, data = {}) {
    const handlers = this._eventListeners.get(eventType)
    if (handlers && handlers.length > 0) {
      const eventData = {
        type: eventType,
        target: this,
        ...data
      }
      
      handlers.forEach(handler => {
        try {
          handler(eventData)
        } catch (error) {
          console.error(`ControlBase: ошибка в обработчике события ${eventType}:`, error)
        }
      })
    }
  }

  /**
   * Обработчик изменения опций
   * @param {string} key - Ключ опции
   * @param {*} newValue - Новое значение
   * @param {*} oldValue - Старое значение
   * @protected
   */
  _onOptionChange(key, newValue, oldValue) {
    switch (key) {
      case 'position':
      case 'margin':
      case 'zIndex':
        this._applyPositioning()
        break
      case 'visible':
        if (newValue) {
          this.show()
        } else {
          this.hide()
        }
        break
      case 'enabled':
        if (newValue) {
          this.enable()
        } else {
          this.disable()
        }
        break
    }
  }

  /**
   * Обработчик клика по карте
   * @param {Object} event - Событие клика
   * @protected
   */
  _onMapClick(event) {
    // Базовая реализация - может быть переопределена в наследниках
  }

  /**
   * Обработчик изменения размера карты
   * @param {Object} event - Событие изменения размера
   * @protected
   */
  _onResize(event) {
    // Переприменяем позиционирование при изменении размера
    this._applyPositioning()
  }

  /**
   * Обработчик уничтожения контрола
   * @protected
   */
  _onDestroy() {
    // Может быть переопределен в наследниках для очистки ресурсов
  }
}