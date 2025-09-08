/**
 * GeolocationControl - TypeScript определения для контрола геолокации
 * 
 * @version 1.0.0
 * @author YMaps Components
 * @created 2025-09-04
 */

import { ControlBase, ControlOptions, ControlState } from '../ControlBase';

/**
 * Размеры кнопки контрола
 */
export interface GeolocationControlSize {
  /** Ширина кнопки в пикселях */
  width: number;
  /** Высота кнопки в пикселях */
  height: number;
}

/**
 * Опции HTML5 Geolocation API
 */
export interface GeolocationOptions {
  /** Запрашивать высокую точность */
  enableHighAccuracy?: boolean;
  /** Таймаут запроса в миллисекундах */
  timeout?: number;
  /** Максимальный возраст кешированной позиции в миллисекундах */
  maximumAge?: number;
}

/**
 * Настройки GeolocationControl
 */
export interface GeolocationControlOptions extends ControlOptions {
  /** Размеры кнопки */
  size?: GeolocationControlSize;
  /** Заголовок для accessibility */
  title?: string;
  /** Не создавать метку на карте */
  noPlacemark?: boolean;
  /** Использовать отступы карты при позиционировании */
  useMapMargin?: boolean;
  /** Автоматически применять состояние карты */
  mapStateAutoApply?: boolean;
  /** Опции Geolocation API */
  geolocationOptions?: GeolocationOptions;
}

/**
 * Результат геолокации
 */
export interface GeolocationResult {
  /** Координаты [широта, долгота] */
  coords: [number, number];
  /** Точность в метрах */
  accuracy: number;
  /** Высота над уровнем моря в метрах */
  altitude: number | null;
  /** Точность высоты в метрах */
  altitudeAccuracy: number | null;
  /** Направление движения в градусах */
  heading: number | null;
  /** Скорость движения в м/с */
  speed: number | null;
  /** Временная метка */
  timestamp: number;
}

/**
 * Ошибка геолокации
 */
export interface GeolocationError {
  /** Сообщение об ошибке */
  message: string;
  /** Код ошибки */
  code: 'PERMISSION_DENIED' | 'POSITION_UNAVAILABLE' | 'TIMEOUT' | 'UNKNOWN_ERROR';
  /** Оригинальная ошибка браузера */
  originalError: GeolocationPositionError;
}

/**
 * Состояние GeolocationControl
 */
export interface GeolocationControlState extends ControlState {
  /** Текущее состояние контрола */
  state: 'ready' | 'pending' | 'error';
  /** Поддерживается ли Geolocation API */
  isSupported: boolean;
  /** Последняя известная позиция */
  lastKnownPosition: GeolocationResult | null;
  /** Есть ли геообъекты на карте */
  hasGeoObjects: boolean;
}

/**
 * Данные события GeolocationControl
 */
export interface GeolocationControlEventData {
  /** Целевой контрол */
  target: GeolocationControl;
  /** Поддержка Geolocation API */
  supported: boolean;
  /** Текущее состояние */
  state: 'ready' | 'pending' | 'error';
}

/**
 * Данные события изменения местоположения
 */
export interface LocationChangeEventData extends GeolocationControlEventData {
  /** Позиция пользователя */
  position: GeolocationResult;
  /** Созданные геообъекты */
  geoObjects: any; // YMaps GeoObjectCollection
}

/**
 * Данные события ошибки геолокации
 */
export interface LocationErrorEventData extends GeolocationControlEventData {
  /** Информация об ошибке */
  error: GeolocationError;
}

/**
 * Данные события изменения состояния
 */
export interface StateChangeEventData extends GeolocationControlEventData {
  /** Новое состояние */
  state: 'ready' | 'pending' | 'error';
  /** Предыдущее состояние */
  previousState: 'ready' | 'pending' | 'error';
  /** Идет ли процесс определения местоположения */
  isLocating: boolean;
}

/**
 * События GeolocationControl
 */
export interface GeolocationControlEvents {
  /** Создание контрола */
  create: GeolocationControlEventData;
  /** Изменение местоположения */
  locationchange: LocationChangeEventData;
  /** Ошибка определения местоположения */
  locationerror: LocationErrorEventData;
  /** Нажатие на кнопку контрола */
  press: GeolocationControlEventData;
  /** Изменение состояния контрола */
  statechange: StateChangeEventData;
}

/**
 * Callback для отслеживания позиции
 */
export type PositionWatchCallback = (
  position: GeolocationResult | null,
  error?: GeolocationError
) => void;

/**
 * Класс контрола геолокации для Яндекс Карт
 */
export declare class GeolocationControl extends ControlBase {
  /**
   * Текущее состояние контрола
   */
  private _state: 'ready' | 'pending' | 'error';

  /**
   * Текущий запрос геолокации
   */
  private _currentRequest: any;

  /**
   * Геообъекты на карте
   */
  private _geoObjects: any;

  /**
   * ID отслеживания позиции
   */
  private _watchId: number | null;

  /**
   * Последняя известная позиция
   */
  private _lastKnownPosition: GeolocationResult | null;

