/**
 * TypeScript определения для модуля Placemark
 * @module Placemark
 */

/**
 * Позиция метки на карте
 */
export type PlacemarkPosition = 
  | [number, number]  // [latitude, longitude]
  | { lat: number; lng: number }
  | { latitude: number; longitude: number }

/**
 * Опции иконки метки
 */
export interface PlacemarkIconOptions {
  /** Тип иконки: обычная, составная или макет */
  type?: 'default' | 'composite' | 'layout'
  /** URL изображения иконки */
  iconImageHref?: string
  /** Размер изображения иконки [width, height] */
  iconImageSize?: [number, number]
  /** Смещение изображения иконки [x, y] */
  iconImageOffset?: [number, number]
  /** Цвет иконки (для preset) */
  iconColor?: string
  /** Содержимое иконки (текст или HTML) */
  iconContent?: string
  /** Цвет содержимого иконки */
  iconContentColor?: string
  /** Смещение содержимого [x, y] */
  iconContentOffset?: [number, number]
  /** Размер содержимого [width, height] */
  iconContentSize?: [number, number]
  /** Отступы содержимого [top, right, bottom, left] */
  iconContentPadding?: number | [number, number] | [number, number, number, number]
  /** Макет иконки */
  iconLayout?: string | null
  /** Z-индекс иконки */
  zIndex?: number
  /** Z-индекс при наведении */
  zIndexHover?: number
  /** Z-индекс при перетаскивании */
  zIndexDrag?: number
}

/**
 * Предустановленные стили меток
 */
export type PlacemarkPreset = 
  | 'islands#blueIcon'
  | 'islands#blueCircleIcon'
  | 'islands#blueDotIcon'
  | 'islands#blueStretchyIcon'
  | 'islands#redIcon'
  | 'islands#redCircleIcon'
  | 'islands#redDotIcon'
  | 'islands#redStretchyIcon'
  | 'islands#darkGreenIcon'
  | 'islands#darkGreenCircleIcon'
  | 'islands#darkGreenDotIcon'
  | 'islands#darkGreenStretchyIcon'
  | 'islands#violetIcon'
  | 'islands#violetCircleIcon'
  | 'islands#violetDotIcon'
  | 'islands#violetStretchyIcon'
  | 'islands#blackIcon'
  | 'islands#blackCircleIcon'
  | 'islands#blackDotIcon'
  | 'islands#blackStretchyIcon'
  | 'islands#grayIcon'
  | 'islands#grayCircleIcon'
  | 'islands#grayDotIcon'
  | 'islands#grayStretchyIcon'
  | 'islands#brownIcon'
  | 'islands#brownCircleIcon'
  | 'islands#brownDotIcon'
  | 'islands#brownStretchyIcon'
  | 'islands#nightIcon'
  | 'islands#nightCircleIcon'
  | 'islands#nightDotIcon'
  | 'islands#nightStretchyIcon'
  | 'islands#darkBlueIcon'
  | 'islands#darkBlueCircleIcon'
  | 'islands#darkBlueDotIcon'
  | 'islands#darkBlueStretchyIcon'
  | 'islands#darkOrangeIcon'
  | 'islands#darkOrangeCircleIcon'
  | 'islands#darkOrangeDotIcon'
  | 'islands#darkOrangeStretchyIcon'
  | 'islands#pinkIcon'
  | 'islands#pinkCircleIcon'
  | 'islands#pinkDotIcon'
  | 'islands#pinkStretchyIcon'
  | 'islands#oliveIcon'
  | 'islands#oliveCircleIcon'
  | 'islands#oliveDotIcon'
  | 'islands#oliveStretchyIcon'

/**
 * Опции метки
 */
export interface PlacemarkOptions extends PlacemarkIconOptions {
  /** Preset стиль метки */
  preset?: PlacemarkPreset
  /** Содержимое всплывающего окна */
  balloonContent?: string | HTMLElement | object
  /** Содержимое хинта */
  hintContent?: string
  /** Можно ли перетаскивать метку */
  draggable?: boolean
  /** Видимость метки */
  visible?: boolean
  /** Курсор при наведении */
  cursor?: string
  /** Прозрачность метки (0-1) */
  opacity?: number
  /** Модель интерактивности */
  interactivityModel?: 'default' | 'layer' | 'opaque' | 'transparent' | 'geoObject'
  /** Панорамирование карты при перетаскивании */
  autoPan?: boolean
  /** Использовать кросс-доменные изображения */
  useMapMarginInDragging?: boolean
  /** Синхронизировать наложения */
  syncOverlayInit?: boolean
  /** Скрывать иконку при открытии balloon */
  hideIconOnBalloonOpen?: boolean
  /** Открывать balloon при клике */
  openBalloonOnClick?: boolean
  /** Открывать пустой balloon */
  openEmptyBalloon?: boolean
  /** Открывать хинт при наведении */
  openHintOnHover?: boolean
  /** Поднимать при наведении */
  raiseOnHover?: boolean
  /** Поднимать при клике */
  raiseOnClick?: boolean
  /** Пользовательские данные */
  data?: any
  /** Идентификатор метки */
  id?: string | number
  /** Имя метки */
  name?: string
  /** Описание метки */
  description?: string
}

