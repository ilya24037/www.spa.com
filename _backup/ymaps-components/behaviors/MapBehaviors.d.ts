/**
 * TypeScript определения для модуля MapBehaviors
 * @module MapBehaviors
 */

/**
 * Типы поведений карты
 */
export type BehaviorType = 
  | 'drag'
  | 'dblClickZoom'
  | 'multiTouch'
  | 'rightMouseButtonMagnifier'
  | 'leftMouseButtonMagnifier'
  | 'ruler'
  | 'routeEditor'
  | 'scrollZoom'

/**
 * Опции перетаскивания
 */
export interface DragOptions {
  /** Инерция при перетаскивании */
  inertia?: boolean
  /** Длительность инерции в миллисекундах */
  inertiaDuration?: number
  /** Курсор при наведении */
  cursor?: string
  /** Курсор при перетаскивании */
  cursorDragging?: string
  /** Использовать отступы карты */
  useMapMargin?: boolean
}

/**
 * Опции масштабирования
 */
export interface ZoomOptions {
  /** Плавное масштабирование */
  smooth?: boolean
  /** Длительность анимации в миллисекундах */
  duration?: number
  /** Центрирование при масштабировании */
  centering?: boolean
  /** Диапазон масштабирования [min, max] */
  zoomRange?: [number, number]
  /** Проверять диапазон масштабирования */
  checkZoomRange?: boolean
}

/**
 * Опции мультитач
 */
export interface MultiTouchOptions {
  /** Порог тремора в пикселях */
  tremor?: number
  /** Предотвращать стандартное действие */
  preventDefaultAction?: boolean
}

/**
 * Опции скролла
 */
export interface ScrollZoomOptions {
  /** Скорость масштабирования */
  speed?: number
  /** Плавное масштабирование */
  smooth?: boolean
  /** Центрирование при масштабировании */
  centering?: boolean
}

/**
 * Опции менеджера поведений
 */
export interface MapBehaviorsOptions {
  /** Перетаскивание карты */
  drag?: boolean
  /** Масштабирование двойным кликом */
  dblClickZoom?: boolean
  /** Мультитач жесты */
  multiTouch?: boolean
  /** Лупа правой кнопкой мыши */
  rightMouseButtonMagnifier?: boolean
  /** Лупа левой кнопкой мыши */
  leftMouseButtonMagnifier?: boolean
  /** Измерение расстояний */
  ruler?: boolean
  /** Редактирование маршрутов */
  routeEditor?: boolean
  /** Масштабирование колесом мыши */
  scrollZoom?: boolean
  
  /** Опции перетаскивания */
  dragOptions?: DragOptions
  /** Опции масштабирования */
  zoomOptions?: ZoomOptions
  /** Опции мультитач */
  multiTouchOptions?: MultiTouchOptions
  /** Опции скролла */
  scrollZoomOptions?: ScrollZoomOptions
  
  /** Ограничение области карты */
  restrictMapArea?: [[number, number], [number, number]] | null
  /** Ограничение диапазона зума */
  restrictZoomRange?: [number, number] | null
  
  /** Обработчик включения поведения */
  onBehaviorEnabled?: (behavior: BehaviorType) => void
  /** Обработчик отключения поведения */
  onBehaviorDisabled?: (behavior: BehaviorType) => void
  /** Обработчик начала перетаскивания */
  onDragStart?: (event: any) => void
  /** Обработчик перетаскивания */
  onDrag?: (event: any) => void
  /** Обработчик конца перетаскивания */
  onDragEnd?: (event: any) => void
  /** Обработчик начала масштабирования */
  onZoomStart?: (event: any) => void
  /** Обработчик изменения масштаба */
  onZoomChange?: (event: any) => void
  /** Обработчик конца масштабирования */
  onZoomEnd?: (event: any) => void
}

/**
 * Состояние менеджера поведений
 */
export interface BehaviorsState {
  /** Происходит ли перетаскивание */
  isDragging: boolean
  /** Происходит ли масштабирование */
  isZooming: boolean
  /** Происходит ли панорамирование */
  isPanning: boolean
  /** Текущий уровень масштабирования */
  currentZoom: number | null
  /** Начальная позиция перетаскивания */
  dragStartPosition: [number, number] | null
  /** Включенные поведения */
  enabledBehaviors: BehaviorType[]
}

/**
 * Конфигурация кастомного поведения
 */
export interface CustomBehaviorConfig {
  /** Обработчик включения */
  onEnable?: (map: any, manager: MapBehaviors) => void
  /** Обработчик отключения */
  onDisable?: (map: any, manager: MapBehaviors) => void
  /** Дополнительные параметры */
  [key: string]: any
}

/**
 * События менеджера поведений
 */
export interface BehaviorsEvents {
  /** Поведение включено */
  behaviorEnabled?: (behavior: BehaviorType) => void
  /** Поведение отключено */
  behaviorDisabled?: (behavior: BehaviorType) => void
  /** Начало перетаскивания */
  dragStart?: (event: any) => void
  /** Перетаскивание */
  drag?: (event: any) => void
  /** Конец перетаскивания */
  dragEnd?: (event: any) => void
  /** Начало масштабирования */
  zoomStart?: (event: any) => void
  /** Изменение масштаба */
  zoomChange?: (event: any) => void
  /** Конец масштабирования */
  zoomEnd?: (event: any) => void
  /** Карта заблокирована */
  locked?: () => void
  /** Карта разблокирована */
  unlocked?: () => void
}

