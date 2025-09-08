/**
 * Модуль кластеризации меток для Yandex Maps
 * Группирует близко расположенные метки в кластеры для улучшения производительности
 * @module Clusterer
 * @version 1.0.0
 */

/**
 * Класс для кластеризации меток на карте
 * Автоматически группирует метки при изменении масштаба
 */
class Clusterer {
  /**
   * Создает экземпляр кластеризатора
   * @param {Object} map - Экземпляр карты Yandex Maps
   * @param {Object} [options={}] - Опции кластеризатора
   * @param {string} [options.preset='islands#blueClusterIcons'] - Preset стиль кластеров
   * @param {number} [options.gridSize=60] - Размер сетки кластеризации в пикселях
   * @param {number} [options.minClusterSize=2] - Минимальное количество меток для создания кластера
   * @param {number} [options.maxZoom=16] - Максимальный зум для кластеризации
   * @param {boolean} [options.hasBalloon=true] - Показывать balloon у кластеров
   * @param {boolean} [options.hasHint=true] - Показывать хинты у кластеров
   * @param {number} [options.zoomMargin=2] - Отступ при клике на кластер
   * @param {boolean} [options.showInAlphabeticalOrder=false] - Сортировка меток в balloon
   * @param {boolean} [options.useMapMargin=true] - Учитывать отступы карты
   * @param {Function} [options.createCluster] - Кастомная функция создания кластера
   * @param {Function} [options.createBalloonContent] - Кастомная функция для содержимого balloon
   * @param {Function} [options.createHintContent] - Кастомная функция для хинта
   */
  constructor(map, options = {}) {
    if (!map) {
      throw new Error('Map instance is required')
    }

    this.map = map
    this.options = this._mergeOptions(options)
    this.placemarks = []
    this.clusters = []
    this.clusterer = null
    this._isReady = false
    this._listeners = new Map()
    this._pendingOperations = []
    
    this._init()
  }

  /**
   * Объединяет опции с настройками по умолчанию
   * @private
   */
  _mergeOptions(options) {
    const defaults = {
      // Визуальные настройки
      preset: 'islands#blueClusterIcons',
      clusterIconContentLayout: null,
      clusterIconLayout: null,
      clusterDisableClickZoom: false,
      clusterHideIconOnBalloonOpen: false,
      clusterBalloonContentLayout: null,
      clusterBalloonPanelMaxMapArea: 700,
      clusterBalloonMaxHeight: 200,
      clusterBalloonPagerSize: 5,
      
      // Параметры кластеризации
      gridSize: 60,
      minClusterSize: 2,
      maxZoom: 16,
      zoomMargin: 2,
      margin: 10,
      
      // Поведение
      hasBalloon: true,
      hasHint: true,
      showInAlphabeticalOrder: false,
      useMapMargin: true,
      viewportMargin: 128,
      
      // Кастомные функции
      createCluster: null,
      createBalloonContent: null,
      createHintContent: null,
      calculateClusterIcon: null,
      
      // События
      onClusterAdd: null,
      onClusterRemove: null,
      onClusterClick: null
    }
    
    return { ...defaults, ...options }
  }

  /**
   * Инициализирует кластеризатор
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
      
      // Создаем опции для кластеризатора
      const clusterOptions = this._createClusterOptions()
      
      // Создаем экземпляр кластеризатора
      this.clusterer = new ymaps.Clusterer(clusterOptions)
      
      // Добавляем обработчики событий
      this._attachEventListeners()
      
      // Добавляем на карту
      this.map.geoObjects.add(this.clusterer)
      
      this._isReady = true
      
      // Выполняем отложенные операции
      await this._processPendingOperations()
      
    } catch (error) {
      console.error('Ошибка инициализации кластеризатора:', error)
      throw error
    }
  }

  /**
   * Ждет готовности карты
   * @private
   */
  _waitForMap() {
    return new Promise((resolve) => {
      if (this.map.geoObjects) {
        resolve()
      } else {
        const checkInterval = setInterval(() => {
          if (this.map.geoObjects) {
            clearInterval(checkInterval)
            resolve()
          }
        }, 100)
      }
    })
  }

