/**
 * Placemark - Модуль меток для Yandex Maps
 * 
 * @module Placemark
 * @version 1.0.0
 * @author SPA Platform Team
 * 
 * Класс для создания и управления метками на карте.
 * Поддерживает различные виды меток, кастомные иконки, анимации и интерактивность.
 * Следует принципам KISS, SOLID и DRY из CLAUDE.md
 */

import YMapsCore from '../../core/YMapsCore.js'
import Balloon from '../Balloon/Balloon.js'

/**
 * Опции по умолчанию для метки
 * @constant {Object}
 */
const DEFAULT_OPTIONS = {
  // Визуальные настройки
  preset: 'islands#blueIcon',          // Предустановленный стиль
  iconLayout: 'default#image',         // Макет иконки
  iconImageHref: null,                  // URL кастомной иконки
  iconImageSize: [30, 42],              // Размер иконки [width, height]
  iconImageOffset: [-15, -42],          // Смещение иконки
  iconContentOffset: [0, 0],           // Смещение контента иконки
  iconShape: null,                      // Форма области клика
  
  // Цвета и стили
  iconColor: null,                      // Цвет иконки
  iconCaption: null,                    // Подпись под меткой
  iconCaptionMaxWidth: 120,            // Максимальная ширина подписи
  
  // Интерактивность
  cursor: 'pointer',                    // Курсор при наведении
  draggable: false,                     // Возможность перетаскивания
  hideIconOnBalloonOpen: false,        // Скрывать иконку при открытии balloon
  openBalloonOnClick: true,            // Открывать balloon по клику
  openEmptyBalloon: false,             // Открывать пустой balloon
  interactivityModel: 'default#geoObject', // Модель интерактивности
  
  // Состояние и видимость
  visible: true,                        // Видимость метки
  zIndex: 0,                           // Z-индекс
  zIndexHover: 0,                      // Z-индекс при наведении
  zIndexDrag: 0,                       // Z-индекс при перетаскивании
  
  // Balloon настройки
  balloonContent: null,                // Содержимое balloon
  balloonContentHeader: null,          // Заголовок balloon
  balloonContentBody: null,            // Тело balloon
  balloonContentFooter: null,          // Футер balloon
  balloonAutoPan: true,                // Автопанорамирование balloon
  balloonMaxWidth: 300,                // Максимальная ширина balloon
  balloonMaxHeight: 350,               // Максимальная высота balloon
  balloonCloseButton: true,            // Кнопка закрытия balloon
  
  // Hint настройки (подсказка при наведении)
  hintContent: null,                   // Содержимое подсказки
  hintLayout: null,                    // Макет подсказки
  hintHideTimeout: 700,                // Задержка скрытия подсказки
  
  // Анимация
  animationDuration: 300,              // Длительность анимации (мс)
  animateOnAdd: false,                 // Анимировать при добавлении
  animateOnRemove: false,              // Анимировать при удалении
  
  // Данные
  properties: {},                       // Пользовательские свойства
  data: null                           // Пользовательские данные
}

/**
 * Предустановленные стили меток
 * @constant {Object}
 */
const PRESET_STYLES = {
  // Острова (islands) - основная тема
  'islands#blueIcon': { color: '#0095b6' },
  'islands#redIcon': { color: '#ed4543' },
  'islands#darkOrangeIcon': { color: '#d84d00' },
  'islands#nightIcon': { color: '#0e4779' },
  'islands#darkBlueIcon': { color: '#177bc9' },
  'islands#pinkIcon': { color: '#f371d1' },
  'islands#grayIcon': { color: '#b3b3b3' },
  'islands#brownIcon': { color: '#793d0e' },
  'islands#darkGreenIcon': { color: '#1bad03' },
  'islands#violetIcon': { color: '#b51eff' },
  'islands#blackIcon': { color: '#595959' },
  'islands#yellowIcon': { color: '#ffd21e' },
  'islands#greenIcon': { color: '#56db40' },
  'islands#orangeIcon': { color: '#ff931e' },
  'islands#lightBlueIcon': { color: '#82cdff' },
  'islands#oliveIcon': { color: '#97a100' },
  
  // С точкой (Dot)
  'islands#blueDotIcon': { color: '#0095b6', shape: 'dot' },
  'islands#redDotIcon': { color: '#ed4543', shape: 'dot' },
  'islands#greenDotIcon': { color: '#56db40', shape: 'dot' },
  
  // Круглые (Circle)
  'islands#blueCircleIcon': { color: '#0095b6', shape: 'circle' },
  'islands#redCircleIcon': { color: '#ed4543', shape: 'circle' },
  'islands#greenCircleIcon': { color: '#56db40', shape: 'circle' },
  
  // Растягивающиеся (Stretch)
  'islands#blueStretchyIcon': { color: '#0095b6', shape: 'stretch' },
  'islands#redStretchyIcon': { color: '#ed4543', shape: 'stretch' },
  'islands#greenStretchyIcon': { color: '#56db40', shape: 'stretch' }
}

