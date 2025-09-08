/**
 * SearchControl - Контрол поиска для Yandex Maps
 * Обеспечивает поиск по адресам, POI и координатам с автодополнением
 * 
 * @version 1.0.0
 * @author SPA Platform
 * @created 2025-09-04
 */

import ControlBase from '../ControlBase.js'
import { DOM, Position, Events, Accessibility } from '../../utils/controlHelpers.js'

/**
 * Класс контрола поиска
 * Предоставляет интерфейс для поиска мест, адресов и координат на карте
 */
export default class SearchControl extends ControlBase {
  /**
   * Создает экземпляр контрола поиска
   * @param {Object} options - Опции контрола
   * @param {string} [options.placeholder='Поиск места...'] - Плейсхолдер для поля ввода
   * @param {boolean} [options.showClearButton=true] - Показать кнопку очистки
   * @param {boolean} [options.showSearchButton=true] - Показать кнопку поиска
   * @param {boolean} [options.enableAutoComplete=true] - Включить автодополнение
   * @param {number} [options.searchDelay=300] - Задержка поиска в мс
   * @param {number} [options.maxResults=10] - Максимальное количество результатов
   * @param {Array<string>} [options.searchTypes=['text', 'geo', 'biz']] - Типы поиска
   * @param {Object} [options.searchOptions] - Дополнительные опции поиска
   * @param {boolean} [options.fitResultBounds=true] - Подстраивать границы под результаты
   * @param {boolean} [options.addResultMarker=true] - Добавлять маркер результата
   * @param {string} [options.resultsContainer] - Селектор контейнера для результатов
   * @param {Function} [options.formatResult] - Функция форматирования результата
   * @param {Function} [options.filterResults] - Функция фильтрации результатов
   */
  constructor(options = {}) {
    super({
      position: 'topLeft',
      zIndex: 1000,
      className: 'ymaps-search-control',
      ...options
    })

    // Настройки поиска
    this._placeholder = options.placeholder || 'Поиск места...'
    this._showClearButton = options.showClearButton !== false
    this._showSearchButton = options.showSearchButton !== false
    this._enableAutoComplete = options.enableAutoComplete !== false
    this._searchDelay = Math.max(100, options.searchDelay || 300)
    this._maxResults = Math.min(50, Math.max(1, options.maxResults || 10))
    
    // Типы поиска по умолчанию
    this._searchTypes = options.searchTypes || ['text', 'geo', 'biz']
    this._searchOptions = {
      results: this._maxResults,
      skip: 0,
      ...options.searchOptions
    }

    // Поведение результатов
    this._fitResultBounds = options.fitResultBounds !== false
    this._addResultMarker = options.addResultMarker !== false
    this._resultsContainer = options.resultsContainer
    this._formatResult = options.formatResult
    this._filterResults = options.filterResults

    // Внутренние состояния
    this._searchValue = ''
    this._isSearching = false
    this._searchResults = []
    this._selectedIndex = -1
    this._currentQuery = null
    this._searchTimeout = null
    this._resultMarker = null
    this._lastSearchBounds = null
    
    // DOM элементы
    this._elements = {
      container: null,
      input: null,
      clearButton: null,
      searchButton: null,
      loadingIndicator: null,
      resultsDropdown: null,
      resultsList: null,
      noResults: null
    }

    // Yandex API объекты
    this._geocoder = null
    this._suggest = null

    this._bindMethods()
  }

  /**
   * Привязывает методы к контексту
   * @private
   */
  _bindMethods() {
    this._handleInputChange = this._handleInputChange.bind(this)
    this._handleInputKeyDown = this._handleInputKeyDown.bind(this)
    this._handleInputFocus = this._handleInputFocus.bind(this)
    this._handleInputBlur = this._handleInputBlur.bind(this)
    this._handleClearClick = this._handleClearClick.bind(this)
    this._handleSearchClick = this._handleSearchClick.bind(this)
    this._handleResultClick = this._handleResultClick.bind(this)
    this._handleResultHover = this._handleResultHover.bind(this)
    this._handleDocumentClick = this._handleDocumentClick.bind(this)
    this._performSearch = this._performSearch.bind(this)
  }

