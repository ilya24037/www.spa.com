/**
 * TypeSelector - Контрол выбора типа карты
 * Извлечено из минифицированного yandex-maps.js и переработано
 * 
 * Основные возможности:
 * - Переключение между типами карт (Схема, Спутник, Гибрид)
 * - Выпадающий список и кнопочный интерфейс
 * - Поддержка кастомных типов карт
 * - Автоматическое определение доступных типов
 * - Иконки и превью для каждого типа
 * - Адаптация под мобильные устройства
 * 
 * @class TypeSelector
 * @extends ControlBase
 * @version 1.0.0
 * @author SPA Platform
 */

import ControlBase from '../ControlBase.js'
import { DOM, Events, Icons, Constants, Validation } from '../../utils/controlHelpers.js'

/**
 * Типы карт по умолчанию для Yandex Maps
 */
const DEFAULT_MAP_TYPES = [
  {
    key: 'yandex#map',
    name: 'Схема',
    title: 'Схематическая карта',
    icon: 'map'
  },
  {
    key: 'yandex#satellite', 
    name: 'Спутник',
    title: 'Спутниковые снимки',
    icon: 'satellite'
  },
  {
    key: 'yandex#hybrid',
    name: 'Гибрид', 
    title: 'Спутниковые снимки с подписями',
    icon: 'hybrid'
  }
]

/**
 * Класс для управления типом карты
 * Позволяет пользователю переключаться между различными слоями карты
 */
export default class TypeSelector extends ControlBase {
  /**
   * Конструктор селектора типов карт
   * @param {Object} options - Опции контрола
   * @param {Array} [options.mapTypes] - Доступные типы карт
   * @param {string} [options.mode='dropdown'] - Режим отображения: 'dropdown' | 'buttons' | 'compact'
   * @param {boolean} [options.showLabels=true] - Показывать названия типов
   * @param {boolean} [options.showIcons=true] - Показывать иконки
   * @param {string} [options.defaultType] - Тип по умолчанию
   * @param {boolean} [options.autoDetect=true] - Автоматически определять доступные типы
   * @param {Object} [options.customTypes] - Кастомные типы карт
   * @param {boolean} [options.compactOnMobile=true] - Компактный режим на мобильных
   */
  constructor(options = {}) {
    super({
      position: 'topRight',
      mode: 'dropdown',
      showLabels: true,
      showIcons: true,
      autoDetect: true,
      compactOnMobile: true,
      mapTypes: [...DEFAULT_MAP_TYPES],
      ...options
    })

    /**
     * Текущий выбранный тип карты
     * @type {string|null}
     * @private
     */
    this._currentType = null

    /**
     * Доступные типы карт
     * @type {Array}
     * @private
     */
    this._availableTypes = []

    /**
     * DOM элементы контрола
     * @type {Object}
     * @private
     */
    this._elements = {
      container: null,
      dropdown: null,
      dropdownButton: null,
      dropdownList: null,
      buttonGroup: null,
      buttons: new Map()
    }

    /**
     * Состояние выпадающего списка
     * @type {Object}
     * @private
     */
    this._dropdownState = {
      isOpen: false,
      selectedIndex: -1
    }

    /**
     * Обработчики событий
     * @type {Array<Function>}
     * @private
     */
    this._eventUnbinders = []

    /**
     * MediaQuery для отслеживания мобильного режима
     * @type {MediaQueryList|null}
     * @private
     */
    this._mobileMediaQuery = null

    // Привязка контекста методов
    this._onDropdownToggle = this._onDropdownToggle.bind(this)
    this._onDropdownItemClick = this._onDropdownItemClick.bind(this)
    this._onButtonClick = this._onButtonClick.bind(this)
    this._onDocumentClick = this._onDocumentClick.bind(this)
    this._onKeyDown = this._onKeyDown.bind(this)
    this._onMapTypeChange = this._onMapTypeChange.bind(this)
    this._onMediaQueryChange = this._onMediaQueryChange.bind(this)
  }