/**
 * Класс метки на карте
 * @class
 */
class Placemark {
  /**
   * Конструктор
   * @param {Object} map - экземпляр карты Yandex
   * @param {Array} position - координаты метки [lat, lng]
   * @param {Object} options - опции метки
   */
  constructor(map, position, options = {}) {
    // Валидация входных параметров (принцип Security by default)
    if (!map) {
      throw new Error('Карта не передана в конструктор Placemark')
    }
    
    if (!this._validatePosition(position)) {
      throw new Error('Некорректная позиция для Placemark')
    }
    
    // Сохраняем ссылки
    this._map = map
    this._position = this._normalizePosition(position)
    this._options = { ...DEFAULT_OPTIONS, ...options }
    
    // Внутреннее состояние
    this._placemarkInstance = null
    this._balloon = null
    this._isAdded = false
    this._isDragging = false
    this._listeners = []
    this._animations = new Map()
    
    // Пользовательские данные
    this._userData = this._options.data || null
    this._properties = this._options.properties || {}
    
    // Bind методов для правильного контекста
    this.setPosition = this.setPosition.bind(this)
    this.getPosition = this.getPosition.bind(this)
    this.setIcon = this.setIcon.bind(this)
    this.setBalloonContent = this.setBalloonContent.bind(this)
    this.openBalloon = this.openBalloon.bind(this)
    this.closeBalloon = this.closeBalloon.bind(this)
    
    // Инициализация
    this._init()
  }
  
  /**
   * Инициализация метки
   * @private
   */
  _init() {
    try {
      // Проверяем доступность ymaps
      const ymaps = window.ymaps || window.YMaps
      if (!ymaps) {
        throw new Error('Yandex Maps API не загружено')
      }
      
      // Подготавливаем геометрию и свойства
      const geometry = {
        type: 'Point',
        coordinates: this._position
      }
      
      const properties = this._prepareProperties()
      const options = this._prepareOptions()
      
      // Создаем экземпляр Placemark через API
      this._placemarkInstance = new ymaps.Placemark(geometry, properties, options)
      
      // Настраиваем обработчики событий
      this._setupEventListeners()
      
      // Создаем balloon если нужно
      if (this._options.balloonContent || 
          this._options.balloonContentHeader || 
          this._options.balloonContentBody) {
        this._setupBalloon()
      }
      
      console.log('✅ Placemark инициализирован')
      
    } catch (error) {
      console.error('❌ Ошибка инициализации Placemark:', error)
      throw error
    }
  }
  
  /**
   * Добавляет метку на карту
   * @returns {Placemark} this для цепочки вызовов
   */
  addToMap() {
    if (this._isAdded) {
      console.warn('Метка уже добавлена на карту')
      return this
    }
    
    try {
      // Добавляем на карту
      this._map.geoObjects.add(this._placemarkInstance)
      this._isAdded = true
      
      // Анимация появления если включена
      if (this._options.animateOnAdd) {
        this._animateAdd()
      }
      
      // Генерируем событие
      this._emit('add')
      
      console.log('✅ Placemark добавлен на карту')
      
    } catch (error) {
      console.error('❌ Ошибка добавления Placemark на карту:', error)
    }
    
    return this
  }
  
