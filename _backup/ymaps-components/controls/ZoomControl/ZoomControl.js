/**
 * ZoomControl - Контрол управления масштабом карты
 * Извлечено из минифицированного yandex-maps.js и полностью переработано
 * 
 * Основные возможности:
 * - Кнопки увеличения/уменьшения масштаба
 * - Слайдер для плавного изменения зума
 * - Drag & Drop для слайдера
 * - Поддержка различных размеров (small, medium, large)
 * - Автоматическая адаптация к зум-диапазону карты
 * - Плавная анимация изменения масштаба
 * 
 * @class ZoomControl
 * @extends ControlBase
 * @version 1.0.0
 * @author SPA Platform
 */

import ControlBase from '../ControlBase.js'
import { DOM, Events, Icons, Constants, Validation } from '../../utils/controlHelpers.js'

/**
 * Класс управления масштабом карты
 * Предоставляет интерфейс для изменения уровня zoom
 */
export default class ZoomControl extends ControlBase {
  /**
   * Конструктор контрола масштабирования
   * @param {Object} options - Опции контрола
   * @param {string} [options.size='medium'] - Размер контрола: 'small' | 'medium' | 'large'
   * @param {boolean} [options.showSlider=true] - Показывать слайдер
   * @param {boolean} [options.showButtons=true] - Показывать кнопки +/-
   * @param {number} [options.zoomDuration=300] - Длительность анимации зума в мс
   * @param {boolean} [options.smooth=true] - Плавное изменение зума
   * @param {number} [options.step=1] - Шаг изменения зума кнопками
   * @param {Object} [options.slider] - Настройки слайдера
   * @param {boolean} [options.slider.continuous=true] - Непрерывное изменение при перетаскивании
   */
  constructor(options = {}) {
    super({
      position: 'topLeft',
      size: 'medium',
      showSlider: true,
      showButtons: true,
      zoomDuration: 300,
      smooth: true,
      step: 1,
      slider: {
        continuous: true
      },
      ...options
    })

    /**
     * Текущий уровень зума
     * @type {number}
     * @private
     */
    this._currentZoom = 10

    /**
     * Диапазон допустимых значений зума
     * @type {Object}
     * @private
     */
    this._zoomRange = {
      min: 0,
      max: 23
    }

    /**
     * DOM элементы контрола
     * @type {Object}
     * @private
     */
    this._elements = {
      container: null,
      zoomIn: null,
      zoomOut: null,
      slider: null,
      sliderTrack: null,
      sliderHandle: null
    }

    /**
     * Состояние drag операции слайдера
     * @type {Object}
     * @private
     */
    this._dragState = {
      isDragging: false,
      startY: 0,
      startZoom: 0,
      sliderHeight: 0
    }

    /**
     * Обработчики событий карты
     * @type {Array<Function>}
     * @private
     */
    this._mapEventUnbinders = []

    /**
     * Обработчик анимации зума
     * @type {Object|null}
     * @private
     */
    this._zoomAnimation = null

    // Привязываем контекст методов для обработчиков событий
    this._onZoomInClick = this._onZoomInClick.bind(this)
    this._onZoomOutClick = this._onZoomOutClick.bind(this)
    this._onSliderMouseDown = this._onSliderMouseDown.bind(this)
    this._onSliderMouseMove = this._onSliderMouseMove.bind(this)
    this._onSliderMouseUp = this._onSliderMouseUp.bind(this)
    this._onMapBoundsChange = this._onMapBoundsChange.bind(this)
    this._onMapZoomRangeChange = this._onMapZoomRangeChange.bind(this)
  }