/**
 * События метки
 */
export interface PlacemarkEvents {
  /** Клик по метке */
  click?: (event: PlacemarkEvent) => void
  /** Двойной клик */
  dblclick?: (event: PlacemarkEvent) => void
  /** Правый клик */
  contextmenu?: (event: PlacemarkEvent) => void
  /** Наведение мыши */
  mouseenter?: (event: PlacemarkEvent) => void
  /** Уход мыши */
  mouseleave?: (event: PlacemarkEvent) => void
  /** Начало перетаскивания */
  dragstart?: (event: PlacemarkEvent) => void
  /** Перетаскивание */
  drag?: (event: PlacemarkEvent) => void
  /** Конец перетаскивания */
  dragend?: (event: PlacemarkEvent) => void
  /** Изменение позиции */
  positionchange?: (event: PlacemarkPositionChangeEvent) => void
  /** Изменение опций */
  optionschange?: (event: PlacemarkEvent) => void
  /** Добавление на карту */
  mapchange?: (event: PlacemarkEvent) => void
  /** Изменение родителя */
  parentchange?: (event: PlacemarkEvent) => void
  /** Изменение видимости */
  visibilitychange?: (event: PlacemarkEvent) => void
}

/**
 * Событие метки
 */
export interface PlacemarkEvent {
  /** Оригинальное событие */
  originalEvent?: Event
  /** Целевая метка */
  target: Placemark
  /** Координаты события */
  coords?: [number, number]
  /** Позиция в пикселях */
  position?: { x: number; y: number }
}

/**
 * Событие изменения позиции
 */
export interface PlacemarkPositionChangeEvent extends PlacemarkEvent {
  /** Старая позиция */
  oldPosition: [number, number]
  /** Новая позиция */
  newPosition: [number, number]
}

/**
 * Состояние метки
 */
export interface PlacemarkState {
  /** Позиция метки */
  position: [number, number] | null
  /** Видимость */
  visible: boolean
  /** Можно ли перетаскивать */
  draggable: boolean
  /** Добавлена ли на карту */
  onMap: boolean
  /** Перетаскивается ли сейчас */
  isDragging: boolean
  /** Наведена ли мышь */
  isHovered: boolean
  /** Выбрана ли метка */
  isSelected: boolean
}

/**
 * Класс метки для Yandex Maps
 */
export class Placemark {
  /**
   * Конструктор
   * @param position - Позиция метки на карте
   * @param properties - Свойства метки
   * @param options - Опции метки
   */
  constructor(
    position: PlacemarkPosition,
    properties?: Record<string, any>,
    options?: PlacemarkOptions
  )
  
  /**
   * Добавляет метку на карту
   * @param map - Экземпляр карты
   * @returns Промис завершения добавления
   */
  addToMap(map: any): Promise<void>
  
  /**
   * Удаляет метку с карты
   * @returns Промис завершения удаления
   */
  removeFromMap(): Promise<void>
  
  /**
   * Устанавливает позицию метки
   * @param position - Новая позиция
   * @param animate - Анимировать перемещение
   * @returns Промис завершения перемещения
   */
  setPosition(position: PlacemarkPosition, animate?: boolean): Promise<void>
  
  /**
   * Получает текущую позицию
   * @returns Координаты или null
   */
  getPosition(): [number, number] | null
  
  /**
   * Устанавливает иконку метки
   * @param options - Опции иконки
   */
  setIcon(options: PlacemarkIconOptions | PlacemarkPreset): void
  
  /**
   * Устанавливает содержимое balloon
   * @param content - Содержимое
   */
  setBalloonContent(content: string | HTMLElement | object): void
  
  /**
   * Устанавливает содержимое хинта
   * @param content - Содержимое
   */
  setHintContent(content: string): void
  
  /**
   * Включает перетаскивание
   */
  enableDragging(): void
  
  /**
   * Отключает перетаскивание
   */
  disableDragging(): void
  