  /**
   * Создание DOM структуры контрола
   * @returns {Promise<void>}
   * @protected
   */
  async _createElement() {
    try {
      // Подготавливаем доступные типы карт
      await this._prepareMapTypes()

      const mode = this._getCurrentMode()

      // Основной контейнер
      this._element = document.createElement('div')
      this._element.className = `ymaps-type-selector ymaps-type-selector--${mode}`
      
      // Создаем интерфейс в зависимости от режима
      if (mode === 'dropdown' || mode === 'compact') {
        this._createDropdownInterface()
      } else if (mode === 'buttons') {
        this._createButtonInterface()
      }

      // Устанавливаем начальный выбранный тип
      this._setInitialType()

      // Настраиваем обработчики клавиатуры для accessibility
      this._setupKeyboardHandlers()
      
    } catch (error) {
      console.error('TypeSelector._createElement:', error)
      throw new Error('Не удалось создать DOM структуру TypeSelector')
    }
  }

  /**
   * Создание выпадающего списка
   * @private
   */
  _createDropdownInterface() {
    this._elements.dropdown = document.createElement('div')
    this._elements.dropdown.className = 'ymaps-type-selector-dropdown'

    // Кнопка выпадающего списка
    this._elements.dropdownButton = DOM.createButton({
      className: 'ymaps-type-selector-dropdown-button',
      title: 'Выбрать тип карты',
      onClick: this._onDropdownToggle
    })

    // Контент кнопки будет обновлен в _updateDropdownButton
    this._elements.dropdown.appendChild(this._elements.dropdownButton)

    // Выпадающий список
    this._elements.dropdownList = document.createElement('ul')
    this._elements.dropdownList.className = 'ymaps-type-selector-dropdown-list'
    this._elements.dropdownList.setAttribute('role', 'listbox')
    this._elements.dropdownList.style.display = 'none'

    // Создаем элементы списка
    this._availableTypes.forEach((type, index) => {
      const listItem = this._createDropdownItem(type, index)
      this._elements.dropdownList.appendChild(listItem)
    })

    this._elements.dropdown.appendChild(this._elements.dropdownList)
    this._element.appendChild(this._elements.dropdown)
  }

  /**
   * Создание элемента выпадающего списка
   * @param {Object} type - Тип карты
   * @param {number} index - Индекс в списке
   * @returns {HTMLLIElement}
   * @private
   */
  _createDropdownItem(type, index) {
    const listItem = document.createElement('li')
    listItem.className = 'ymaps-type-selector-dropdown-item'
    listItem.setAttribute('role', 'option')
    listItem.setAttribute('data-type', type.key)
    listItem.setAttribute('data-index', index)
    listItem.tabIndex = -1

    const button = document.createElement('button')
    button.type = 'button'
    button.className = 'ymaps-type-selector-dropdown-item-button'
    button.title = type.title || type.name

    // Иконка
    if (this._options.showIcons && type.icon) {
      const icon = document.createElement('span')
      icon.className = `ymaps-type-selector-icon ymaps-icon-${type.icon}`
      if (Icons.get(type.icon)) {
        Icons.apply(icon, type.icon)
      }
      button.appendChild(icon)
    }

    // Текст
    if (this._options.showLabels) {
      const text = document.createElement('span')
      text.className = 'ymaps-type-selector-text'
      text.textContent = type.name
      button.appendChild(text)
    }

    // Обработчик клика
    button.addEventListener('click', (e) => {
      e.preventDefault()
      e.stopPropagation()
      this._onDropdownItemClick(type.key, index)
    })

    listItem.appendChild(button)
    return listItem
  }

  /**
   * Создание кнопочного интерфейса
   * @private
   */
  _createButtonInterface() {
    this._elements.buttonGroup = DOM.createButtonGroup({
      className: 'ymaps-type-selector-button-group',
      direction: this._options.direction || 'horizontal'
    })

    this._availableTypes.forEach(type => {
      const button = DOM.createButton({
        className: 'ymaps-type-selector-button',
        title: type.title || type.name,
        text: this._options.showLabels ? type.name : null,
        onClick: () => this._onButtonClick(type.key)
      })

      // Добавляем иконку
      if (this._options.showIcons && type.icon) {
        const icon = button.querySelector('.ymaps-control-icon')
        icon.classList.add(`ymaps-icon-${type.icon}`)
        if (Icons.get(type.icon)) {
          Icons.apply(icon, type.icon)
        }
      }

      button.setAttribute('data-type', type.key)
      this._elements.buttons.set(type.key, button)
      this._elements.buttonGroup.appendChild(button)
    })

    this._element.appendChild(this._elements.buttonGroup)
  }