  /**
   * Создает опции для кластеризатора
   * @private
   */
  _createClusterOptions() {
    const options = {
      preset: this.options.preset,
      gridSize: this.options.gridSize,
      minClusterSize: this.options.minClusterSize,
      maxZoom: this.options.maxZoom,
      zoomMargin: this.options.zoomMargin,
      margin: this.options.margin,
      hasBalloon: this.options.hasBalloon,
      hasHint: this.options.hasHint,
      showInAlphabeticalOrder: this.options.showInAlphabeticalOrder,
      useMapMargin: this.options.useMapMargin,
      viewportMargin: this.options.viewportMargin
    }

    // Кастомные макеты
    if (this.options.clusterIconContentLayout) {
      options.clusterIconContentLayout = this.options.clusterIconContentLayout
    }

    if (this.options.clusterIconLayout) {
      options.clusterIconLayout = this.options.clusterIconLayout
    }

    if (this.options.clusterBalloonContentLayout) {
      options.clusterBalloonContentLayout = this.options.clusterBalloonContentLayout
    }

    // Дополнительные опции
    if (this.options.clusterDisableClickZoom) {
      options.clusterDisableClickZoom = true
    }

    if (this.options.clusterHideIconOnBalloonOpen) {
      options.clusterHideIconOnBalloonOpen = true
    }

    // Кастомные функции для генерации контента
    if (this.options.createBalloonContent) {
      options.clusterBalloonContentLayoutWidth = 300
      options.clusterBalloonContentLayoutHeight = 200
    }

    return options
  }

  /**
   * Добавляет обработчики событий
   * @private
   */
  _attachEventListeners() {
    if (!this.clusterer || !this.clusterer.events) return

    // Добавление кластера
    this.clusterer.events.add('add', (e) => {
      const cluster = e.get('child')
      this._onClusterAdd(cluster)
    })

    // Удаление кластера
    this.clusterer.events.add('remove', (e) => {
      const cluster = e.get('child')
      this._onClusterRemove(cluster)
    })

    // Клик по кластеру
    this.clusterer.events.add('click', (e) => {
      const target = e.get('target')
      const isCluster = target.getGeoObjects !== undefined
      
      if (isCluster) {
        this._onClusterClick(target, e)
      }
    })

    // Изменение масштаба карты
    this.map.events.add('boundschange', () => {
      this._onBoundsChange()
    })
  }

  /**
   * Обработчик добавления кластера
   * @private
   */
  _onClusterAdd(cluster) {
    // Кастомизация иконки кластера
    if (this.options.calculateClusterIcon) {
      const geoObjects = cluster.getGeoObjects()
      const iconData = this.options.calculateClusterIcon(geoObjects)
      
      if (iconData) {
        cluster.options.set(iconData)
      }
    }

    // Кастомное содержимое balloon
    if (this.options.createBalloonContent) {
      const geoObjects = cluster.getGeoObjects()
      const content = this.options.createBalloonContent(geoObjects)
      
      cluster.properties.set('balloonContent', content)
    }

    // Кастомный хинт
    if (this.options.createHintContent) {
      const geoObjects = cluster.getGeoObjects()
      const hint = this.options.createHintContent(geoObjects)
      
      cluster.properties.set('hintContent', hint)
    }

    // Callback
    if (this.options.onClusterAdd) {
      this.options.onClusterAdd(cluster)
    }
  }

  /**
   * Обработчик удаления кластера
   * @private
   */
  _onClusterRemove(cluster) {
    if (this.options.onClusterRemove) {
      this.options.onClusterRemove(cluster)
    }
  }

  /**
   * Обработчик клика по кластеру
   * @private
   */
  _onClusterClick(cluster, event) {
    // Предотвращаем стандартное поведение если нужно
    if (this.options.clusterDisableClickZoom) {
      event.preventDefault()
    }

    // Callback
    if (this.options.onClusterClick) {
      this.options.onClusterClick(cluster, event)
    }

    // Кастомное поведение при клике
    if (this.options.clusterDisableClickZoom && cluster.getGeoObjects) {
      const geoObjects = cluster.getGeoObjects()
      
      // Если в кластере мало объектов, показываем их
      if (geoObjects.length <= 5) {
        this._showClusterObjects(cluster)
      } else {
        // Иначе зумим на кластер
        this._zoomToCluster(cluster)
      }
    }
  }

  /**
   * Обработчик изменения границ карты
   * @private
   */
  _onBoundsChange() {
    // Можно добавить логику для динамической подгрузки меток
    this._updateClustersVisibility()
  }

  /**
   * Обновляет видимость кластеров
   * @private
   */
  _updateClustersVisibility() {
    const zoom = this.map.getZoom()
    
    // Скрываем кластеры на большом зуме
    if (zoom > this.options.maxZoom && this.clusterer) {
      // Логика для отображения отдельных меток
    }
  }

  /**
   * Показывает объекты кластера
   * @private
   */
  _showClusterObjects(cluster) {
    const geoObjects = cluster.getGeoObjects()
    const bounds = cluster.getBounds()
    
    // Открываем balloon со списком объектов
    if (this.options.hasBalloon) {
      cluster.balloon.open()
    }
  }

