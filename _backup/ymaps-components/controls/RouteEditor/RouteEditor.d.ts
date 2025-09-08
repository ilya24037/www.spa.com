/**
 * TypeScript определения для RouteEditor компонента
 * Полная типизация без any типов в соответствии с принципами CLAUDE.md
 * 
 * @version 1.0.0
 * @author SPA Platform
 */

import { ControlBase, ControlBaseOptions, ControlBaseEvents } from '../ControlBase'

/**
 * Режимы передвижения
 */
export type TravelMode = 'driving' | 'walking' | 'transit' | 'bicycle'

/**
 * Единицы измерения
 */
export type Units = 'metric' | 'imperial'

/**
 * Тип путевой точки
 */
export type WaypointType = 'start' | 'waypoint' | 'end' | 'coordinates' | 'address'

/**
 * Статус маршрута
 */
export type RouteStatus = 'pending' | 'calculating' | 'ready' | 'failed'

/**
 * Путевая точка
 */
export interface Waypoint {
  /** Координаты [широта, долгота] */
  coordinates: [number, number]
  /** Адрес точки */
  address?: string
  /** Тип точки */
  type: WaypointType
  /** Название точки */
  name?: string
  /** Индекс в маршруте */
  index?: number
  /** Дополнительные данные */
  metadata?: Record<string, unknown>
}

/**
 * Сегмент маршрута
 */
export interface RouteSegment {
  /** Индекс сегмента */
  index: number
  /** Координаты сегмента */
  coordinates: [number, number][]
  /** Расстояние сегмента в метрах */
  distance: number
  /** Время прохождения в секундах */
  duration: number
  /** Инструкция для сегмента */
  instruction?: string
  /** Тип дороги */
  roadType?: string
}

/**
 * Инструкция маршрута
 */
export interface RouteInstruction {
  /** Индекс инструкции */
  index: number
  /** Текст инструкции */
  text: string
  /** Расстояние до следующего маневра */
  distance: number
  /** Время до следующего маневра */
  duration: number
  /** Тип маневра */
  maneuver?: string
  /** Направление поворота */
  direction?: 'left' | 'right' | 'straight' | 'u-turn'
  /** Координаты маневра */
  coordinates?: [number, number]
}

/**
 * Данные маршрута
 */
export interface Route {
  /** Индекс маршрута */
  index: number
  /** Путевые точки */
  waypoints: Waypoint[]
  /** Геометрия маршрута */
  geometry: unknown
  /** Свойства маршрута */
  properties: unknown
  /** Общее расстояние в метрах */
  distance: number
  /** Общее время в секундах */
  duration: number
  /** Сегменты маршрута */
  segments: RouteSegment[]
  /** Инструкции навигации */
  instructions: RouteInstruction[]
  /** Статус маршрута */
  status?: RouteStatus
  /** Описание маршрута */
  description?: string
  /** Альтернативный маршрут */
  isAlternative?: boolean
}

/**
 * Ограничения маршрута
 */
export interface RouteConstraints {
  /** Избегать платных дорог */
  avoidTolls?: boolean
  /** Избегать автомагистралей */
  avoidHighways?: boolean
  /** Избегать паромов */
  avoidFerries?: boolean
  /** Избегать пробок */
  avoidTrafficJams?: boolean
}

/**
 * Опции маршрутизации
 */
export interface RouteOptions extends RouteConstraints {
  /** Множественные маршруты */
  multiRoute?: boolean
  /** Перетаскивание путевых точек */
  wayPointDragging?: boolean
  /** Показать альтернативные маршруты */
  showAlternatives?: boolean
  /** Максимальное количество альтернатив */
  maxAlternatives?: number
}

/**
 * Опции RouteEditor
 */