  /**
   * Настройка обработчиков событий карты
   * @protected
   */
  _setupEventListeners() {
    super._setupEventListeners()

    if (!this._map) return

    try {
      // Слушаем изменения типа карты
      if (this._map.events) {
        const unbindTypeChange = Events.on(
          this._map.events,
          'typechange', 
          this._onMapTypeChange
        )
        this._eventUnbinders.push(unbindTypeChange)
      }

      // Инициализируем текущий тип
      this._updateFromMap()

      // Настраиваем MediaQuery для мобильной адаптации
      if (this._options.compactOnMobile && window.matchMedia) {
        this._mobileMediaQuery = window.matchMedia('(max-width: 768px)')
        this._mobileMediaQuery.addListener(this._onMediaQueryChange)
        this._onMediaQueryChange(this._mobileMediaQuery)
      }

      // Глобальный обработчик для закрытия выпадающего списка
      const unbindDocumentClick = Events.on(document, 'click', this._onDocumentClick)
      this._eventUnbinders.push(unbindDocumentClick)
      
    } catch (error) {
      console.error('TypeSelector._setupEventListeners:', error)
    }
  }

  /**
   * Удаление обработчиков событий
   * @protected
   */
  _removeEventListeners() {
    super._removeEventListeners()
    
    // Отвязываем все обработчики
    this._eventUnbinders.forEach(unbinder => {
      try {
        unbinder()
      } catch (error) {
        console.error('TypeSelector: ошибка отвязки обработчика:', error)
      }
    })
    this._eventUnbinders = []

    // Убираем MediaQuery listener
    if (this._mobileMediaQuery && this._mobileMediaQuery.removeListener) {
      this._mobileMediaQuery.removeListener(this._onMediaQueryChange)
      this._mobileMediaQuery = null
    }
  }

  /**
   * Получить текущий тип карты
   * @returns {string|null}
   */
  getCurrentType() {
    return this._currentType
  }

  /**
   * Установить тип карты
   * @param {string} type - Ключ типа карты
   * @returns {Promise<void>}
   */
  async setCurrentType(type) {
    if (typeof type !== 'string') {
      throw new TypeError('TypeSelector: тип карты должен быть строкой')
    }

    if (!this._isTypeAvailable(type)) {
      throw new Error(`TypeSelector: тип карты "${type}" недоступен`)
    }

    try {
      const oldType = this._currentType

      if (this._map && typeof this._map.setType === 'function') {
        await this._map.setType(type)
      }

      this._setCurrentTypeInternal(type)

      this._emitEvent('typechange', {
        oldType,
        newType: type
      })

    } catch (error) {
      console.error('TypeSelector.setCurrentType:', error)
      throw error
    }
  }

  /**
   * Получить доступные типы карт
   * @returns {Array}
   */
  getAvailableTypes() {
    return [...this._availableTypes]
  }

  /**
   * Добавить новый тип карты
   * @param {Object} typeConfig - Конфигурация типа
   * @param {string} typeConfig.key - Уникальный ключ типа
   * @param {string} typeConfig.name - Отображаемое имя
   * @param {string} [typeConfig.title] - Подсказка
   * @param {string} [typeConfig.icon] - Иконка
   * @param {number} [position] - Позиция в списке
   */
  addMapType(typeConfig, position) {
    if (!typeConfig || !typeConfig.key || !typeConfig.name) {
      throw new Error('TypeSelector: некорректная конфигурация типа карты')
    }

    if (this._isTypeAvailable(typeConfig.key)) {
      throw new Error(`TypeSelector: тип "${typeConfig.key}" уже существует`)
    }

    const type = {
      key: typeConfig.key,
      name: typeConfig.name,
      title: typeConfig.title || typeConfig.name,
      icon: typeConfig.icon || 'map'
    }

    // Добавляем в массив доступных типов
    if (typeof position === 'number' && position >= 0 && position < this._availableTypes.length) {
      this._availableTypes.splice(position, 0, type)
    } else {
      this._availableTypes.push(type)
    }

    // Пересоздаем интерфейс если контрол уже инициализирован
    if (this._initialized) {
      this._recreateInterface()
    }

    this._emitEvent('typeadd', { type })
  }