  /**
   * Зумит на кластер
   * @private
   */
  async _zoomToCluster(cluster) {
    const bounds = cluster.getBounds()
    
    if (bounds) {
      await this.map.setBounds(bounds, {
        checkZoomRange: true,
        zoomMargin: this.options.zoomMargin
      })
    }
  }

  /**
   * Выполняет отложенные операции
   * @private
   */
  async _processPendingOperations() {
    for (const operation of this._pendingOperations) {
      await operation()
    }
    this._pendingOperations = []
  }

  /**
   * Добавляет метку в кластеризатор
   * @param {Object|Array} placemark - Метка или массив меток
   * @returns {Promise<void>}
   */
  async add(placemark) {
    if (!this._isReady) {
      this._pendingOperations.push(() => this.add(placemark))
      return
    }

    try {
      if (Array.isArray(placemark)) {
        // Добавляем массив меток
        this.clusterer.add(placemark)
        this.placemarks.push(...placemark)
      } else {
        // Добавляем одну метку
        this.clusterer.add(placemark)
        this.placemarks.push(placemark)
      }
    } catch (error) {
      console.error('Ошибка добавления метки в кластеризатор:', error)
      throw error
    }
  }

  /**
   * Удаляет метку из кластеризатора
   * @param {Object|Array} placemark - Метка или массив меток
   * @returns {Promise<void>}
   */
  async remove(placemark) {
    if (!this._isReady) {
      this._pendingOperations.push(() => this.remove(placemark))
      return
    }

    try {
      if (Array.isArray(placemark)) {
        // Удаляем массив меток
        this.clusterer.remove(placemark)
        placemark.forEach(pm => {
          const index = this.placemarks.indexOf(pm)
          if (index !== -1) {
            this.placemarks.splice(index, 1)
          }
        })
      } else {
        // Удаляем одну метку
        this.clusterer.remove(placemark)
        const index = this.placemarks.indexOf(placemark)
        if (index !== -1) {
          this.placemarks.splice(index, 1)
        }
      }
    } catch (error) {
      console.error('Ошибка удаления метки из кластеризатора:', error)
      throw error
    }
  }

  /**
   * Удаляет все метки из кластеризатора
   * @returns {Promise<void>}
   */
  async removeAll() {
    if (!this._isReady) {
      this._pendingOperations.push(() => this.removeAll())
      return
    }

    try {
      this.clusterer.removeAll()
      this.placemarks = []
    } catch (error) {
      console.error('Ошибка удаления всех меток:', error)
      throw error
    }
  }

  /**
   * Получает все метки
   * @returns {Array} Массив меток
   */
  getPlacemarks() {
    return [...this.placemarks]
  }

  /**
   * Получает количество меток
   * @returns {number}
   */
  getPlacemarksCount() {
    return this.placemarks.length
  }

  /**
   * Получает границы всех меток
   * @returns {Array|null} Границы [[minLat, minLng], [maxLat, maxLng]]
   */
  getBounds() {
    if (!this._isReady || !this.clusterer) {
      return null
    }

    try {
      return this.clusterer.getBounds()
    } catch (error) {
      console.error('Ошибка получения границ:', error)
      return null
    }
  }

  /**
   * Центрирует карту по всем меткам
   * @param {Object} [options={}] - Опции центрирования
   * @returns {Promise<void>}
   */
  async fitToViewport(options = {}) {
    const bounds = this.getBounds()
    
    if (bounds) {
      const fitOptions = {
        checkZoomRange: true,
        zoomMargin: this.options.zoomMargin,
        ...options
      }
      
      await this.map.setBounds(bounds, fitOptions)
    }
  }

  /**
   * Устанавливает новые опции
   * @param {Object} options - Новые опции
   */
  setOptions(options) {
    this.options = { ...this.options, ...options }
    
    if (this.clusterer) {
      // Применяем новые опции к кластеризатору
      Object.keys(options).forEach(key => {
        if (this.clusterer.options.get(key) !== undefined) {
          this.clusterer.options.set(key, options[key])
        }
      })
    }
  }

  /**
   * Получает опции кластеризатора
   * @returns {Object}
   */
  getOptions() {
    return { ...this.options }
  }

  /**
   * Устанавливает preset стиль
   * @param {string} preset - Название preset стиля
   */
  setPreset(preset) {
    this.setOptions({ preset })
  }

  /**
   * Устанавливает размер сетки кластеризации
   * @param {number} size - Размер в пикселях
   */
  setGridSize(size) {
    if (size < 10 || size > 300) {
      console.warn('Grid size should be between 10 and 300')
      return
    }
    
    this.setOptions({ gridSize: size })
    
    // Перестраиваем кластеры
    if (this._isReady) {
      this.refresh()
    }
  }

