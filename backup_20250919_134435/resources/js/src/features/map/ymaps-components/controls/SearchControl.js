/**
 * SearchControl - Упрощенный контрол поиска для Yandex Maps
 * Адаптированный для использования в SPA Platform
 * 
 * @version 1.0.0
 * @author SPA Platform
 */

/**
 * Класс контрола поиска
 */
export default class SearchControl {
  constructor(options = {}) {
    this.options = {
      placeholder: 'Поиск места...',
      maxResults: 10,
      searchDelay: 300,
      fitResultBounds: true,
      addResultMarker: true,
      ...options
    }

    // Внутренние состояния
    this.map = null
    this.isSearching = false
    this.searchResults = []
    this.currentMarker = null
    this.searchTimeout = null
    
    // DOM элементы
    this.container = null
    this.input = null
    this.resultsList = null

    this._bindMethods()
  }

  _bindMethods() {
    this.search = this.search.bind(this)
    this.clear = this.clear.bind(this)
    this._handleInputChange = this._handleInputChange.bind(this)
    this._handleResultClick = this._handleResultClick.bind(this)
  }

  /**
   * Добавляет контрол на карту
   */
  async addToMap(map) {
    this.map = map
    
    // Проверяем что API загружено
    if (!window.ymaps) {
      throw new Error('Yandex Maps API не загружен')
    }

    this._createElement()
    this._attachEvents()
    
    return this
  }

  /**
   * Создает DOM элементы
   */
  _createElement() {
    this.container = document.createElement('div')
    this.container.className = 'yandex-search-control'
    
    this.input = document.createElement('input')
    this.input.type = 'text'
    this.input.placeholder = this.options.placeholder
    this.input.className = 'yandex-search-input'
    
    this.resultsList = document.createElement('div')
    this.resultsList.className = 'yandex-search-results'
    
    this.container.appendChild(this.input)
    this.container.appendChild(this.resultsList)
    
    // Добавляем базовые стили
    this._addStyles()
  }

  /**
   * Подключает обработчики событий
   */
  _attachEvents() {
    this.input.addEventListener('input', this._handleInputChange)
  }

  /**
   * Обработчик изменения текста в поле ввода
   */
  _handleInputChange(event) {
    const value = event.target.value.trim()
    
    if (this.searchTimeout) {
      clearTimeout(this.searchTimeout)
    }
    
    if (value.length < 2) {
      this._clearResults()
      return
    }
    
    this.searchTimeout = setTimeout(() => {
      this.search(value)
    }, this.options.searchDelay)
  }

  /**
   * Выполняет поиск
   */
  async search(query) {
    if (!query || this.isSearching) return []
    
    try {
      this.isSearching = true
      this._showLoading()

      // Выполняем геокодирование
      const result = await window.ymaps.geocode(query, {
        results: this.options.maxResults
      })

      const results = []
      result.geoObjects.each((geoObject) => {
        results.push({
          displayName: geoObject.getAddressLine(),
          coordinates: geoObject.geometry.getCoordinates(),
          geoObject: geoObject
        })
      })

      this._showResults(results)
      this.searchResults = results
      
      return results
      
    } catch (error) {
      console.error('Ошибка поиска:', error)
      return []
    } finally {
      this.isSearching = false
      this._hideLoading()
    }
  }

  /**
   * Показывает результаты поиска
   */
  _showResults(results) {
    this.resultsList.innerHTML = ''
    
    if (results.length === 0) {
      this.resultsList.innerHTML = '<div class="no-results">Ничего не найдено</div>'
      return
    }

    results.forEach((result, index) => {
      const item = document.createElement('div')
      item.className = 'search-result-item'
      item.textContent = result.displayName
      item.addEventListener('click', () => this._handleResultClick(result, index))
      this.resultsList.appendChild(item)
    })
    
    this.resultsList.style.display = 'block'
  }