  /**
   * Удалить тип карты
   * @param {string} typeKey - Ключ типа для удаления
   */
  removeMapType(typeKey) {
    const index = this._availableTypes.findIndex(type => type.key === typeKey)
    
    if (index === -1) {
      throw new Error(`TypeSelector: тип "${typeKey}" не найден`)
    }

    if (this._availableTypes.length <= 1) {
      throw new Error('TypeSelector: нельзя удалить последний тип карты')
    }

    const removedType = this._availableTypes.splice(index, 1)[0]

    // Если удаляемый тип был выбран, переключаемся на первый доступный
    if (this._currentType === typeKey) {
      const newType = this._availableTypes[0].key
      this.setCurrentType(newType)
    }

    // Пересоздаем интерфейс
    if (this._initialized) {
      this._recreateInterface()
    }

    this._emitEvent('typeremove', { type: removedType })
  }

  // PRIVATE методы

  /**
   * Подготовка доступных типов карт
   * @private
   */
  async _prepareMapTypes() {
    const configuredTypes = this._options.mapTypes || DEFAULT_MAP_TYPES

    if (this._options.autoDetect && this._map) {
      // В реальном проекте здесь было бы определение доступных типов через API карты
      this._availableTypes = configuredTypes.filter(type => {
        // Простая проверка - все стандартные типы Yandex доступны
        return type.key.startsWith('yandex#')
      })
    } else {
      this._availableTypes = [...configuredTypes]
    }

    if (this._availableTypes.length === 0) {
      this._availableTypes = [...DEFAULT_MAP_TYPES]
    }
  }

  /**
   * Получить текущий режим отображения
   * @returns {string}
   * @private
   */
  _getCurrentMode() {
    if (this._options.compactOnMobile && this._isMobileDevice()) {
      return 'compact'
    }
    return this._options.mode || 'dropdown'
  }

  /**
   * Проверка мобильного устройства
   * @returns {boolean}
   * @private
   */
  _isMobileDevice() {
    if (this._mobileMediaQuery) {
      return this._mobileMediaQuery.matches
    }
    return window.innerWidth <= 768
  }

  /**
   * Установка начального типа
   * @private
   */
  _setInitialType() {
    let initialType = this._options.defaultType || 
                      (this._map && this._map.getType && this._map.getType()) ||
                      this._availableTypes[0]?.key

    if (!this._isTypeAvailable(initialType)) {
      initialType = this._availableTypes[0]?.key
    }

    if (initialType) {
      this._setCurrentTypeInternal(initialType)
    }
  }

  /**
   * Внутренняя установка текущего типа без вызова API карты
   * @param {string} type - Тип карты
   * @private
   */
  _setCurrentTypeInternal(type) {
    this._currentType = type
    this._updateInterfaceState()
  }

  /**
   * Обновление состояния интерфейса
   * @private
   */
  _updateInterfaceState() {
    const mode = this._getCurrentMode()

    if (mode === 'dropdown' || mode === 'compact') {
      this._updateDropdownButton()
      this._updateDropdownSelection()
    } else if (mode === 'buttons') {
      this._updateButtonSelection()
    }
  }

  /**
   * Обновление кнопки выпадающего списка
   * @private
   */
  _updateDropdownButton() {
    if (!this._elements.dropdownButton || !this._currentType) return

    const currentTypeConfig = this._availableTypes.find(t => t.key === this._currentType)
    if (!currentTypeConfig) return

    // Очищаем содержимое кнопки
    this._elements.dropdownButton.innerHTML = ''

    // Добавляем иконку
    if (this._options.showIcons && currentTypeConfig.icon) {
      const icon = document.createElement('span')
      icon.className = `ymaps-type-selector-icon ymaps-icon-${currentTypeConfig.icon}`
      if (Icons.get(currentTypeConfig.icon)) {
        Icons.apply(icon, currentTypeConfig.icon)
      }
      this._elements.dropdownButton.appendChild(icon)
    }

    // Добавляем текст (кроме компактного режима)
    if (this._options.showLabels && this._getCurrentMode() !== 'compact') {
      const text = document.createElement('span')
      text.className = 'ymaps-type-selector-text'
      text.textContent = currentTypeConfig.name
      this._elements.dropdownButton.appendChild(text)
    }

    // Добавляем стрелку выпадающего списка
    const arrow = document.createElement('span')
    arrow.className = 'ymaps-type-selector-arrow'
    arrow.innerHTML = '▼'
    this._elements.dropdownButton.appendChild(arrow)

    // Обновляем title
    this._elements.dropdownButton.title = currentTypeConfig.title || currentTypeConfig.name
  }

