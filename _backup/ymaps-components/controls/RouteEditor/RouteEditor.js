/**
 * RouteEditor - Контрол построения и редактирования маршрутов
 * Обеспечивает интерактивное создание, редактирование и оптимизацию маршрутов
 * 
 * @version 1.0.0
 * @author SPA Platform
 * @created 2025-09-04
 */

import ControlBase from '../ControlBase.js'
import { DOM, Position, Events, Accessibility } from '../../utils/controlHelpers.js'

/**
 * Класс контрола редактирования маршрутов
 * Предоставляет полнофункциональный интерфейс для работы с маршрутами
 */
export default class RouteEditor extends ControlBase {
  /**
   * Создает экземпляр контрола маршрутов
   * @param {Object} options - Опции контрола
   * @param {Array<string>} [options.travelModes=['driving', 'walking', 'transit']] - Режимы передвижения
   * @param {string} [options.defaultTravelMode='driving'] - Режим по умолчанию
   * @param {boolean} [options.allowMultipleRoutes=true] - Множественные маршруты
   * @param {number} [options.maxWaypoints=8] - Максимум промежуточных точек
   * @param {boolean} [options.enableDragDrop=true] - Перетаскивание точек
   * @param {boolean} [options.enableOptimization=true] - Оптимизация маршрута
   * @param {boolean} [options.showDistanceTime=true] - Показать расстояние и время
   * @param {boolean} [options.showAlternatives=false] - Показать альтернативы
   * @param {boolean} [options.avoidTolls=false] - Избегать платных дорог
   * @param {boolean} [options.avoidHighways=false] - Избегать автомагистралей
   * @param {boolean} [options.avoidFerries=false] - Избегать паромов
   * @param {string} [options.units='metric'] - Единицы измерения
   * @param {string} [options.language='ru'] - Язык направлений
   * @param {Function} [options.waypointRenderer] - Кастомный рендерер точек
   * @param {Function} [options.routeRenderer] - Кастомный рендерер маршрута
   * @param {Function} [options.instructionsRenderer] - Кастомный рендерер инструкций
   */
  constructor(options = {}) {
    super({
      position: 'topLeft',
      zIndex: 1000,
      className: 'ymaps-route-editor',
      ...options
    })

    // Настройки маршрутизации
    this._travelModes = options.travelModes || ['driving', 'walking', 'transit', 'bicycle']
    this._defaultTravelMode = options.defaultTravelMode || 'driving'
    this._currentTravelMode = this._defaultTravelMode
    this._allowMultipleRoutes = options.allowMultipleRoutes !== false
    this._maxWaypoints = Math.min(23, Math.max(2, options.maxWaypoints || 8))
    
    // Интерактивность
    this._enableDragDrop = options.enableDragDrop !== false
    this._enableOptimization = options.enableOptimization !== false
    this._showDistanceTime = options.showDistanceTime !== false
    this._showAlternatives = options.showAlternatives === true
    
    // Ограничения маршрута
    this._avoidTolls = options.avoidTolls === true
    this._avoidHighways = options.avoidHighways === true
    this._avoidFerries = options.avoidFerries === true
    
    // Локализация
    this._units = options.units || 'metric'
    this._language = options.language || 'ru'
    
    // Кастомные рендереры
    this._waypointRenderer = options.waypointRenderer
    this._routeRenderer = options.routeRenderer
    this._instructionsRenderer = options.instructionsRenderer

    // Состояние маршрутов
    this._routes = []
    this._activeRouteIndex = -1
    this._waypoints = []
    this._isCalculating = false
    this._isDragging = false
    this._editingMode = false
    
    // Интерактивные элементы
    this._waypointMarkers = new Map()
    this._routePolylines = []
    this._dragMarker = null
    this._hoverSegment = null
    
    // DOM элементы
    this._elements = {
      container: null,
      panel: null,
      header: null,
      travelModeSelect: null,
      waypointsContainer: null,
      addWaypointButton: null,
      clearButton: null,
      optimizeButton: null,
      calculateButton: null,
      routesContainer: null,
      instructionsContainer: null,
      optionsPanel: null,
      loadingIndicator: null
    }

    // Yandex API объекты
    this._router = null
    this._dragProvider = null

    this._bindMethods()
  }

  /**
   * Привязывает методы к контексту
   * @private
   */
  _bindMethods() {
    this._handleTravelModeChange = this._handleTravelModeChange.bind(this)
    this._handleAddWaypoint = this._handleAddWaypoint.bind(this)
    this._handleClearRoute = this._handleClearRoute.bind(this)
    this._handleOptimizeRoute = this._handleOptimizeRoute.bind(this)
    this._handleCalculateRoute = this._handleCalculateRoute.bind(this)
    this._handleWaypointDrag = this._handleWaypointDrag.bind(this)
    this._handleWaypointDrop = this._handleWaypointDrop.bind(this)
    this._handleWaypointRemove = this._handleWaypointRemove.bind(this)
    this._handleMapClick = this._handleMapClick.bind(this)
    this._handleRouteClick = this._handleRouteClick.bind(this)
    this._handleRouteHover = this._handleRouteHover.bind(this)
  }

