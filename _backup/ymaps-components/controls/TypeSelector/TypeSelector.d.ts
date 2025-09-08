/**
 * TypeScript определения для TypeSelector компонента
 * Полная типизация без any типов в соответствии с принципами CLAUDE.md
 * 
 * @version 1.0.0
 * @author SPA Platform
 */

import { ControlBase, ControlBaseOptions, ControlBaseEvents } from '../ControlBase'

/**
 * Режимы отображения селектора типов
 */
export type TypeSelectorMode = 'dropdown' | 'buttons' | 'compact'

/**
 * Направление для кнопочного режима
 */
export type TypeSelectorDirection = 'horizontal' | 'vertical'

/**
 * Конфигурация типа карты
 */
export interface MapTypeConfig {
  /** Уникальный ключ типа карты */
  key: string
  /** Отображаемое название */
  name: string
  /** Подсказка (tooltip) */
  title?: string
  /** Ключ иконки */
  icon?: string
  /** Дополнительные метаданные */
  metadata?: Record<string, unknown>
}

/**
 * Опции селектора типов карт
 */
export interface TypeSelectorOptions extends ControlBaseOptions {
  /** Доступные типы карт */
  mapTypes?: MapTypeConfig[]
  /** Режим отображения */
  mode?: TypeSelectorMode
  /** Направление для кнопочного режима */
  direction?: TypeSelectorDirection
  /** Показывать названия типов */
  showLabels?: boolean
  /** Показывать иконки */
  showIcons?: boolean
  /** Тип карты по умолчанию */
  defaultType?: string
  /** Автоматически определять доступные типы */
  autoDetect?: boolean
  /** Кастомные типы карт */
  customTypes?: Record<string, MapTypeConfig>
  /** Компактный режим на мобильных устройствах */
  compactOnMobile?: boolean
}

/**
 * События селектора типов карт
 */
export interface TypeSelectorEvents extends ControlBaseEvents {
  /** Изменение типа карты */
  typechange: {
    /** Предыдущий тип карты */
    oldType: string | null
    /** Новый тип карты */
    newType: string
  }
  /** Добавление нового типа карты */
  typeadd: {
    /** Добавленный тип */
    type: MapTypeConfig
  }
  /** Удаление типа карты */
  typeremove: {
    /** Удаленный тип */
    type: MapTypeConfig
  }
  /** Открытие выпадающего списка */
  dropdownopen: Record<string, never>
  /** Закрытие выпадающего списка */
  dropdownclose: Record<string, never>
}

/**
 * Тип обработчика событий TypeSelector
 */
export type TypeSelectorEventHandler<T extends keyof TypeSelectorEvents> = (
  event: {
    type: T
    target: TypeSelector
  } & TypeSelectorEvents[T]
) => void

/**
 * Состояние выпадающего списка
 */
export interface DropdownState {
  /** Открыт ли список */
  isOpen: boolean
  /** Индекс выбранного элемента */
  selectedIndex: number
}

/**
 * DOM элементы селектора типов
 */
export interface TypeSelectorElements {
  /** Основной контейнер */
  container: HTMLElement | null
  /** Контейнер выпадающего списка */
  dropdown: HTMLElement | null
  /** Кнопка выпадающего списка */
  dropdownButton: HTMLButtonElement | null
  /** Список элементов */
  dropdownList: HTMLUListElement | null
  /** Группа кнопок */
  buttonGroup: HTMLElement | null
  /** Карта кнопок по типам */
  buttons: Map<string, HTMLButtonElement>
}

/**
 * Основной класс селектора типов карт
 */
export declare class TypeSelector extends ControlBase {
  /**
   * Создает экземпляр селектора типов карт
   * @param options Опции селектора
   */
  constructor(options?: TypeSelectorOptions)

  // Публичные методы

  /**
   * Получить текущий тип карты
   * @returns Ключ текущего типа карты или null
   */
  getCurrentType(): string | null

  /**
   * Установить тип карты
   * @param type Ключ типа карты
   * @throws {TypeError} Если тип не является строкой
   * @throws {Error} Если тип недоступен
   */
  setCurrentType(type: string): Promise<void>

  /**
   * Получить список доступных типов карт
   * @returns Массив конфигураций типов карт
   */
  getAvailableTypes(): MapTypeConfig[]

  /**
   * Добавить новый тип карты
   * @param typeConfig Конфигурация типа
   * @param position Позиция в списке (опционально)
   * @throws {Error} Если конфигурация некорректна или тип уже существует
   */
  addMapType(typeConfig: MapTypeConfig, position?: number): void

  /**
   * Удалить тип карты
   * @param typeKey Ключ типа для удаления
   * @throws {Error} Если тип не найден или это последний тип
   */
  removeMapType(typeKey: string): void

  // Методы событий с типизацией

  /**
   * Добавить обработчик события
   * @param event Тип события
   * @param handler Обработчик события
   */
  on<T extends keyof TypeSelectorEvents>(
    event: T,
    handler: TypeSelectorEventHandler<T>
  ): void