  /**
   * Обновление выбранного элемента в выпадающем списке
   * @private
   */
  _updateDropdownSelection() {
    if (!this._elements.dropdownList) return

    const items = this._elements.dropdownList.querySelectorAll('.ymaps-type-selector-dropdown-item')
    
    items.forEach(item => {
      const isSelected = item.getAttribute('data-type') === this._currentType
      item.classList.toggle('selected', isSelected)
      item.setAttribute('aria-selected', isSelected)
      
      if (isSelected) {
        this._dropdownState.selectedIndex = parseInt(item.getAttribute('data-index'))
      }
    })
  }

  /**
   * Обновление выбранной кнопки
   * @private
   */
  _updateButtonSelection() {
    this._elements.buttons.forEach((button, typeKey) => {
      const isSelected = typeKey === this._currentType
      button.classList.toggle('selected', isSelected)
      button.setAttribute('aria-pressed', isSelected)
    })
  }

  /**
   * Проверка доступности типа карты
   * @param {string} type - Тип карты
   * @returns {boolean}
   * @private
   */
  _isTypeAvailable(type) {
    return this._availableTypes.some(t => t.key === type)
  }

  /**
   * Обновление на основе изменений карты
   * @private
   */
  _updateFromMap() {
    if (!this._map || typeof this._map.getType !== 'function') return

    try {
      const mapType = this._map.getType()
      if (mapType && mapType !== this._currentType) {
        this._setCurrentTypeInternal(mapType)
      }
    } catch (error) {
      console.error('TypeSelector._updateFromMap:', error)
    }
  }

  /**
   * Пересоздание интерфейса
   * @private
   */
  async _recreateInterface() {
    try {
      // Сохраняем текущий тип
      const currentType = this._currentType
      
      // Очищаем старый интерфейс
      if (this._element) {
        this._element.innerHTML = ''
      }

      // Пересоздаем элементы
      this._elements = {
        container: null,
        dropdown: null,
        dropdownButton: null,
        dropdownList: null,
        buttonGroup: null,
        buttons: new Map()
      }

      // Создаем новый интерфейс
      await this._createElement()
      
      // Восстанавливаем выбранный тип
      if (currentType && this._isTypeAvailable(currentType)) {
        this._setCurrentTypeInternal(currentType)
      }

    } catch (error) {
      console.error('TypeSelector._recreateInterface:', error)
    }
  }

  /**
   * Настройка обработчиков клавиатуры
   * @private
   */
  _setupKeyboardHandlers() {
    if (this._elements.dropdownButton) {
      this._elements.dropdownButton.addEventListener('keydown', this._onKeyDown)
    }

    if (this._elements.dropdownList) {
      this._elements.dropdownList.addEventListener('keydown', this._onKeyDown)
    }
  }

  // EVENT HANDLERS

  /**
   * Обработчик переключения выпадающего списка
   * @private
   */
  _onDropdownToggle() {
    if (!this._options.enabled) return

    if (this._dropdownState.isOpen) {
      this._closeDropdown()
    } else {
      this._openDropdown()
    }
  }

  /**
   * Открытие выпадающего списка
   * @private
   */
  _openDropdown() {
    if (!this._elements.dropdownList) return

    this._elements.dropdownList.style.display = 'block'
    this._dropdownState.isOpen = true
    this._elements.dropdownButton.classList.add('open')
    this._elements.dropdownButton.setAttribute('aria-expanded', 'true')

    // Фокус на выбранном элементе
    const selectedItem = this._elements.dropdownList.querySelector('.selected')
    if (selectedItem) {
      selectedItem.focus()
    }

    this._emitEvent('dropdownopen')
  }