  /**
   * Создает DOM структуру контрола
   * @returns {HTMLElement} Корневой элемент
   * @protected
   */
  _createElement() {
    const container = DOM.createElement('div', {
      className: `${this._options.className} ymaps-route-editor-container`,
      'aria-label': 'Редактор маршрутов'
    })

    // Основная панель
    this._elements.panel = DOM.createElement('div', {
      className: 'ymaps-route-editor-panel'
    })

    // Заголовок с режимами передвижения
    this._elements.header = this._createHeader()
    
    // Контейнер путевых точек
    this._elements.waypointsContainer = this._createWaypointsContainer()
    
    // Кнопки управления
    const controlsGroup = this._createControlsGroup()
    
    // Контейнер результатов
    this._elements.routesContainer = this._createRoutesContainer()
    
    // Инструкции навигации
    this._elements.instructionsContainer = this._createInstructionsContainer()
    
    // Индикатор загрузки
    this._elements.loadingIndicator = DOM.createElement('div', {
      className: 'ymaps-route-editor-loading'
    })
    this._elements.loadingIndicator.innerHTML = `
      <div class="loading-spinner"></div>
      <span class="loading-text">Построение маршрута...</span>
    `

    // Собираем панель
    this._elements.panel.appendChild(this._elements.header)
    this._elements.panel.appendChild(this._elements.waypointsContainer)
    this._elements.panel.appendChild(controlsGroup)
    this._elements.panel.appendChild(this._elements.routesContainer)
    this._elements.panel.appendChild(this._elements.instructionsContainer)
    this._elements.panel.appendChild(this._elements.loadingIndicator)

    container.appendChild(this._elements.panel)
    this._elements.container = container

    this._attachEventListeners()
    this._updateVisualState()

    return container
  }

  /**
   * Создает заголовок с селектором режима передвижения
   * @returns {HTMLElement}
   * @private
   */
  _createHeader() {
    const header = DOM.createElement('div', {
      className: 'ymaps-route-editor-header'
    })

    const title = DOM.createElement('h3', {
      className: 'route-editor-title'
    })
    title.textContent = 'Построение маршрута'

    this._elements.travelModeSelect = DOM.createElement('select', {
      className: 'route-editor-travel-mode',
      'aria-label': 'Режим передвижения'
    })

    // Добавляем опции режимов передвижения
    this._travelModes.forEach(mode => {
      const option = DOM.createElement('option', {
        value: mode,
        selected: mode === this._currentTravelMode
      })
      option.textContent = this._getTravelModeLabel(mode)
      this._elements.travelModeSelect.appendChild(option)
    })

    header.appendChild(title)
    header.appendChild(this._elements.travelModeSelect)

    return header
  }

  /**
   * Создает контейнер путевых точек
   * @returns {HTMLElement}
   * @private
   */
  _createWaypointsContainer() {
    const container = DOM.createElement('div', {
      className: 'ymaps-route-editor-waypoints',
      role: 'list',
      'aria-label': 'Путевые точки маршрута'
    })

    // Добавляем начальные точки
    this._addWaypointElement(0, 'Откуда', 'start')
    this._addWaypointElement(1, 'Куда', 'end')

    // Кнопка добавления промежуточной точки
    this._elements.addWaypointButton = DOM.createElement('button', {
      className: 'route-editor-add-waypoint',
      type: 'button'
    })
    this._elements.addWaypointButton.innerHTML = `
      <span class="add-icon">+</span>
      <span class="add-text">Добавить остановку</span>
    `

    container.appendChild(this._elements.addWaypointButton)

    return container
  }

  /**
   * Создает группу кнопок управления
   * @returns {HTMLElement}
   * @private
   */
  _createControlsGroup() {
    const group = DOM.createElement('div', {
      className: 'ymaps-route-editor-controls'
    })

    // Кнопка расчета маршрута
    this._elements.calculateButton = DOM.createElement('button', {
      className: 'route-editor-button route-editor-calculate',
      type: 'button'
    })
    this._elements.calculateButton.textContent = 'Построить маршрут'

    // Кнопка оптимизации
    if (this._enableOptimization) {
      this._elements.optimizeButton = DOM.createElement('button', {
        className: 'route-editor-button route-editor-optimize',
        type: 'button',
        title: 'Оптимизировать порядок точек'
      })
      this._elements.optimizeButton.textContent = 'Оптимизировать'
    }

    // Кнопка очистки
    this._elements.clearButton = DOM.createElement('button', {
      className: 'route-editor-button route-editor-clear',
      type: 'button',
      title: 'Очистить маршрут'
    })
    this._elements.clearButton.textContent = 'Очистить'

    group.appendChild(this._elements.calculateButton)
    if (this._elements.optimizeButton) {
      group.appendChild(this._elements.optimizeButton)
    }
    group.appendChild(this._elements.clearButton)

    return group
  }

