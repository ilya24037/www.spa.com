/**
 * FullscreenControl - Контрол полноэкранного режима для Яндекс Карт
 * 
 * Обеспечивает возможность переключения карты в полноэкранный режим и обратно.
 * Автоматически определяет текущее состояние карты и синхронизирует UI.
 * 
 * @version 1.0.0
 * @author YMaps Components
 * @created 2025-09-04
 */

import ControlBase from '../ControlBase.js';
import { DOM, Position, Events } from '../../utils/controlHelpers.js';

/**
 * Класс контрола полноэкранного режима
 * @extends ControlBase
 */
export default class FullscreenControl extends ControlBase {
  /**
   * @param {Object} options - Настройки контрола
   * @param {string} [options.position='topRight'] - Позиция на карте
   * @param {boolean} [options.adjustMapMargin=false] - Учитывать отступы карты
   * @param {number} [options.zIndex=1000] - Z-index элемента
   * @param {Object} [options.size={width: 36, height: 36}] - Размеры кнопки
   * @param {string} [options.title=''] - Заголовок для accessibility
   * @param {Object} [options.icons] - Настройки иконок
   */
  constructor(options = {}) {
    const defaultOptions = {
      position: 'topRight',
      adjustMapMargin: false,
      zIndex: 1000,
      size: { width: 36, height: 36 },
      title: '',
      icons: {
        enter: '⛶', // Иконка входа в полноэкранный режим
        exit: '⛷'   // Иконка выхода из полноэкранного режима
      }
    };

    super({ ...defaultOptions, ...options });
    
    // Состояние контрола
    this._isFullscreen = false;
    this._mapContainerListener = null;
    this._documentListener = null;
    
    // Элементы интерфейса
    this._button = null;
    this._icon = null;
    
    // Проверка поддержки Fullscreen API
    this._isSupported = this._checkFullscreenSupport();
    
    if (!this._isSupported) {
      console.warn('FullscreenControl: Fullscreen API не поддерживается в этом браузере');
    }
  }

  /**
   * Добавление контрола на карту
   * @param {YMaps} map - Экземпляр карты
   * @returns {Promise<void>}
   */
  async addToMap(map) {
    try {
      await super.addToMap(map);
      
      if (!this._isSupported) {
        return;
      }

      await this._createButton();
      await this._bindEvents();
      await this._updateButtonState();
      
      // Событие создания контрола
      this._fireEvent('create');
      
    } catch (error) {
      console.error('FullscreenControl: Ошибка при добавлении на карту:', error);
      throw error;
    }
  }

  /**
   * Удаление контрола с карты
   * @returns {Promise<void>}
   */
  async removeFromMap() {
    try {
      if (this._map && this._isFullscreen) {
        await this.exitFullscreen();
      }
      
      this._unbindEvents();
      
      if (this._button && this._button.parentNode) {
        this._button.parentNode.removeChild(this._button);
      }
      
      await super.removeFromMap();
      
    } catch (error) {
      console.error('FullscreenControl: Ошибка при удалении с карты:', error);
      throw error;
    }
  }

  /**
   * Вход в полноэкранный режим
   * @returns {Promise<boolean>} - true если успешно
   */
  async enterFullscreen() {
    if (!this._isSupported || !this._map) {
      return false;
    }

    try {
      const container = this._map.container.getElement();
      
      if (container.requestFullscreen) {
        await container.requestFullscreen();
      } else if (container.webkitRequestFullscreen) {
        await container.webkitRequestFullscreen();
      } else if (container.mozRequestFullScreen) {
        await container.mozRequestFullScreen();
      } else if (container.msRequestFullscreen) {
        await container.msRequestFullscreen();
      }
      
      this._fireEvent('fullscreenenter');
      return true;
      
    } catch (error) {
      console.error('FullscreenControl: Ошибка входа в полноэкранный режим:', error);
      return false;
    }
  }

  /**
   * Выход из полноэкранного режима
   * @returns {Promise<boolean>} - true если успешно
   */
  async exitFullscreen() {
    if (!this._isSupported) {
      return false;
    }

    try {
      if (document.exitFullscreen) {
        await document.exitFullscreen();
      } else if (document.webkitExitFullscreen) {
        await document.webkitExitFullscreen();
      } else if (document.mozCancelFullScreen) {
        await document.mozCancelFullScreen();
      } else if (document.msExitFullscreen) {
        await document.msExitFullscreen();
      }
      
      this._fireEvent('fullscreenexit');
      return true;
      
    } catch (error) {
      console.error('FullscreenControl: Ошибка выхода из полноэкранного режима:', error);
      return false;
    }
  }

