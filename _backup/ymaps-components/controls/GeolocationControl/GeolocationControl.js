/**
 * GeolocationControl - Контрол геолокации для Яндекс Карт
 * 
 * Предоставляет возможность определения местоположения пользователя и
 * центрирования карты на этой позиции с созданием метки.
 * 
 * @version 1.0.0
 * @author YMaps Components
 * @created 2025-09-04
 */

import ControlBase from '../ControlBase.js';
import { DOM, Position, Events } from '../../utils/controlHelpers.js';

/**
 * Класс контрола геолокации
 * @extends ControlBase
 */
export default class GeolocationControl extends ControlBase {
  /**
   * @param {Object} options - Настройки контрола
   * @param {string} [options.position='topLeft'] - Позиция на карте
   * @param {boolean} [options.adjustMapMargin=false] - Учитывать отступы карты
   * @param {number} [options.zIndex=1000] - Z-index элемента
   * @param {Object} [options.size={width: 36, height: 36}] - Размеры кнопки
   * @param {string} [options.title=''] - Заголовок для accessibility
   * @param {boolean} [options.noPlacemark=false] - Не создавать метку на карте
   * @param {boolean} [options.useMapMargin=true] - Использовать отступы карты при позиционировании
   * @param {boolean} [options.mapStateAutoApply=true] - Автоматически применять состояние карты
   * @param {Object} [options.geolocationOptions] - Опции HTML5 Geolocation API
   */
  constructor(options = {}) {
    const defaultOptions = {
      position: 'topLeft',
      adjustMapMargin: false,
      zIndex: 1000,
      size: { width: 36, height: 36 },
      title: '',
      noPlacemark: false,
      useMapMargin: true,
      mapStateAutoApply: true,
      geolocationOptions: {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 300000 // 5 минут
      }
    };

    super({ ...defaultOptions, ...options });
    
    // Состояние контрола
    this._state = 'ready'; // ready, pending, error
    this._currentRequest = null;
    this._geoObjects = null;
    this._watchId = null;
    this._lastKnownPosition = null;
    
    // Элементы интерфейса
    this._button = null;
    this._icon = null;
    this._spinner = null;
    
    // Проверка поддержки Geolocation API
    this._isSupported = this._checkGeolocationSupport();
    
    if (!this._isSupported) {
      console.warn('GeolocationControl: Geolocation API не поддерживается в этом браузере');
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
      
      // Событие создания контрола
      this._fireEvent('create');
      
    } catch (error) {
      console.error('GeolocationControl: Ошибка при добавлении на карту:', error);
      throw error;
    }
  }

  /**
   * Удаление контрола с карты
   * @returns {Promise<void>}
   */
  async removeFromMap() {
    try {
      // Остановка отслеживания позиции
      if (this._watchId !== null) {
        navigator.geolocation.clearWatch(this._watchId);
        this._watchId = null;
      }
      
      // Отмена текущего запроса
      if (this._currentRequest) {
        this._currentRequest.cancel = true;
        this._currentRequest = null;
      }
      
      // Удаление геообъектов с карты
      if (this._geoObjects && this._map) {
        try {
          this._map.geoObjects.remove(this._geoObjects);
        } catch (e) {
          // Игнорируем ошибки удаления
        }
        this._geoObjects = null;
      }
      
      this._unbindEvents();
      
      if (this._button && this._button.parentNode) {
        this._button.parentNode.removeChild(this._button);
      }
      
      await super.removeFromMap();
      
    } catch (error) {
      console.error('GeolocationControl: Ошибка при удалении с карты:', error);
      throw error;
    }
  }

  /**
   * Получение текущего местоположения
   * @returns {Promise<Object>} - Объект с координатами и дополнительной информацией
   */
  async getCurrentPosition() {
    if (!this._isSupported) {
      throw new Error('Geolocation API не поддерживается');
    }

    return new Promise((resolve, reject) => {
      const request = {
        cancel: false,
        timestamp: Date.now()
      };
      
      this._currentRequest = request;
      
      const onSuccess = (position) => {
        if (request.cancel) return;
        
        const result = this._processGeolocationResult(position);
        this._lastKnownPosition = result;
        this._currentRequest = null;
        
        resolve(result);
      };
      
      const onError = (error) => {
        if (request.cancel) return;
        
        this._currentRequest = null;
        const processedError = this._processGeolocationError(error);
        reject(processedError);
      };
      
      navigator.geolocation.getCurrentPosition(
        onSuccess,
        onError,
        this._options.geolocationOptions
      );
    });
  }