  /**
   * Удаляет метку с карты
   * @returns {Placemark} this для цепочки вызовов
   */
  removeFromMap() {
    if (!this._isAdded) {
      console.warn('Метка не добавлена на карту')
      return this
    }
    
    try {
      // Закрываем balloon если открыт
      this.closeBalloon()
      
      // Анимация удаления если включена
      if (this._options.animateOnRemove) {
        this._animateRemove(() => {
          this._performRemove()
        })
      } else {
        this._performRemove()
      }
      
    } catch (error) {
      console.error('❌ Ошибка удаления Placemark с карты:', error)
    }
    
    return this
  }
  
  /**
   * Выполняет удаление с карты
   * @private
   */
  _performRemove() {
    this._map.geoObjects.remove(this._placemarkInstance)
    this._isAdded = false
    
    // Генерируем событие
    this._emit('remove')
    
    console.log('✅ Placemark удален с карты')
  }
  
  /**
   * Устанавливает позицию метки
   * @param {Array|Object} position - новая позиция
   * @returns {Placemark} this для цепочки вызовов
   */
  setPosition(position) {
    const validPosition = this._normalizePosition(position)
    if (!validPosition) {
      console.error('Некорректная позиция для Placemark')
      return this
    }
    
    this._position = validPosition
    
    // Обновляем геометрию
    if (this._placemarkInstance) {
      this._placemarkInstance.geometry.setCoordinates(validPosition)
      
      // Генерируем событие
      this._emit('positionchange', {
        oldPosition: this._position,
        newPosition: validPosition
      })
    }
    
    return this
  }
  
  /**
   * Получает текущую позицию метки
   * @returns {Array} координаты [lat, lng]
   */
  getPosition() {
    if (this._placemarkInstance) {
      return this._placemarkInstance.geometry.getCoordinates()
    }
    return this._position
  }
  
  /**
   * Устанавливает иконку метки
   * @param {string|Object} icon - preset имя или объект с опциями
   * @returns {Placemark} this для цепочки вызовов
   */
  setIcon(icon) {
    if (!this._placemarkInstance) return this
    
    try {
      if (typeof icon === 'string') {
        // Если передан preset
        this._placemarkInstance.options.set('preset', icon)
        
        // Обновляем цвет если есть в пресете
        const presetStyle = PRESET_STYLES[icon]
        if (presetStyle && presetStyle.color) {
          this._placemarkInstance.options.set('iconColor', presetStyle.color)
        }
        
      } else if (typeof icon === 'object') {
        // Если передан объект с опциями
        if (icon.href || icon.url) {
          // Кастомная иконка
          this._placemarkInstance.options.set({
            iconLayout: 'default#image',
            iconImageHref: icon.href || icon.url,
            iconImageSize: icon.size || [30, 42],
            iconImageOffset: icon.offset || [-15, -42]
          })
        }
        
        if (icon.color) {
          this._placemarkInstance.options.set('iconColor', icon.color)
        }
        
        if (icon.glyph) {
          this._placemarkInstance.options.set('iconGlyph', icon.glyph)
        }
        
        if (icon.caption) {
          this._placemarkInstance.properties.set('iconCaption', icon.caption)
        }
      }
      
      // Генерируем событие
      this._emit('iconchange', { icon })
      
    } catch (error) {
      console.error('❌ Ошибка установки иконки:', error)
    }
    
    return this
  }
  
  /**
   * Устанавливает содержимое balloon
   * @param {string|Object} content - содержимое
   * @returns {Placemark} this для цепочки вызовов
   */
  setBalloonContent(content) {
    if (!this._placemarkInstance) return this
    
    try {
      if (typeof content === 'string') {
        // Простое текстовое содержимое
        this._placemarkInstance.properties.set('balloonContent', content)
        
      } else if (typeof content === 'object') {
        // Структурированное содержимое
        if (content.header) {
          this._placemarkInstance.properties.set('balloonContentHeader', content.header)
        }
        if (content.body) {
          this._placemarkInstance.properties.set('balloonContentBody', content.body)
        }
        if (content.footer) {
          this._placemarkInstance.properties.set('balloonContentFooter', content.footer)
        }
      }
      
      // Обновляем в balloon если он есть
      if (this._balloon) {
        this._balloon.setContent(content)
      }
      
    } catch (error) {
      console.error('❌ Ошибка установки содержимого balloon:', error)
    }
    
    return this
  }
  