  /**
   * Создает контейнер результатов маршрутизации
   * @returns {HTMLElement}
   * @private
   */
  _createRoutesContainer() {
    const container = DOM.createElement('div', {
      className: 'ymaps-route-editor-routes'
    })

    container.style.display = 'none'
    return container
  }

  /**
   * Создает контейнер инструкций навигации
   * @returns {HTMLElement}
   * @private
   */
  _createInstructionsContainer() {
    const container = DOM.createElement('div', {
      className: 'ymaps-route-editor-instructions'
    })

    container.style.display = 'none'
    return container
  }

  /**
   * Добавляет элемент путевой точки
   * @param {number} index - Индекс точки
   * @param {string} placeholder - Плейсхолдер
   * @param {string} type - Тип точки (start, waypoint, end)
   * @private
   */
  _addWaypointElement(index, placeholder, type) {
    const waypointElement = DOM.createElement('div', {
      className: `route-editor-waypoint route-editor-waypoint--${type}`,
      'data-index': index,
      role: 'listitem'
    })

    // Иконка точки
    const icon = DOM.createElement('div', {
      className: 'waypoint-icon'
    })
    icon.textContent = this._getWaypointIcon(type, index)

    // Поле ввода адреса
    const input = DOM.createElement('input', {
      type: 'text',
      className: 'waypoint-input',
      placeholder: placeholder,
      'aria-label': `${placeholder} (точка ${index + 1})`
    })

    // Кнопка удаления (только для промежуточных точек)
    let removeButton = null
    if (type === 'waypoint') {
      removeButton = DOM.createElement('button', {
        className: 'waypoint-remove',
        type: 'button',
        'aria-label': `Удалить точку ${index + 1}`,
        title: 'Удалить остановку'
      })
      removeButton.textContent = '×'
    }

    // Маркер перетаскивания
    const dragHandle = DOM.createElement('div', {
      className: 'waypoint-drag-handle',
      'aria-label': 'Перетащить для изменения порядка',
      title: 'Перетащить'
    })
    dragHandle.innerHTML = '⋮⋮'

    waypointElement.appendChild(icon)
    waypointElement.appendChild(input)
    if (removeButton) {
      waypointElement.appendChild(removeButton)
    }
    if (this._enableDragDrop) {
      waypointElement.appendChild(dragHandle)
    }

    // Вставляем в правильное место
    const existingWaypoints = this._elements.waypointsContainer.querySelectorAll('.route-editor-waypoint')
    if (index >= existingWaypoints.length) {
      this._elements.waypointsContainer.insertBefore(
        waypointElement, 
        this._elements.addWaypointButton
      )
    } else {
      this._elements.waypointsContainer.insertBefore(
        waypointElement,
        existingWaypoints[index]
      )
    }

    return waypointElement
  }

  /**
   * Подключает обработчики событий
   * @private
   */
  _attachEventListeners() {
    if (!this._elements.container) return

    // События селектора режима передвижения
    if (this._elements.travelModeSelect) {
      this._elements.travelModeSelect.addEventListener('change', this._handleTravelModeChange)
    }

    // События кнопок
    if (this._elements.addWaypointButton) {
      this._elements.addWaypointButton.addEventListener('click', this._handleAddWaypoint)
    }

    if (this._elements.calculateButton) {
      this._elements.calculateButton.addEventListener('click', this._handleCalculateRoute)
    }

    if (this._elements.optimizeButton) {
      this._elements.optimizeButton.addEventListener('click', this._handleOptimizeRoute)
    }

    if (this._elements.clearButton) {
      this._elements.clearButton.addEventListener('click', this._handleClearRoute)
    }

    // Делегирование событий для путевых точек
    this._elements.waypointsContainer.addEventListener('input', this._handleWaypointInput.bind(this))
    this._elements.waypointsContainer.addEventListener('click', this._handleWaypointClick.bind(this))
    this._elements.waypointsContainer.addEventListener('keydown', this._handleWaypointKeydown.bind(this))

    // Drag & Drop для путевых точек
    if (this._enableDragDrop) {
      this._elements.waypointsContainer.addEventListener('dragstart', this._handleWaypointDragStart.bind(this))
      this._elements.waypointsContainer.addEventListener('dragover', this._handleWaypointDragOver.bind(this))
      this._elements.waypointsContainer.addEventListener('drop', this._handleWaypointDrop)
    }
  }

  /**
   * Отключает обработчики событий
   * @private
   */
  _detachEventListeners() {
    if (!this._elements.container) return

    // Удаляем все слушатели
    const elements = Object.values(this._elements).filter(el => el instanceof HTMLElement)
    elements.forEach(element => {
      element.replaceWith(element.cloneNode(true))
    })

    // Удаляем слушатели карты
    if (this._map) {
      this._map.events.remove('click', this._handleMapClick)
    }
  }