  /**
   * Закрытие выпадающего списка
   * @private
   */
  _closeDropdown() {
    if (!this._elements.dropdownList) return

    this._elements.dropdownList.style.display = 'none'
    this._dropdownState.isOpen = false
    this._elements.dropdownButton.classList.remove('open')
    this._elements.dropdownButton.setAttribute('aria-expanded', 'false')

    this._emitEvent('dropdownclose')
  }

  /**
   * Обработчик клика по элементу выпадающего списка
   * @param {string} typeKey - Ключ типа карты
   * @param {number} index - Индекс элемента
   * @private
   */
  async _onDropdownItemClick(typeKey, index) {
    if (!this._options.enabled) return

    try {
      await this.setCurrentType(typeKey)
      this._closeDropdown()
      
      // Возвращаем фокус на кнопку
      if (this._elements.dropdownButton) {
        this._elements.dropdownButton.focus()
      }
    } catch (error) {
      console.error('TypeSelector._onDropdownItemClick:', error)
    }
  }

  /**
   * Обработчик клика по кнопке типа
   * @param {string} typeKey - Ключ типа карты
   * @private
   */
  async _onButtonClick(typeKey) {
    if (!this._options.enabled) return

    try {
      await this.setCurrentType(typeKey)
    } catch (error) {
      console.error('TypeSelector._onButtonClick:', error)
    }
  }

  /**
   * Обработчик клика по документу (для закрытия выпадающего списка)
   * @param {Event} event - Событие клика
   * @private
   */
  _onDocumentClick(event) {
    if (this._dropdownState.isOpen && this._element) {
      const isClickInside = this._element.contains(event.target)
      if (!isClickInside) {
        this._closeDropdown()
      }
    }
  }

  /**
   * Обработчик нажатий клавиш
   * @param {KeyboardEvent} event - Событие клавиатуры
   * @private
   */
  _onKeyDown(event) {
    if (!this._options.enabled) return

    switch (event.key) {
      case 'Enter':
      case ' ':
        if (event.target === this._elements.dropdownButton) {
          event.preventDefault()
          this._onDropdownToggle()
        }
        break

      case 'Escape':
        if (this._dropdownState.isOpen) {
          event.preventDefault()
          this._closeDropdown()
          this._elements.dropdownButton.focus()
        }
        break

      case 'ArrowDown':
      case 'ArrowUp':
        if (this._elements.dropdownList && this._dropdownState.isOpen) {
          event.preventDefault()
          this._navigateDropdown(event.key === 'ArrowDown' ? 1 : -1)
        }
        break
    }
  }

  /**
   * Навигация по выпадающему списку с клавиатуры
   * @param {number} direction - Направление: 1 (вниз) или -1 (вверх)
   * @private
   */
  _navigateDropdown(direction) {
    const items = this._elements.dropdownList.querySelectorAll('.ymaps-type-selector-dropdown-item')
    let newIndex = this._dropdownState.selectedIndex + direction

    // Циклическая навигация
    if (newIndex < 0) {
      newIndex = items.length - 1
    } else if (newIndex >= items.length) {
      newIndex = 0
    }

    const targetItem = items[newIndex]
    if (targetItem) {
      targetItem.focus()
      this._dropdownState.selectedIndex = newIndex
    }
  }

  /**
   * Обработчик изменения типа карты
   * @param {Object} event - Событие карты
   * @private
   */
  _onMapTypeChange(event) {
    try {
      if (this._map && typeof this._map.getType === 'function') {
        const newType = this._map.getType()
        if (newType && newType !== this._currentType) {
          this._setCurrentTypeInternal(newType)
        }
      }
    } catch (error) {
      console.error('TypeSelector._onMapTypeChange:', error)
    }
  }

  /**
   * Обработчик изменения MediaQuery
   * @param {MediaQueryList} mq - MediaQuery объект
   * @private
   */
  _onMediaQueryChange(mq) {
    // Пересоздаем интерфейс при изменении режима
    if (this._initialized) {
      this._recreateInterface()
    }
  }