  /**
   * Устанавливает содержимое подсказки (hint)
   * @param {string} content - содержимое подсказки
   * @returns {Placemark} this для цепочки вызовов
   */
  setHintContent(content) {
    if (!this._placemarkInstance) return this
    
    this._placemarkInstance.properties.set('hintContent', content)
    
    return this
  }
  
  /**
   * Открывает balloon метки
   * @param {Object} options - дополнительные опции
   * @returns {Promise}
   */
  async openBalloon(options = {}) {
    if (!this._placemarkInstance || !this._isAdded) {
      console.warn('Невозможно открыть balloon: метка не на карте')
      return Promise.reject()
    }
    
    try {
      // Если есть кастомный balloon
      if (this._balloon) {
        return this._balloon.open(
          this.getPosition(),
          this._placemarkInstance.properties.get('balloonContent'),
          options
        )
      }
      
      // Используем встроенный balloon
      return this._placemarkInstance.balloon.open()
      
    } catch (error) {
      console.error('❌ Ошибка открытия balloon:', error)
      return Promise.reject(error)
    }
  }
  
  /**
   * Закрывает balloon метки
   * @returns {Promise}
   */
  async closeBalloon() {
    if (!this._placemarkInstance) {
      return Promise.resolve()
    }
    
    try {
      // Если есть кастомный balloon
      if (this._balloon && this._balloon.isOpen()) {
        return this._balloon.close()
      }
      
      // Закрываем встроенный balloon
      return this._placemarkInstance.balloon.close()
      
    } catch (error) {
      console.error('❌ Ошибка закрытия balloon:', error)
      return Promise.reject(error)
    }
  }
  
  /**
   * Проверяет, открыт ли balloon
   * @returns {boolean}
   */
  isBalloonOpen() {
    if (this._balloon) {
      return this._balloon.isOpen()
    }
    
    if (this._placemarkInstance) {
      return this._placemarkInstance.balloon.isOpen()
    }
    
    return false
  }
  
  /**
   * Показывает метку
   * @returns {Placemark} this для цепочки вызовов
   */
  show() {
    if (this._placemarkInstance) {
      this._placemarkInstance.options.set('visible', true)
      this._emit('show')
    }
    return this
  }
  
  /**
   * Скрывает метку
   * @returns {Placemark} this для цепочки вызовов
   */
  hide() {
    if (this._placemarkInstance) {
      this._placemarkInstance.options.set('visible', false)
      this._emit('hide')
    }
    return this
  }
  
  /**
   * Проверяет видимость метки
   * @returns {boolean}
   */
  isVisible() {
    if (this._placemarkInstance) {
      return this._placemarkInstance.options.get('visible')
    }
    return false
  }
  
  /**
   * Включает перетаскивание метки
   * @returns {Placemark} this для цепочки вызовов
   */
  enableDragging() {
    if (this._placemarkInstance) {
      this._placemarkInstance.options.set('draggable', true)
      this._setupDragListeners()
    }
    return this
  }
  
  /**
   * Отключает перетаскивание метки
   * @returns {Placemark} this для цепочки вызовов
   */
  disableDragging() {
    if (this._placemarkInstance) {
      this._placemarkInstance.options.set('draggable', false)
      this._removeDragListeners()
    }
    return this
  }
  
  /**
   * Проверяет, включено ли перетаскивание
   * @returns {boolean}
   */
  isDraggable() {
    if (this._placemarkInstance) {
      return this._placemarkInstance.options.get('draggable')
    }
    return false
  }
  
  /**
   * Устанавливает пользовательские данные
   * @param {*} data - любые данные
   * @returns {Placemark} this для цепочки вызовов
   */
  setData(data) {
    this._userData = data
    return this
  }
  
  /**
   * Получает пользовательские данные
   * @returns {*}
   */
  getData() {
    return this._userData
  }
  
  /**
   * Устанавливает свойство
   * @param {string} key - ключ свойства
   * @param {*} value - значение
   * @returns {Placemark} this для цепочки вызовов
   */
  setProperty(key, value) {
    this._properties[key] = value
    
    if (this._placemarkInstance) {
      this._placemarkInstance.properties.set(key, value)
    }
    
    return this
  }
  
  /**
   * Получает свойство
   * @param {string} key - ключ свойства
   * @returns {*}
   */
  getProperty(key) {
    if (this._placemarkInstance) {
      return this._placemarkInstance.properties.get(key)
    }
    return this._properties[key]
  }
  