/**
 * Класс управления поведениями карты
 */
export class MapBehaviors {
  /**
   * Конструктор
   * @param map - Экземпляр карты Yandex Maps
   * @param options - Опции поведений
   */
  constructor(map: any, options?: MapBehaviorsOptions)
  
  /**
   * Включает поведение
   * @param behavior - Название поведения или массив названий
   */
  enable(behavior: BehaviorType | BehaviorType[]): void
  
  /**
   * Отключает поведение
   * @param behavior - Название поведения или массив названий
   */
  disable(behavior: BehaviorType | BehaviorType[]): void
  
  /**
   * Проверяет, включено ли поведение
   * @param behavior - Название поведения
   * @returns true если включено
   */
  isEnabled(behavior: BehaviorType): boolean
  
  /**
   * Получает список включенных поведений
   * @returns Массив названий поведений
   */
  getEnabled(): BehaviorType[]
  
  /**
   * Включает перетаскивание карты
   */
  enableDrag(): void
  
  /**
   * Отключает перетаскивание карты
   */
  disableDrag(): void
  
  /**
   * Включает масштабирование двойным кликом
   */
  enableDblClickZoom(): void
  
  /**
   * Отключает масштабирование двойным кликом
   */
  disableDblClickZoom(): void
  
  /**
   * Включает мультитач жесты
   */
  enableMultiTouch(): void
  
  /**
   * Отключает мультитач жесты
   */
  disableMultiTouch(): void
  
  /**
   * Включает масштабирование колесом мыши
   */
  enableScrollZoom(): void
  
  /**
   * Отключает масштабирование колесом мыши
   */
  disableScrollZoom(): void
  
  /**
   * Включает линейку
   */
  enableRuler(): void
  
  /**
   * Отключает линейку
   */
  disableRuler(): void
  
  /**
   * Устанавливает ограничение области карты
   * @param bounds - Границы [[minLat, minLng], [maxLat, maxLng]]
   */
  setRestrictMapArea(bounds: [[number, number], [number, number]]): void
  
  /**
   * Снимает ограничение области карты
   */
  removeRestrictMapArea(): void
  
  /**
   * Устанавливает ограничение диапазона зума
   * @param minZoom - Минимальный зум
   * @param maxZoom - Максимальный зум
   */
  setZoomRange(minZoom: number, maxZoom: number): void
  
  /**
   * Снимает ограничение диапазона зума
   */
  removeZoomRange(): void
  
  /**
   * Включает все поведения
   */
  enableAll(): void
  
  /**
   * Отключает все поведения
   */
  disableAll(): void
  
  /**
   * Сбрасывает к настройкам по умолчанию
   */
  reset(): void
  
  /**
   * Блокирует карту (отключает все интерактивные возможности)
   */
  lock(): void
  
  /**
   * Разблокирует карту (восстанавливает предыдущие поведения)
   */
  unlock(): void
  
  /**
   * Проверяет, заблокирована ли карта
   * @returns true если заблокирована
   */
  isLocked(): boolean
  
  /**
   * Создает кастомное поведение
   * @param name - Название поведения
   * @param config - Конфигурация поведения
   */
  createCustomBehavior(name: string, config: CustomBehaviorConfig): void
  
  /**
   * Включает кастомное поведение
   * @param name - Название поведения
   */
  enableCustomBehavior(name: string): void
  
  /**
   * Отключает кастомное поведение
   * @param name - Название поведения
   */
  disableCustomBehavior(name: string): void
  
  /**
   * Получает состояние поведений
   * @returns Объект состояния
   */
  getState(): BehaviorsState
  
  /**
   * Добавляет обработчик события
   * @param event - Название события
   * @param handler - Обработчик
   */
  on<K extends keyof BehaviorsEvents>(
    event: K,
    handler: BehaviorsEvents[K]
  ): void
  
  /**
   * Удаляет обработчик события
   * @param event - Название события
   * @param handler - Обработчик
   */
  off<K extends keyof BehaviorsEvents>(
    event: K,
    handler?: BehaviorsEvents[K]
  ): void
  
  /**
   * Проверяет готовность менеджера
   * @returns true если готов
   */
  isReady(): boolean
  
  /**
   * Уничтожает экземпляр и освобождает ресурсы
   */
  destroy(): void
}

/**
 * Список доступных поведений
 */
export const BEHAVIORS: Readonly<{
  DRAG: 'drag'
  DBL_CLICK_ZOOM: 'dblClickZoom'
  MULTI_TOUCH: 'multiTouch'
  RIGHT_MOUSE_MAGNIFIER: 'rightMouseButtonMagnifier'
  LEFT_MOUSE_MAGNIFIER: 'leftMouseButtonMagnifier'
  RULER: 'ruler'
  ROUTE_EDITOR: 'routeEditor'
  SCROLL_ZOOM: 'scrollZoom'
}>

/**
 * Фабричная функция для создания менеджера поведений
 */
export function createMapBehaviors(
  map: any,
  options?: MapBehaviorsOptions
): MapBehaviors

/**
 * Проверяет поддержку behaviors в браузере
 */
export function isBehaviorsSupported(): boolean

/**
 * Версия модуля
 */
export const VERSION: string

/**
 * Опции по умолчанию
 */
export const DEFAULT_OPTIONS: Readonly<MapBehaviorsOptions>

export default MapBehaviors