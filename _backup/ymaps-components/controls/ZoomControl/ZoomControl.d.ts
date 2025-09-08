/**
 * TypeScript определения для ZoomControl компонента
 * Полная типизация без any типов в соответствии с принципами CLAUDE.md
 * 
 * @version 1.0.0
 * @author SPA Platform
 */

import { ControlBase, ControlBaseOptions, ControlBaseEvents } from '../ControlBase'

/**
 * Размеры контрола масштабирования
 */
export type ZoomControlSize = 'small' | 'medium' | 'large'

/**
 * Опции слайдера масштабирования
 */
export interface ZoomControlSliderOptions {
  /** Непрерывное изменение зума при перетаскивании */
  continuous?: boolean
}

/**
 * Опции для анимации зума
 */
export interface ZoomAnimationOptions {
  /** Длительность анимации в миллисекундах */
  duration?: number
  /** Использовать плавную анимацию */
  smooth?: boolean
}

/**
 * Диапазон значений зума
 */
export interface ZoomRange {
  /** Минимальный уровень зума */
  min: number
  /** Максимальный уровень зума */
  max: number
}

/**
 * Опции контрола масштабирования
 */
export interface ZoomControlOptions extends ControlBaseOptions {
  /** Размер контрола */
  size?: ZoomControlSize
  /** Показывать слайдер */
  showSlider?: boolean
  /** Показывать кнопки +/- */
  showButtons?: boolean
  /** Длительность анимации зума в мс */
  zoomDuration?: number
  /** Плавное изменение зума */
  smooth?: boolean
  /** Шаг изменения зума кнопками */
  step?: number
  /** Настройки слайдера */
  slider?: ZoomControlSliderOptions
}

/**
 * События контрола масштабирования
 */
export interface ZoomControlEvents extends ControlBaseEvents {
  /** Изменение уровня зума */
  zoomchange: {
    /** Предыдущий уровень зума */
    oldZoom: number
    /** Новый уровень зума */
    newZoom: number
  }
  /** Увеличение масштаба кнопкой */
  zoomin: {
    /** Текущий уровень зума */
    zoom: number
  }
  /** Уменьшение масштаба кнопкой */
  zoomout: {
    /** Текущий уровень зума */
    zoom: number
  }
  /** Начало перетаскивания слайдера */
  dragstart: {
    /** Уровень зума на момент начала */
    zoom: number
  }
  /** Перетаскивание слайдера */
  drag: {
    /** Текущий уровень зума */
    zoom: number
  }
  /** Окончание перетаскивания слайдера */
  dragend: {
    /** Финальный уровень зума */
    zoom: number
  }
}

/**
 * Тип обработчика событий ZoomControl
 */
export type ZoomControlEventHandler<T extends keyof ZoomControlEvents> = (
  event: {
    type: T
    target: ZoomControl
  } & ZoomControlEvents[T]
) => void

/**
 * Основной класс контрола масштабирования карты
 */
export declare class ZoomControl extends ControlBase {
  /**
   * Создает экземпляр контрола масштабирования
   * @param options Опции контрола
   */
  constructor(options?: ZoomControlOptions)

  // Публичные методы

  /**
   * Получить текущий уровень зума
   * @returns Текущий уровень зума
   */
  getZoom(): number

  /**
   * Установить уровень зума
   * @param zoom Уровень зума
   * @param options Опции анимации
   * @throws {Error} Если указан некорректный уровень зума
   */
  setZoom(zoom: number, options?: ZoomAnimationOptions): Promise<void>

  /**
   * Увеличить масштаб на один шаг
   */
  zoomIn(): Promise<void>

  /**
   * Уменьшить масштаб на один шаг
   */
  zoomOut(): Promise<void>

  /**
   * Получить диапазон допустимых значений зума
   * @returns Объект с минимальным и максимальным значениями зума
   */
  getZoomRange(): ZoomRange

  /**
   * Установить диапазон зума
   * @param min Минимальный зум
   * @param max Максимальный зум
   * @throws {Error} Если указан некорректный диапазон
   */
  setZoomRange(min: number, max: number): void

  // Методы событий с типизацией

  /**
   * Добавить обработчик события
   * @param event Тип события
   * @param handler Обработчик события
   */
  on<T extends keyof ZoomControlEvents>(
    event: T,
    handler: ZoomControlEventHandler<T>
  ): void

  /**
   * Удалить обработчик события
   * @param event Тип события
   * @param handler Обработчик события
   */
  off<T extends keyof ZoomControlEvents>(
    event: T,
    handler: ZoomControlEventHandler<T>
  ): void

  // Переопределенные методы из ControlBase с правильными типами

  /**
   * Получить опции контрола
   * @returns Копия опций контрола
   */
  getOptions(): ZoomControlOptions

  /**
   * Установить опцию контрола
   * @param key Ключ опции
   * @param value Значение опции
   */
  setOption<K extends keyof ZoomControlOptions>(
    key: K,
    value: ZoomControlOptions[K]
  ): void
}

/**
 * Вспомогательные типы для проверки размеров
 */
export namespace ZoomControlTypes {
  /**
   * Проверка валидности размера контрола
   */
  export function isValidSize(size: string): size is ZoomControlSize

  /**
   * Проверка валидности уровня зума
   */
  export function isValidZoom(zoom: number, range: ZoomRange): boolean

  /**
   * Проверка валидности диапазона зума
   */
  export function isValidZoomRange(min: number, max: number): boolean

  /**
   * Получение размера по умолчанию
   */
  export function getDefaultSize(): ZoomControlSize

  /**
   * Получение диапазона зума по умолчанию
   */
  export function getDefaultZoomRange(): ZoomRange
}

/**
 * Константы для ZoomControl
 */
export declare const ZoomControlConstants: {
  /** Размеры контрола по умолчанию */
  readonly DEFAULT_SIZE: ZoomControlSize
  /** Длительность анимации по умолчанию */
  readonly DEFAULT_ANIMATION_DURATION: number
  /** Шаг изменения зума по умолчанию */
  readonly DEFAULT_STEP: number
  /** Минимальный возможный зум */
  readonly MIN_ZOOM: number
  /** Максимальный возможный зум */
  readonly MAX_ZOOM: number
  /** Диапазон зума по умолчанию */
  readonly DEFAULT_ZOOM_RANGE: ZoomRange
  /** Допустимые размеры */
  readonly VALID_SIZES: readonly ZoomControlSize[]
}

// Экспорт по умолчанию
export default ZoomControl