  /**
   * Переключение полноэкранного режима
   * @returns {Promise<boolean>}
   */
  async toggleFullscreen() {
    if (this._isFullscreen) {
      return await this.exitFullscreen();
    } else {
      return await this.enterFullscreen();
    }
  }

  /**
   * Проверка текущего состояния полноэкранного режима
   * @returns {boolean}
   */
  isFullscreen() {
    return this._isFullscreen;
  }

  /**
   * Проверка поддержки Fullscreen API
   * @returns {boolean}
   * @private
   */
  _checkFullscreenSupport() {
    return !!(
      document.fullscreenEnabled ||
      document.webkitFullscreenEnabled ||
      document.mozFullScreenEnabled ||
      document.msFullscreenEnabled
    );
  }

  /**
   * Создание кнопки контрола
   * @returns {Promise<void>}
   * @private
   */
  async _createButton() {
    if (!this._isSupported) {
      return;
    }

    const iconEnter = this._options.icons.enter || '⛶';
    
    this._button = DOM.createButton({
      className: 'ymaps-fullscreen-control',
      title: this._options.title || 'Полноэкранный режим',
      size: this._options.size,
      content: `<span class="ymaps-fullscreen-icon">${iconEnter}</span>`,
      ariaLabel: 'Переключить полноэкранный режим'
    });

    this._icon = this._button.querySelector('.ymaps-fullscreen-icon');
    
    // Позиционирование кнопки
    Position.setControlPosition(this._button, this._options.position, this._options.zIndex);
    
    // Стили кнопки
    this._applyStyles();
    
    // Добавление на карту
    this._map.container.getElement().appendChild(this._button);
  }

  /**
   * Применение стилей к кнопке
   * @private
   */
  _applyStyles() {
    if (!this._button) return;

    Object.assign(this._button.style, {
      backgroundColor: '#ffffff',
      border: '1px solid #e5e7eb',
      borderRadius: '6px',
      boxShadow: '0 2px 4px rgba(0, 0, 0, 0.1)',
      cursor: 'pointer',
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center',
      fontSize: '16px',
      color: '#374151',
      transition: 'all 0.2s ease',
      userSelect: 'none'
    });

    // Hover эффекты
    this._button.addEventListener('mouseenter', () => {
      Object.assign(this._button.style, {
        backgroundColor: '#f9fafb',
        boxShadow: '0 4px 8px rgba(0, 0, 0, 0.15)'
      });
    });

    this._button.addEventListener('mouseleave', () => {
      Object.assign(this._button.style, {
        backgroundColor: '#ffffff',
        boxShadow: '0 2px 4px rgba(0, 0, 0, 0.1)'
      });
    });

    // Active состояние
    this._button.addEventListener('mousedown', () => {
      this._button.style.transform = 'scale(0.95)';
    });

    this._button.addEventListener('mouseup', () => {
      this._button.style.transform = 'scale(1)';
    });
  }

  /**
   * Привязка событий
   * @returns {Promise<void>}
   * @private
   */
  async _bindEvents() {
    if (!this._isSupported || !this._button) {
      return;
    }

    // Клик по кнопке
    this._button.addEventListener('click', this._onButtonClick.bind(this));
    
    // События документа для отслеживания изменений fullscreen
    this._documentListener = Events.group(document)
      .add('fullscreenchange', this._onFullscreenChange.bind(this))
      .add('webkitfullscreenchange', this._onFullscreenChange.bind(this))
      .add('mozfullscreenchange', this._onFullscreenChange.bind(this))
      .add('msfullscreenchange', this._onFullscreenChange.bind(this));

    // Клавиатурное управление (Escape)
    document.addEventListener('keydown', this._onKeyDown.bind(this));
  }

  /**
   * Отвязка событий
   * @private
   */
  _unbindEvents() {
    if (this._documentListener) {
      this._documentListener.removeAll();
      this._documentListener = null;
    }

    document.removeEventListener('keydown', this._onKeyDown.bind(this));
  }

  /**
   * Обработчик клика по кнопке
   * @param {Event} event
   * @private
   */
  async _onButtonClick(event) {
    event.preventDefault();
    event.stopPropagation();

    if (!this._isSupported) {
      return;
    }

    await this.toggleFullscreen();
    this._fireEvent('buttonclick');
  }

  /**
   * Обработчик изменения fullscreen состояния
   * @private
   */
  _onFullscreenChange() {
    const wasFullscreen = this._isFullscreen;
    this._isFullscreen = this._getCurrentFullscreenState();
    
    if (wasFullscreen !== this._isFullscreen) {
      this._updateButtonState();
      
      if (this._isFullscreen) {
        this._fireEvent('fullscreenenter');
      } else {
        this._fireEvent('fullscreenexit');
      }
    }
  }