  /**
   * Поддержка Geolocation API
   */
  private _isSupported: boolean;

  /**
   * Элемент кнопки
   */
  private _button: HTMLButtonElement | null;

  /**
   * Элемент иконки
   */
  private _icon: HTMLElement | null;

  /**
   * Элемент спиннера загрузки
   */
  private _spinner: HTMLElement | null;

  /**
   * Создание нового экземпляра GeolocationControl
   * @param options Настройки контрола
   */
  constructor(options?: GeolocationControlOptions);

  /**
   * Добавление контрола на карту
   * @param map Экземпляр карты
   */
  addToMap(map: any): Promise<void>;

  /**
   * Удаление контрола с карты
   */
  removeFromMap(): Promise<void>;

  /**
   * Получение текущего местоположения
   * @returns Промис с координатами и дополнительной информацией
   */
  getCurrentPosition(): Promise<GeolocationResult>;

  /**
   * Запуск отслеживания позиции пользователя
   * @param callback Callback для получения обновлений позиции
   * @returns ID для отмены отслеживания
   */
  watchPosition(callback: PositionWatchCallback): number | null;

  /**
   * Остановка отслеживания позиции
   */
  clearWatch(): void;

  /**
   * Получение местоположения с добавлением на карту
   * @returns Промис с позицией и геообъектами
   */
  locate(): Promise<{ position: GeolocationResult; geoObjects: any } | null>;

  /**
   * Получение последней известной позиции
   * @returns Последняя позиция или null
   */
  getLastKnownPosition(): GeolocationResult | null;

  /**
   * Получение текущего состояния контрола
   * @returns Состояние контрола
   */
  getControlState(): 'ready' | 'pending' | 'error';

  /**
   * Получение текущего состояния контрола
   */
  getState(): GeolocationControlState;

  /**
   * Установка состояния контрола
   * @param state Новое состояние
   */
  setState(state: Partial<GeolocationControlState>): Promise<void>;

  /**
   * Установка видимости контрола
   * @param visible Видимость контрола
   */
  setVisible(visible: boolean): void;

  /**
   * Проверка видимости контрола
   * @returns true если контрол видим
   */
  isVisible(): boolean;

  /**
   * Получение размеров контрола для adjustMapMargin
   * @returns [width, height]
   */
  getSize(): [number, number];

  /**
   * Уничтожение контрола
   */
  destroy(): Promise<void>;

  /**
   * Проверка поддержки Geolocation API
   * @private
   */
  private _checkGeolocationSupport(): boolean;

  /**
   * Создание кнопки контрола
   * @private
   */
  private _createButton(): Promise<void>;

  /**
   * Применение стилей к кнопке
   * @private
   */
  private _applyStyles(): void;

  /**
   * Привязка событий
   * @private
   */
  private _bindEvents(): Promise<void>;

  /**
   * Отвязка событий
   * @private
   */
  private _unbindEvents(): void;

  /**
   * Обработчик клика по кнопке
   * @param event Событие клика
   * @private
   */
  private _onButtonClick(event: Event): Promise<void>;

  /**
   * Обработчик нажатия клавиш
   * @param event Событие клавиатуры
   * @private
   */
  private _onKeyDown(event: KeyboardEvent): void;

  /**
   * Установка состояния контрола
   * @param state Новое состояние
   * @private
   */
  private _setState(state: 'ready' | 'pending' | 'error'): void;

  /**
   * Обновление визуального состояния кнопки
   * @private
   */
  private _updateButtonState(): void;

  /**
   * Обработка результата геолокации
   * @param position Позиция от Geolocation API
   * @returns Обработанный результат
   * @private
   */
  private _processGeolocationResult(position: GeolocationPosition): GeolocationResult;

  /**
   * Обработка ошибки геолокации
   * @param error Ошибка от Geolocation API
   * @returns Обработанная ошибка
   * @private
   */
  private _processGeolocationError(error: GeolocationPositionError): GeolocationError;

  /**
   * Создание геообъектов на карте
   * @param position Позиция для создания объектов
   * @private
   */
  private _createGeoObjects(position: GeolocationResult): Promise<void>;

  /**
   * Создание содержимого балуна
   * @param position Позиция с информацией
   * @returns HTML содержимое балуна
   * @private
   */
  private _createBalloonContent(position: GeolocationResult): string;

  /**
   * Применение состояния карты
   * @param position Позиция для центрирования карты
   * @private
   */
  private _applyMapState(position: GeolocationResult): Promise<void>;

  /**
   * Создание событий контрола
   * @param type Тип события
   * @param data Данные события
   * @private
   */
  private _fireEvent(
    type: keyof GeolocationControlEvents,
    data?: Partial<GeolocationControlEventData>
  ): void;
}

/**
 * Фабрика для создания GeolocationControl
 * @param options Опции контрола
 * @returns Экземпляр GeolocationControl
 */
export declare function createGeolocationControl(options?: GeolocationControlOptions): GeolocationControl;

/**
 * Проверка поддержки Geolocation API в браузере
 * @returns true если Geolocation API поддерживается
 */
export declare function isGeolocationSupported(): boolean;

export default GeolocationControl;