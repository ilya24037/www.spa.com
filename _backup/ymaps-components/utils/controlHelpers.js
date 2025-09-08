/**
 * Утилиты для Controls компонентов Yandex Maps
 * Общие функции и хелперы для всех контролов
 * Соблюдение принципа DRY (Don't Repeat Yourself)
 * 
 * @module controlHelpers
 * @version 1.0.0
 * @author SPA Platform
 */

/**
 * Утилиты для создания DOM элементов контролов
 */
export const DOM = {
  /**
   * Создание кнопки контрола с иконкой
   * @param {Object} options - Опции кнопки
   * @param {string} options.className - CSS класс
   * @param {string} options.title - Заголовок (tooltip)
   * @param {string} options.iconClass - Класс иконки
   * @param {string} [options.text] - Текст кнопки
   * @param {Function} [options.onClick] - Обработчик клика
   * @returns {HTMLButtonElement}
   */
  createButton(options = {}) {
    if (typeof options !== 'object') {
      throw new TypeError('DOM.createButton: options должны быть объектом')
    }

    const button = document.createElement('button')
    button.type = 'button'
    button.className = `ymaps-control-button ${options.className || ''}`
    button.title = options.title || ''
    
    // Создаем структуру кнопки
    const icon = document.createElement('span')
    icon.className = `ymaps-control-icon ${options.iconClass || ''}`
    button.appendChild(icon)
    
    if (options.text) {
      const text = document.createElement('span')
      text.className = 'ymaps-control-text'
      text.textContent = options.text
      button.appendChild(text)
    }
    
    // Добавляем обработчик клика
    if (typeof options.onClick === 'function') {
      button.addEventListener('click', options.onClick)
    }
    
    // Предотвращаем всплытие событий карты
    button.addEventListener('mousedown', (e) => e.stopPropagation())
    button.addEventListener('click', (e) => e.stopPropagation())
    
    return button
  },

  /**
   * Создание контейнера для группы кнопок
   * @param {Object} options - Опции контейнера
   * @param {string} [options.className] - Дополнительный CSS класс
   * @param {string} [options.direction='vertical'] - Направление: 'vertical' | 'horizontal'
   * @returns {HTMLDivElement}
   */
  createButtonGroup(options = {}) {
    const container = document.createElement('div')
    const direction = options.direction || 'vertical'
    container.className = `ymaps-control-group ymaps-control-group--${direction} ${options.className || ''}`
    
    return container
  },

  /**
   * Создание выпадающего списка
   * @param {Object} options - Опции списка
   * @param {Array} options.items - Элементы списка
   * @param {string} [options.className] - CSS класс
   * @param {Function} [options.onSelect] - Обработчик выбора
   * @returns {HTMLSelectElement}
   */
  createSelect(options = {}) {
    if (!Array.isArray(options.items)) {
      throw new TypeError('DOM.createSelect: items должно быть массивом')
    }

    const select = document.createElement('select')
    select.className = `ymaps-control-select ${options.className || ''}`
    
    options.items.forEach(item => {
      const option = document.createElement('option')
      option.value = typeof item === 'object' ? item.value : item
      option.textContent = typeof item === 'object' ? item.text : item
      select.appendChild(option)
    })
    
    if (typeof options.onSelect === 'function') {
      select.addEventListener('change', (e) => {
        options.onSelect(e.target.value, e)
      })
    }
    
    // Предотвращаем всплытие событий карты
    select.addEventListener('mousedown', (e) => e.stopPropagation())
    select.addEventListener('click', (e) => e.stopPropagation())
    
    return select
  },

  /**
   * Создание поля ввода с кнопкой поиска
   * @param {Object} options - Опции поля
   * @param {string} [options.placeholder] - Placeholder
   * @param {Function} [options.onSearch] - Обработчик поиска
   * @param {Function} [options.onInput] - Обработчик ввода
   * @returns {HTMLDivElement}
   */
  createSearchInput(options = {}) {
    const container = document.createElement('div')
    container.className = 'ymaps-control-search-input'
    
    const input = document.createElement('input')
    input.type = 'text'
    input.className = 'ymaps-control-input'
    input.placeholder = options.placeholder || 'Поиск...'
    
    const button = this.createButton({
      className: 'ymaps-control-search-button',
      iconClass: 'ymaps-icon-search',
      title: 'Поиск',
      onClick: () => {
        if (typeof options.onSearch === 'function') {
          options.onSearch(input.value, input)
        }
      }
    })
    
    container.appendChild(input)
    container.appendChild(button)
    
    // Обработчик ввода
    if (typeof options.onInput === 'function') {
      input.addEventListener('input', (e) => {
        options.onInput(e.target.value, e)
      })
    }
    
    // Поиск по Enter
    input.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' && typeof options.onSearch === 'function') {
        options.onSearch(input.value, input)
      }
    })
    
    // Предотвращаем всплытие событий карты
    container.addEventListener('mousedown', (e) => e.stopPropagation())
    container.addEventListener('click', (e) => e.stopPropagation())
    
    return container
  }
}