  /**
   * Обработчик клика по результату
   */
  async _handleResultClick(result, index) {
    // Обновляем поле ввода
    this.input.value = result.displayName
    
    // Скрываем результаты
    this.resultsList.style.display = 'none'
    
    // Центрируем карту
    if (this.map && result.coordinates) {
      this.map.setCenter(result.coordinates, 15)
      
      // Добавляем маркер
      if (this.options.addResultMarker) {
        this._addResultMarker(result)
      }
    }

    // Генерируем событие
    this._emitEvent('resultselect', { result, index })
  }

  /**
   * Добавляет маркер результата
   */
  _addResultMarker(result) {
    // Удаляем предыдущий маркер
    if (this.currentMarker) {
      this.map.geoObjects.remove(this.currentMarker)
    }

    // Создаем новый маркер
    this.currentMarker = new window.ymaps.Placemark(
      result.coordinates,
      {
        balloonContent: result.displayName,
        hintContent: result.displayName
      },
      {
        preset: 'islands#redIcon'
      }
    )

    this.map.geoObjects.add(this.currentMarker)
  }

  /**
   * Очищает результаты
   */
  _clearResults() {
    this.resultsList.innerHTML = ''
    this.resultsList.style.display = 'none'
    this.searchResults = []
  }

  /**
   * Показывает индикатор загрузки
   */
  _showLoading() {
    this.input.style.background = 'url("data:image/gif;base64,R0lGODlhEAAQAPIAAP///wAAAMLCwkJCQgAAAGJiYoKCgpKSkiH+GkNyZWF0ZWQgd2l0aCBhamF4bG9hZC5pbmZvACH5BAAKAAAALAAAAAAQABAAAAMuGLrc/jDKSatlQtScKdceCAjDII7HyFCCS6EO653VqRJh62xsYr19KqgKhj3wuG4CADs=") no-repeat right center'
  }

  /**
   * Скрывает индикатор загрузки
   */
  _hideLoading() {
    this.input.style.background = ''
  }

  /**
   * Очищает поиск
   */
  clear() {
    this.input.value = ''
    this._clearResults()
    
    if (this.currentMarker) {
      this.map.geoObjects.remove(this.currentMarker)
      this.currentMarker = null
    }
  }

  /**
   * Устанавливает значение поиска
   */
  setValue(value) {
    this.input.value = value
  }

  /**
   * Получает значение поиска
   */
  getValue() {
    return this.input.value
  }

  /**
   * Получает контейнер для встраивания
   */
  getContainer() {
    return this.container
  }

  /**
   * Эмитирует событие
   */
  _emitEvent(eventName, data) {
    const event = new CustomEvent(eventName, { detail: data })
    this.container.dispatchEvent(event)
  }

  /**
   * Добавляет базовые стили
   */
  _addStyles() {
    const style = document.createElement('style')
    style.textContent = `
      .yandex-search-control {
        position: relative;
        z-index: 1000;
      }
      
      .yandex-search-input {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #ccc;
        border-radius: 4px;
        font-size: 14px;
        background: white;
      }
      
      .yandex-search-input:focus {
        outline: none;
        border-color: #007bff;
        box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
      }
      
      .yandex-search-results {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #ccc;
        border-top: none;
        border-radius: 0 0 4px 4px;
        max-height: 200px;
        overflow-y: auto;
        display: none;
        z-index: 1001;
      }
      
      .search-result-item {
        padding: 8px 12px;
        cursor: pointer;
        border-bottom: 1px solid #eee;
      }
      
      .search-result-item:hover {
        background-color: #f5f5f5;
      }
      
      .search-result-item:last-child {
        border-bottom: none;
      }
      
      .no-results {
        padding: 8px 12px;
        color: #666;
        font-style: italic;
      }
    `
    
    if (!document.querySelector('#yandex-search-styles')) {
      style.id = 'yandex-search-styles'
      document.head.appendChild(style)
    }
  }

  /**
   * Уничтожает контрол
   */
  destroy() {
    if (this.searchTimeout) {
      clearTimeout(this.searchTimeout)
    }
    
    if (this.currentMarker && this.map) {
      this.map.geoObjects.remove(this.currentMarker)
    }
    
    if (this.container && this.container.parentNode) {
      this.container.parentNode.removeChild(this.container)
    }
    
    this.map = null
    this.container = null
    this.input = null
    this.resultsList = null
  }
}