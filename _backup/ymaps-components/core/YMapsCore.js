/**
 * YMapsCore - Базовое ядро для работы с Yandex Maps API
 * 
 * @module YMapsCore
 * @version 1.0.0
 * @author SPA Platform Team
 * 
 * Основной модуль для инициализации и управления картой Яндекс.
 * Обеспечивает загрузку API, создание экземпляра карты и базовые операции.
 */

/**
 * Конфигурация по умолчанию
 */
const DEFAULT_CONFIG = {
  apiKey: null,
  lang: 'ru_RU',
  version: '2.1.79',
  coordorder: 'latlong',
  mode: 'release',
  load: 'package.full',
  ns: 'ymaps'
}

/**
 * Опции карты по умолчанию
 */
const DEFAULT_MAP_OPTIONS = {
  center: [55.753994, 37.622093], // Москва
  zoom: 10,
  controls: ['zoomControl', 'fullscreenControl'],
  behaviors: ['default']
}

/**
 * Класс для работы с картами Яндекс
 * @class
 */
class YMapsCore {
  /**
   * Конструктор
   * @param {Object} config - конфигурация для инициализации
   */
  constructor(config = {}) {
    // Объединяем конфигурацию с дефолтной
    this.config = { ...DEFAULT_CONFIG, ...config }
    
    // Внутреннее состояние
    this._isLoaded = false
    this._isLoading = false
    this._loadPromise = null
    this._maps = new Map() // Хранилище созданных карт
    this._modules = new Map() // Загруженные модули
    
    // Bind методов для правильного контекста
    this.loadAPI = this.loadAPI.bind(this)
    this.createMap = this.createMap.bind(this)
    this.destroyMap = this.destroyMap.bind(this)
  }

  /**
   * Загружает Yandex Maps API
   * @returns {Promise} промис загрузки API
   */
  async loadAPI() {
    // Если уже загружено
    if (this._isLoaded) {
      return Promise.resolve(window[this.config.ns])
    }

    // Если в процессе загрузки
    if (this._isLoading) {
      return this._loadPromise
    }

    // Начинаем загрузку
    this._isLoading = true
    
    this._loadPromise = new Promise((resolve, reject) => {
      try {
        // Проверяем, может API уже загружено другим способом
        if (window[this.config.ns] && window[this.config.ns].ready) {
          window[this.config.ns].ready(() => {
            this._isLoaded = true
            this._isLoading = false
            console.log('✅ Yandex Maps API уже загружено')
            resolve(window[this.config.ns])
          })
          return
        }

        // Формируем URL для загрузки
        const apiUrl = this._buildAPIUrl()
        
        // Создаем script элемент
        const script = document.createElement('script')
        script.type = 'text/javascript'
        script.src = apiUrl
        script.async = true
        
        // Обработчики событий
        script.onload = () => {
          // Ждем готовности API
          window[this.config.ns].ready(() => {
            this._isLoaded = true
            this._isLoading = false
            console.log('✅ Yandex Maps API успешно загружено')
            resolve(window[this.config.ns])
          })
        }
        
        script.onerror = (error) => {
          this._isLoading = false
          console.error('❌ Ошибка загрузки Yandex Maps API:', error)
          reject(new Error('Не удалось загрузить Yandex Maps API'))
        }
        
        // Добавляем script в документ
        document.head.appendChild(script)
        
      } catch (error) {
        this._isLoading = false
        console.error('❌ Ошибка при инициализации загрузки API:', error)
        reject(error)
      }
    })
    
    return this._loadPromise
  }

  /**
   * Формирует URL для загрузки API
   * @private
   * @returns {string} URL для загрузки
   */
  _buildAPIUrl() {
    const params = []
    
    // Добавляем API ключ если есть
    if (this.config.apiKey) {
      params.push(`apikey=${this.config.apiKey}`)
    }
    
    // Язык
    params.push(`lang=${this.config.lang}`)
    
    // Порядок координат
    if (this.config.coordorder) {
      params.push(`coordorder=${this.config.coordorder}`)
    }
    
    // Режим загрузки
    if (this.config.load) {
      params.push(`load=${this.config.load}`)
    }
    
    // Режим (release/debug)
    if (this.config.mode) {
      params.push(`mode=${this.config.mode}`)
    }
    
    // Namespace
    if (this.config.ns !== 'ymaps') {
      params.push(`ns=${this.config.ns}`)
    }
    
    return `https://api-maps.yandex.ru/${this.config.version}/?${params.join('&')}`
  }