/**
 * Утилиты для работы с позиционированием
 */
export const Position = {
  /**
   * Допустимые позиции для контролов
   */
  POSITIONS: [
    'topLeft', 'topCenter', 'topRight',
    'centerLeft', 'center', 'centerRight', 
    'bottomLeft', 'bottomCenter', 'bottomRight'
  ],

  /**
   * Проверка корректности позиции
   * @param {string} position - Позиция
   * @returns {boolean}
   */
  isValidPosition(position) {
    return typeof position === 'string' && this.POSITIONS.includes(position)
  },

  /**
   * Получение позиции по умолчанию
   * @returns {string}
   */
  getDefault() {
    return 'topRight'
  },

  /**
   * Вычисление координат позиционирования
   * @param {string} position - Позиция
   * @param {Object} containerSize - Размер контейнера карты
   * @param {Object} elementSize - Размер элемента контрола
   * @param {Object} margin - Отступы
   * @returns {Object} Координаты {top, left, right, bottom, transform}
   */
  calculateCoordinates(position, containerSize, elementSize, margin = {}) {
    const coords = {}
    const { width: containerWidth, height: containerHeight } = containerSize
    const { width: elementWidth, height: elementHeight } = elementSize
    const m = { top: 10, right: 10, bottom: 10, left: 10, ...margin }

    switch (position) {
      case 'topLeft':
        coords.top = m.top
        coords.left = m.left
        break
      case 'topCenter':
        coords.top = m.top
        coords.left = (containerWidth - elementWidth) / 2
        break
      case 'topRight':
        coords.top = m.top
        coords.right = m.right
        break
      case 'centerLeft':
        coords.top = (containerHeight - elementHeight) / 2
        coords.left = m.left
        break
      case 'center':
        coords.top = (containerHeight - elementHeight) / 2
        coords.left = (containerWidth - elementWidth) / 2
        break
      case 'centerRight':
        coords.top = (containerHeight - elementHeight) / 2
        coords.right = m.right
        break
      case 'bottomLeft':
        coords.bottom = m.bottom
        coords.left = m.left
        break
      case 'bottomCenter':
        coords.bottom = m.bottom
        coords.left = (containerWidth - elementWidth) / 2
        break
      case 'bottomRight':
        coords.bottom = m.bottom
        coords.right = m.right
        break
    }

    return coords
  }
}

/**
 * Утилиты для работы с CSS стилями
 */
export const Styles = {
  /**
   * Базовые CSS стили для контролов
   */
  BASE_STYLES: `
    .ymaps-control-base {
      box-sizing: border-box;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      font-size: 14px;
      line-height: 1.4;
      background: #fff;
      border-radius: 3px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.3);
      user-select: none;
      -webkit-user-select: none;
      -moz-user-select: none;
      -ms-user-select: none;
    }

    .ymaps-control-button {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 8px;
      margin: 0;
      border: none;
      background: #fff;
      cursor: pointer;
      transition: background-color 0.2s ease;
      min-width: 34px;
      min-height: 34px;
    }

    .ymaps-control-button:hover {
      background: #f5f5f5;
    }

    .ymaps-control-button:active {
      background: #e5e5e5;
    }

    .ymaps-control-button.disabled {
      opacity: 0.5;
      cursor: not-allowed;
      pointer-events: none;
    }

    .ymaps-control-group {
      display: flex;
      background: #fff;
      border-radius: 3px;
      overflow: hidden;
      box-shadow: 0 1px 3px rgba(0,0,0,0.3);
    }

    .ymaps-control-group--vertical {
      flex-direction: column;
    }

    .ymaps-control-group--horizontal {
      flex-direction: row;
    }

    .ymaps-control-group .ymaps-control-button {
      border-radius: 0;
      box-shadow: none;
    }

    .ymaps-control-group--vertical .ymaps-control-button:not(:last-child) {
      border-bottom: 1px solid #e0e0e0;
    }

    .ymaps-control-group--horizontal .ymaps-control-button:not(:last-child) {
      border-right: 1px solid #e0e0e0;
    }

    .ymaps-control-icon {
      width: 16px;
      height: 16px;
      background-size: contain;
      background-repeat: no-repeat;
      background-position: center;
    }

    .ymaps-control-text {
      margin-left: 8px;
      font-weight: 500;
    }

    .ymaps-control-select {
      padding: 8px;
      border: 1px solid #e0e0e0;
      background: #fff;
      border-radius: 3px;
      font-size: 14px;
      cursor: pointer;
    }

    .ymaps-control-input {
      padding: 8px;
      border: 1px solid #e0e0e0;
      border-radius: 3px 0 0 3px;
      font-size: 14px;
      outline: none;
    }

    .ymaps-control-search-input {
      display: flex;
      background: #fff;
      border-radius: 3px;
      overflow: hidden;
      box-shadow: 0 1px 3px rgba(0,0,0,0.3);
    }

    .ymaps-control-search-button {
      border-radius: 0 3px 3px 0 !important;
      border-left: 1px solid #e0e0e0;
    }
  `,

  /**
   * Инъекция базовых стилей в документ
   */
  injectBaseStyles() {
    // Проверяем, не были ли стили уже добавлены
    if (document.getElementById('ymaps-controls-styles')) {
      return
    }

    const styleElement = document.createElement('style')
    styleElement.id = 'ymaps-controls-styles'
    styleElement.textContent = this.BASE_STYLES
    document.head.appendChild(styleElement)
  }
}