  /**
   * Обработчик уничтожения контрола
   * @protected
   */
  _onDestroy() {
    super._onDestroy()

    // Закрываем выпадающий список
    if (this._dropdownState.isOpen) {
      this._closeDropdown()
    }

    // Очищаем коллекции
    this._elements.buttons.clear()
    this._availableTypes = []
  }
}

// Специфичные CSS стили для TypeSelector
const TYPE_SELECTOR_STYLES = `
  .ymaps-type-selector {
    display: inline-block;
  }

  /* Выпадающий список */
  .ymaps-type-selector-dropdown {
    position: relative;
  }

  .ymaps-type-selector-dropdown-button {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    min-width: 120px;
    text-align: left;
    white-space: nowrap;
  }

  .ymaps-type-selector-dropdown-button.open {
    background: #f0f0f0;
  }

  .ymaps-type-selector-arrow {
    margin-left: auto;
    transition: transform 0.2s ease;
    font-size: 10px;
    opacity: 0.7;
  }

  .ymaps-type-selector-dropdown-button.open .ymaps-type-selector-arrow {
    transform: rotate(180deg);
  }

  .ymaps-type-selector-dropdown-list {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 0 0 3px 3px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    z-index: 1001;
    margin: 0;
    padding: 0;
    list-style: none;
    max-height: 200px;
    overflow-y: auto;
  }

  .ymaps-type-selector-dropdown-item {
    margin: 0;
    padding: 0;
  }

  .ymaps-type-selector-dropdown-item-button {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border: none;
    background: transparent;
    text-align: left;
    cursor: pointer;
    transition: background 0.2s ease;
  }

  .ymaps-type-selector-dropdown-item-button:hover,
  .ymaps-type-selector-dropdown-item:focus-within .ymaps-type-selector-dropdown-item-button {
    background: #f5f5f5;
  }

  .ymaps-type-selector-dropdown-item.selected .ymaps-type-selector-dropdown-item-button {
    background: #e3f2fd;
    color: #1976d2;
  }

  /* Кнопочный режим */
  .ymaps-type-selector-button-group {
    background: rgba(255, 255, 255, 0.9);
  }

  .ymaps-type-selector-button {
    min-width: 80px;
    text-align: center;
  }

  .ymaps-type-selector-button.selected {
    background: #1976d2;
    color: white;
  }

  .ymaps-type-selector-button.selected:hover {
    background: #1565c0;
  }

  /* Иконки */
  .ymaps-type-selector-icon {
    width: 16px;
    height: 16px;
    flex-shrink: 0;
  }

  .ymaps-type-selector-text {
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  /* Компактный режим */
  .ymaps-type-selector--compact .ymaps-type-selector-dropdown-button {
    min-width: 44px;
    padding: 10px;
    justify-content: center;
  }

  .ymaps-type-selector--compact .ymaps-type-selector-text {
    display: none;
  }

  .ymaps-type-selector--compact .ymaps-type-selector-arrow {
    display: none;
  }

  /* Адаптивность */
  @media (max-width: 768px) {
    .ymaps-type-selector-dropdown-button {
      min-width: 44px;
      padding: 12px;
    }

    .ymaps-type-selector-button {
      min-width: 44px;
      min-height: 44px;
    }

    .ymaps-type-selector-dropdown-item-button {
      padding: 12px;
      font-size: 16px;
    }
  }

  /* Accessibility */
  .ymaps-type-selector-dropdown-button:focus,
  .ymaps-type-selector-button:focus,
  .ymaps-type-selector-dropdown-item-button:focus {
    outline: 2px solid #1976d2;
    outline-offset: 2px;
  }

  @media (prefers-reduced-motion: reduce) {
    .ymaps-type-selector-arrow,
    .ymaps-type-selector-dropdown-item-button {
      transition: none;
    }
  }
`

// Инъекция стилей в документ
if (typeof document !== 'undefined') {
  if (!document.getElementById('ymaps-type-selector-styles')) {
    const styleElement = document.createElement('style')
    styleElement.id = 'ymaps-type-selector-styles'
    styleElement.textContent = TYPE_SELECTOR_STYLES
    document.head.appendChild(styleElement)
  }
}