  /**
   * Создает экземпляр карты
   * @param {string|HTMLElement} container - контейнер для карты
   * @param {Object} options - опции карты
   * @returns {Promise<Object>} промис с экземпляром карты
   */
  async createMap(container, options = {}) {
    try {
      // Загружаем API если еще не загружено
      await this.loadAPI()
      
      // Получаем контейнер
      const containerElement = typeof container === 'string' 
        ? document.getElementById(container) 
        : container
        
      if (!containerElement) {
        throw new Error(`Контейнер для карты не найден: ${container}`)
      }
      
      // Проверяем что контейнер еще не используется
      const containerId = containerElement.id || this._generateId()
      if (this._maps.has(containerId)) {
        console.warn('⚠️ Карта в этом контейнере уже создана')
        return this._maps.get(containerId)
      }
      
      // Объединяем опции с дефолтными
      const mapOptions = { ...DEFAULT_MAP_OPTIONS, ...options }
      
      // Валидация опций
      this._validateMapOptions(mapOptions)
      
      // Создаем карту
      const ymaps = window[this.config.ns]
      const map = new ymaps.Map(containerElement, {
        center: mapOptions.center,
        zoom: mapOptions.zoom,
        controls: mapOptions.controls,
        behaviors: mapOptions.behaviors
      }, mapOptions.extra || {})
      
      // Сохраняем в хранилище
      this._maps.set(containerId, {
        id: containerId,
        map: map,
        container: containerElement,
        options: mapOptions,
        modules: new Set(),
        objects: new Map()
      })
      
      console.log(`✅ Карта создана в контейнере: ${containerId}`)
      
      return map
      
    } catch (error) {
      console.error('❌ Ошибка создания карты:', error)
      throw error
    }
  }

  /**
   * Уничтожает карту и освобождает ресурсы
   * @param {string|Object} mapOrId - карта или ID контейнера
   */
  destroyMap(mapOrId) {
    try {
      let mapData = null
      
      // Определяем что передано
      if (typeof mapOrId === 'string') {
        mapData = this._maps.get(mapOrId)
      } else {
        // Ищем по экземпляру карты
        for (const [id, data] of this._maps) {
          if (data.map === mapOrId) {
            mapData = data
            break
          }
        }
      }
      
      if (!mapData) {
        console.warn('⚠️ Карта для удаления не найдена')
        return
      }
      
      // Удаляем все объекты с карты
      if (mapData.objects.size > 0) {
        mapData.map.geoObjects.removeAll()
        mapData.objects.clear()
      }
      
      // Уничтожаем карту
      mapData.map.destroy()
      
      // Очищаем контейнер
      mapData.container.innerHTML = ''
      
      // Удаляем из хранилища
      this._maps.delete(mapData.id)
      
      console.log(`✅ Карта уничтожена: ${mapData.id}`)
      
    } catch (error) {
      console.error('❌ Ошибка уничтожения карты:', error)
    }
  }

  /**
   * Получает карту по ID контейнера
   * @param {string} containerId - ID контейнера
   * @returns {Object|null} экземпляр карты или null
   */
  getMap(containerId) {
    const mapData = this._maps.get(containerId)
    return mapData ? mapData.map : null
  }

  /**
   * Получает все созданные карты
   * @returns {Map} коллекция всех карт
   */
  getAllMaps() {
    const maps = new Map()
    for (const [id, data] of this._maps) {
      maps.set(id, data.map)
    }
    return maps
  }

  /**
   * Загружает дополнительный модуль
   * @param {string} moduleName - имя модуля
   * @returns {Promise} промис загрузки модуля
   */
  async loadModule(moduleName) {
    // Если модуль уже загружен
    if (this._modules.has(moduleName)) {
      return this._modules.get(moduleName)
    }
    
    // Загружаем API если еще не загружено
    await this.loadAPI()
    
    const ymaps = window[this.config.ns]
    
    return new Promise((resolve, reject) => {
      ymaps.modules.require([moduleName], (module) => {
        this._modules.set(moduleName, module)
        console.log(`✅ Модуль загружен: ${moduleName}`)
        resolve(module)
      }, (error) => {
        console.error(`❌ Ошибка загрузки модуля ${moduleName}:`, error)
        reject(error)
      })
    })
  }

