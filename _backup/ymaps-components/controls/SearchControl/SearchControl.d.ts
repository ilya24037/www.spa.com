/**
 * TypeScript определения для SearchControl компонента
 * Полная типизация без any типов в соответствии с принципами CLAUDE.md
 * 
 * @version 1.0.0
 * @author SPA Platform
 */

import { ControlBase, ControlBaseOptions, ControlBaseEvents } from '../ControlBase'

/**
 * Типы поиска
 */
export type SearchType = 'text' | 'geo' | 'biz'

/**
 * Тип результата поиска
 */
export type SearchResultType = 'suggestion' | 'geocode' | 'unknown'

/**
 * Точность геокодирования
 */
export type GeocodePrecision = 'exact' | 'number' | 'near' | 'range' | 'street' | 'other'

/**
 * Вид объекта по классификации Яндекса
 */
export type GeocodeKind = 'house' | 'street' | 'metro' | 'district' | 'locality' | 'area' | 'province' | 'country' | 'other'

/**
 * Данные геообъекта
 */
export interface GeoObjectData {
  /** Название объекта */
  name?: string
  /** Полный текст адреса */
  text?: string
  /** Описание объекта */
  description?: string
  /** Контент балуна */
  balloonContent?: string
  /** Контент подсказки */
  hintContent?: string
}

/**
 * Результат поиска
 */
export interface SearchResult {
  /** Индекс в массиве результатов */
  index: number
  /** Тип результата */
  type: SearchResultType
  /** Отображаемое название */
  displayName: string
  /** Описание результата */
  description?: string
  /** Координаты [широта, долгота] */
  coordinates?: [number, number]
  /** Границы объекта */
  bounds?: [[number, number], [number, number]]
  /** Полный адрес */
  address?: string
  /** Вид объекта */
  kind?: GeocodeKind
  /** Точность геокодирования */
  precision?: GeocodePrecision
  /** Оригинальный геообъект */
  geoObject?: unknown
  /** Дополнительные данные */
  data?: GeoObjectData
  /** Значение для автодополнения */
  value?: string
}

/**
 * Предложение автодополнения
 */
export interface SearchSuggestion {
  /** Отображаемый текст */
  displayName: string
  /** Значение для подстановки */
  value: string
  /** Дополнительные данные от API */
  data?: Record<string, unknown>
}

/**
 * Опции поиска для Yandex API
 */
export interface YandexSearchOptions {
  /** Количество результатов */
  results?: number
  /** Пропустить результатов */
  skip?: number
  /** Ограничивающие координаты */
  boundedBy?: [[number, number], [number, number]]
  /** Строгие границы поиска */
  strictBounds?: boolean
  /** Дополнительные параметры */
  [key: string]: unknown
}

/**
 * Опции SearchControl
 */
export interface SearchControlOptions extends ControlBaseOptions {
  /** Плейсхолдер поля ввода */
  placeholder?: string
  /** Показать кнопку очистки */
  showClearButton?: boolean
  /** Показать кнопку поиска */
  showSearchButton?: boolean
  /** Включить автодополнение */
  enableAutoComplete?: boolean
  /** Задержка поиска в миллисекундах */
  searchDelay?: number
  /** Максимальное количество результатов */
  maxResults?: number
  /** Типы поиска */
  searchTypes?: SearchType[]
  /** Дополнительные опции поиска */
  searchOptions?: YandexSearchOptions
  /** Подстраивать границы карты под результаты */
  fitResultBounds?: boolean
  /** Добавлять маркер результата */
  addResultMarker?: boolean
  /** Селектор внешнего контейнера для результатов */
  resultsContainer?: string
  /** Функция форматирования результата */
  formatResult?: (result: SearchResult) => string | HTMLElement
  /** Функция фильтрации результатов */
  filterResults?: (result: SearchResult) => boolean
}

/**
 * События SearchControl
 */