  /**
   * Запуск отслеживания позиции пользователя
   * @param {Function} callback - Callback для получения обновлений позиции
   * @returns {number} - ID для отмены отслеживания
   */
  watchPosition(callback) {
    if (!this._isSupported || this._watchId !== null) {
      return null;
    }

    const onSuccess = (position) => {
      const result = this._processGeolocationResult(position);
      this._lastKnownPosition = result;
      callback(result);
    };
    
    const onError = (error) => {
      const processedError = this._processGeolocationError(error);
      callback(null, processedError);
    };

    this._watchId = navigator.geolocation.watchPosition(
      onSuccess,
      onError,
      this._options.geolocationOptions
    );
    
    return this._watchId;
  }

  /**
   * Остановка отслеживания позиции
   */
  clearWatch() {
    if (this._watchId !== null) {
      navigator.geolocation.clearWatch(this._watchId);
      this._watchId = null;
    }
  }

  /**
   * Получение местоположения с добавлением на карту
   * @returns {Promise<Object>}
   */
  async locate() {
    if (this._state !== 'ready') {
      return null;
    }

    try {
      this._setState('pending');
      
      const position = await this.getCurrentPosition();
      
      // Создание геообъектов если нужно
      if (!this._options.noPlacemark) {
        await this._createGeoObjects(position);
      }
      
      // Применение состояния карты
      if (this._options.mapStateAutoApply) {
        await this._applyMapState(position);
      }
      
      this._setState('ready');
      this._fireEvent('locationchange', { position, geoObjects: this._geoObjects });
      
      return {
        position,
        geoObjects: this._geoObjects
      };
      
    } catch (error) {
      this._setState('error');
      this._fireEvent('locationerror', { error });
      throw error;
    }
  }

  /**
   * Получение последней известной позиции
   * @returns {Object|null}
   */
  getLastKnownPosition() {
    return this._lastKnownPosition;
  }

  /**
   * Получение текущего состояния контрола
   * @returns {string} - ready, pending, error
   */
  getControlState() {
    return this._state;
  }