  /**
   * Показывает метку
   */
  show(): void
  
  /**
   * Скрывает метку
   */
  hide(): void
  
  /**
   * Переключает видимость
   */
  toggle(): void
  
  /**
   * Открывает balloon
   * @returns Промис завершения открытия
   */
  openBalloon(): Promise<void>
  
  /**
   * Закрывает balloon
   * @returns Промис завершения закрытия
   */
  closeBalloon(): Promise<void>
  
  /**
   * Анимирует метку
   * @param type - Тип анимации
   * @param options - Опции анимации
   * @returns Промис завершения анимации
   */
  animate(
    type: 'bounce' | 'drop' | 'pulse' | 'shake',
    options?: { duration?: number; iterations?: number }
  ): Promise<void>
  
  /**
   * Устанавливает пользовательские данные
   * @param key - Ключ или объект данных
   * @param value - Значение (если key - строка)
   */
  setData(key: string | object, value?: any): void
  
  /**
   * Получает пользовательские данные
   * @param key - Ключ данных
   * @returns Значение или весь объект данных
   */
  getData(key?: string): any
  
  /**
   * Устанавливает опции метки
   * @param options - Новые опции (будут объединены с текущими)
   */
  setOptions(options: Partial<PlacemarkOptions>): void
  
  /**
   * Получает копию опций
   * @returns Объект с опциями
   */
  getOptions(): PlacemarkOptions
  
  /**
   * Проверяет, находится ли метка на карте
   * @returns true если метка на карте
   */
  isOnMap(): boolean
  
  /**
   * Проверяет видимость метки
   * @returns true если метка видима
   */
  isVisible(): boolean
  
  /**
   * Проверяет, можно ли перетаскивать
   * @returns true если можно перетаскивать
   */
  isDraggable(): boolean
  
  /**
   * Получает границы метки
   * @returns Границы [[minLat, minLng], [maxLat, maxLng]]
   */
  getBounds(): [[number, number], [number, number]] | null
  
  /**
   * Добавляет обработчик события
   * @param event - Название события
   * @param handler - Обработчик
   */
  on<K extends keyof PlacemarkEvents>(
    event: K,
    handler: PlacemarkEvents[K]
  ): void
  
  /**
   * Удаляет обработчик события
   * @param event - Название события
   * @param handler - Обработчик
   */
  off<K extends keyof PlacemarkEvents>(
    event: K,
    handler?: PlacemarkEvents[K]
  ): void
  
  /**
   * Генерирует событие
   * @param event - Название события
   * @param data - Данные события
   */
  emit(event: string, data?: any): void
  
  /**
   * Уничтожает экземпляр и освобождает ресурсы
   */
  destroy(): void
}

/**
 * Фабричные функции для создания меток
 */

/**
 * Создает простую метку
 */
export function createSimplePlacemark(
  position: PlacemarkPosition,
  options?: PlacemarkOptions
): Placemark

/**
 * Создает метку с текстом
 */
export function createTextPlacemark(
  position: PlacemarkPosition,
  text: string,
  options?: PlacemarkOptions
): Placemark

/**
 * Создает метку с изображением
 */
export function createImagePlacemark(
  position: PlacemarkPosition,
  imageUrl: string,
  options?: PlacemarkOptions
): Placemark

/**
 * Создает круглую метку
 */
export function createCirclePlacemark(
  position: PlacemarkPosition,
  color: string,
  options?: PlacemarkOptions
): Placemark

/**
 * Создает метку-точку
 */
export function createDotPlacemark(
  position: PlacemarkPosition,
  color: string,
  options?: PlacemarkOptions
): Placemark

/**
 * Создает растягиваемую метку
 */
export function createStretchyPlacemark(
  position: PlacemarkPosition,
  text: string,
  color: string,
  options?: PlacemarkOptions
): Placemark

/**
 * Проверяет поддержку Placemark в браузере
 */
export function isPlacemarkSupported(): boolean

/**
 * Версия модуля
 */
export const VERSION: string

/**
 * Опции по умолчанию
 */
export const DEFAULT_OPTIONS: Readonly<PlacemarkOptions>

/**
 * Доступные preset стили
 */
export const PRESET_STYLES: ReadonlyArray<PlacemarkPreset>

/**
 * Цвета для preset стилей
 */
export const PRESET_COLORS: Readonly<{
  blue: string
  red: string
  darkGreen: string
  violet: string
  black: string
  gray: string
  brown: string
  night: string
  darkBlue: string
  darkOrange: string
  pink: string
  olive: string
}>

export default Placemark