  /**
   * Создание DOM структуры контрола
   * @returns {Promise<void>}
   * @protected
   */
  async _createElement() {
    try {
      const size = this._options.size
      const showButtons = this._options.showButtons
      const showSlider = this._options.showSlider

      // Основной контейнер
      this._element = document.createElement('div')
      this._element.className = `ymaps-zoom-control ymaps-zoom-control--${size}`
      
      this._elements.container = DOM.createButtonGroup({
        className: 'ymaps-zoom-control-group',
        direction: 'vertical'
      })

      // Кнопка увеличения масштаба
      if (showButtons) {
        this._elements.zoomIn = DOM.createButton({
          className: 'ymaps-zoom-control-button ymaps-zoom-control-button--zoom-in',
          title: 'Увеличить масштаб',
          iconClass: 'ymaps-icon-zoom-in',
          onClick: this._onZoomInClick
        })

        // Применяем иконку
        const zoomInIcon = this._elements.zoomIn.querySelector('.ymaps-control-icon')
        Icons.apply(zoomInIcon, 'zoomIn')
        
        this._elements.container.appendChild(this._elements.zoomIn)
      }

      // Слайдер масштабирования
      if (showSlider) {
        this._createSlider()
        this._elements.container.appendChild(this._elements.slider)
      }

      // Кнопка уменьшения масштаба
      if (showButtons) {
        this._elements.zoomOut = DOM.createButton({
          className: 'ymaps-zoom-control-button ymaps-zoom-control-button--zoom-out',
          title: 'Уменьшить масштаб',
          iconClass: 'ymaps-icon-zoom-out',
          onClick: this._onZoomOutClick
        })

        // Применяем иконку
        const zoomOutIcon = this._elements.zoomOut.querySelector('.ymaps-control-icon')
        Icons.apply(zoomOutIcon, 'zoomOut')

        this._elements.container.appendChild(this._elements.zoomOut)
      }

      this._element.appendChild(this._elements.container)
      
      // Применяем размер-зависимые стили
      this._applySizeStyles()
      
    } catch (error) {
      console.error('ZoomControl._createElement:', error)
      throw new Error('Не удалось создать DOM структуру ZoomControl')
    }
  }

  /**
   * Создание слайдера масштабирования
   * @private
   */
  _createSlider() {
    this._elements.slider = document.createElement('div')
    this._elements.slider.className = 'ymaps-zoom-control-slider'
    
    this._elements.sliderTrack = document.createElement('div')
    this._elements.sliderTrack.className = 'ymaps-zoom-control-slider-track'
    
    this._elements.sliderHandle = document.createElement('div')
    this._elements.sliderHandle.className = 'ymaps-zoom-control-slider-handle'
    this._elements.sliderHandle.title = 'Перетащите для изменения масштаба'
    
    this._elements.sliderTrack.appendChild(this._elements.sliderHandle)
    this._elements.slider.appendChild(this._elements.sliderTrack)
    
    // Добавляем обработчики drag & drop
    this._elements.sliderHandle.addEventListener('mousedown', this._onSliderMouseDown)
    
    // Предотвращаем выделение текста при drag
    this._elements.slider.addEventListener('selectstart', (e) => e.preventDefault())
  }

  /**
   * Настройка обработчиков событий карты
   * @protected
   */
  _setupEventListeners() {
    super._setupEventListeners()

    if (!this._map) return

    try {
      // Слушаем изменения зума карты
      if (this._map.events) {
        const unbindBoundsChange = Events.on(
          this._map.events, 
          'boundschange', 
          this._onMapBoundsChange
        )
        this._mapEventUnbinders.push(unbindBoundsChange)
      }

      // Слушаем изменения диапазона зума
      if (this._map.zoomRange && this._map.zoomRange.events) {
        const unbindZoomRangeChange = Events.on(
          this._map.zoomRange.events,
          'change',
          this._onMapZoomRangeChange
        )
        this._mapEventUnbinders.push(unbindZoomRangeChange)
      }

      // Инициализируем текущее состояние
      this._updateFromMap()
      
    } catch (error) {
      console.error('ZoomControl._setupEventListeners:', error)
    }
  }

  /**
   * Удаление обработчиков событий карты
   * @protected
   */
  _removeEventListeners() {
    super._removeEventListeners()
    
    // Отвязываем все обработчики карты
    this._mapEventUnbinders.forEach(unbinder => {
      try {
        unbinder()
      } catch (error) {
        console.error('ZoomControl: ошибка отвязки обработчика:', error)
      }
    })
    this._mapEventUnbinders = []

    // Убираем глобальные обработчики drag
    if (this._dragState.isDragging) {
      document.removeEventListener('mousemove', this._onSliderMouseMove)
      document.removeEventListener('mouseup', this._onSliderMouseUp)
      this._dragState.isDragging = false
    }
  }