  /**
   * Инициализирует API Yandex Maps
   * @private
   */
  async _initializeAPI() {
    try {
      if (window.ymaps && window.ymaps.ready) {
        await new Promise((resolve) => {
          window.ymaps.ready(resolve)
        })

        // Инициализируем маршрутизатор
        if (window.ymaps.route) {
          this._router = window.ymaps.route
        }

        // Инициализируем провайдер перетаскивания
        if (window.ymaps.behavior && window.ymaps.behavior.DragProvider) {
          this._dragProvider = new window.ymaps.behavior.DragProvider()
        }

        this._emit('apiready', { 
          router: !!this._router, 
          dragProvider: !!this._dragProvider 
        })
      } else {
        throw new Error('Yandex Maps API недоступен')
      }
    } catch (error) {
      console.error('[RouteEditor] Ошибка инициализации API:', error)
      this._emit('apierror', { error })
      throw error
    }
  }

  /**
   * Добавляет контрол на карту
   * @param {ymaps.Map} map - Экземпляр карты
   * @returns {Promise<void>}
   */
  async addToMap(map) {
    try {
      await super.addToMap(map)
      await this._initializeAPI()

      // Подключаем события карты
      map.events.add('click', this._handleMapClick)

      this._emit('ready', { control: this })
    } catch (error) {
      console.error('[RouteEditor] Ошибка добавления на карту:', error)
      this._emit('error', { error, operation: 'addToMap' })
      throw error
    }
  }

  // === ОБРАБОТЧИКИ СОБЫТИЙ ===

  /**
   * Обработчик изменения режима передвижения
   * @param {Event} event
   * @private
   */
  _handleTravelModeChange(event) {
    const newMode = event.target.value
    if (this._travelModes.includes(newMode)) {
      this._currentTravelMode = newMode
      this._emit('travelmodechange', { oldMode: this._currentTravelMode, newMode })
      
      // Пересчитываем маршрут если есть точки
      if (this._waypoints.length >= 2) {
        this._calculateRoute()
      }
    }
  }

  /**
   * Обработчик добавления промежуточной точки
   * @private
   */
  _handleAddWaypoint() {
    if (this._waypoints.length >= this._maxWaypoints) {
      this._emit('error', { 
        error: new Error(`Максимальное количество точек: ${this._maxWaypoints}`),
        operation: 'addWaypoint' 
      })
      return
    }

    const index = this._waypoints.length - 1 // Вставляем перед последней точкой
    this._addWaypointElement(index, `Остановка ${index}`, 'waypoint')
    this._waypoints.splice(index, 0, null)

    this._updateWaypointIndices()
    this._emit('waypointadd', { index, total: this._waypoints.length })
  }

  /**
   * Обработчик очистки маршрута
   * @private
   */
  _handleClearRoute() {
    this._clearRoute()
    this._emit('clear')
  }

  /**
   * Обработчик оптимизации маршрута
   * @private
   */
  async _handleOptimizeRoute() {
    if (this._waypoints.length < 3) {
      this._emit('error', { 
        error: new Error('Для оптимизации нужно минимум 3 точки'),
        operation: 'optimize' 
      })
      return
    }

    try {
      await this._optimizeWaypoints()
      this._emit('optimize', { waypoints: this._waypoints })
    } catch (error) {
      console.error('[RouteEditor] Ошибка оптимизации:', error)
      this._emit('error', { error, operation: 'optimize' })
    }
  }

  /**
   * Обработчик расчета маршрута
   * @private
   */
  async _handleCalculateRoute() {
    await this._calculateRoute()
  }

  /**
   * Обработчик ввода в поля путевых точек
   * @param {Event} event
   * @private
   */
  _handleWaypointInput(event) {
    if (!event.target.classList.contains('waypoint-input')) return

    const waypointElement = event.target.closest('.route-editor-waypoint')
    const index = parseInt(waypointElement.dataset.index, 10)
    const value = event.target.value.trim()

    this._emit('waypointchange', { index, value })

    // Автоматический поиск/геокодирование (с задержкой)
    clearTimeout(this._geocodeTimeout)
    if (value.length > 2) {
      this._geocodeTimeout = setTimeout(() => {
        this._geocodeWaypoint(index, value)
      }, 500)
    }
  }

  /**
   * Обработчик кликов по элементам путевых точек
   * @param {Event} event
   * @private
   */
  _handleWaypointClick(event) {
    if (event.target.classList.contains('waypoint-remove')) {
      const waypointElement = event.target.closest('.route-editor-waypoint')
      const index = parseInt(waypointElement.dataset.index, 10)
      this._removeWaypoint(index)
    }
  }

  /**
   * Обработчик нажатий клавиш в путевых точках
   * @param {KeyboardEvent} event
   * @private
   */
  _handleWaypointKeydown(event) {
    if (event.key === 'Enter' && event.target.classList.contains('waypoint-input')) {
      event.preventDefault()
      this._calculateRoute()
    }
  }