  /**
   * Устанавливает несколько свойств
   * @param {Object} properties - объект со свойствами
   * @returns {Placemark} this для цепочки вызовов
   */
  setProperties(properties) {
    Object.assign(this._properties, properties)
    
    if (this._placemarkInstance) {
      Object.keys(properties).forEach(key => {
        this._placemarkInstance.properties.set(key, properties[key])
      })
    }
    
    return this
  }
  
  /**
   * Получает все свойства
   * @returns {Object}
   */
  getProperties() {
    if (this._placemarkInstance) {
      return this._placemarkInstance.properties.getAll()
    }
    return { ...this._properties }
  }
  
  /**
   * Устанавливает опции
   * @param {Object} options - новые опции
   * @returns {Placemark} this для цепочки вызовов
   */
  setOptions(options) {
    Object.assign(this._options, options)
    
    if (this._placemarkInstance) {
      this._placemarkInstance.options.set(options)
    }
    
    return this
  }
  
  /**
   * Получает опции
   * @returns {Object}
   */
  getOptions() {
    return { ...this._options }
  }
  
  /**
   * Получает экземпляр Yandex Placemark
   * @returns {Object|null}
   */
  getPlacemarkInstance() {
    return this._placemarkInstance
  }
  
  /**
   * Добавляет обработчик события
   * @param {string} event - название события
   * @param {Function} handler - обработчик
   * @returns {Placemark} this для цепочки вызовов
   */
  on(event, handler) {
    if (!this._placemarkInstance) return this
    
    this._placemarkInstance.events.add(event, handler)
    this._listeners.push({ event, handler })
    
    return this
  }
  
  /**
   * Удаляет обработчик события
   * @param {string} event - название события
   * @param {Function} handler - обработчик
   * @returns {Placemark} this для цепочки вызовов
   */
  off(event, handler) {
    if (!this._placemarkInstance) return this
    
    this._placemarkInstance.events.remove(event, handler)
    
    this._listeners = this._listeners.filter(
      l => l.event !== event || l.handler !== handler
    )
    
    return this
  }
  
  /**
   * Уничтожает экземпляр и освобождает ресурсы
   */
  destroy() {
    try {
      // Удаляем с карты
      if (this._isAdded) {
        this.removeFromMap()
      }
      
      // Уничтожаем balloon
      if (this._balloon) {
        this._balloon.destroy()
        this._balloon = null
      }
      
      // Очищаем обработчики
      this._clearEventListeners()
      
      // Очищаем анимации
      this._animations.forEach(animation => clearTimeout(animation))
      this._animations.clear()
      
      // Очищаем ссылки
      this._placemarkInstance = null
      this._map = null
      this._position = null
      this._options = null
      this._userData = null
      this._properties = null
      
      console.log('✅ Placemark уничтожен')
      
    } catch (error) {
      console.error('❌ Ошибка уничтожения Placemark:', error)
    }
  }
  
  // =====================
  // Приватные методы
  // =====================
  
  /**
   * Валидация позиции
   * @private
   */
  _validatePosition(position) {
    if (!position) return false
    
    if (Array.isArray(position)) {
      return position.length === 2 && 
             typeof position[0] === 'number' && 
             typeof position[1] === 'number'
    }
    
    if (typeof position === 'object') {
      return (position.lat && position.lng) || 
             (position.latitude && position.longitude)
    }
    
    return false
  }
  
  /**
   * Нормализация позиции к массиву
   * @private
   */
  _normalizePosition(position) {
    if (!this._validatePosition(position)) return null
    
    if (Array.isArray(position)) {
      return position
    }
    
    if (position.lat && position.lng) {
      return [position.lat, position.lng]
    }
    
    if (position.latitude && position.longitude) {
      return [position.latitude, position.longitude]
    }
    
    return null
  }
  
