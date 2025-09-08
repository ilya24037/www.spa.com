/**
 * FullscreenControl - TypeScript определения для контрола полноэкранного режима
 * 
 * @version 1.0.0
 * @author YMaps Components
 * @created 2025-09-04
 */

import { ControlBase, ControlOptions, ControlState } from '../ControlBase';

/**
 * Иконки для FullscreenControl
 */
export interface FullscreenControlIcons {
  /** Иконка входа в полноэкранный режим */
  enter?: string;
  /** Иконка выхода из полноэкранного режима */
  exit?: string;
}

/**
 * Размеры кнопки контрола
 */
export interface FullscreenControlSize {
  /** Ширина кнопки в пикселях */
  width: number;
  /** Высота кнопки в пикселях */
  height: number;
}

/**
 * Настройки FullscreenControl
 */
export interface FullscreenControlOptions extends ControlOptions {
  /** Размеры кнопки */
  size?: FullscreenControlSize;
  /** Заголовок для accessibility */
  title?: string;
  /** Настройки иконок */
  icons?: FullscreenControlIcons;
}

/**
 * Состояние FullscreenControl
 */
export interface FullscreenControlState extends ControlState {
  /** Находится ли карта в полноэкранном режиме */
  isFullscreen: boolean;
  /** Поддерживается ли Fullscreen API */
  isSupported: boolean;
}

/**
 * Данные события FullscreenControl
 */
export interface FullscreenControlEventData {
  /** Целевой контрол */
  target: FullscreenControl;
  /** Текущее состояние полноэкранного режима */
  isFullscreen: boolean;
  /** Поддержка Fullscreen API */
  supported: boolean;
}

/**
 * События FullscreenControl
 */
export interface FullscreenControlEvents {
  /** Создание контрола */
  create: FullscreenControlEventData;
  /** Вход в полноэкранный режим */
  fullscreenenter: FullscreenControlEventData;
  /** Выход из полноэкранного режима */
  fullscreenexit: FullscreenControlEventData;
  /** Клик по кнопке контрола */
  buttonclick: FullscreenControlEventData;
}

/**
 * Класс контрола полноэкранного режима для Яндекс Карт
 */
export declare class FullscreenControl extends ControlBase {
  /**
   * Состояние полноэкранного режима
   */
  private _isFullscreen: boolean;

  /**
   * Поддержка Fullscreen API
   */
  private _isSupported: boolean;

  /**
   * Слушатель событий контейнера карты
   */
  private _mapContainerListener: any;

  /**
   * Слушатель событий документа
   */
  private _documentListener: any;

  /**
   * Элемент кнопки
   */
  private _button: HTMLButtonElement | null;

  /**
   * Элемент иконки
   */
  private _icon: HTMLElement | null;

  /**
   * Создание нового экземпляра FullscreenControl
   * @param options Настройки контрола
   */
  constructor(options?: FullscreenControlOptions);

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
   * Вход в полноэкранный режим
   * @returns true если успешно
   */
  enterFullscreen(): Promise<boolean>;

  /**
   * Выход из полноэкранного режима
   * @returns true если успешно
   */
  exitFullscreen(): Promise<boolean>;

  /**
   * Переключение полноэкранного режима
   * @returns true если операция успешна
   */
  toggleFullscreen(): Promise<boolean>;

  /**
   * Проверка текущего состояния полноэкранного режима
   * @returns true если карта в полноэкранном режиме
   */
  isFullscreen(): boolean;

  /**
   * Получение текущего состояния контрола
   */
  getState(): FullscreenControlState;

  /**
   * Установка состояния контрола
   * @param state Новое состояние
   */
  setState(state: Partial<FullscreenControlState>): Promise<void>;

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
   * Выбор контрола (активация)
   */
  select(): void;

  /**
   * Отмена выбора контрола (деактивация)
   */
  deselect(): void;

  /**
   * Уничтожение контрола
   */
  destroy(): Promise<void>;

  /**
   * Проверка поддержки Fullscreen API
   * @private
   */
  private _checkFullscreenSupport(): boolean;

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
   * Обработчик изменения fullscreen состояния
   * @private
   */
  private _onFullscreenChange(): void;

  /**
   * Получение текущего состояния fullscreen
   * @returns true если активен полноэкранный режим
   * @private
   */
  private _getCurrentFullscreenState(): boolean;

  /**
   * Обновление состояния кнопки
   * @private
   */
  private _updateButtonState(): void;

  /**
   * Обработчик нажатия клавиш
   * @param event Событие клавиатуры
   * @private
   */
  private _onKeyDown(event: KeyboardEvent): void;

  /**
   * Создание событий контрола
   * @param type Тип события
   * @param data Данные события
   * @private
   */
  private _fireEvent(type: keyof FullscreenControlEvents, data?: Partial<FullscreenControlEventData>): void;
}

/**
 * Фабрика для создания FullscreenControl
 * @param options Опции контрола
 * @returns Экземпляр FullscreenControl
 */
export declare function createFullscreenControl(options?: FullscreenControlOptions): FullscreenControl;

/**
 * Проверка поддержки Fullscreen API в браузере
 * @returns true если Fullscreen API поддерживается
 */
export declare function isFullscreenSupported(): boolean;

export default FullscreenControl;