/**
 * Утилиты для работы с событиями
 */
export const Events = {
  /**
   * Создание кастомного события
   * @param {string} type - Тип события
   * @param {Object} detail - Данные события
   * @returns {CustomEvent}
   */
  create(type, detail = {}) {
    return new CustomEvent(type, {
      detail,
      bubbles: true,
      cancelable: true
    })
  },

  /**
   * Безопасная привязка обработчика события
   * @param {EventTarget} target - Цель события
   * @param {string} type - Тип события
   * @param {Function} handler - Обработчик
   * @param {Object} [options] - Опции addEventListener
   * @returns {Function} Функция для отвязки обработчика
   */
  on(target, type, handler, options = {}) {
    if (!target || typeof target.addEventListener !== 'function') {
      throw new TypeError('Events.on: некорректная цель события')
    }
    
    if (typeof handler !== 'function') {
      throw new TypeError('Events.on: обработчик должен быть функцией')
    }

    target.addEventListener(type, handler, options)
    
    // Возвращаем функцию для отвязки
    return () => target.removeEventListener(type, handler, options)
  },

  /**
   * Debounce функция для оптимизации обработчиков
   * @param {Function} func - Функция
   * @param {number} wait - Задержка в мс
   * @param {boolean} [immediate=false] - Немедленное выполнение
   * @returns {Function} Debounced функция
   */
  debounce(func, wait, immediate = false) {
    let timeout
    return function executedFunction(...args) {
      const later = () => {
        timeout = null
        if (!immediate) func.apply(this, args)
      }
      const callNow = immediate && !timeout
      clearTimeout(timeout)
      timeout = setTimeout(later, wait)
      if (callNow) func.apply(this, args)
    }
  },

  /**
   * Throttle функция для ограничения частоты вызовов
   * @param {Function} func - Функция
   * @param {number} limit - Лимит в мс
   * @returns {Function} Throttled функция
   */
  throttle(func, limit) {
    let inThrottle
    return function(...args) {
      if (!inThrottle) {
        func.apply(this, args)
        inThrottle = true
        setTimeout(() => inThrottle = false, limit)
      }
    }
  }
}

/**
 * Утилиты для валидации
 */
export const Validation = {
  /**
   * Проверка, является ли значение экземпляром карты
   * @param {*} value - Значение для проверки
   * @returns {boolean}
   */
  isMap(value) {
    return value && 
           typeof value === 'object' && 
           value.container && 
           typeof value.container.getElement === 'function'
  },

  /**
   * Проверка корректности опций контрола
   * @param {Object} options - Опции
   * @returns {boolean}
   */
  isValidOptions(options) {
    return typeof options === 'object' && options !== null
  },

  /**
   * Проверка корректности позиции
   * @param {string} position - Позиция
   * @returns {boolean}
   */
  isValidPosition(position) {
    return Position.isValidPosition(position)
  },

  /**
   * Проверка корректности margin объекта
   * @param {Object} margin - Отступы
   * @returns {boolean}
   */
  isValidMargin(margin) {
    if (typeof margin !== 'object' || margin === null) {
      return false
    }

    const validKeys = ['top', 'right', 'bottom', 'left']
    return validKeys.some(key => 
      margin.hasOwnProperty(key) && 
      typeof margin[key] === 'number' && 
      margin[key] >= 0
    )
  }
}

/**
 * Утилиты для работы с иконками
 */