  /**
   * Получить текущий уровень зума
   * @returns {number}
   */
  getZoom() {
    return this._currentZoom
  }

  /**
   * Установить уровень зума
   * @param {number} zoom - Уровень зума
   * @param {Object} [options] - Опции анимации
   * @param {number} [options.duration] - Длительность анимации
   * @param {boolean} [options.smooth] - Плавная анимация
   * @returns {Promise<void>}
   */
  async setZoom(zoom, options = {}) {
    if (typeof zoom !== 'number' || zoom < this._zoomRange.min || zoom > this._zoomRange.max) {
      throw new Error(`ZoomControl: некорректный уровень зума ${zoom}`)
    }

    try {
      const duration = options.duration ?? this._options.zoomDuration
      const smooth = options.smooth ?? this._options.smooth

      if (this._map) {
        // Изменяем зум карты
        if (smooth && duration > 0) {
          await this._animateZoom(zoom, duration)
        } else {
          this._map.setZoom(zoom)
        }
      } else {
        // Обновляем только внутреннее состояние если карта не подключена
        this._setZoomState(zoom)
      }

      this._emitEvent('zoomchange', {
        oldZoom: this._currentZoom,
        newZoom: zoom
      })

    } catch (error) {
      console.error('ZoomControl.setZoom:', error)
      throw error
    }
  }

  /**
   * Увеличить масштаб на один шаг
   * @returns {Promise<void>}
   */
  async zoomIn() {
    const newZoom = Math.min(this._currentZoom + this._options.step, this._zoomRange.max)
    if (newZoom !== this._currentZoom) {
      await this.setZoom(newZoom)
    }
  }

  /**
   * Уменьшить масштаб на один шаг
   * @returns {Promise<void>}
   */
  async zoomOut() {
    const newZoom = Math.max(this._currentZoom - this._options.step, this._zoomRange.min)
    if (newZoom !== this._currentZoom) {
      await this.setZoom(newZoom)
    }
  }

  /**
   * Получить диапазон допустимых значений зума
   * @returns {Object} {min: number, max: number}
   */
  getZoomRange() {
    return { ...this._zoomRange }
  }

  /**
   * Установить диапазон зума
   * @param {number} min - Минимальный зум
   * @param {number} max - Максимальный зум
   */
  setZoomRange(min, max) {
    if (typeof min !== 'number' || typeof max !== 'number' || min > max) {
      throw new Error('ZoomControl: некорректный диапазон зума')
    }

    this._zoomRange.min = Math.max(0, Math.floor(min))
    this._zoomRange.max = Math.min(23, Math.floor(max))
    
    // Корректируем текущий зум если он вышел за диапазон
    if (this._currentZoom < this._zoomRange.min) {
      this.setZoom(this._zoomRange.min)
    } else if (this._currentZoom > this._zoomRange.max) {
      this.setZoom(this._zoomRange.max)
    }
    
    this._updateSliderPosition()
    this._updateButtonsState()
  }

  // PRIVATE методы

  /**
   * Обновление состояния контрола на основе карты
   * @private
   */
  _updateFromMap() {
    if (!this._map) return

    try {
      // Получаем текущий зум
      if (typeof this._map.getZoom === 'function') {
        this._setZoomState(this._map.getZoom())
      }

      // Получаем диапазон зума
      if (this._map.zoomRange && typeof this._map.zoomRange.getCurrent === 'function') {
        const range = this._map.zoomRange.getCurrent()
        if (Array.isArray(range) && range.length === 2) {
          this._zoomRange.min = range[0]
          this._zoomRange.max = range[1]
        }
      }

      this._updateSliderPosition()
      this._updateButtonsState()
      
    } catch (error) {
      console.error('ZoomControl._updateFromMap:', error)
    }
  }