export interface RouteEditorOptions extends ControlBaseOptions {
  /** Доступные режимы передвижения */
  travelModes?: TravelMode[]
  /** Режим передвижения по умолчанию */
  defaultTravelMode?: TravelMode
  /** Разрешить множественные маршруты */
  allowMultipleRoutes?: boolean
  /** Максимальное количество путевых точек */
  maxWaypoints?: number
  /** Включить перетаскивание точек */
  enableDragDrop?: boolean
  /** Включить оптимизацию маршрута */
  enableOptimization?: boolean
  /** Показать расстояние и время */
  showDistanceTime?: boolean
  /** Показать альтернативные маршруты */
  showAlternatives?: boolean
  /** Ограничения маршрута */
  avoidTolls?: boolean
  avoidHighways?: boolean
  avoidFerries?: boolean
  /** Единицы измерения */
  units?: Units
  /** Язык направлений */
  language?: string
  /** Кастомный рендерер путевых точек */
  waypointRenderer?: (waypoint: Waypoint, index: number) => HTMLElement | string
  /** Кастомный рендерер маршрута */
  routeRenderer?: (route: Route) => HTMLElement | string
  /** Кастомный рендерер инструкций */
  instructionsRenderer?: (instructions: RouteInstruction[]) => HTMLElement | string
}

/**
 * События RouteEditor
 */
export interface RouteEditorEvents extends ControlBaseEvents {
  /** Готовность API */
  apiready: {
    /** Доступность маршрутизатора */
    router: boolean
    /** Доступность провайдера перетаскивания */
    dragProvider: boolean
  }
  /** Ошибка API */
  apierror: {
    /** Объект ошибки */
    error: Error
  }
  /** Изменение режима передвижения */
  travelmodechange: {
    /** Предыдущий режим */
    oldMode: TravelMode
    /** Новый режим */
    newMode: TravelMode
  }
  /** Добавление путевой точки */
  waypointadd: {
    /** Индекс добавленной точки */
    index: number
    /** Общее количество точек */
    total: number
  }
  /** Удаление путевой точки */
  waypointremove: {
    /** Индекс удаленной точки */
    index: number
    /** Удаленная точка */
    waypoint: Waypoint
    /** Общее количество точек */
    total: number
  }
  /** Установка путевой точки */
  waypointset: {
    /** Индекс точки */
    index: number
    /** Данные точки */
    waypoint: Waypoint
  }
  /** Изменение путевой точки */
  waypointchange: {
    /** Индекс точки */
    index: number
    /** Новое значение */
    value: string
  }
  /** Перетаскивание путевой точки */
  waypointdrag: {
    /** Индекс точки */
    index: number
    /** Новые координаты */
    coordinates: [number, number]
    /** Старые координаты */
    oldCoordinates: [number, number]
  }
  /** Начало расчета маршрута */
  calculatestart: Record<string, never>
  /** Окончание расчета маршрута */
  calculateend: Record<string, never>
  /** Маршрут рассчитан */
  routecalculated: {
    /** Массив маршрутов */
    routes: Route[]
    /** Индекс активного маршрута */
    activeIndex: number
  }
  /** Выбор маршрута */
  routeselect: {
    /** Предыдущий индекс */
    oldIndex: number
    /** Новый индекс */
    newIndex: number
    /** Выбранный маршрут */
    route: Route
  }
  /** Клик по маршруту */
  routeclick: {
    /** Индекс маршрута */
    routeIndex: number
    /** Координаты клика */
    coordinates: [number, number]
    /** Маршрут */
    route: Route
  }
  /** Наведение на маршрут */
  routehover: {
    /** Индекс маршрута */
    routeIndex: number
    /** Состояние наведения */
    isHovered: boolean
    /** Маршрут */
    route: Route
  }
  /** Оптимизация маршрута */
  optimize: {
    /** Оптимизированные путевые точки */
    waypoints: Waypoint[]
  }
  /** Очистка маршрута */
  clear: Record<string, never>
  /** Изменение режима редактирования */
  editingmodechange: {
    /** Включен ли режим редактирования */
    enabled: boolean
  }
}

/**
 * Тип обработчика событий RouteEditor
 */
export type RouteEditorEventHandler<T extends keyof RouteEditorEvents> = (
  event: {
    type: T
    target: RouteEditor
  } & RouteEditorEvents[T]
) => void

/**
 * DOM элементы RouteEditor
 */
