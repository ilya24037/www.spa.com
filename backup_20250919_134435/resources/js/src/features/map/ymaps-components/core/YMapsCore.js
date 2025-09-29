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
  apiKey: '23ff8acc-835f-4e99-8b19-d33c5d346e18', // Используем ключ из проекта
  lang: 'ru_RU',
  version: '2.1.79',
  coordorder: 'latlong',
  mode: 'release',
  load: 'package.full',
  ns: 'ymaps'
}

/**
 * Опции анимации по умолчанию (очень плавно, как на Avito)
 */
const DEFAULT_ANIMATION_OPTIONS = {
  duration: 3600,
  timingFunction: 'ease-out'
}

/**
 * Опции карты по умолчанию (ПРОСТЫЕ как в "Карта феи")
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

        // API уже загружается через CDN в app.blade.php
        // Просто ждем готовности
        const checkAPI = () => {
          if (window[this.config.ns] && window[this.config.ns].ready) {
            window[this.config.ns].ready(() => {
              this._isLoaded = true
              this._isLoading = false
              console.log('✅ Yandex Maps API успешно загружено')
              resolve(window[this.config.ns])
            })
          } else {
            // Проверяем еще раз через 100мс
            setTimeout(checkAPI, 100)
          }
        }
        
        checkAPI()
        
      } catch (error) {
        this._isLoading = false
        console.error('❌ Ошибка при инициализации загрузки API:', error)
        reject(error)
      }
    })
    
    return this._loadPromise
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
   * Проверяет активность карты и возможность выполнения операций
   * @param {Object} map - экземпляр карты
   * @returns {boolean}
   */
  _isMapActive(map) {
    try {
      // Проверяем что карта существует и имеет базовые методы
      if (!map || typeof map.getState !== 'function') {
        return false
      }
      
      // Проверяем что карта не уничтожена (через приватное свойство)
      if (map._destroyed || map.destroyed) {
        return false
      }
      
      // Проверяем состояние через getState (безопасный метод)
      const state = map.getState()
      if (!state) {
        return false
      }
      
      return true
      
    } catch (error) {
      console.debug('🔍 [YMapsCore] Ошибка проверки активности карты:', error)
      return false
    }
  }

  /**
   * Безопасно отключает все behaviors карты
   * @param {Object} map - экземпляр карты
   */
  _disableMapBehaviors(map) {
    try {
      if (!this._isMapActive(map)) {
        console.debug('🔍 [YMapsCore] Карта неактивна - пропускаю отключение behaviors')
        return
      }
      
      console.log('🧹 [YMapsCore] Отключаю все behaviors карты...')
      
      // Отключаем все стандартные behaviors
      const behaviorsToDisable = [
        'drag', 'scrollZoom', 'dblClickZoom', 'multiTouch', 
        'rightMouseButtonMagnifier', 'leftMouseButtonMagnifier',
        'ruler', 'routeEditor'
      ]
      
      behaviorsToDisable.forEach(behavior => {
        try {
          if (map.behaviors && map.behaviors.get(behavior)) {
            map.behaviors.disable(behavior)
            console.log(`✅ [YMapsCore] Отключен behavior: ${behavior}`)
          }
        } catch (error) {
          console.debug(`🔍 [YMapsCore] Не удалось отключить behavior ${behavior}:`, error)
        }
      })
      
      // Дополнительно отключаем все behaviors разом (если метод доступен)
      try {
        if (map.behaviors && typeof map.behaviors.disable === 'function') {
          map.behaviors.disable('default')
        }
      } catch (error) {
        console.debug('🔍 [YMapsCore] Не удалось отключить default behaviors:', error)
      }
      
    } catch (error) {
      console.warn('⚠️ [YMapsCore] Ошибка при отключении behaviors:', error)
    }
  }

  /**
   * Безопасно удаляет все event listeners карты
   * @param {Object} map - экземпляр карты
   */
  _removeMapEventListeners(map) {
    try {
      if (!this._isMapActive(map)) {
        console.debug('🔍 [YMapsCore] Карта неактивна - пропускаю удаление listeners')
        return
      }
      
      console.log('🧹 [YMapsCore] Удаляю все event listeners карты...')
      
      // Удаляем все event listeners
      if (map.events && typeof map.events.removeAll === 'function') {
        map.events.removeAll()
        console.log('✅ [YMapsCore] Все event listeners удалены')
      }
      
    } catch (error) {
      console.warn('⚠️ [YMapsCore] Ошибка при удалении event listeners:', error)
    }
  }

  /**
   * Уничтожает карту и освобождает ресурсы (БЕЗОПАСНО С ПРОВЕРКОЙ АКТИВНОСТИ)
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
          if (data && data.map === mapOrId) {
            mapData = data
            break
          }
        }
      }
      
      if (!mapData) {
        // Карта уже удалена или не существует - это нормально при повторных вызовах
        console.debug('🔍 [YMapsCore] Карта для удаления не найдена (возможно уже удалена)')
        return
      }
      
      console.log(`🧹 [YMapsCore] Начинаю безопасное уничтожение карты: ${mapData.id}`)
      
      // КРИТИЧЕСКИ ВАЖНО: сначала проверяем активность карты
      if (!this._isMapActive(mapData.map)) {
        console.log('⚠️ [YMapsCore] Карта уже неактивна - пропускаю отключение behaviors и listeners')
      } else {
        // 1. ПЕРВЫМ ДЕЛОМ отключаем все behaviors (особенно drag)
        this._disableMapBehaviors(mapData.map)
        
        // 2. Удаляем все event listeners
        this._removeMapEventListeners(mapData.map)
        
        // 3. Небольшая пауза для завершения всех операций
        console.log('⏳ [YMapsCore] Ожидаю завершения операций...')
      }
      
      // 4. Безопасно удаляем все объекты с карты
      try {
        if (mapData.objects && mapData.objects.size > 0) {
          console.log(`🧹 [YMapsCore] Удаляю ${mapData.objects.size} объектов с карты...`)
          if (this._isMapActive(mapData.map) && mapData.map.geoObjects) {
            mapData.map.geoObjects.removeAll()
          }
          mapData.objects.clear()
        }
      } catch (error) {
        console.warn('⚠️ [YMapsCore] Ошибка при удалении объектов с карты:', error)
      }
      
      // 5. Безопасно уничтожаем карту (только если она еще активна)
      try {
        if (mapData.map) {
          if (this._isMapActive(mapData.map) && typeof mapData.map.destroy === 'function') {
            console.log('🧹 [YMapsCore] Карта активна - вызываю map.destroy()...')
            mapData.map.destroy()
          } else {
            console.log('🔍 [YMapsCore] Карта неактивна - пропускаю map.destroy()')
          }
        }
      } catch (error) {
        console.warn('⚠️ [YMapsCore] Ошибка при вызове map.destroy():', error)
      }
      
      // 6. Безопасно очищаем контейнер
      try {
        if (mapData.container) {
          console.log('🧹 [YMapsCore] Очищаю контейнер карты...')
          mapData.container.innerHTML = ''
        }
      } catch (error) {
        console.warn('⚠️ [YMapsCore] Ошибка при очистке контейнера:', error)
      }
      
      // 7. Удаляем из хранилища
      try {
        this._maps.delete(mapData.id)
        console.log(`✅ [YMapsCore] Карта безопасно уничтожена: ${mapData.id}`)
      } catch (error) {
        console.warn('⚠️ [YMapsCore] Ошибка при удалении из хранилища:', error)
      }
      
    } catch (error) {
      console.error('❌ [YMapsCore] Критическая ошибка уничтожения карты:', error)
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
   * Простое центрирование карты (как в "Карта феи")
   * @param {Object} map - экземпляр карты
   * @param {number[]} coordinates - координаты центра [lat, lng]
   * @param {number} [zoom] - уровень зума (опционально)
   * @returns {Promise} промис завершения
   */
  smoothCenterTo(map, coordinates, zoom = null) {
    try {
      // ПРОСТОЙ ПОДХОД как в "Карта феи"
      map.setCenter(coordinates, zoom || map.getZoom())
      return Promise.resolve()
    } catch (error) {
      console.warn('Ошибка setCenter:', error)
      return Promise.resolve()
    }
  }

  /**
   * Получает опции анимации по умолчанию
   * @returns {Object}
   */
  getDefaultAnimationOptions() {
    return { ...DEFAULT_ANIMATION_OPTIONS }
  }
}

// Экспорт для использования
export default YMapsCore

// Для использования в браузере
if (typeof window !== 'undefined') {
  window.YMapsCore = YMapsCore
}