  /**
   * Подготовка свойств для API
   * @private
   */
  _prepareProperties() {
    const properties = {
      ...this._properties
    }
    
    // Balloon контент
    if (this._options.balloonContent) {
      properties.balloonContent = this._options.balloonContent
    }
    if (this._options.balloonContentHeader) {
      properties.balloonContentHeader = this._options.balloonContentHeader
    }
    if (this._options.balloonContentBody) {
      properties.balloonContentBody = this._options.balloonContentBody
    }
    if (this._options.balloonContentFooter) {
      properties.balloonContentFooter = this._options.balloonContentFooter
    }
    
    // Hint контент
    if (this._options.hintContent) {
      properties.hintContent = this._options.hintContent
    }
    
    // Подпись иконки
    if (this._options.iconCaption) {
      properties.iconCaption = this._options.iconCaption
    }
    
    return properties
  }
  
  /**
   * Подготовка опций для API
   * @private
   */
  _prepareOptions() {
    const options = {}
    
    // Копируем поддерживаемые опции
    const supportedOptions = [
      'preset', 'iconLayout', 'iconImageHref', 'iconImageSize',
      'iconImageOffset', 'iconContentOffset', 'iconShape',
      'iconColor', 'iconCaptionMaxWidth', 'cursor', 'draggable',
      'hideIconOnBalloonOpen', 'openBalloonOnClick', 'openEmptyBalloon',
      'interactivityModel', 'visible', 'zIndex', 'zIndexHover',
      'zIndexDrag', 'balloonAutoPan', 'balloonMaxWidth', 'balloonMaxHeight',
      'balloonCloseButton', 'hintLayout', 'hintHideTimeout'
    ]
    
    supportedOptions.forEach(key => {
      if (key in this._options && this._options[key] !== null) {
        options[key] = this._options[key]
      }
    })
    
    return options
  }
  
  /**
   * Настройка обработчиков событий
   * @private
   */
  _setupEventListeners() {
    if (!this._placemarkInstance) return
    
    // Клик по метке
    this._placemarkInstance.events.add('click', (e) => {
      this._emit('click', { event: e })
      
      // Открываем balloon если нужно
      if (this._options.openBalloonOnClick && !this.isBalloonOpen()) {
        this.openBalloon()
      }
    })
    
    // Двойной клик
    this._placemarkInstance.events.add('dblclick', (e) => {
      this._emit('dblclick', { event: e })
    })
    
    // Наведение мыши
    this._placemarkInstance.events.add('mouseenter', (e) => {
      this._emit('mouseenter', { event: e })
    })
    
    // Уход мыши
    this._placemarkInstance.events.add('mouseleave', (e) => {
      this._emit('mouseleave', { event: e })
    })
    
    // Контекстное меню
    this._placemarkInstance.events.add('contextmenu', (e) => {
      this._emit('contextmenu', { event: e })
    })
  }
  
  /**
   * Настройка обработчиков перетаскивания
   * @private
   */
  _setupDragListeners() {
    if (!this._placemarkInstance) return
    
    // Начало перетаскивания
    this._placemarkInstance.events.add('dragstart', (e) => {
      this._isDragging = true
      this._emit('dragstart', { event: e })
    })
    
    // Перетаскивание
    this._placemarkInstance.events.add('drag', (e) => {
      const newPosition = e.get('target').geometry.getCoordinates()
      this._position = newPosition
      this._emit('drag', { 
        event: e, 
        position: newPosition 
      })
    })
    
    // Конец перетаскивания
    this._placemarkInstance.events.add('dragend', (e) => {
      this._isDragging = false
      const finalPosition = e.get('target').geometry.getCoordinates()
      this._position = finalPosition
      this._emit('dragend', { 
        event: e, 
        position: finalPosition 
      })
    })
  }
  
  /**
   * Удаление обработчиков перетаскивания
   * @private
   */
  _removeDragListeners() {
    if (!this._placemarkInstance) return
    
    this._placemarkInstance.events.remove('dragstart')
    this._placemarkInstance.events.remove('drag')
    this._placemarkInstance.events.remove('dragend')
  }
  
  /**
   * Очистка всех обработчиков
   * @private
   */
  _clearEventListeners() {
    if (!this._placemarkInstance) return
    
    this._listeners.forEach(({ event, handler }) => {
      this._placemarkInstance.events.remove(event, handler)
    })
    
    this._listeners = []
  }
  
  /**
   * Настройка balloon
   * @private
   */
  _setupBalloon() {
    // Можно создать кастомный balloon
    if (this._options.customBalloon) {
      this._balloon = new Balloon(this._map, {
        closeButton: this._options.balloonCloseButton,
        autoPan: this._options.balloonAutoPan,
        maxWidth: this._options.balloonMaxWidth,
        maxHeight: this._options.balloonMaxHeight
      })
    }
  }
  