  /**
   * Установка состояния зума без изменения карты
   * @param {number} zoom - Уровень зума
   * @private
   */
  _setZoomState(zoom) {
    this._currentZoom = Math.max(
      this._zoomRange.min,
      Math.min(this._zoomRange.max, zoom)
    )
  }

  /**
   * Обновление позиции слайдера
   * @private
   */
  _updateSliderPosition() {
    if (!this._elements.sliderHandle || !this._elements.sliderTrack) return

    const range = this._zoomRange.max - this._zoomRange.min
    if (range <= 0) return

    const progress = (this._currentZoom - this._zoomRange.min) / range
    const trackHeight = this._elements.sliderTrack.offsetHeight
    const handleHeight = this._elements.sliderHandle.offsetHeight
    const maxPosition = trackHeight - handleHeight
    
    const position = maxPosition * (1 - progress) // Инвертируем (вверх = больше зума)
    this._elements.sliderHandle.style.top = Math.max(0, Math.min(maxPosition, position)) + 'px'
  }

  /**
   * Обновление состояния кнопок
   * @private
   */
  _updateButtonsState() {
    if (this._elements.zoomIn) {
      if (this._currentZoom >= this._zoomRange.max) {
        this._elements.zoomIn.classList.add('disabled')
        this._elements.zoomIn.disabled = true
      } else {
        this._elements.zoomIn.classList.remove('disabled')
        this._elements.zoomIn.disabled = false
      }
    }

    if (this._elements.zoomOut) {
      if (this._currentZoom <= this._zoomRange.min) {
        this._elements.zoomOut.classList.add('disabled')
        this._elements.zoomOut.disabled = true
      } else {
        this._elements.zoomOut.classList.remove('disabled')
        this._elements.zoomOut.disabled = false
      }
    }
  }

  /**
   * Анимация изменения зума
   * @param {number} targetZoom - Целевой зум
   * @param {number} duration - Длительность анимации
   * @returns {Promise<void>}
   * @private
   */
  async _animateZoom(targetZoom, duration) {
    return new Promise((resolve, reject) => {
      try {
        // Отменяем предыдущую анимацию
        if (this._zoomAnimation) {
          this._zoomAnimation.cancel()
        }

        const startZoom = this._currentZoom
        const deltaZoom = targetZoom - startZoom
        const startTime = Date.now()

        const animate = () => {
          const elapsed = Date.now() - startTime
          const progress = Math.min(elapsed / duration, 1)
          
          // Easing функция (ease-in-out)
          const easedProgress = progress < 0.5
            ? 2 * progress * progress
            : -1 + (4 - 2 * progress) * progress

          const currentZoom = startZoom + deltaZoom * easedProgress
          this._setZoomState(currentZoom)
          
          if (this._map && typeof this._map.setZoom === 'function') {
            this._map.setZoom(currentZoom, { duration: 0 })
          }

          this._updateSliderPosition()

          if (progress < 1) {
            this._zoomAnimation = { 
              cancel: () => cancelAnimationFrame(this._zoomAnimation.id),
              id: requestAnimationFrame(animate)
            }
          } else {
            this._zoomAnimation = null
            resolve()
          }
        }

        this._zoomAnimation = { 
          cancel: () => cancelAnimationFrame(this._zoomAnimation.id),
          id: requestAnimationFrame(animate)
        }

      } catch (error) {
        this._zoomAnimation = null
        reject(error)
      }
    })
  }

  /**
   * Применение стилей размера
   * @private
   */
  _applySizeStyles() {
    if (!this._element) return

    const size = this._options.size
    const sizes = {
      small: { buttonSize: 28, sliderHeight: 60, fontSize: 12 },
      medium: { buttonSize: 34, sliderHeight: 80, fontSize: 14 },
      large: { buttonSize: 40, sliderHeight: 100, fontSize: 16 }
    }

    const config = sizes[size] || sizes.medium

    // Применяем CSS переменные для размеров
    this._element.style.setProperty('--button-size', config.buttonSize + 'px')
    this._element.style.setProperty('--slider-height', config.sliderHeight + 'px')
    this._element.style.setProperty('--font-size', config.fontSize + 'px')
  }