  /**
   * Устанавливает минимальный размер кластера
   * @param {number} size - Минимальное количество меток
   */
  setMinClusterSize(size) {
    if (size < 2 || size > 100) {
      console.warn('Min cluster size should be between 2 and 100')
      return
    }
    
    this.setOptions({ minClusterSize: size })
    
    // Перестраиваем кластеры
    if (this._isReady) {
      this.refresh()
    }
  }

  /**
   * Перестраивает кластеры
   */
  refresh() {
    if (!this._isReady || !this.clusterer) return
    
    // Сохраняем метки
    const placemarks = this.getPlacemarks()
    
    // Удаляем все
    this.removeAll()
    
    // Добавляем обратно
    if (placemarks.length > 0) {
      this.add(placemarks)
    }
  }

  /**
   * Включает balloon у кластеров
   */
  enableBalloon() {
    this.setOptions({ hasBalloon: true })
  }

  /**
   * Отключает balloon у кластеров
   */
  disableBalloon() {
    this.setOptions({ hasBalloon: false })
  }

  /**
   * Включает хинты у кластеров
   */
  enableHint() {
    this.setOptions({ hasHint: true })
  }

  /**
   * Отключает хинты у кластеров
   */
  disableHint() {
    this.setOptions({ hasHint: false })
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
    
    // Добавляем обработчик к кластеризатору если он готов
    if (this._isReady && this.clusterer && this.clusterer.events) {
      this.clusterer.events.add(event, handler)
    }
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
      
      // Удаляем обработчик из кластеризатора
      if (this._isReady && this.clusterer && this.clusterer.events) {
        this.clusterer.events.remove(event, handler)
      }
    } else {
      // Удаляем все обработчики события
      this._listeners.delete(event)
      
      if (this._isReady && this.clusterer && this.clusterer.events) {
        this.clusterer.events.remove(event)
      }
    }
  }

  /**
   * Проверяет готовность кластеризатора
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
      // Удаляем обработчики
      this._listeners.clear()
      
      // Удаляем с карты
      if (this.map && this.map.geoObjects && this.clusterer) {
        this.map.geoObjects.remove(this.clusterer)
      }
      
      // Очищаем кластеризатор
      if (this.clusterer) {
        this.clusterer.removeAll()
        this.clusterer = null
      }
      
      // Очищаем массивы
      this.placemarks = []
      this.clusters = []
      this._pendingOperations = []
      
      // Сбрасываем флаги
      this._isReady = false
      
      // Очищаем ссылки
      this.map = null
      this.options = null
      
    } catch (error) {
      console.error('Ошибка при уничтожении кластеризатора:', error)
    }
  }
}

/**
 * Фабричная функция для создания кластеризатора
 * @param {Object} map - Экземпляр карты
 * @param {Object} [options] - Опции
 * @returns {Clusterer}
 */
export function createClusterer(map, options) {
  return new Clusterer(map, options)
}

/**
 * Preset стили для кластеров
 */
export const CLUSTER_PRESETS = {
  BLUE: 'islands#blueClusterIcons',
  RED: 'islands#redClusterIcons',
  DARK_GREEN: 'islands#darkGreenClusterIcons',
  VIOLET: 'islands#violetClusterIcons',
  BLACK: 'islands#blackClusterIcons',
  GRAY: 'islands#grayClusterIcons',
  BROWN: 'islands#brownClusterIcons',
  NIGHT: 'islands#nightClusterIcons',
  DARK_BLUE: 'islands#darkBlueClusterIcons',
  DARK_ORANGE: 'islands#darkOrangeClusterIcons',
  PINK: 'islands#pinkClusterIcons',
  OLIVE: 'islands#oliveClusterIcons',
  INVERTED_BLUE: 'islands#invertedBlueClusterIcons',
  INVERTED_RED: 'islands#invertedRedClusterIcons',
  INVERTED_DARK_GREEN: 'islands#invertedDarkGreenClusterIcons',
  INVERTED_VIOLET: 'islands#invertedVioletClusterIcons'
}

/**
 * Проверяет поддержку кластеризации в браузере
 * @returns {boolean}
 */
export function isClustererSupported() {
  return typeof ymaps !== 'undefined' && 
         ymaps.Clusterer !== undefined
}

/**
 * Версия модуля
 */
export const VERSION = '1.0.0'

/**
 * Опции по умолчанию
 */
export const DEFAULT_OPTIONS = {
  preset: 'islands#blueClusterIcons',
  gridSize: 60,
  minClusterSize: 2,
  maxZoom: 16,
  hasBalloon: true,
  hasHint: true
}

// Экспортируем класс по умолчанию
export default Clusterer