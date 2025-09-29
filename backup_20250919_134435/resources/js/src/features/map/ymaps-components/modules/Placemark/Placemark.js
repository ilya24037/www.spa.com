/**
 * Placemark - Упрощенный модуль для работы с метками
 * Адаптированный для использования в SPA Platform
 * 
 * @version 1.0.0
 * @author SPA Platform
 */

export default class Placemark {
  /**
   * Создает экземпляр метки
   * @param {Array} coordinates - Координаты [lat, lng]
   * @param {Object} properties - Свойства метки
   * @param {Object} options - Опции отображения
   */
  constructor(coordinates, properties = {}, options = {}) {
    this.coordinates = coordinates
    this.properties = properties
    this.options = {
      preset: 'islands#blueIcon',
      draggable: false,
      ...options
    }
    
    this.placemark = null
    this.map = null
    this.isAddedToMap = false
    
    // События
    this.events = new Map()
  }

  /**
   * Добавляет метку на карту
   * @param {Object} map - Экземпляр карты
   */
  async addToMap(map) {
    if (!window.ymaps) {
      throw new Error('Yandex Maps API не загружен')
    }

    this.map = map
    
    // Создаем метку
    this.placemark = new window.ymaps.Placemark(
      this.coordinates,
      this.properties,
      this.options
    )

    // Добавляем на карту
    map.geoObjects.add(this.placemark)
    this.isAddedToMap = true
    
    // Подключаем события
    this._setupEvents()
    
    return this.placemark
  }

  /**
   * Удаляет метку с карты
   */
  removeFromMap() {
    if (this.placemark && this.map && this.isAddedToMap) {
      this.map.geoObjects.remove(this.placemark)
      this.isAddedToMap = false
    }
  }

  /**
   * Устанавливает координаты метки
   * @param {Array} coordinates - Новые координаты [lat, lng]
   */
  setCoordinates(coordinates) {
    this.coordinates = coordinates
    if (this.placemark) {
      this.placemark.geometry.setCoordinates(coordinates)
    }
  }

  /**
   * Получает текущие координаты
   * @returns {Array} Координаты [lat, lng]
   */
  getCoordinates() {
    if (this.placemark) {
      return this.placemark.geometry.getCoordinates()
    }
    return this.coordinates
  }

  /**
   * Устанавливает свойства метки
   * @param {Object} properties - Новые свойства
   */
  setProperties(properties) {
    this.properties = { ...this.properties, ...properties }
    if (this.placemark) {
      Object.keys(properties).forEach(key => {
        this.placemark.properties.set(key, properties[key])
      })
    }
  }

  /**
   * Получает свойство метки
   * @param {string} key - Ключ свойства
   * @returns {*} Значение свойства
   */
  getProperty(key) {
    if (this.placemark) {
      return this.placemark.properties.get(key)
    }
    return this.properties[key]
  }

  /**
   * Устанавливает опции метки
   * @param {Object} options - Новые опции
   */
  setOptions(options) {
    this.options = { ...this.options, ...options }
    if (this.placemark) {
      Object.keys(options).forEach(key => {
        this.placemark.options.set(key, options[key])
      })
    }
  }

  /**
   * Получает опцию метки
   * @param {string} key - Ключ опции
   * @returns {*} Значение опции
   */
  getOption(key) {
    if (this.placemark) {
      return this.placemark.options.get(key)
    }
    return this.options[key]
  }

  /**
   * Открывает balloon
   * @param {string|Object} content - Содержимое balloon
   */
  openBalloon(content) {
    if (this.placemark) {
      if (typeof content === 'string') {
        this.placemark.properties.set('balloonContent', content)
      }
      this.placemark.balloon.open()
    }
  }

  /**
   * Закрывает balloon
   */
  closeBalloon() {
    if (this.placemark) {
      this.placemark.balloon.close()
    }
  }

  /**
   * Показывает hint
   * @param {string} content - Содержимое hint
   */
  showHint(content) {
    if (this.placemark) {
      if (content) {
        this.placemark.properties.set('hintContent', content)
      }
      // Hint показывается автоматически при наведении
    }
  }

  /**
   * Устанавливает видимость метки
   * @param {boolean} visible - Видимость
   */
  setVisible(visible) {
    if (this.placemark) {
      this.placemark.options.set('visible', visible)
    }
  }

  /**
   * Проверяет видимость метки
   * @returns {boolean} Видима ли метка
   */
  isVisible() {
    if (this.placemark) {
      return this.placemark.options.get('visible', true)
    }
    return true
  }

  /**
   * Устанавливает z-index метки
   * @param {number} zIndex - Z-index
   */
  setZIndex(zIndex) {
    if (this.placemark) {
      this.placemark.options.set('zIndex', zIndex)
    }
  }

  /**
   * Настройка событий метки
   * @private
   */
  _setupEvents() {
    if (!this.placemark) return

    // Проксируем основные события
    const eventNames = ['click', 'dblclick', 'mouseenter', 'mouseleave', 'dragend']
    
    eventNames.forEach(eventName => {
      this.placemark.events.add(eventName, (e) => {
        const handlers = this.events.get(eventName)
        if (handlers) {
          handlers.forEach(handler => {
            handler(e, this)
          })
        }
      })
    })
  }

  /**
   * Добавляет обработчик события
   * @param {string} eventName - Имя события
   * @param {Function} handler - Обработчик
   */
  on(eventName, handler) {
    if (!this.events.has(eventName)) {
      this.events.set(eventName, [])
    }
    this.events.get(eventName).push(handler)
  }

  /**
   * Удаляет обработчик события
   * @param {string} eventName - Имя события
   * @param {Function} handler - Обработчик
   */
  off(eventName, handler) {
    if (this.events.has(eventName)) {
      const handlers = this.events.get(eventName)
      const index = handlers.indexOf(handler)
      if (index > -1) {
        handlers.splice(index, 1)
      }
    }
  }

  /**
   * Получает экземпляр Yandex Placemark
   * @returns {Object} Экземпляр ymaps.Placemark
   */
  getYandexPlacemark() {
    return this.placemark
  }

  /**
   * Проверяет добавлена ли метка на карту
   * @returns {boolean}
   */
  isOnMap() {
    return this.isAddedToMap
  }

  /**
   * Получает карту на которой размещена метка
   * @returns {Object|null}
   */
  getMap() {
    return this.map
  }

  /**
   * Анимирует метку (простая анимация масштаба)
   */
  animate() {
    if (this.placemark) {
      // Простая анимация изменения иконки
      const originalPreset = this.getOption('preset')
      this.setOptions({ preset: 'islands#redDotIcon' })
      
      setTimeout(() => {
        this.setOptions({ preset: originalPreset })
      }, 500)
    }
  }

  /**
   * Уничтожает метку
   */
  destroy() {
    this.removeFromMap()
    this.events.clear()
    this.placemark = null
    this.map = null
  }

  /**
   * Статический метод для создания множественных меток
   * @param {Array} markersData - Массив данных для меток
   * @returns {Array} Массив экземпляров Placemark
   */
  static createMultiple(markersData) {
    return markersData.map(data => {
      return new Placemark(
        data.coordinates,
        data.properties || {},
        data.options || {}
      )
    })
  }

  /**
   * Статический метод для добавления множественных меток на карту
   * @param {Array} placemarks - Массив меток
   * @param {Object} map - Карта
   */
  static async addMultipleToMap(placemarks, map) {
    const promises = placemarks.map(placemark => placemark.addToMap(map))
    return await Promise.all(promises)
  }
}