  /**
   * Проверка поддержки Geolocation API
   * @returns {boolean}
   * @private
   */
  _checkGeolocationSupport() {
    return 'geolocation' in navigator && 
           typeof navigator.geolocation.getCurrentPosition === 'function';
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

    this._button = DOM.createButton({
      className: 'ymaps-geolocation-control',
      title: this._options.title || 'Определить местоположение',
      size: this._options.size,
      ariaLabel: 'Определить мое местоположение'
    });

    // Создание иконки и спиннера
    this._icon = document.createElement('span');
    this._icon.className = 'ymaps-geolocation-icon';
    this._icon.innerHTML = '🎯'; // GPS иконка
    
    this._spinner = document.createElement('span');
    this._spinner.className = 'ymaps-geolocation-spinner';
    this._spinner.innerHTML = '↻';
    this._spinner.style.display = 'none';
    
    this._button.appendChild(this._icon);
    this._button.appendChild(this._spinner);
    
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
      userSelect: 'none',
      position: 'relative'
    });

    // Стили для иконки
    Object.assign(this._icon.style, {
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center',
      transition: 'opacity 0.2s ease'
    });

    // Стили для спиннера
    Object.assign(this._spinner.style, {
      position: 'absolute',
      display: 'none',
      fontSize: '14px',
      animation: 'geolocation-spin 1s linear infinite'
    });

    // CSS анимация для спиннера
    if (!document.getElementById('geolocation-spinner-styles')) {
      const style = document.createElement('style');
      style.id = 'geolocation-spinner-styles';
      style.textContent = `
        @keyframes geolocation-spin {
          from { transform: rotate(0deg); }
          to { transform: rotate(360deg); }
        }
      `;
      document.head.appendChild(style);
    }

    // Hover эффекты
    this._button.addEventListener('mouseenter', () => {
      if (this._state === 'ready') {
        Object.assign(this._button.style, {
          backgroundColor: '#f9fafb',
          boxShadow: '0 4px 8px rgba(0, 0, 0, 0.15)'
        });
      }
    });

    this._button.addEventListener('mouseleave', () => {
      if (this._state === 'ready') {
        Object.assign(this._button.style, {
          backgroundColor: '#ffffff',
          boxShadow: '0 2px 4px rgba(0, 0, 0, 0.1)'
        });
      }
    });

    // Active состояние
    this._button.addEventListener('mousedown', () => {
      if (this._state === 'ready') {
        this._button.style.transform = 'scale(0.95)';
      }
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
    
    // Клавиатурное управление
    this._button.addEventListener('keydown', this._onKeyDown.bind(this));
  }

  /**
   * Отвязка событий
   * @private
   */
  _unbindEvents() {
    // События отвязываются автоматически при удалении элемента
  }

  /**
   * Обработчик клика по кнопке
   * @param {Event} event
   * @private
   */
  async _onButtonClick(event) {
    event.preventDefault();
    event.stopPropagation();

    if (!this._isSupported || this._state !== 'ready') {
      return;
    }

    try {
      await this.locate();
      this._fireEvent('press');
    } catch (error) {
      console.error('GeolocationControl: Ошибка при определении местоположения:', error);
    }
  }

  /**
   * Обработчик нажатия клавиш
   * @param {KeyboardEvent} event
   * @private
   */
  _onKeyDown(event) {
    if (event.key === 'Enter' || event.key === ' ') {
      event.preventDefault();
      this._onButtonClick(event);
    }
  }

  /**
   * Установка состояния контрола
   * @param {string} state - ready, pending, error
   * @private
   */
  _setState(state) {
    if (this._state === state) return;
    
    const previousState = this._state;
    this._state = state;
    
    this._updateButtonState();
    this._fireEvent('statechange', { 
      state, 
      previousState,
      isLocating: state === 'pending'
    });
  }

  /**
   * Обновление визуального состояния кнопки
   * @private
   */
  _updateButtonState() {
    if (!this._button || !this._icon || !this._spinner) return;

    switch (this._state) {
      case 'ready':
        this._button.disabled = false;
        this._button.style.cursor = 'pointer';
        this._button.style.backgroundColor = '#ffffff';
        this._button.style.color = '#374151';
        this._icon.style.display = 'flex';
        this._spinner.style.display = 'none';
        this._button.title = 'Определить местоположение';
        break;
        
      case 'pending':
        this._button.disabled = true;
        this._button.style.cursor = 'not-allowed';
        this._button.style.backgroundColor = '#f3f4f6';
        this._button.style.color = '#6b7280';
        this._icon.style.display = 'none';
        this._spinner.style.display = 'flex';
        this._button.title = 'Определение местоположения...';
        break;
        
      case 'error':
        this._button.disabled = false;
        this._button.style.cursor = 'pointer';
        this._button.style.backgroundColor = '#fee2e2';
        this._button.style.color = '#dc2626';
        this._button.style.borderColor = '#fecaca';
        this._icon.style.display = 'flex';
        this._spinner.style.display = 'none';
        this._button.title = 'Ошибка определения местоположения. Повторить?';
        
        // Автоматический сброс состояния через 3 секунды
        setTimeout(() => {
          if (this._state === 'error') {
            this._setState('ready');
          }
        }, 3000);
        break;
    }
  }

  /**
   * Обработка результата геолокации
   * @param {GeolocationPosition} position
   * @returns {Object}
   * @private
   */
  _processGeolocationResult(position) {
    const coords = position.coords;
    
    return {
      coords: [coords.latitude, coords.longitude],
      accuracy: coords.accuracy,
      altitude: coords.altitude,
      altitudeAccuracy: coords.altitudeAccuracy,
      heading: coords.heading,
      speed: coords.speed,
      timestamp: position.timestamp
    };
  }

  /**
   * Обработка ошибки геолокации
   * @param {GeolocationPositionError} error
   * @returns {Object}
   * @private
   */
  _processGeolocationError(error) {
    let message = 'Неизвестная ошибка геолокации';
    let code = 'UNKNOWN_ERROR';
    
    switch (error.code) {
      case error.PERMISSION_DENIED:
        message = 'Доступ к геолокации запрещен пользователем';
        code = 'PERMISSION_DENIED';
        break;
      case error.POSITION_UNAVAILABLE:
        message = 'Местоположение недоступно';
        code = 'POSITION_UNAVAILABLE';
        break;
      case error.TIMEOUT:
        message = 'Превышено время ожидания определения местоположения';
        code = 'TIMEOUT';
        break;
    }
    
    return {
      message,
      code,
      originalError: error
    };
  }

  /**
   * Создание геообъектов на карте
   * @param {Object} position
   * @returns {Promise<void>}
   * @private
   */
  async _createGeoObjects(position) {
    if (!this._map || this._options.noPlacemark) {
      return;
    }

    try {
      // Удаление предыдущих геообъектов
      if (this._geoObjects) {
        this._map.geoObjects.remove(this._geoObjects);
      }

      // Создание новой метки
      const placemark = new ymaps.Placemark(position.coords, {
        balloonContentHeader: 'Ваше местоположение',
        balloonContentBody: this._createBalloonContent(position),
        balloonContentFooter: 'Точность: ±' + Math.round(position.accuracy) + ' м',
        hintContent: 'Ваше текущее местоположение'
      }, {
        preset: 'islands#redDotIcon',
        iconColor: '#1e40af',
        draggable: false
      });

      // Создание круга точности если доступно
      let accuracyCircle = null;
      if (position.accuracy && position.accuracy < 10000) {
        accuracyCircle = new ymaps.Circle([position.coords, position.accuracy], {
          balloonContent: 'Зона точности определения местоположения'
        }, {
          fillColor: '#1e40af',
          fillOpacity: 0.1,
          strokeColor: '#1e40af',
          strokeOpacity: 0.3,
          strokeWidth: 2
        });
      }

      // Создание коллекции геообъектов
      this._geoObjects = new ymaps.GeoObjectCollection();
      this._geoObjects.add(placemark);
      
      if (accuracyCircle) {
        this._geoObjects.add(accuracyCircle);
      }

      // Добавление на карту
      this._map.geoObjects.add(this._geoObjects);

    } catch (error) {
      console.error('GeolocationControl: Ошибка создания геообъектов:', error);
    }
  }

  /**
   * Создание содержимого балуна
   * @param {Object} position
   * @returns {string}
   * @private
   */
  _createBalloonContent(position) {
    const time = new Date(position.timestamp).toLocaleTimeString();
    
    let content = `
      <div style="font-size: 12px; line-height: 1.4;">
        <p><strong>Координаты:</strong><br>
        ${position.coords[0].toFixed(6)}, ${position.coords[1].toFixed(6)}</p>
        <p><strong>Время определения:</strong> ${time}</p>
    `;
    
    if (position.altitude !== null) {
      content += `<p><strong>Высота:</strong> ${Math.round(position.altitude)} м</p>`;
    }
    
    if (position.speed !== null && position.speed > 0) {
      content += `<p><strong>Скорость:</strong> ${Math.round(position.speed * 3.6)} км/ч</p>`;
    }
    
    content += '</div>';
    return content;
  }

  /**
   * Применение состояния карты
   * @param {Object} position
   * @returns {Promise<void>}
   * @private
   */
  async _applyMapState(position) {
    if (!this._map) return;

    try {
      // Определение подходящего зума на основе точности
      let zoom = 16; // По умолчанию
      
      if (position.accuracy) {
        if (position.accuracy < 100) zoom = 17;
        else if (position.accuracy < 500) zoom = 15;
        else if (position.accuracy < 1000) zoom = 14;
        else zoom = 13;
      }

      // Анимированное перемещение к позиции
      await this._map.setCenter(position.coords, zoom, {
        checkZoomRange: true,
        duration: 500
      });

    } catch (error) {
      console.error('GeolocationControl: Ошибка применения состояния карты:', error);
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
        supported: this._isSupported,
        state: this._state,
        ...data
      });
    }
  }

  /**
   * Получение состояния контрола
   * @returns {Object}
   */
  getState() {
    return {
      state: this._state,
      isSupported: this._isSupported,
      position: this._options.position,
      visible: this._options.visible,
      lastKnownPosition: this._lastKnownPosition,
      hasGeoObjects: !!this._geoObjects
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

    if (typeof state.visible === 'boolean') {
      this.setVisible(state.visible);
    }

    // Восстановление последней позиции
    if (state.lastKnownPosition && !this._options.noPlacemark) {
      try {
        await this._createGeoObjects(state.lastKnownPosition);
        if (this._options.mapStateAutoApply) {
          await this._applyMapState(state.lastKnownPosition);
        }
      } catch (error) {
        console.error('GeolocationControl: Ошибка восстановления позиции:', error);
      }
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
   * Уничтожение контрола
   * @returns {Promise<void>}
   */
  async destroy() {
    try {
      this.clearWatch();
      
      if (this._currentRequest) {
        this._currentRequest.cancel = true;
        this._currentRequest = null;
      }
      
      await this.removeFromMap();
      
      // Очистка ссылок
      this._button = null;
      this._icon = null;
      this._spinner = null;
      this._geoObjects = null;
      this._map = null;
      this._lastKnownPosition = null;
      
    } catch (error) {
      console.error('GeolocationControl: Ошибка при уничтожении:', error);
      throw error;
    }
  }
}

/**
 * Фабрика для создания GeolocationControl
 * @param {Object} options - Опции контрола
 * @returns {GeolocationControl}
 */
export function createGeolocationControl(options) {
  return new GeolocationControl(options);
}

/**
 * Проверка поддержки Geolocation API в браузере
 * @returns {boolean}
 */
export function isGeolocationSupported() {
  return 'geolocation' in navigator && 
         typeof navigator.geolocation.getCurrentPosition === 'function';
}