  // EVENT HANDLERS

  /**
   * Обработчик клика по кнопке увеличения
   * @private
   */
  async _onZoomInClick() {
    if (!this._options.enabled) return
    
    try {
      await this.zoomIn()
      this._emitEvent('zoomin', { zoom: this._currentZoom })
    } catch (error) {
      console.error('ZoomControl._onZoomInClick:', error)
    }
  }

  /**
   * Обработчик клика по кнопке уменьшения
   * @private
   */
  async _onZoomOutClick() {
    if (!this._options.enabled) return
    
    try {
      await this.zoomOut()
      this._emitEvent('zoomout', { zoom: this._currentZoom })
    } catch (error) {
      console.error('ZoomControl._onZoomOutClick:', error)
    }
  }

  /**
   * Обработчик начала drag операции слайдера
   * @param {MouseEvent} event - Событие мыши
   * @private
   */
  _onSliderMouseDown(event) {
    if (!this._options.enabled) return

    event.preventDefault()
    event.stopPropagation()

    this._dragState.isDragging = true
    this._dragState.startY = event.clientY
    this._dragState.startZoom = this._currentZoom
    this._dragState.sliderHeight = this._elements.sliderTrack.offsetHeight

    // Добавляем глобальные обработчики
    document.addEventListener('mousemove', this._onSliderMouseMove)
    document.addEventListener('mouseup', this._onSliderMouseUp)

    // Меняем курсор
    document.body.style.cursor = 'grabbing'
    this._elements.sliderHandle.classList.add('dragging')

    this._emitEvent('dragstart', { zoom: this._currentZoom })
  }

  /**
   * Обработчик перемещения мыши при drag операции
   * @param {MouseEvent} event - Событие мыши
   * @private
   */
  _onSliderMouseMove(event) {
    if (!this._dragState.isDragging) return

    event.preventDefault()

    const deltaY = event.clientY - this._dragState.startY
    const handleHeight = this._elements.sliderHandle.offsetHeight
    const maxDelta = this._dragState.sliderHeight - handleHeight
    
    // Инвертируем движение (вверх = больше зума)
    const progress = -deltaY / maxDelta
    const range = this._zoomRange.max - this._zoomRange.min
    const zoomDelta = progress * range
    
    const newZoom = Math.max(
      this._zoomRange.min,
      Math.min(this._zoomRange.max, this._dragState.startZoom + zoomDelta)
    )

    this._setZoomState(newZoom)
    this._updateSliderPosition()

    // Непрерывное обновление карты если включено
    if (this._options.slider.continuous && this._map) {
      try {
        this._map.setZoom(newZoom, { duration: 0 })
      } catch (error) {
        console.error('ZoomControl: ошибка непрерывного зума:', error)
      }
    }

    this._emitEvent('drag', { zoom: newZoom })
  }

  /**
   * Обработчик окончания drag операции
   * @param {MouseEvent} event - Событие мыши
   * @private
   */
  async _onSliderMouseUp(event) {
    if (!this._dragState.isDragging) return

    event.preventDefault()

    // Убираем глобальные обработчики
    document.removeEventListener('mousemove', this._onSliderMouseMove)
    document.removeEventListener('mouseup', this._onSliderMouseUp)

    // Восстанавливаем курсор
    document.body.style.cursor = ''
    this._elements.sliderHandle.classList.remove('dragging')

    const finalZoom = this._currentZoom
    this._dragState.isDragging = false

    // Финальное обновление карты если непрерывный режим был выключен
    if (!this._options.slider.continuous && this._map) {
      try {
        await this.setZoom(finalZoom)
      } catch (error) {
        console.error('ZoomControl: ошибка финального зума:', error)
      }
    }

    this._emitEvent('dragend', { zoom: finalZoom })
    this._emitEvent('zoomchange', { 
      oldZoom: this._dragState.startZoom, 
      newZoom: finalZoom 
    })
  }