  /**
   * Создает DOM структуру контрола
   * @returns {HTMLElement} Корневой элемент
   * @protected
   */
  _createElement() {
    const container = DOM.createElement('div', {
      className: `${this._options.className} ymaps-search-control-container`,
      'aria-label': 'Поиск на карте'
    })

    // Основная группа элементов поиска
    const searchGroup = DOM.createElement('div', {
      className: 'ymaps-search-control-group'
    })

    // Поле ввода
    this._elements.input = DOM.createElement('input', {
      type: 'text',
      className: 'ymaps-search-control-input',
      placeholder: this._placeholder,
      autocomplete: 'off',
      spellcheck: 'false',
      'aria-label': 'Введите адрес или название места',
      'aria-expanded': 'false',
      'aria-haspopup': 'listbox',
      'aria-autocomplete': 'list'
    })

    // Кнопка очистки
    if (this._showClearButton) {
      this._elements.clearButton = DOM.createElement('button', {
        className: 'ymaps-search-control-clear',
        type: 'button',
        'aria-label': 'Очистить поиск',
        title: 'Очистить поиск'
      })
      this._elements.clearButton.innerHTML = '✕'
    }

    // Кнопка поиска
    if (this._showSearchButton) {
      this._elements.searchButton = DOM.createElement('button', {
        className: 'ymaps-search-control-search',
        type: 'button',
        'aria-label': 'Найти',
        title: 'Найти'
      })
      this._elements.searchButton.innerHTML = '🔍'
    }

    // Индикатор загрузки
    this._elements.loadingIndicator = DOM.createElement('div', {
      className: 'ymaps-search-control-loading'
    })
    this._elements.loadingIndicator.innerHTML = 
      '<div class="ymaps-search-loading-spinner"></div>'

    // Собираем группу поиска
    searchGroup.appendChild(this._elements.input)
    if (this._elements.clearButton) {
      searchGroup.appendChild(this._elements.clearButton)
    }
    if (this._elements.searchButton) {
      searchGroup.appendChild(this._elements.searchButton)
    }
    searchGroup.appendChild(this._elements.loadingIndicator)

    // Контейнер результатов
    this._elements.resultsDropdown = DOM.createElement('div', {
      className: 'ymaps-search-control-results',
      role: 'listbox',
      'aria-label': 'Результаты поиска'
    })

    // Список результатов
    this._elements.resultsList = DOM.createElement('ul', {
      className: 'ymaps-search-results-list',
      role: 'group'
    })

    // Сообщение об отсутствии результатов
    this._elements.noResults = DOM.createElement('div', {
      className: 'ymaps-search-no-results'
    })
    this._elements.noResults.textContent = 'Ничего не найдено'

    this._elements.resultsDropdown.appendChild(this._elements.resultsList)
    this._elements.resultsDropdown.appendChild(this._elements.noResults)

    // Собираем контейнер
    container.appendChild(searchGroup)
    container.appendChild(this._elements.resultsDropdown)

    this._elements.container = container
    this._attachEventListeners()
    this._updateVisualState()

    return container
  }

  /**
   * Подключает обработчики событий
   * @private
   */
  _attachEventListeners() {
    if (!this._elements.input) return

    // События поля ввода
    this._elements.input.addEventListener('input', this._handleInputChange)
    this._elements.input.addEventListener('keydown', this._handleInputKeyDown)
    this._elements.input.addEventListener('focus', this._handleInputFocus)
    this._elements.input.addEventListener('blur', this._handleInputBlur)

    // События кнопок
    if (this._elements.clearButton) {
      this._elements.clearButton.addEventListener('click', this._handleClearClick)
    }
    if (this._elements.searchButton) {
      this._elements.searchButton.addEventListener('click', this._handleSearchClick)
    }

    // Глобальные события
    document.addEventListener('click', this._handleDocumentClick)
  }