  /**
   * Проверяет, загружен ли API
   * @returns {boolean}
   */
  isAPILoaded() {
    return this._isLoaded
  }

  /**
   * Получает глобальный объект ymaps
   * @returns {Object|null}
   */
  getYMaps() {
    return this._isLoaded ? window[this.config.ns] : null
  }

  /**
   * Валидация опций карты
   * @private
   * @param {Object} options - опции для проверки
   * @throws {Error} если опции некорректны
   */
  _validateMapOptions(options) {
    // Проверка координат центра
    if (!Array.isArray(options.center) || options.center.length !== 2) {
      throw new Error('Некорректные координаты центра карты')
    }
    
    if (typeof options.center[0] !== 'number' || typeof options.center[1] !== 'number') {
      throw new Error('Координаты должны быть числами')
    }
    
    // Проверка зума
    if (typeof options.zoom !== 'number' || options.zoom < 0 || options.zoom > 23) {
      throw new Error('Некорректное значение zoom (должно быть от 0 до 23)')
    }
    
    // Проверка контролов
    if (options.controls && !Array.isArray(options.controls)) {
      throw new Error('Controls должны быть массивом')
    }
    
    // Проверка behaviors
    if (options.behaviors && !Array.isArray(options.behaviors)) {
      throw new Error('Behaviors должны быть массивом')
    }
  }

  /**
   * Генерирует уникальный ID
   * @private
   * @returns {string}
   */
  _generateId() {
    return `ymap-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`
  }

  /**
   * Устанавливает центр карты
   * @param {string} mapId - ID контейнера карты
   * @param {Array} center - координаты центра [lat, lng]
   * @param {number} zoom - уровень зума (опционально)
   */
  setCenter(mapId, center, zoom = null) {
    const map = this.getMap(mapId)
    if (!map) {
      console.error(`Карта не найдена: ${mapId}`)
      return
    }
    
    if (zoom !== null) {
      map.setCenter(center, zoom)
    } else {
      map.setCenter(center)
    }
  }

  /**
   * Получает текущий центр карты
   * @param {string} mapId - ID контейнера карты
   * @returns {Array|null} координаты центра или null
   */
  getCenter(mapId) {
    const map = this.getMap(mapId)
    return map ? map.getCenter() : null
  }

  /**
   * Устанавливает зум карты
   * @param {string} mapId - ID контейнера карты
   * @param {number} zoom - уровень зума
   */
  setZoom(mapId, zoom) {
    const map = this.getMap(mapId)
    if (!map) {
      console.error(`Карта не найдена: ${mapId}`)
      return
    }
    
    map.setZoom(zoom)
  }

  /**
   * Получает текущий зум карты
   * @param {string} mapId - ID контейнера карты
   * @returns {number|null} уровень зума или null
   */
  getZoom(mapId) {
    const map = this.getMap(mapId)
    return map ? map.getZoom() : null
  }

  /**
   * Устанавливает границы карты
   * @param {string} mapId - ID контейнера карты
   * @param {Array} bounds - границы [[lat1, lng1], [lat2, lng2]]
   * @param {Object} options - дополнительные опции
   */
  setBounds(mapId, bounds, options = {}) {
    const map = this.getMap(mapId)
    if (!map) {
      console.error(`Карта не найдена: ${mapId}`)
      return
    }
    
    map.setBounds(bounds, options)
  }

  /**
   * Получает текущие границы карты
   * @param {string} mapId - ID контейнера карты
   * @returns {Array|null} границы или null
   */
  getBounds(mapId) {
    const map = this.getMap(mapId)
    return map ? map.getBounds() : null
  }
}

// Экспорт для использования
export default YMapsCore

// Для использования без ES6 модулей
if (typeof module !== 'undefined' && module.exports) {
  module.exports = YMapsCore
}

// Для использования в браузере
if (typeof window !== 'undefined') {
  window.YMapsCore = YMapsCore
}