  /**
   * Обработчик изменения границ карты
   * @param {Object} event - Событие карты
   * @private
   */
  _onMapBoundsChange(event) {
    try {
      if (event && typeof event.get === 'function') {
        const newZoom = event.get('newZoom')
        if (typeof newZoom === 'number' && newZoom !== this._currentZoom) {
          this._setZoomState(newZoom)
          this._updateSliderPosition()
          this._updateButtonsState()
        }
      }
    } catch (error) {
      console.error('ZoomControl._onMapBoundsChange:', error)
    }
  }

  /**
   * Обработчик изменения диапазона зума карты
   * @param {Object} event - Событие карты
   * @private
   */
  _onMapZoomRangeChange(event) {
    try {
      if (event && typeof event.get === 'function') {
        const newZoomRange = event.get('newZoomRange')
        if (Array.isArray(newZoomRange) && newZoomRange.length === 2) {
          this.setZoomRange(newZoomRange[0], newZoomRange[1])
        }
      }
    } catch (error) {
      console.error('ZoomControl._onMapZoomRangeChange:', error)
    }
  }

  /**
   * Обработчик уничтожения контрола
   * @protected
   */
  _onDestroy() {
    super._onDestroy()
    
    // Отменяем анимацию
    if (this._zoomAnimation) {
      this._zoomAnimation.cancel()
      this._zoomAnimation = null
    }

    // Очищаем состояние drag
    if (this._dragState.isDragging) {
      document.removeEventListener('mousemove', this._onSliderMouseMove)
      document.removeEventListener('mouseup', this._onSliderMouseUp)
      document.body.style.cursor = ''
    }
  }
}

// Добавляем специфичные для ZoomControl CSS стили
const ZOOM_CONTROL_STYLES = `
  .ymaps-zoom-control {
    display: inline-block;
  }

  .ymaps-zoom-control-group {
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
  }

  .ymaps-zoom-control-button {
    width: var(--button-size, 34px);
    height: var(--button-size, 34px);
    font-size: var(--font-size, 14px);
    font-weight: bold;
    position: relative;
  }

  .ymaps-zoom-control-slider {
    width: var(--button-size, 34px);
    height: var(--slider-height, 80px);
    background: rgba(255, 255, 255, 0.9);
    position: relative;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .ymaps-zoom-control-slider-track {
    width: 4px;
    height: calc(var(--slider-height, 80px) - 16px);
    background: #e0e0e0;
    border-radius: 2px;
    position: relative;
  }

  .ymaps-zoom-control-slider-handle {
    width: 12px;
    height: 12px;
    background: #333;
    border: 2px solid #fff;
    border-radius: 50%;
    position: absolute;
    left: -4px;
    cursor: grab;
    box-shadow: 0 1px 3px rgba(0,0,0,0.3);
    transition: all 0.2s ease;
  }

  .ymaps-zoom-control-slider-handle:hover {
    transform: scale(1.1);
    box-shadow: 0 2px 6px rgba(0,0,0,0.4);
  }

  .ymaps-zoom-control-slider-handle.dragging {
    cursor: grabbing;
    transform: scale(1.2);
    box-shadow: 0 3px 9px rgba(0,0,0,0.5);
  }

  .ymaps-zoom-control--small {
    --button-size: 28px;
    --slider-height: 60px;
    --font-size: 12px;
  }

  .ymaps-zoom-control--medium {
    --button-size: 34px;
    --slider-height: 80px;
    --font-size: 14px;
  }

  .ymaps-zoom-control--large {
    --button-size: 40px;
    --slider-height: 100px;
    --font-size: 16px;
  }

  @media (max-width: 768px) {
    .ymaps-zoom-control {
      --button-size: 40px;
      --font-size: 16px;
    }
    
    .ymaps-zoom-control-slider-handle {
      width: 14px;
      height: 14px;
      left: -5px;
    }
  }
`

// Инъекция стилей в документ
if (typeof document !== 'undefined') {
  if (!document.getElementById('ymaps-zoom-control-styles')) {
    const styleElement = document.createElement('style')
    styleElement.id = 'ymaps-zoom-control-styles'
    styleElement.textContent = ZOOM_CONTROL_STYLES
    document.head.appendChild(styleElement)
  }
}