  /**
   * Обработчик клика по карте
   * @param {Event} event
   * @private
   */
  _handleMapClick(event) {
    if (!this._editingMode) return

    const coordinates = event.get('coords')
    const emptyIndex = this._waypoints.findIndex(waypoint => !waypoint)

    if (emptyIndex >= 0) {
      this._setWaypointCoordinates(emptyIndex, coordinates)
    } else if (this._waypoints.length < this._maxWaypoints) {
      // Добавляем новую промежуточную точку
      this._handleAddWaypoint()
      this._setWaypointCoordinates(this._waypoints.length - 2, coordinates)
    }
  }

  // === МЕТОДЫ РАБОТЫ С МАРШРУТАМИ ===

  /**
   * Вычисляет маршрут между путевыми точками
   * @returns {Promise<Array>} Массив маршрутов
   * @private
   */
  async _calculateRoute() {
    if (!this._router || this._isCalculating) return []

    // Получаем заполненные точки
    const validWaypoints = this._waypoints.filter(waypoint => waypoint && waypoint.coordinates)
    if (validWaypoints.length < 2) {
      this._emit('error', { 
        error: new Error('Необходимо минимум 2 точки для построения маршрута'),
        operation: 'calculate' 
      })
      return []
    }

    try {
      this._setCalculatingState(true)

      // Формируем точки для маршрутизации
      const routePoints = validWaypoints.map(waypoint => waypoint.coordinates)

      // Опции маршрутизации
      const routeOptions = {
        multiRoute: this._allowMultipleRoutes,
        wayPointDragging: this._enableDragDrop,
        avoidTrafficJams: true,
        ...this._getRouteConstraints()
      }

      // Строим маршрут
      const multiRoute = this._router(routePoints, routeOptions)
      
      // Ждем готовности маршрута
      await new Promise((resolve, reject) => {
        multiRoute.model.events.add('requestsuccess', resolve)
        multiRoute.model.events.add('requestfail', reject)
      })

      // Получаем маршруты
      const routes = []
      multiRoute.getRoutes().each((route, index) => {
        const routeData = {
          index,
          waypoints: validWaypoints,
          geometry: route.geometry,
          properties: route.properties,
          distance: route.properties.get('distance'),
          duration: route.properties.get('duration'),
          segments: this._extractRouteSegments(route),
          instructions: this._extractRouteInstructions(route)
        }
        routes.push(routeData)
      })

      // Сохраняем результаты
      this._routes = routes
      this._activeRouteIndex = 0

      // Отображаем на карте
      await this._displayRoutesOnMap(multiRoute)

      // Обновляем UI
      this._updateRoutesDisplay()
      this._updateInstructionsDisplay()

      this._emit('routecalculated', { routes, activeIndex: this._activeRouteIndex })
      return routes

    } catch (error) {
      console.error('[RouteEditor] Ошибка расчета маршрута:', error)
      this._emit('error', { error, operation: 'calculate' })
      return []
    } finally {
      this._setCalculatingState(false)
    }
  }

  /**
   * Оптимизирует порядок промежуточных точек
   * @returns {Promise<void>}
   * @private
   */
  async _optimizeWaypoints() {
    if (!this._enableOptimization || this._waypoints.length < 3) return

    try {
      const validWaypoints = this._waypoints.filter(waypoint => waypoint && waypoint.coordinates)
      
      // Фиксируем первую и последнюю точки
      const startPoint = validWaypoints[0]
      const endPoint = validWaypoints[validWaypoints.length - 1]
      const intermediatePoints = validWaypoints.slice(1, -1)

      if (intermediatePoints.length === 0) return

      // Простая оптимизация методом ближайшего соседа
      const optimizedOrder = await this._findOptimalOrder(
        startPoint.coordinates,
        intermediatePoints.map(wp => wp.coordinates),
        endPoint.coordinates
      )

      // Переупорядочиваем точки
      const optimizedWaypoints = [
        startPoint,
        ...optimizedOrder.map(index => intermediatePoints[index]),
        endPoint
      ]

      this._waypoints = optimizedWaypoints
      this._updateWaypointsDisplay()

      // Пересчитываем маршрут
      await this._calculateRoute()

    } catch (error) {
      console.error('[RouteEditor] Ошибка оптимизации:', error)
      throw error
    }
  }

  /**
   * Находит оптимальный порядок промежуточных точек
   * @param {Array} start - Начальная точка
   * @param {Array} waypoints - Промежуточные точки  
   * @param {Array} end - Конечная точка
   * @returns {Promise<Array>} Оптимальный порядок индексов
   * @private
   */
  async _findOptimalOrder(start, waypoints, end) {
    // Простая жадная оптимизация по принципу ближайшего соседа
    const order = []
    const remaining = waypoints.map((_, index) => index)
    let current = start

    while (remaining.length > 0) {
      let nearestIndex = 0
      let nearestDistance = this._calculateDistance(current, waypoints[remaining[0]])

      for (let i = 1; i < remaining.length; i++) {
        const distance = this._calculateDistance(current, waypoints[remaining[i]])
        if (distance < nearestDistance) {
          nearestDistance = distance
          nearestIndex = i
        }
      }

      const selectedIndex = remaining[nearestIndex]
      order.push(selectedIndex)
      current = waypoints[selectedIndex]
      remaining.splice(nearestIndex, 1)
    }

    return order
  }