export interface RouteEditorElements {
  /** Основной контейнер */
  container: HTMLElement | null
  /** Панель управления */
  panel: HTMLElement | null
  /** Заголовок */
  header: HTMLElement | null
  /** Селектор режима передвижения */
  travelModeSelect: HTMLSelectElement | null
  /** Контейнер путевых точек */
  waypointsContainer: HTMLElement | null
  /** Кнопка добавления точки */
  addWaypointButton: HTMLButtonElement | null
  /** Кнопка очистки */
  clearButton: HTMLButtonElement | null
  /** Кнопка оптимизации */
  optimizeButton: HTMLButtonElement | null
  /** Кнопка расчета */
  calculateButton: HTMLButtonElement | null
  /** Контейнер маршрутов */
  routesContainer: HTMLElement | null
  /** Контейнер инструкций */
  instructionsContainer: HTMLElement | null
  /** Панель опций */
  optionsPanel: HTMLElement | null
  /** Индикатор загрузки */
  loadingIndicator: HTMLElement | null
}

/**
 * Результат геокодирования
 */
export interface GeocodeResult {
  /** Координаты */
  coordinates: [number, number]
  /** Адрес */
  address: string
  /** Точность геокодирования */
  precision?: string
  /** Дополнительные данные */
  metadata?: Record<string, unknown>
}

/**
 * Основной класс RouteEditor
 */
export declare class RouteEditor extends ControlBase {
  /**
   * Создает экземпляр редактора маршрутов
   * @param options Опции редактора
   */
  constructor(options?: RouteEditorOptions)

  // Публичные методы - Путевые точки

  /**
   * Устанавливает путевую точку
   * @param index Индекс точки
   * @param location Адрес или координаты
   * @throws {Error} Если индекс вне допустимого диапазона
   * @throws {Error} Если не удается найти адрес
   */
  setWaypoint(index: number, location: string | [number, number]): Promise<void>

  /**
   * Получает путевую точку
   * @param index Индекс точки
   * @returns Данные точки или null
   */
  getWaypoint(index: number): Waypoint | null

  /**
   * Получает все путевые точки
   * @returns Массив путевых точек
   */
  getWaypoints(): Waypoint[]

  /**
   * Добавляет новую путевую точку
   * @param location Адрес или координаты
   * @param index Позиция для вставки (опционально)
   * @returns Индекс добавленной точки
   */
  addWaypoint(location?: string | [number, number], index?: number): Promise<number>

  /**
   * Удаляет путевую точку
   * @param index Индекс точки для удаления
   * @throws {Error} Если индекс некорректен
   */
  removeWaypoint(index: number): void

  // Публичные методы - Режим передвижения

  /**
   * Устанавливает режим передвижения
   * @param mode Режим передвижения
   * @throws {Error} Если режим не поддерживается
   */
  setTravelMode(mode: TravelMode): void

  /**
   * Получает текущий режим передвижения
   * @returns Режим передвижения
   */
  getTravelMode(): TravelMode

  /**
   * Получает доступные режимы передвижения
   * @returns Массив доступных режимов
   */
  getAvailableTravelModes(): TravelMode[]

  // Публичные методы - Маршруты

  /**
   * Запускает расчет маршрута
   * @returns Массив рассчитанных маршрутов
   * @throws {Error} Если недостаточно точек для расчета
   */
  calculateRoute(): Promise<Route[]>

  /**
   * Получает рассчитанные маршруты
   * @returns Массив маршрутов
   */
  getRoutes(): Route[]

  /**
   * Получает активный маршрут
   * @returns Активный маршрут или null
   */
  getActiveRoute(): Route | null

  /**
   * Выбирает маршрут как активный
   * @param routeIndex Индекс маршрута
   * @throws {Error} Если индекс некорректен
   */
  selectRoute(routeIndex: number): void

  /**
   * Оптимизирует порядок путевых точек
   * @returns Оптимизированные путевые точки
   * @throws {Error} Если недостаточно точек для оптимизации
   */
  optimizeRoute(): Promise<Waypoint[]>

  // Публичные методы - Управление

  /**
   * Очищает все маршруты и точки
   */
  clear(): void

  /**
   * Включает/выключает режим редактирования
   * @param enabled Включить режим редактирования
   */
  setEditingMode(enabled: boolean): void

  /**
   * Проверяет включен ли режим редактирования
   * @returns Состояние режима редактирования
   */
  isEditingMode(): boolean

  /**
   * Проверяет идет ли расчет маршрута
   * @returns Состояние расчета
   */
  isCalculating(): boolean

  // Публичные методы - Настройки