  /**
   * Получение текущего состояния fullscreen
   * @returns {boolean}
   * @private
   */
  _getCurrentFullscreenState() {
    return !!(
      document.fullscreenElement ||
      document.webkitFullscreenElement ||
      document.mozFullScreenElement ||
      document.msFullscreenElement
    );
  }

  /**
   * Обновление состояния кнопки
   * @private
   */
  _updateButtonState() {
    if (!this._icon) return;

    const iconEnter = this._options.icons.enter || '⛶';
    const iconExit = this._options.icons.exit || '⛷';
    
    this._icon.textContent = this._isFullscreen ? iconExit : iconEnter;
    
    const title = this._isFullscreen ? 
      'Выйти из полноэкранного режима' : 
      'Войти в полноэкранный режим';
    
    this._button.title = title;
    this._button.setAttribute('aria-label', title);
    
    // Визуальное состояние active
    if (this._isFullscreen) {
      this._button.style.backgroundColor = '#3b82f6';
      this._button.style.color = '#ffffff';
      this._button.style.borderColor = '#3b82f6';
    } else {
      this._button.style.backgroundColor = '#ffffff';
      this._button.style.color = '#374151';
      this._button.style.borderColor = '#e5e7eb';
    }
  }

  /**
   * Обработчик нажатия клавиш
   * @param {KeyboardEvent} event
   * @private
   */
  _onKeyDown(event) {
    // F11 или Escape для управления fullscreen
    if (event.key === 'F11') {
      event.preventDefault();
      this.toggleFullscreen();
    }
  }

  /**
   * Создание событий контрола
   * @param {string} type - Тип события
   * @param {Object} data - Данные события
   * @private
   */
  _fireEvent(type, data = {}) {
    if (typeof this.events !== 'undefined' && this.events.fire) {
      this.events.fire(type, {
        target: this,
        isFullscreen: this._isFullscreen,
        supported: this._isSupported,
        ...data
      });
    }
  }

  /**
   * Получение текущего состояния контрола
   * @returns {Object}
   */
  getState() {
    return {
      isFullscreen: this._isFullscreen,
      isSupported: this._isSupported,
      position: this._options.position,
      visible: this._options.visible
    };
  }

  /**
   * Установка состояния контрола
   * @param {Object} state
   * @returns {Promise<void>}
   */
  async setState(state) {
    if (typeof state !== 'object' || state === null) {
      return;
    }

    if (typeof state.isFullscreen === 'boolean' && 
        state.isFullscreen !== this._isFullscreen) {
      if (state.isFullscreen) {
        await this.enterFullscreen();
      } else {
        await this.exitFullscreen();
      }
    }

    if (typeof state.visible === 'boolean') {
      this.setVisible(state.visible);
    }
  }

  /**
   * Установка видимости контрола
   * @param {boolean} visible
   */
  setVisible(visible) {
    this._options.visible = visible;
    
    if (this._button) {
      this._button.style.display = visible ? 'flex' : 'none';
    }
  }

  /**
   * Проверка видимости контрола
   * @returns {boolean}
   */
  isVisible() {
    return this._options.visible !== false;
  }

  /**
   * Уничтожение контрола
   * @returns {Promise<void>}
   */
  async destroy() {
    try {
      if (this._isFullscreen) {
        await this.exitFullscreen();
      }
      
      await this.removeFromMap();
      
      // Очистка ссылок
      this._button = null;
      this._icon = null;
      this._map = null;
      this._mapContainerListener = null;
      this._documentListener = null;
      
    } catch (error) {
      console.error('FullscreenControl: Ошибка при уничтожении:', error);
      throw error;
    }
  }

  /**
   * Получение размеров контрола для adjustMapMargin
   * @returns {Array<number>} [width, height]
   */
  getSize() {
    if (!this.isVisible() || !this._isSupported) {
      return [0, 0];
    }
    
    return [this._options.size.width, this._options.size.height];
  }

  /**
   * Методы для совместимости с YMaps API
   */
  
  /**
   * Выбор контрола (активация)
   */
  select() {
    if (!this._isFullscreen) {
      this.enterFullscreen();
    }
  }

  /**
   * Отмена выбора контрола (деактивация)
   */
  deselect() {
    if (this._isFullscreen) {
      this.exitFullscreen();
    }
  }
}

/**
 * Фабрика для создания FullscreenControl
 * @param {Object} options - Опции контрола
 * @returns {FullscreenControl}
 */
export function createFullscreenControl(options) {
  return new FullscreenControl(options);
}

/**
 * Проверка поддержки Fullscreen API в браузере
 * @returns {boolean}
 */
export function isFullscreenSupported() {
  return !!(
    document.fullscreenEnabled ||
    document.webkitFullscreenEnabled ||
    document.mozFullScreenEnabled ||
    document.msFullscreenEnabled
  );
}