  /**
   * Анимация появления
   * @private
   */
  _animateAdd() {
    if (!this._placemarkInstance) return
    
    // Сохраняем оригинальные размеры
    const originalSize = this._placemarkInstance.options.get('iconImageSize')
    
    // Начинаем с маленького размера
    this._placemarkInstance.options.set('iconImageSize', [1, 1])
    
    // Анимируем увеличение
    let step = 0
    const steps = 10
    const duration = this._options.animationDuration
    const stepDuration = duration / steps
    
    const animate = () => {
      step++
      
      if (step <= steps) {
        const progress = step / steps
        const size = [
          originalSize[0] * progress,
          originalSize[1] * progress
        ]
        
        this._placemarkInstance.options.set('iconImageSize', size)
        
        const timeoutId = setTimeout(animate, stepDuration)
        this._animations.set('add', timeoutId)
      } else {
        this._animations.delete('add')
      }
    }
    
    animate()
  }
  
  /**
   * Анимация удаления
   * @private
   */
  _animateRemove(callback) {
    if (!this._placemarkInstance) {
      callback()
      return
    }
    
    // Сохраняем оригинальные размеры
    const originalSize = this._placemarkInstance.options.get('iconImageSize')
    
    // Анимируем уменьшение
    let step = 10
    const steps = 10
    const duration = this._options.animationDuration
    const stepDuration = duration / steps
    
    const animate = () => {
      step--
      
      if (step >= 0) {
        const progress = step / steps
        const size = [
          originalSize[0] * progress,
          originalSize[1] * progress
        ]
        
        this._placemarkInstance.options.set('iconImageSize', size)
        
        const timeoutId = setTimeout(animate, stepDuration)
        this._animations.set('remove', timeoutId)
      } else {
        this._animations.delete('remove')
        callback()
      }
    }
    
    animate()
  }
  
  /**
   * Генерация события
   * @private
   */
  _emit(eventName, data = {}) {
    // Можно добавить EventEmitter или систему callbacks
    const callbackName = `on${eventName}`
    if (typeof this._options[callbackName] === 'function') {
      this._options[callbackName]({
        type: eventName,
        target: this,
        ...data
      })
    }
  }
}

// =====================
// Вспомогательные функции
// =====================

/**
 * Создает метку с предустановленным стилем
 * @param {Object} map - карта
 * @param {Array} position - позиция
 * @param {string} preset - имя пресета
 * @param {Object} options - дополнительные опции
 * @returns {Placemark}
 */
Placemark.createWithPreset = function(map, position, preset, options = {}) {
  return new Placemark(map, position, {
    preset,
    ...options
  })
}

/**
 * Создает метку с кастомной иконкой
 * @param {Object} map - карта
 * @param {Array} position - позиция
 * @param {string} iconUrl - URL иконки
 * @param {Object} options - дополнительные опции
 * @returns {Placemark}
 */
Placemark.createWithIcon = function(map, position, iconUrl, options = {}) {
  return new Placemark(map, position, {
    preset: null,
    iconLayout: 'default#image',
    iconImageHref: iconUrl,
    ...options
  })
}

/**
 * Создает перетаскиваемую метку
 * @param {Object} map - карта
 * @param {Array} position - позиция
 * @param {Object} options - дополнительные опции
 * @returns {Placemark}
 */
Placemark.createDraggable = function(map, position, options = {}) {
  return new Placemark(map, position, {
    draggable: true,
    cursor: 'move',
    ...options
  })
}

// Экспорт класса и констант
export { Placemark as default, PRESET_STYLES, DEFAULT_OPTIONS }

// Для использования без ES6 модулей
if (typeof module !== 'undefined' && module.exports) {
  module.exports = Placemark
  module.exports.PRESET_STYLES = PRESET_STYLES
  module.exports.DEFAULT_OPTIONS = DEFAULT_OPTIONS
}

// Для использования в браузере
if (typeof window !== 'undefined') {
  window.YMapsPlacemark = Placemark
  window.YMapsPlacemarkPresets = PRESET_STYLES
}