  /**
   * Удалить обработчик события
   * @param event Тип события
   * @param handler Обработчик события
   */
  off<T extends keyof TypeSelectorEvents>(
    event: T,
    handler: TypeSelectorEventHandler<T>
  ): void

  // Переопределенные методы из ControlBase

  /**
   * Получить опции селектора
   * @returns Копия опций селектора
   */
  getOptions(): TypeSelectorOptions

  /**
   * Установить опцию селектора
   * @param key Ключ опции
   * @param value Значение опции
   */
  setOption<K extends keyof TypeSelectorOptions>(
    key: K,
    value: TypeSelectorOptions[K]
  ): void
}

/**
 * Вспомогательные типы и утилиты
 */
export namespace TypeSelectorTypes {
  /**
   * Проверка валидности режима отображения
   */
  export function isValidMode(mode: string): mode is TypeSelectorMode

  /**
   * Проверка валидности направления
   */
  export function isValidDirection(direction: string): direction is TypeSelectorDirection

  /**
   * Проверка валидности конфигурации типа
   */
  export function isValidMapTypeConfig(config: unknown): config is MapTypeConfig

  /**
   * Получение режима по умолчанию
   */
  export function getDefaultMode(): TypeSelectorMode

  /**
   * Получение направления по умолчанию
   */
  export function getDefaultDirection(): TypeSelectorDirection

  /**
   * Создание конфигурации типа карты
   */
  export function createMapTypeConfig(
    key: string,
    name: string,
    options?: Partial<Omit<MapTypeConfig, 'key' | 'name'>>
  ): MapTypeConfig

  /**
   * Проверка доступности типа в массиве
   */
  export function isTypeAvailable(
    typeKey: string,
    availableTypes: MapTypeConfig[]
  ): boolean

  /**
   * Фильтрация типов по критериям
   */
  export function filterTypes(
    types: MapTypeConfig[],
    predicate: (type: MapTypeConfig) => boolean
  ): MapTypeConfig[]
}

/**
 * Предустановленные типы карт Yandex
 */
export declare const DefaultMapTypes: {
  /** Схематическая карта */
  readonly MAP: MapTypeConfig
  /** Спутниковые снимки */
  readonly SATELLITE: MapTypeConfig
  /** Гибридная карта */
  readonly HYBRID: MapTypeConfig
}

/**
 * Константы для TypeSelector
 */
export declare const TypeSelectorConstants: {
  /** Режим по умолчанию */
  readonly DEFAULT_MODE: TypeSelectorMode
  /** Направление по умолчанию */
  readonly DEFAULT_DIRECTION: TypeSelectorDirection
  /** Максимальное количество типов в интерфейсе */
  readonly MAX_VISIBLE_TYPES: number
  /** Ширина мобильного экрана для переключения в компактный режим */
  readonly MOBILE_BREAKPOINT: number
  /** Допустимые режимы */
  readonly VALID_MODES: readonly TypeSelectorMode[]
  /** Допустимые направления */
  readonly VALID_DIRECTIONS: readonly TypeSelectorDirection[]
  /** Стандартные иконки типов */
  readonly DEFAULT_ICONS: Record<string, string>
}

/**
 * Интерфейс для интеграции с картой
 */
export interface MapTypeIntegration {
  /** Получить текущий тип карты */
  getCurrentMapType(): string | null
  /** Установить тип карты */
  setMapType(type: string): Promise<void>
  /** Получить доступные типы карт */
  getAvailableMapTypes(): string[]
  /** Проверить поддержку типа карты */
  isMapTypeSupported(type: string): boolean
}

/**
 * Настройки для мобильной адаптации
 */
export interface MobileAdaptationSettings {
  /** Ширина экрана для переключения в мобильный режим */
  breakpoint: number
  /** Принудительный компактный режим */
  forceCompact: boolean
  /** Размер тач-элементов */
  touchTargetSize: number
  /** Использовать MediaQuery для отслеживания */
  useMediaQuery: boolean
}

/**
 * Конфигурация accessibility
 */
export interface AccessibilityConfig {
  /** Использовать ARIA атрибуты */
  useAria: boolean
  /** Поддержка клавиатурной навигации */
  keyboardNavigation: boolean
  /** Анонсирование изменений для скрин-ридеров */
  announceChanges: boolean
  /** Минимальный размер кликабельных элементов */
  minTouchTarget: number
}

/**
 * Расширенные опции TypeSelector
 */
export interface ExtendedTypeSelectorOptions extends TypeSelectorOptions {
  /** Настройки мобильной адаптации */
  mobileAdaptation?: Partial<MobileAdaptationSettings>
  /** Настройки доступности */
  accessibility?: Partial<AccessibilityConfig>
  /** Колбэк для валидации смены типа */
  validateTypeChange?: (oldType: string | null, newType: string) => boolean | Promise<boolean>
  /** Колбэк для кастомного рендеринга элементов */
  customRenderer?: {
    renderDropdownItem?: (type: MapTypeConfig) => HTMLElement
    renderButton?: (type: MapTypeConfig) => HTMLElement
  }
}

// Экспорт по умолчанию
export default TypeSelector