  /**
   * Устанавливает ограничения маршрута
   * @param constraints Ограничения
   */
  setConstraints(constraints: Partial<RouteConstraints>): void

  /**
   * Получает текущие ограничения маршрута
   * @returns Ограничения
   */
  getConstraints(): RouteConstraints

  /**
   * Устанавливает единицы измерения
   * @param units Единицы измерения
   */
  setUnits(units: Units): void

  /**
   * Получает единицы измерения
   * @returns Единицы измерения
   */
  getUnits(): Units

  /**
   * Устанавливает максимальное количество путевых точек
   * @param maxWaypoints Максимальное количество (2-23)
   * @throws {Error} Если значение вне допустимого диапазона
   */
  setMaxWaypoints(maxWaypoints: number): void

  /**
   * Получает максимальное количество путевых точек
   * @returns Максимальное количество
   */
  getMaxWaypoints(): number

  // Методы событий с типизацией

  /**
   * Добавить обработчик события
   * @param event Тип события
   * @param handler Обработчик события
   */
  on<T extends keyof RouteEditorEvents>(
    event: T,
    handler: RouteEditorEventHandler<T>
  ): void

  /**
   * Удалить обработчик события
   * @param event Тип события
   * @param handler Обработчик события
   */
  off<T extends keyof RouteEditorEvents>(
    event: T,
    handler: RouteEditorEventHandler<T>
  ): void

  // Переопределенные методы из ControlBase

  /**
   * Получить опции редактора
   * @returns Копия опций редактора
   */
  getOptions(): RouteEditorOptions

  /**
   * Установить опцию редактора
   * @param key Ключ опции
   * @param value Значение опции
   */
  setOption<K extends keyof RouteEditorOptions>(
    key: K,
    value: RouteEditorOptions[K]
  ): void
}

/**
 * Утилиты для RouteEditor
 */
export declare const RouteEditorUtils: {
  /**
   * Форматирует расстояние
   * @param meters Расстояние в метрах
   * @returns Отформатированная строка
   */
  formatDistance(meters: number): string

  /**
   * Форматирует время
   * @param seconds Время в секундах
   * @returns Отформатированная строка
   */
  formatDuration(seconds: number): string

  /**
   * Проверяет корректность координат
   * @param coords Координаты для проверки
   * @returns Результат проверки
   */
  isValidCoordinates(coords: unknown): coords is [number, number]
}

/**
 * Вспомогательные типы и функции
 */
export namespace RouteEditorTypes {
  /**
   * Проверка валидности режима передвижения
   */
  export function isValidTravelMode(mode: string): mode is TravelMode

  /**
   * Проверка валидности путевой точки
   */
  export function isValidWaypoint(waypoint: unknown): waypoint is Waypoint

  /**
   * Проверка валидности маршрута
   */
  export function isValidRoute(route: unknown): route is Route

  /**
   * Создание путевой точки
   */
  export function createWaypoint(
    coordinates: [number, number],
    type: WaypointType,
    options?: Partial<Omit<Waypoint, 'coordinates' | 'type'>>
  ): Waypoint

  /**
   * Создание ограничений маршрута
   */
  export function createConstraints(
    options?: Partial<RouteConstraints>
  ): RouteConstraints

  /**
   * Сортировка маршрутов по времени
   */
  export function sortRoutesByDuration(routes: Route[]): Route[]

  /**
   * Сортировка маршрутов по расстоянию
   */
  export function sortRoutesByDistance(routes: Route[]): Route[]

  /**
   * Фильтрация маршрутов по критериям
   */
  export function filterRoutes(
    routes: Route[],
    predicate: (route: Route) => boolean
  ): Route[]

  /**
   * Расчет общего расстояния путевых точек
   */
  export function calculateTotalDistance(waypoints: Waypoint[]): number

  /**
   * Проверка пересечения маршрутов
   */
  export function checkRouteIntersection(route1: Route, route2: Route): boolean
}

/**
 * Константы RouteEditor
 */