export const Icons = {
  /**
   * Карта иконок для контролов
   */
  ICONS: {
    zoomIn: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTYiIGhlaWdodD0iMTYiIHZpZXdCb3g9IjAgMCAxNiAxNiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTggM1Y4TTggOFYxM004IDhIM004IDhIMTMiIHN0cm9rZT0iIzMzMzMzMyIgc3Ryb2tlLXdpZHRoPSIyIiBzdHJva2UtbGluZWNhcD0icm91bmQiLz4KPHN2Zz4K',
    zoomOut: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTYiIGhlaWdodD0iMTYiIHZpZXdCb3g9IjAgMCAxNiAxNiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTNITTEzIiBzdHJva2U9IiMzMzMzMzMiIHN0cm9rZS13aWR0aD0iMiIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIi8+Cjwvc3ZnPgo=',
    layers: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTYiIGhlaWdodD0iMTYiIHZpZXdCb3g9IjAgMCAxNiAxNiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTEgNUw4IDJMMTUgNUw4IDhMMSA1WiIgc3Ryb2tlPSIjMzMzMzMzIiBzdHJva2Utd2lkdGg9IjEuNSIgc3Ryb2tlLWxpbmVqb2luPSJyb3VuZCIvPgo8cGF0aCBkPSJNMSA4TDggMTFMMTUgOCIgc3Ryb2tlPSIjMzMzMzMzIiBzdHJva2Utd2lkdGg9IjEuNSIgc3Ryb2tlLWxpbmVqb2luPSJyb3VuZCIvPgo8cGF0aCBkPSJNMSAxMUw4IDE0TDE1IDExIiBzdHJva2U9IiMzMzMzMzMiIHN0cm9rZS13aWR0aD0iMS41IiBzdHJva2UtbGluZWpvaW49InJvdW5kIi8+Cjwvc3ZnPgo=',
    search: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTYiIGhlaWdodD0iMTYiIHZpZXdCb3g9IjAgMCAxNiAxNiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iNyIgY3k9IjciIHI9IjQiIHN0cm9rZT0iIzMzMzMzMyIgc3Ryb2tlLXdpZHRoPSIxLjUiLz4KPHBhdGggZD0ibTExIDExIDMgMyIgc3Ryb2tlPSIjMzMzMzMzIiBzdHJva2Utd2lkdGg9IjEuNSIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIi8+Cjwvc3ZnPgo=',
    geolocation: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTYiIGhlaWdodD0iMTYiIHZpZXdCb3g9IjAgMCAxNiAxNiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iOCIgY3k9IjgiIHI9IjQiIHN0cm9rZT0iIzMzMzMzMyIgc3Ryb2tlLXdpZHRoPSIxLjUiLz4KPHBhdGggZD0iTTggMVY0TTggMTJWMTVNMSA4SDRNMTIgOEgxNSIgc3Ryb2tlPSIjMzMzMzMzIiBzdHJva2Utd2lkdGg9IjEuNSIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIi8+Cjwvc3ZnPgo=',
    fullscreen: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTYiIGhlaWdodD0iMTYiIHZpZXdCb3g9IjAgMCAxNiAxNiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTMgM0g2VjVIM1Y2SDFWMyIgZmlsbD0iIzMzMzMzMyIvPgo8cGF0aCBkPSJNMTMgM0gxMFYxSDEzVjEwSDEzVjNaIiBmaWxsPSIjMzMzMzMzIi8+CjxwYXRoIGQ9Ik0zIDEzSDZWMTVIM1YxM1oiIGZpbGw9IiMzMzMzMzMiLz4KPHBhdGggZD0iTTEzIDEzSDEwVjE1SDEzVjEzWiIgZmlsbD0iIzMzMzMzMyIvPgo8L3N2Zz4K'
  },

  /**
   * Получить иконку по имени
   * @param {string} name - Имя иконки
   * @returns {string} Data URL иконки
   */
  get(name) {
    return this.ICONS[name] || ''
  },

  /**
   * Применить иконку к элементу
   * @param {HTMLElement} element - DOM элемент
   * @param {string} iconName - Имя иконки
   */
  apply(element, iconName) {
    const icon = this.get(iconName)
    if (icon && element) {
      element.style.backgroundImage = `url("${icon}")`
    }
  }
}

/**
 * Общие константы для контролов
 */
export const Constants = {
  DEFAULT_POSITION: 'topRight',
  DEFAULT_Z_INDEX: 1000,
  DEFAULT_MARGIN: { top: 10, right: 10, bottom: 10, left: 10 },
  ANIMATION_DURATION: 200,
  DEBOUNCE_DELAY: 300,
  THROTTLE_LIMIT: 100
}

// Автоматическая инъекция стилей при импорте модуля
if (typeof document !== 'undefined') {
  Styles.injectBaseStyles()
}