export interface SearchControlEvents extends ControlBaseEvents {
  /** Готовность API */
  apiready: {
    /** Доступность геокодера */
    geocoder: boolean
    /** Доступность автодополнения */
    suggest: boolean
  }
  /** Ошибка API */
  apierror: {
    /** Объект ошибки */
    error: Error
  }
  /** Изменение текста в поле ввода */
  inputchange: {
    /** Новое значение */
    value: string
  }
  /** Фокус на поле ввода */
  focus: Record<string, never>
  /** Потеря фокуса поля ввода */
  blur: Record<string, never>
  /** Очистка поиска */
  clear: Record<string, never>
  /** Изменение запроса программно */
  querychange: {
    /** Новый запрос */
    query: string
    /** Запущен ли поиск автоматически */
    triggerSearch: boolean
  }
  /** Начало поиска */
  searchstart: Record<string, never>
  /** Окончание поиска */
  searchend: Record<string, never>
  /** Завершение поиска с результатами */
  searchcomplete: {
    /** Поисковый запрос */
    query: string
    /** Результаты поиска */
    results: SearchResult[]
    /** Общее количество результатов */
    total: number
  }
  /** Выбор результата */
  resultselect: {
    /** Выбранный результат */
    result: SearchResult
    /** Индекс результата */
    index: number
  }
  /** Обработка результата на карте */
  resultprocessed: {
    /** Обработанный результат */
    result: SearchResult
    /** Созданный маркер */
    marker: unknown | null
  }
}

/**
 * Тип обработчика событий SearchControl
 */
export type SearchControlEventHandler<T extends keyof SearchControlEvents> = (
  event: {
    type: T
    target: SearchControl
  } & SearchControlEvents[T]
) => void

/**
 * DOM элементы SearchControl
 */
export interface SearchControlElements {
  /** Основной контейнер */
  container: HTMLElement | null
  /** Поле ввода */
  input: HTMLInputElement | null
  /** Кнопка очистки */
  clearButton: HTMLButtonElement | null
  /** Кнопка поиска */
  searchButton: HTMLButtonElement | null
  /** Индикатор загрузки */
  loadingIndicator: HTMLElement | null
  /** Контейнер результатов */
  resultsDropdown: HTMLElement | null
  /** Список результатов */
  resultsList: HTMLUListElement | null
  /** Сообщение об отсутствии результатов */
  noResults: HTMLElement | null
}

/**
 * Основной класс SearchControl
 */
export declare class SearchControl extends ControlBase {
  /**
   * Создает экземпляр контрола поиска
   * @param options Опции контрола
   */
  constructor(options?: SearchControlOptions)

  // Публичные методы

  /**
   * Устанавливает текст поиска программно
   * @param query Поисковый запрос
   * @param triggerSearch Запустить поиск автоматически
   * @throws {TypeError} Если query не является строкой
   */
  setQuery(query: string, triggerSearch?: boolean): void

  /**
   * Получает текущий поисковый запрос
   * @returns Текущий запрос
   */
  getQuery(): string

  /**
   * Запускает поиск по текущему запросу
   * @returns Результаты поиска
   * @throws {Error} Если поисковый запрос пуст
   */
  search(): Promise<SearchResult[]>

  /**
   * Очищает поиск и результаты
   */
  clear(): void

  /**
   * Получает результаты последнего поиска
   * @returns Массив результатов
   */
  getResults(): SearchResult[]

  /**
   * Получает выбранный результат
   * @returns Выбранный результат или null
   */
  getSelectedResult(): SearchResult | null

  /**
   * Фокус на поле поиска
   */
  focus(): void

  /**
   * Снимает фокус с поля поиска
   */
  blur(): void

  /**
   * Устанавливает плейсхолдер поля ввода
   * @param placeholder Текст плейсхолдера
   * @throws {TypeError} Если placeholder не является строкой
   */
  setPlaceholder(placeholder: string): void

  /**
   * Получает текущий плейсхолдер
   * @returns Текст плейсхолдера
   */
  getPlaceholder(): string

  /**
   * Устанавливает максимальное количество результатов
   * @param maxResults Максимальное количество результатов (1-50)
   * @throws {Error} Если значение вне допустимого диапазона
   */
  setMaxResults(maxResults: number): void

  /**
   * Получает максимальное количество результатов
   * @returns Максимальное количество результатов
   */
  getMaxResults(): number

  /**
   * Включает/выключает автодополнение
   * @param enabled Включить автодополнение
   */
  setAutoComplete(enabled: boolean): void

  /**
   * Проверяет включено ли автодополнение
   * @returns Состояние автодополнения
   */
  isAutoCompleteEnabled(): boolean