  /**
   * Вычисляет расстояние между двумя точками
   * @param {Array} point1 - Первая точка [lat, lng]
   * @param {Array} point2 - Вторая точка [lat, lng]
   * @returns {number} Расстояние в километрах
   * @private
   */
  _calculateDistance(point1, point2) {
    const R = 6371 // Радиус Земли в км
    const dLat = this._toRad(point2[0] - point1[0])
    const dLng = this._toRad(point2[1] - point1[1])
    
    const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
              Math.cos(this._toRad(point1[0])) * Math.cos(this._toRad(point2[0])) *
              Math.sin(dLng / 2) * Math.sin(dLng / 2)
    
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a))
    return R * c
  }

  /**
   * Преобразует градусы в радианы
   * @param {number} degrees - Градусы
   * @returns {number} Радианы
   * @private
   */
  _toRad(degrees) {
    return degrees * (Math.PI / 180)
  }

  /**
   * Отображает маршруты на карте
   * @param {ymaps.multiRoute.MultiRoute} multiRoute - Мульти-маршрут
   * @private
   */
  async _displayRoutesOnMap(multiRoute) {
    if (!this._map) return

    // Очищаем предыдущие маршруты
    this._clearMapRoutes()

    // Добавляем мульти-маршрут на карту
    this._map.geoObjects.add(multiRoute)
    this._currentMultiRoute = multiRoute

    // Подстраиваем границы карты
    const bounds = multiRoute.getBounds()
    if (bounds) {
      this._map.setBounds(bounds, {
        checkZoomRange: true,
        margin: [50, 50, 50, 50]
      })
    }

    // Подключаем события маршрута
    this._setupRouteEvents(multiRoute)
  }

  /**
   * Настраивает события маршрута
   * @param {ymaps.multiRoute.MultiRoute} multiRoute - Мульти-маршрут
   * @private
   */
  _setupRouteEvents(multiRoute) {
    // События кликов по маршруту
    multiRoute.getRoutes().each((route, index) => {
      route.events.add('click', (event) => {
        this._handleRouteClick(event, index)
      })

      route.events.add('mouseenter', (event) => {
        this._handleRouteHover(event, index, true)
      })

      route.events.add('mouseleave', (event) => {
        this._handleRouteHover(event, index, false)
      })
    })

    // События путевых точек
    multiRoute.getWayPoints().each((waypoint, index) => {
      if (this._enableDragDrop) {
        waypoint.events.add('dragend', (event) => {
          this._handleWaypointMapDrag(event, index)
        })
      }
    })
  }

  // === УТИЛИТЫ И ОБНОВЛЕНИЯ ===

  /**
   * Устанавливает состояние вычисления
   * @param {boolean} isCalculating - Флаг состояния
   * @private
   */
  _setCalculatingState(isCalculating) {
    this._isCalculating = isCalculating
    this._updateVisualState()
    this._emit(isCalculating ? 'calculatestart' : 'calculateend')
  }

  /**
   * Обновляет визуальное состояние контрола
   * @private
   */
  _updateVisualState() {
    if (!this._elements.container) return

    this._elements.container.classList.toggle('route-editor--calculating', this._isCalculating)
    this._elements.container.classList.toggle('route-editor--has-routes', this._routes.length > 0)

    // Состояние индикатора загрузки
    if (this._elements.loadingIndicator) {
      this._elements.loadingIndicator.style.display = this._isCalculating ? 'block' : 'none'
    }

    // Состояние кнопок
    if (this._elements.calculateButton) {
      this._elements.calculateButton.disabled = this._isCalculating
      this._elements.calculateButton.textContent = this._isCalculating ? 'Вычисление...' : 'Построить маршрут'
    }

    if (this._elements.optimizeButton) {
      this._elements.optimizeButton.disabled = this._isCalculating || this._waypoints.length < 3
    }
  }

  /**
   * Обновляет отображение результатов маршрутов
   * @private
   */
  _updateRoutesDisplay() {
    if (!this._elements.routesContainer) return

    this._elements.routesContainer.innerHTML = ''

    if (this._routes.length === 0) {
      this._elements.routesContainer.style.display = 'none'
      return
    }

    this._elements.routesContainer.style.display = 'block'

    // Заголовок
    const header = DOM.createElement('h4', {
      className: 'routes-header'
    })
    header.textContent = this._routes.length === 1 ? 'Маршрут' : `Маршруты (${this._routes.length})`
    this._elements.routesContainer.appendChild(header)

    // Список маршрутов
    const routesList = DOM.createElement('div', {
      className: 'routes-list'
    })

    this._routes.forEach((route, index) => {
      const routeElement = this._createRouteElement(route, index)
      routesList.appendChild(routeElement)
    })

    this._elements.routesContainer.appendChild(routesList)
  }

  /**
   * Создает элемент маршрута
   * @param {Object} route - Данные маршрута
   * @param {number} index - Индекс маршрута
   * @returns {HTMLElement}
   * @private
   */
  _createRouteElement(route, index) {
    const element = DOM.createElement('div', {
      className: `route-item ${index === this._activeRouteIndex ? 'route-item--active' : ''}`,
      'data-route-index': index
    })

    // Основная информация
    const info = DOM.createElement('div', {
      className: 'route-info'
    })

    const distance = this._formatDistance(route.distance)
    const duration = this._formatDuration(route.duration)

    info.innerHTML = `
      <div class="route-summary">
        <span class="route-distance">${distance}</span>
        <span class="route-duration">${duration}</span>
      </div>
      <div class="route-description">
        ${route.properties.get('description') || `Маршрут ${index + 1}`}
      </div>
    `

    // Кнопка выбора маршрута
    const selectButton = DOM.createElement('button', {
      className: 'route-select-button',
      type: 'button'
    })
    selectButton.textContent = index === this._activeRouteIndex ? 'Выбран' : 'Выбрать'

    element.appendChild(info)
    element.appendChild(selectButton)

    // События
    element.addEventListener('click', () => {
      this._selectRoute(index)
    })

    return element
  }

  /**
   * Получает ограничения маршрута
   * @returns {Object} Объект ограничений
   * @private
   */
  _getRouteConstraints() {
    const constraints = {}

    if (this._avoidTolls) constraints.avoidTolls = true
    if (this._avoidHighways) constraints.avoidHighways = true
    if (this._avoidFerries) constraints.avoidFerries = true

    return constraints
  }

  /**
   * Получает иконку путевой точки
   * @param {string} type - Тип точки
   * @param {number} index - Индекс точки
   * @returns {string} Иконка
   * @private
   */
  _getWaypointIcon(type, index) {
    const icons = {
      start: '🚀',
      end: '🏁',
      waypoint: String(index)
    }
    return icons[type] || '📍'
  }

  /**
   * Получает локализованную метку режима передвижения
   * @param {string} mode - Режим передвижения
   * @returns {string} Локализованная метка
   * @private
   */
  _getTravelModeLabel(mode) {
    const labels = {
      driving: '🚗 На автомобиле',
      walking: '🚶 Пешком',
      transit: '🚌 Общественным транспортом',
      bicycle: '🚴 На велосипеде'
    }
    return labels[mode] || mode
  }

  /**
   * Форматирует расстояние
   * @param {number} meters - Расстояние в метрах
   * @returns {string} Отформатированное расстояние
   * @private
   */
  _formatDistance(meters) {
    if (meters < 1000) {
      return `${Math.round(meters)} м`
    }
    return `${(meters / 1000).toFixed(1)} км`
  }

  /**
   * Форматирует время
   * @param {number} seconds - Время в секундах
   * @returns {string} Отформатированное время
   * @private
   */
  _formatDuration(seconds) {
    const hours = Math.floor(seconds / 3600)
    const minutes = Math.floor((seconds % 3600) / 60)

    if (hours > 0) {
      return `${hours} ч ${minutes} мин`
    }
    return `${minutes} мин`
  }

  /**
   * Очищает маршрут полностью
   * @private
   */
  _clearRoute() {
    // Очищаем путевые точки
    this._waypoints = [null, null] // Оставляем начальную и конечную
    
    // Очищаем маршруты
    this._routes = []
    this._activeRouteIndex = -1
    
    // Очищаем карту
    this._clearMapRoutes()
    
    // Обновляем UI
    this._updateWaypointsDisplay()
    this._updateRoutesDisplay()
    this._updateInstructionsDisplay()
    this._updateVisualState()
  }

  /**
   * Очищает маршруты с карты
   * @private
   */
  _clearMapRoutes() {
    if (this._currentMultiRoute && this._map) {
      this._map.geoObjects.remove(this._currentMultiRoute)
      this._currentMultiRoute = null
    }

    // Очищаем дополнительные маркеры
    this._waypointMarkers.forEach(marker => {
      if (this._map) {
        this._map.geoObjects.remove(marker)
      }
    })
    this._waypointMarkers.clear()
  }

  // === ПУБЛИЧНЫЕ МЕТОДЫ ===

  /**
   * Устанавливает путевую точку
   * @param {number} index - Индекс точки
   * @param {string|Array} location - Адрес или координаты
   * @returns {Promise<void>}
   */
  async setWaypoint(index, location) {
    if (index < 0 || index >= this._maxWaypoints) {
      throw new Error(`Индекс точки должен быть от 0 до ${this._maxWaypoints - 1}`)
    }

    // Расширяем массив если необходимо
    while (this._waypoints.length <= index) {
      this._waypoints.push(null)
    }

    if (Array.isArray(location)) {
      // Координаты
      this._waypoints[index] = {
        coordinates: location,
        address: `${location[0].toFixed(6)}, ${location[1].toFixed(6)}`,
        type: 'coordinates'
      }
    } else {
      // Адрес - нужно геокодировать
      try {
        const geocoded = await this._geocodeAddress(location)
        this._waypoints[index] = {
          coordinates: geocoded.coordinates,
          address: geocoded.address || location,
          type: 'address'
        }
      } catch (error) {
        throw new Error(`Не удалось найти адрес: ${location}`)
      }
    }

    this._updateWaypointsDisplay()
    this._emit('waypointset', { index, waypoint: this._waypoints[index] })
  }

  /**
   * Получает путевую точку
   * @param {number} index - Индекс точки
   * @returns {Object|null} Данные точки
   */
  getWaypoint(index) {
    if (index >= 0 && index < this._waypoints.length) {
      return this._waypoints[index]
    }
    return null
  }

  /**
   * Получает все путевые точки
   * @returns {Array} Массив точек
   */
  getWaypoints() {
    return [...this._waypoints]
  }

  /**
   * Устанавливает режим передвижения
   * @param {string} mode - Режим передвижения
   */
  setTravelMode(mode) {
    if (!this._travelModes.includes(mode)) {
      throw new Error(`Неподдерживаемый режим передвижения: ${mode}`)
    }

    const oldMode = this._currentTravelMode
    this._currentTravelMode = mode

    if (this._elements.travelModeSelect) {
      this._elements.travelModeSelect.value = mode
    }

    this._emit('travelmodechange', { oldMode, newMode: mode })
  }

  /**
   * Получает текущий режим передвижения
   * @returns {string} Режим передвижения
   */
  getTravelMode() {
    return this._currentTravelMode
  }

  /**
   * Запускает расчет маршрута
   * @returns {Promise<Array>} Массив маршрутов
   */
  async calculateRoute() {
    return await this._calculateRoute()
  }

  /**
   * Получает рассчитанные маршруты
   * @returns {Array} Массив маршрутов
   */
  getRoutes() {
    return [...this._routes]
  }

  /**
   * Получает активный маршрут
   * @returns {Object|null} Активный маршрут
   */
  getActiveRoute() {
    if (this._activeRouteIndex >= 0 && this._activeRouteIndex < this._routes.length) {
      return this._routes[this._activeRouteIndex]
    }
    return null
  }

  /**
   * Выбирает маршрут как активный
   * @param {number} routeIndex - Индекс маршрута
   */
  selectRoute(routeIndex) {
    if (routeIndex >= 0 && routeIndex < this._routes.length) {
      const oldIndex = this._activeRouteIndex
      this._activeRouteIndex = routeIndex
      this._updateRoutesDisplay()
      this._updateInstructionsDisplay()
      this._emit('routeselect', { oldIndex, newIndex: routeIndex, route: this._routes[routeIndex] })
    }
  }

  /**
   * Очищает все маршруты
   */
  clear() {
    this._clearRoute()
  }

  /**
   * Включает/выключает режим редактирования
   * @param {boolean} enabled - Включить режим редактирования
   */
  setEditingMode(enabled) {
    this._editingMode = Boolean(enabled)
    this._elements.container.classList.toggle('route-editor--editing', this._editingMode)
    this._emit('editingmodechange', { enabled: this._editingMode })
  }

  /**
   * Проверяет включен ли режим редактирования
   * @returns {boolean} Состояние режима редактирования
   */
  isEditingMode() {
    return this._editingMode
  }

  /**
   * Переопределение метода уничтожения
   */
  destroy() {
    // Очищаем маршруты с карты
    this._clearMapRoutes()

    // Очищаем таймеры
    if (this._geocodeTimeout) {
      clearTimeout(this._geocodeTimeout)
    }

    // Отключаем события
    this._detachEventListeners()

    // Очищаем ссылки
    this._router = null
    this._dragProvider = null
    this._elements = {}
    this._routes = []
    this._waypoints = []
    this._waypointMarkers.clear()

    super.destroy()
  }
}

// Экспорт утилит
export const RouteEditorUtils = {
  /**
   * Форматирует расстояние
   */
  formatDistance(meters) {
    return meters < 1000 ? `${Math.round(meters)} м` : `${(meters / 1000).toFixed(1)} км`
  },

  /**
   * Форматирует время
   */
  formatDuration(seconds) {
    const hours = Math.floor(seconds / 3600)
    const minutes = Math.floor((seconds % 3600) / 60)
    return hours > 0 ? `${hours} ч ${minutes} мин` : `${minutes} мин`
  },

  /**
   * Проверяет корректность координат
   */
  isValidCoordinates(coords) {
    return Array.isArray(coords) && coords.length === 2 && 
           coords.every(c => typeof c === 'number' && !isNaN(c))
  }
}