  /**
   * Отключает обработчики событий
   * @private
   */
  _detachEventListeners() {
    if (!this._elements.input) return

    this._elements.input.removeEventListener('input', this._handleInputChange)
    this._elements.input.removeEventListener('keydown', this._handleInputKeyDown)
    this._elements.input.removeEventListener('focus', this._handleInputFocus)
    this._elements.input.removeEventListener('blur', this._handleInputBlur)

    if (this._elements.clearButton) {
      this._elements.clearButton.removeEventListener('click', this._handleClearClick)
    }
    if (this._elements.searchButton) {
      this._elements.searchButton.removeEventListener('click', this._handleSearchClick)
    }

    document.removeEventListener('click', this._handleDocumentClick)
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

        // Инициализируем геокодер
        this._geocoder = window.ymaps.geocode
        
        // Инициализируем саджест (если доступен)
        if (window.ymaps.suggest) {
          this._suggest = window.ymaps.suggest
        }

        this._emit('apiready', { geocoder: !!this._geocoder, suggest: !!this._suggest })
      } else {
        throw new Error('Yandex Maps API недоступен')
      }
    } catch (error) {
      console.error('[SearchControl] Ошибка инициализации API:', error)
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
      this._emit('ready', { control: this })
    } catch (error) {
      console.error('[SearchControl] Ошибка добавления на карту:', error)
      this._emit('error', { error, operation: 'addToMap' })
      throw error
    }
  }

  // === ОБРАБОТЧИКИ СОБЫТИЙ ===

  /**
   * Обработчик изменения текста в поле ввода
   * @param {Event} event
   * @private
   */
  _handleInputChange(event) {
    const value = event.target.value.trim()
    this._searchValue = value

    // Очистка предыдущего таймера
    if (this._searchTimeout) {
      clearTimeout(this._searchTimeout)
    }

    // Показываем/скрываем кнопку очистки
    this._updateVisualState()

    if (value.length === 0) {
      this._clearResults()
      return
    }

    // Автодополнение с задержкой
    if (this._enableAutoComplete && value.length >= 2) {
      this._searchTimeout = setTimeout(() => {
        this._performAutoComplete(value)
      }, this._searchDelay)
    }

    this._emit('inputchange', { value })
  }

  /**
   * Обработчик нажатий клавиш в поле ввода
   * @param {KeyboardEvent} event
   * @private
   */
  _handleInputKeyDown(event) {
    switch (event.key) {
      case 'Enter':
        event.preventDefault()
        if (this._selectedIndex >= 0) {
          this._selectResult(this._selectedIndex)
        } else if (this._searchValue.trim()) {
          this._performSearch(this._searchValue.trim())
        }
        break

      case 'ArrowDown':
        event.preventDefault()
        this._navigateResults(1)
        break

      case 'ArrowUp':
        event.preventDefault()
        this._navigateResults(-1)
        break

      case 'Escape':
        event.preventDefault()
        this._closeResults()
        this._elements.input.blur()
        break

      case 'Tab':
        if (this._selectedIndex >= 0) {
          event.preventDefault()
          this._selectResult(this._selectedIndex)
        }
        break
    }
  }

  /**
   * Обработчик фокуса поля ввода
   * @private
   */
  _handleInputFocus() {
    if (this._searchResults.length > 0) {
      this._showResults()
    }
    this._emit('focus')
  }

  /**
   * Обработчик потери фокуса поля ввода
   * @private
   */
  _handleInputBlur() {
    // Небольшая задержка для обработки кликов по результатам
    setTimeout(() => {
      this._closeResults()
    }, 150)
    this._emit('blur')
  }

  /**
   * Обработчик клика по кнопке очистки
   * @private
   */
  _handleClearClick() {
    this._clearSearch()
    this._elements.input.focus()
    this._emit('clear')
  }

  /**
   * Обработчик клика по кнопке поиска
   * @private
   */
  _handleSearchClick() {
    const query = this._searchValue.trim()
    if (query) {
      this._performSearch(query)
    }
  }

  /**
   * Обработчик клика по результату поиска
   * @param {Event} event
   * @private
   */
  _handleResultClick(event) {
    const resultElement = event.currentTarget
    const index = parseInt(resultElement.dataset.index, 10)
    if (index >= 0) {
      this._selectResult(index)
    }
  }

  /**
   * Обработчик наведения на результат
   * @param {Event} event
   * @private
   */
  _handleResultHover(event) {
    const resultElement = event.currentTarget
    const index = parseInt(resultElement.dataset.index, 10)
    this._setSelectedIndex(index)
  }

  /**
   * Обработчик кликов вне контрола
   * @param {Event} event
   * @private
   */
  _handleDocumentClick(event) {
    if (this._elements.container && !this._elements.container.contains(event.target)) {
      this._closeResults()
    }
  }

  // === МЕТОДЫ ПОИСКА ===

  /**
   * Выполняет автодополнение для введенного текста
   * @param {string} query - Поисковый запрос
   * @private
   */
  async _performAutoComplete(query) {
    if (!this._suggest || this._isSearching) return

    try {
      this._setSearchingState(true)

      const suggestions = await this._suggest(query, {
        results: this._maxResults,
        ...this._searchOptions
      })

      if (suggestions && suggestions.length > 0) {
        this._updateResults(suggestions.map((suggestion, index) => ({
          index,
          type: 'suggestion',
          displayName: suggestion.displayName,
          value: suggestion.value,
          data: suggestion
        })))
      } else {
        this._updateResults([])
      }

    } catch (error) {
      console.error('[SearchControl] Ошибка автодополнения:', error)
      this._emit('error', { error, operation: 'autocomplete', query })
    } finally {
      this._setSearchingState(false)
    }
  }

  /**
   * Выполняет полный поиск по запросу
   * @param {string} query - Поисковый запрос
   * @returns {Promise<Array>} Результаты поиска
   */
  async _performSearch(query) {
    if (!this._geocoder || this._isSearching) return []

    try {
      this._setSearchingState(true)
      this._currentQuery = query

      // Определяем границы поиска
      const searchBounds = this._map ? this._map.getBounds() : null

      // Опции поиска
      const searchOptions = {
        results: this._maxResults,
        skip: 0,
        boundedBy: searchBounds,
        strictBounds: false,
        ...this._searchOptions
      }

      // Выполняем геокодирование
      const geocodeResult = await this._geocoder(query, searchOptions)
      const geoObjects = geocodeResult.geoObjects

      const results = []
      
      // Обрабатываем результаты
      geoObjects.each((geoObject, index) => {
        const geometry = geoObject.geometry
        const properties = geoObject.properties

        const result = {
          index,
          type: 'geocode',
          displayName: properties.get('name') || properties.get('text'),
          description: properties.get('description'),
          coordinates: geometry.getCoordinates(),
          bounds: geometry.getBounds && geometry.getBounds(),
          address: properties.get('text'),
          kind: properties.get('metaDataProperty.GeocoderMetaData.kind'),
          precision: properties.get('metaDataProperty.GeocoderMetaData.precision'),
          geoObject: geoObject,
          data: {
            name: properties.get('name'),
            text: properties.get('text'),
            description: properties.get('description'),
            balloonContent: properties.get('balloonContent'),
            hintContent: properties.get('hintContent')
          }
        }

        // Применяем пользовательскую фильтрацию
        if (!this._filterResults || this._filterResults(result)) {
          results.push(result)
        }
      })

      this._updateResults(results)
      this._emit('searchcomplete', { query, results, total: results.length })
      
      return results

    } catch (error) {
      console.error('[SearchControl] Ошибка поиска:', error)
      this._emit('error', { error, operation: 'search', query })
      return []
    } finally {
      this._setSearchingState(false)
    }
  }

  // === УПРАВЛЕНИЕ РЕЗУЛЬТАТАМИ ===

  /**
   * Обновляет отображение результатов поиска
   * @param {Array} results - Результаты поиска
   * @private
   */
  _updateResults(results) {
    this._searchResults = results
    this._selectedIndex = -1

    if (!this._elements.resultsList) return

    // Очищаем список
    this._elements.resultsList.innerHTML = ''

    if (results.length === 0) {
      this._elements.resultsDropdown.classList.add('ymaps-search-control-results--empty')
      this._elements.input.setAttribute('aria-expanded', 'false')
      return
    }

    // Создаем элементы результатов
    results.forEach((result, index) => {
      const item = this._createResultItem(result, index)
      this._elements.resultsList.appendChild(item)
    })

    this._elements.resultsDropdown.classList.remove('ymaps-search-control-results--empty')
    this._showResults()
  }

  /**
   * Создает DOM элемент результата поиска
   * @param {Object} result - Данные результата
   * @param {number} index - Индекс результата
   * @returns {HTMLElement}
   * @private
   */
  _createResultItem(result, index) {
    const item = DOM.createElement('li', {
      className: 'ymaps-search-result-item',
      role: 'option',
      'data-index': index
    })

    // Пользовательское форматирование
    if (this._formatResult) {
      const customContent = this._formatResult(result)
      if (typeof customContent === 'string') {
        item.innerHTML = customContent
      } else if (customContent instanceof HTMLElement) {
        item.appendChild(customContent)
      }
    } else {
      // Стандартное форматирование
      const name = DOM.createElement('div', {
        className: 'ymaps-search-result-name'
      })
      name.textContent = result.displayName || result.value || 'Неизвестное место'

      const description = DOM.createElement('div', {
        className: 'ymaps-search-result-description'
      })
      description.textContent = result.description || result.address || ''

      item.appendChild(name)
      if (description.textContent) {
        item.appendChild(description)
      }
    }

    // События
    item.addEventListener('click', this._handleResultClick)
    item.addEventListener('mouseenter', this._handleResultHover)

    return item
  }

  /**
   * Показывает результаты поиска
   * @private
   */
  _showResults() {
    if (this._searchResults.length > 0) {
      this._elements.resultsDropdown.classList.add('ymaps-search-control-results--visible')
      this._elements.input.setAttribute('aria-expanded', 'true')
    }
  }

  /**
   * Скрывает результаты поиска
   * @private
   */
  _closeResults() {
    this._elements.resultsDropdown.classList.remove('ymaps-search-control-results--visible')
    this._elements.input.setAttribute('aria-expanded', 'false')
    this._selectedIndex = -1
    this._updateResultsSelection()
  }

  /**
   * Очищает результаты поиска
   * @private
   */
  _clearResults() {
    this._searchResults = []
    this._selectedIndex = -1
    if (this._elements.resultsList) {
      this._elements.resultsList.innerHTML = ''
    }
    this._closeResults()
  }

  /**
   * Навигация по результатам с клавиатуры
   * @param {number} direction - Направление (1 - вниз, -1 - вверх)
   * @private
   */
  _navigateResults(direction) {
    if (this._searchResults.length === 0) return

    const newIndex = this._selectedIndex + direction

    if (newIndex < -1) {
      this._setSelectedIndex(this._searchResults.length - 1)
    } else if (newIndex >= this._searchResults.length) {
      this._setSelectedIndex(-1)
    } else {
      this._setSelectedIndex(newIndex)
    }

    this._showResults()
  }

  /**
   * Устанавливает выбранный индекс результата
   * @param {number} index - Индекс результата
   * @private
   */
  _setSelectedIndex(index) {
    this._selectedIndex = index
    this._updateResultsSelection()

    // Обновляем ARIA атрибуты
    if (index >= 0 && this._searchResults[index]) {
      this._elements.input.setAttribute('aria-activedescendant', `search-result-${index}`)
    } else {
      this._elements.input.removeAttribute('aria-activedescendant')
    }
  }

  /**
   * Обновляет визуальное выделение результатов
   * @private
   */
  _updateResultsSelection() {
    const items = this._elements.resultsList.querySelectorAll('.ymaps-search-result-item')
    
    items.forEach((item, index) => {
      item.classList.toggle('ymaps-search-result-item--selected', index === this._selectedIndex)
      item.setAttribute('aria-selected', index === this._selectedIndex ? 'true' : 'false')
      
      if (index === this._selectedIndex) {
        item.scrollIntoView({ block: 'nearest', behavior: 'smooth' })
      }
    })
  }

  /**
   * Выбирает результат поиска
   * @param {number} index - Индекс результата
   * @private
   */
  async _selectResult(index) {
    if (index < 0 || index >= this._searchResults.length) return

    const result = this._searchResults[index]
    
    try {
      // Обновляем поле ввода
      this._searchValue = result.displayName || result.value || ''
      this._elements.input.value = this._searchValue

      // Закрываем результаты
      this._closeResults()

      // Обрабатываем результат на карте
      if (result.coordinates && this._map) {
        await this._processSelectedResult(result)
      }

      this._emit('resultselect', { result, index })

    } catch (error) {
      console.error('[SearchControl] Ошибка при выборе результата:', error)
      this._emit('error', { error, operation: 'selectResult', result })
    }
  }

  /**
   * Обрабатывает выбранный результат на карте
   * @param {Object} result - Выбранный результат
   * @private
   */
  async _processSelectedResult(result) {
    if (!this._map || !result.coordinates) return

    try {
      // Удаляем предыдущий маркер
      if (this._resultMarker) {
        this._map.geoObjects.remove(this._resultMarker)
        this._resultMarker = null
      }

      // Добавляем маркер результата
      if (this._addResultMarker && window.ymaps) {
        this._resultMarker = new window.ymaps.Placemark(result.coordinates, {
          hintContent: result.displayName,
          balloonContent: result.description || result.address
        }, {
          preset: 'islands#redDotIcon'
        })

        this._map.geoObjects.add(this._resultMarker)
      }

      // Подстраиваем границы карты
      if (this._fitResultBounds) {
        if (result.bounds && result.bounds.length === 2) {
          this._map.setBounds(result.bounds, {
            checkZoomRange: true,
            margin: [50, 50, 50, 50]
          })
        } else {
          this._map.setCenter(result.coordinates, 16, {
            duration: 300
          })
        }
      }

      this._lastSearchBounds = result.bounds
      this._emit('resultprocessed', { result, marker: this._resultMarker })

    } catch (error) {
      console.error('[SearchControl] Ошибка обработки результата:', error)
      this._emit('error', { error, operation: 'processResult', result })
    }
  }

  // === УТИЛИТЫ ===

  /**
   * Устанавливает состояние поиска
   * @param {boolean} isSearching - Флаг состояния поиска
   * @private
   */
  _setSearchingState(isSearching) {
    this._isSearching = isSearching
    this._updateVisualState()
    this._emit(isSearching ? 'searchstart' : 'searchend')
  }

  /**
   * Обновляет визуальное состояние контрола
   * @private
   */
  _updateVisualState() {
    if (!this._elements.container) return

    const hasValue = this._searchValue.length > 0

    // Состояние поиска
    this._elements.container.classList.toggle('ymaps-search-control--searching', this._isSearching)
    this._elements.container.classList.toggle('ymaps-search-control--has-value', hasValue)

    // Видимость кнопки очистки
    if (this._elements.clearButton) {
      this._elements.clearButton.style.display = hasValue ? 'block' : 'none'
    }

    // Состояние индикатора загрузки
    if (this._elements.loadingIndicator) {
      this._elements.loadingIndicator.style.display = this._isSearching ? 'block' : 'none'
    }

    // Состояние кнопки поиска
    if (this._elements.searchButton) {
      this._elements.searchButton.disabled = this._isSearching
    }
  }

  /**
   * Очищает поиск полностью
   * @private
   */
  _clearSearch() {
    this._searchValue = ''
    this._elements.input.value = ''
    this._clearResults()
    this._updateVisualState()

    // Удаляем маркер результата
    if (this._resultMarker && this._map) {
      this._map.geoObjects.remove(this._resultMarker)
      this._resultMarker = null
    }

    // Очищаем таймер
    if (this._searchTimeout) {
      clearTimeout(this._searchTimeout)
      this._searchTimeout = null
    }
  }

  // === ПУБЛИЧНЫЕ МЕТОДЫ ===

  /**
   * Устанавливает текст поиска программно
   * @param {string} query - Поисковый запрос
   * @param {boolean} [triggerSearch=false] - Запустить поиск автоматически
   */
  setQuery(query, triggerSearch = false) {
    if (typeof query !== 'string') {
      throw new TypeError('Query должен быть строкой')
    }

    this._searchValue = query
    if (this._elements.input) {
      this._elements.input.value = query
    }

    this._updateVisualState()

    if (triggerSearch && query.trim()) {
      this._performSearch(query.trim())
    }

    this._emit('querychange', { query, triggerSearch })
  }

  /**
   * Получает текущий поисковый запрос
   * @returns {string} Текущий запрос
   */
  getQuery() {
    return this._searchValue
  }

  /**
   * Запускает поиск по текущему запросу
   * @returns {Promise<Array>} Результаты поиска
   */
  async search() {
    const query = this._searchValue.trim()
    if (!query) {
      throw new Error('Поисковый запрос не может быть пустым')
    }

    return await this._performSearch(query)
  }

  /**
   * Очищает поиск и результаты
   */
  clear() {
    this._clearSearch()
    this._emit('clear')
  }

  /**
   * Получает результаты последнего поиска
   * @returns {Array} Массив результатов
   */
  getResults() {
    return [...this._searchResults]
  }

  /**
   * Получает выбранный результат
   * @returns {Object|null} Выбранный результат или null
   */
  getSelectedResult() {
    if (this._selectedIndex >= 0 && this._selectedIndex < this._searchResults.length) {
      return this._searchResults[this._selectedIndex]
    }
    return null
  }

  /**
   * Фокус на поле поиска
   */
  focus() {
    if (this._elements.input) {
      this._elements.input.focus()
    }
  }

  /**
   * Снимает фокус с поля поиска
   */
  blur() {
    if (this._elements.input) {
      this._elements.input.blur()
    }
  }

  /**
   * Устанавливает плейсхолдер поля ввода
   * @param {string} placeholder - Текст плейсхолдера
   */
  setPlaceholder(placeholder) {
    if (typeof placeholder !== 'string') {
      throw new TypeError('Placeholder должен быть строкой')
    }

    this._placeholder = placeholder
    if (this._elements.input) {
      this._elements.input.placeholder = placeholder
    }
  }

  /**
   * Получает текущий плейсхолдер
   * @returns {string} Текст плейсхолдера
   */
  getPlaceholder() {
    return this._placeholder
  }

  /**
   * Устанавливает максимальное количество результатов
   * @param {number} maxResults - Максимальное количество результатов
   */
  setMaxResults(maxResults) {
    if (!Number.isInteger(maxResults) || maxResults < 1 || maxResults > 50) {
      throw new Error('maxResults должно быть целым числом от 1 до 50')
    }

    this._maxResults = maxResults
    this._searchOptions.results = maxResults
  }

  /**
   * Получает максимальное количество результатов
   * @returns {number} Максимальное количество результатов
   */
  getMaxResults() {
    return this._maxResults
  }

  /**
   * Включает/выключает автодополнение
   * @param {boolean} enabled - Включить автодополнение
   */
  setAutoComplete(enabled) {
    this._enableAutoComplete = Boolean(enabled)
  }

  /**
   * Проверяет включено ли автодополнение
   * @returns {boolean} Состояние автодополнения
   */
  isAutoCompleteEnabled() {
    return this._enableAutoComplete
  }

  /**
   * Устанавливает задержку поиска
   * @param {number} delay - Задержка в миллисекундах
   */
  setSearchDelay(delay) {
    if (!Number.isInteger(delay) || delay < 0) {
      throw new Error('Delay должна быть неотрицательным целым числом')
    }

    this._searchDelay = Math.max(100, delay)
  }

  /**
   * Получает задержку поиска
   * @returns {number} Задержка в миллисекундах
   */
  getSearchDelay() {
    return this._searchDelay
  }

  /**
   * Проверяет идет ли поиск в данный момент
   * @returns {boolean} Состояние поиска
   */
  isSearching() {
    return this._isSearching
  }

  /**
   * Получает маркер последнего выбранного результата
   * @returns {ymaps.Placemark|null} Маркер или null
   */
  getResultMarker() {
    return this._resultMarker
  }

  /**
   * Получает границы последнего результата поиска
   * @returns {Array|null} Границы или null
   */
  getLastSearchBounds() {
    return this._lastSearchBounds
  }

  /**
   * Переопределение метода уничтожения
   */
  destroy() {
    // Очищаем таймеры
    if (this._searchTimeout) {
      clearTimeout(this._searchTimeout)
      this._searchTimeout = null
    }

    // Удаляем маркер
    if (this._resultMarker && this._map) {
      this._map.geoObjects.remove(this._resultMarker)
      this._resultMarker = null
    }

    // Отключаем события
    this._detachEventListeners()

    // Очищаем ссылки
    this._geocoder = null
    this._suggest = null
    this._elements = {}
    this._searchResults = []

    super.destroy()
  }
}