  /**
   * Устанавливает задержку поиска
   * @param delay Задержка в миллисекундах (минимум 100)
   * @throws {Error} Если delay не является положительным числом
   */
  setSearchDelay(delay: number): void

  /**
   * Получает задержку поиска
   * @returns Задержка в миллисекундах
   */
  getSearchDelay(): number

  /**
   * Проверяет идет ли поиск в данный момент
   * @returns Состояние поиска
   */
  isSearching(): boolean

  /**
   * Получает маркер последнего выбранного результата
   * @returns Маркер или null
   */
  getResultMarker(): unknown | null

  /**
   * Получает границы последнего результата поиска
   * @returns Границы или null
   */
  getLastSearchBounds(): [[number, number], [number, number]] | null

  // Методы событий с типизацией

  /**
   * Добавить обработчик события
   * @param event Тип события
   * @param handler Обработчик события
   */
  on<T extends keyof SearchControlEvents>(
    event: T,
    handler: SearchControlEventHandler<T>
  ): void

  /**
   * Удалить обработчик события
   * @param event Тип события
   * @param handler Обработчик события
   */
  off<T extends keyof SearchControlEvents>(
    event: T,
    handler: SearchControlEventHandler<T>
  ): void

  // Переопределенные методы из ControlBase

  /**
   * Получить опции контрола
   * @returns Копия опций контрола
   */
  getOptions(): SearchControlOptions

  /**
   * Установить опцию контрола
   * @param key Ключ опции
   * @param value Значение опции
   */
  setOption<K extends keyof SearchControlOptions>(
    key: K,
    value: SearchControlOptions[K]
  ): void
}

/**
 * Утилиты для работы с SearchControl
 */
export declare const SearchControlUtils: {
  /**
   * Проверяет корректность координат
   * @param coordinates Координаты [lat, lng]
   * @returns Результат проверки
   */
  isValidCoordinates(coordinates: unknown): coordinates is [number, number]

  /**
   * Форматирует адрес для отображения
   * @param result Результат поиска
   * @returns Отформатированный адрес
   */
  formatAddress(result: SearchResult | null): string

  /**
   * Определяет тип результата поиска
   * @param result Результат поиска
   * @returns Тип результата
   */
  getResultType(result: SearchResult | null): SearchResultType | 'unknown'
}

/**
 * Вспомогательные типы и интерфейсы
 */
export namespace SearchControlTypes {
  /**
   * Проверка валидности типа поиска
   */
  export function isValidSearchType(type: string): type is SearchType

  /**
   * Проверка валидности результата поиска
   */
  export function isValidSearchResult(result: unknown): result is SearchResult

  /**
   * Проверка валидности предложения автодополнения
   */
  export function isValidSuggestion(suggestion: unknown): suggestion is SearchSuggestion

  /**
   * Создание результата поиска
   */
  export function createSearchResult(
    index: number,
    type: SearchResultType,
    displayName: string,
    options?: Partial<Omit<SearchResult, 'index' | 'type' | 'displayName'>>
  ): SearchResult

  /**
   * Создание предложения автодополнения
   */
  export function createSuggestion(
    displayName: string,
    value: string,
    data?: Record<string, unknown>
  ): SearchSuggestion

  /**
   * Фильтрация результатов по типу
   */
  export function filterByType(
    results: SearchResult[],
    types: SearchResultType[]
  ): SearchResult[]

  /**
   * Сортировка результатов по релевантности
   */
  export function sortByRelevance(
    results: SearchResult[],
    query: string
  ): SearchResult[]

  /**
   * Группировка результатов по типу
   */
  export function groupByType(
    results: SearchResult[]
  ): Record<SearchResultType, SearchResult[]>
}

/**
 * Константы SearchControl
 */