export declare const RouteEditorConstants: {
  /** Режим передвижения по умолчанию */
  readonly DEFAULT_TRAVEL_MODE: TravelMode
  /** Максимальное количество путевых точек */
  readonly MAX_WAYPOINTS: number
  /** Минимальное количество путевых точек */
  readonly MIN_WAYPOINTS: number
  /** Единицы измерения по умолчанию */
  readonly DEFAULT_UNITS: Units
  /** Язык по умолчанию */
  readonly DEFAULT_LANGUAGE: string
  /** Допустимые режимы передвижения */
  readonly VALID_TRAVEL_MODES: readonly TravelMode[]
  /** Допустимые единицы измерения */
  readonly VALID_UNITS: readonly Units[]
  /** Максимальное время расчета маршрута (мс) */
  readonly MAX_CALCULATION_TIME: number
  /** Минимальное расстояние между точками (м) */
  readonly MIN_WAYPOINT_DISTANCE: number
}

/**
 * Настройки оптимизации маршрута
 */
export interface OptimizationSettings {
  /** Алгоритм оптимизации */
  algorithm: 'nearest_neighbor' | 'genetic' | 'simulated_annealing'
  /** Максимальное время оптимизации */
  maxTime: number
  /** Точность оптимизации */
  precision: 'fast' | 'balanced' | 'precise'
  /** Сохранять первую и последнюю точки */
  preserveEndpoints: boolean
}

/**
 * Статистика маршрута
 */
export interface RouteStatistics {
  /** Общее расстояние */
  totalDistance: number
  /** Общее время */
  totalDuration: number
  /** Количество сегментов */
  segmentCount: number
  /** Средняя скорость */
  averageSpeed: number
  /** Максимальная скорость */
  maxSpeed: number
  /** Количество поворотов */
  turnCount: number
  /** Сложность маршрута (1-10) */
  complexity: number
}

/**
 * Настройки экспорта маршрута
 */
export interface ExportSettings {
  /** Формат экспорта */
  format: 'gpx' | 'kml' | 'geojson' | 'csv'
  /** Включить путевые точки */
  includeWaypoints: boolean
  /** Включить инструкции */
  includeInstructions: boolean
  /** Включить метаданные */
  includeMetadata: boolean
  /** Точность координат (знаков после запятой) */
  coordinatePrecision: number
}

/**
 * Расширенные опции RouteEditor
 */
export interface ExtendedRouteEditorOptions extends RouteEditorOptions {
  /** Настройки оптимизации */
  optimizationSettings?: Partial<OptimizationSettings>
  /** Настройки экспорта */
  exportSettings?: Partial<ExportSettings>
  /** Колбэк валидации путевой точки */
  validateWaypoint?: (waypoint: Waypoint, index: number) => boolean | Promise<boolean>
  /** Колбэк предобработки маршрута */
  preprocessRoute?: (route: Route) => Route
  /** Колбэк постобработки маршрута */
  postprocessRoute?: (route: Route) => Route
  /** Кастомные стили маршрута */
  customStyles?: {
    routeStyle?: Record<string, unknown>
    waypointStyle?: Record<string, unknown>
    activeRouteStyle?: Record<string, unknown>
  }
}

/**
 * Интерфейс интеграции с картой для маршрутов
 */
export interface MapRouteIntegration {
  /** Добавить маршрут на карту */
  addRouteToMap(route: Route): unknown
  /** Удалить маршрут с карты */
  removeRouteFromMap(routeId: string): void
  /** Обновить отображение маршрута */
  updateRouteDisplay(routeId: string, route: Route): void
  /** Добавить путевую точку на карту */
  addWaypointToMap(waypoint: Waypoint): unknown
  /** Удалить путевую точку с карты */
  removeWaypointFromMap(waypointId: string): void
  /** Подстроить карту под маршрут */
  fitMapToRoute(route: Route, margin?: number[]): void
  /** Выделить активный маршрут */
  highlightActiveRoute(routeIndex: number): void
}

/**
 * Менеджер истории маршрутов
 */
export interface RouteHistoryManager {
  /** Сохранить маршрут в истории */
  saveRoute(route: Route, name?: string): void
  /** Загрузить маршрут из истории */
  loadRoute(id: string): Route | null
  /** Получить историю маршрутов */
  getHistory(): Array<{ id: string; name: string; route: Route; timestamp: Date }>
  /** Очистить историю */
  clearHistory(): void
  /** Экспорт истории */
  exportHistory(format: 'json' | 'csv'): string
}

// Экспорт по умолчанию
export default RouteEditor