// Экспорт дополнительных утилит
export const SearchControlUtils = {
  /**
   * Проверяет корректность координат
   * @param {Array} coordinates - Координаты [lat, lng]
   * @returns {boolean} Результат проверки
   */
  isValidCoordinates(coordinates) {
    return Array.isArray(coordinates) &&
           coordinates.length === 2 &&
           typeof coordinates[0] === 'number' &&
           typeof coordinates[1] === 'number' &&
           coordinates[0] >= -90 && coordinates[0] <= 90 &&
           coordinates[1] >= -180 && coordinates[1] <= 180
  },

  /**
   * Форматирует адрес для отображения
   * @param {Object} result - Результат поиска
   * @returns {string} Отформатированный адрес
   */
  formatAddress(result) {
    if (!result) return ''
    
    const parts = []
    if (result.displayName) parts.push(result.displayName)
    if (result.description && result.description !== result.displayName) {
      parts.push(result.description)
    }
    
    return parts.join(', ')
  },

  /**
   * Определяет тип результата поиска
   * @param {Object} result - Результат поиска
   * @returns {string} Тип результата
   */
  getResultType(result) {
    if (!result) return 'unknown'
    
    if (result.kind) return result.kind
    if (result.type) return result.type
    
    // Пытаемся определить по данным
    if (result.coordinates) return 'geocode'
    if (result.value) return 'suggestion'
    
    return 'unknown'
  }
}