export declare const SearchControlConstants: {
  /** Плейсхолдер по умолчанию */
  readonly DEFAULT_PLACEHOLDER: string
  /** Задержка поиска по умолчанию */
  readonly DEFAULT_SEARCH_DELAY: number
  /** Максимальное количество результатов по умолчанию */
  readonly DEFAULT_MAX_RESULTS: number
  /** Минимальная длина запроса для автодополнения */
  readonly MIN_AUTOCOMPLETE_LENGTH: number
  /** Максимальная длина поискового запроса */
  readonly MAX_QUERY_LENGTH: number
  /** Допустимые типы поиска */
  readonly VALID_SEARCH_TYPES: readonly SearchType[]
  /** Допустимые типы результатов */
  readonly VALID_RESULT_TYPES: readonly SearchResultType[]
  /** Диапазон задержки поиска */
  readonly SEARCH_DELAY_RANGE: {
    readonly min: number
    readonly max: number
  }
  /** Диапазон количества результатов */
  readonly RESULTS_RANGE: {
    readonly min: number
    readonly max: number
  }
}

/**
 * Настройки адаптации для мобильных устройств
 */
export interface MobileSearchSettings {
  /** Увеличенное поле ввода */
  largerInput: boolean
  /** Полноэкранные результаты */
  fullscreenResults: boolean
  /** Виртуальная клавиатура */
  virtualKeyboardSupport: boolean
  /** Размер touch-элементов */
  touchTargetSize: number
}

/**
 * Настройки производительности
 */
export interface SearchPerformanceSettings {
  /** Дебаунс поиска */
  debounceDelay: number
  /** Кеширование результатов */
  cacheResults: boolean
  /** Размер кеша */
  cacheSize: number
  /** Время жизни кеша */
  cacheTTL: number
  /** Пакетная обработка запросов */
  batchRequests: boolean
}

/**
 * Расширенные опции SearchControl
 */
export interface ExtendedSearchControlOptions extends SearchControlOptions {
  /** Настройки мобильной адаптации */
  mobileSettings?: Partial<MobileSearchSettings>
  /** Настройки производительности */
  performanceSettings?: Partial<SearchPerformanceSettings>
  /** Колбэк валидации запроса */
  validateQuery?: (query: string) => boolean | Promise<boolean>
  /** Колбэк предобработки результатов */
  preprocessResults?: (results: SearchResult[]) => SearchResult[]
  /** Колбэк постобработки результатов */
  postprocessResults?: (results: SearchResult[]) => SearchResult[]
  /** Кастомный рендерер автодополнения */
  customAutocompleteRenderer?: {
    renderSuggestion?: (suggestion: SearchSuggestion) => HTMLElement
    renderNoResults?: () => HTMLElement
    renderLoading?: () => HTMLElement
  }
}

/**
 * Интерфейс интеграции с картой
 */
export interface MapSearchIntegration {
  /** Получить текущий центр карты */
  getMapCenter(): [number, number]
  /** Получить текущий зум карты */
  getMapZoom(): number
  /** Получить границы карты */
  getMapBounds(): [[number, number], [number, number]]
  /** Установить центр карты */
  setMapCenter(coordinates: [number, number], zoom?: number): Promise<void>
  /** Установить границы карты */
  setMapBounds(bounds: [[number, number], [number, number]]): Promise<void>
  /** Добавить маркер на карту */
  addMarker(coordinates: [number, number], options?: unknown): unknown
  /** Удалить маркер с карты */
  removeMarker(marker: unknown): void
  /** Проверить видимость точки на карте */
  isPointVisible(coordinates: [number, number]): boolean
}

/**
 * Статистика поиска
 */
export interface SearchStatistics {
  /** Общее количество поисковых запросов */
  totalQueries: number
  /** Количество успешных поисков */
  successfulSearches: number
  /** Количество пустых результатов */
  emptyResults: number
  /** Средняя задержка поиска */
  averageSearchTime: number
  /** Самые популярные запросы */
  topQueries: Array<{ query: string; count: number }>
  /** Статистика по типам результатов */
  resultTypeStats: Record<SearchResultType, number>
}

/**
 * Менеджер статистики поиска
 */
export interface SearchStatisticsManager {
  /** Записать событие поиска */
  recordSearch(query: string, results: SearchResult[], searchTime: number): void
  /** Записать выбор результата */
  recordSelection(result: SearchResult): void
  /** Получить статистику */
  getStatistics(): SearchStatistics
  /** Сбросить статистику */
  resetStatistics(): void
  /** Экспорт статистики */
  exportStatistics(format: 'json' | 'csv'): string
}

// Экспорт по умолчанию
export default SearchControl