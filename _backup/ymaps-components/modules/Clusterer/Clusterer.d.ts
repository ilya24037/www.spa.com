/**
 * TypeScript определения для модуля Clusterer
 * @module Clusterer
 */

import type { Placemark } from '../Placemark/Placemark'

/**
 * Preset стили кластеров
 */
export type ClusterPreset = 
  | 'islands#blueClusterIcons'
  | 'islands#redClusterIcons'
  | 'islands#darkGreenClusterIcons'
  | 'islands#violetClusterIcons'
  | 'islands#blackClusterIcons'
  | 'islands#grayClusterIcons'
  | 'islands#brownClusterIcons'
  | 'islands#nightClusterIcons'
  | 'islands#darkBlueClusterIcons'
  | 'islands#darkOrangeClusterIcons'
  | 'islands#pinkClusterIcons'
  | 'islands#oliveClusterIcons'
  | 'islands#invertedBlueClusterIcons'
  | 'islands#invertedRedClusterIcons'
  | 'islands#invertedDarkGreenClusterIcons'
  | 'islands#invertedVioletClusterIcons'

/**
 * Опции кластеризатора
 */
export interface ClustererOptions {
  /** Preset стиль кластеров */
  preset?: ClusterPreset
  
  /** Макет содержимого иконки кластера */
  clusterIconContentLayout?: string | null
  
  /** Макет иконки кластера */
  clusterIconLayout?: string | null
  
  /** Отключить зум при клике на кластер */
  clusterDisableClickZoom?: boolean
  
  /** Скрывать иконку при открытии balloon */
  clusterHideIconOnBalloonOpen?: boolean
  
  /** Макет содержимого balloon кластера */
  clusterBalloonContentLayout?: string | null
  
  /** Максимальная площадь balloon в режиме панели */
  clusterBalloonPanelMaxMapArea?: number
  
  /** Максимальная высота balloon */
  clusterBalloonMaxHeight?: number
  
  /** Размер пейджера в balloon */
  clusterBalloonPagerSize?: number
  
  /** Размер сетки кластеризации в пикселях */
  gridSize?: number
  
  /** Минимальное количество меток для создания кластера */
  minClusterSize?: number
  
  /** Максимальный зум для кластеризации */
  maxZoom?: number
  
  /** Отступ при клике на кластер */
  zoomMargin?: number
  
  /** Отступ между метками в кластере */
  margin?: number
  
  /** Показывать balloon у кластеров */
  hasBalloon?: boolean
  
  /** Показывать хинты у кластеров */
  hasHint?: boolean
  
  /** Сортировка меток в balloon по алфавиту */
  showInAlphabeticalOrder?: boolean
  
  /** Учитывать отступы карты */
  useMapMargin?: boolean
  
  /** Отступ от края viewport */
  viewportMargin?: number
  
  /** Кастомная функция создания кластера */
  createCluster?: (placemarks: Placemark[]) => any
  
  /** Кастомная функция для содержимого balloon */
  createBalloonContent?: (placemarks: Placemark[]) => string | HTMLElement
  
  /** Кастомная функция для хинта */
  createHintContent?: (placemarks: Placemark[]) => string
  
  /** Функция расчета иконки кластера */
  calculateClusterIcon?: (placemarks: Placemark[]) => ClusterIconData | null
  
  /** Обработчик добавления кластера */
  onClusterAdd?: (cluster: any) => void
  
  /** Обработчик удаления кластера */
  onClusterRemove?: (cluster: any) => void
  
  /** Обработчик клика по кластеру */
  onClusterClick?: (cluster: any, event: any) => void
}

/**
 * Данные иконки кластера
 */
export interface ClusterIconData {
  /** URL изображения */
  iconImageHref?: string
  /** Размер изображения */
  iconImageSize?: [number, number]
  /** Смещение изображения */
  iconImageOffset?: [number, number]
  /** Цвет иконки */
  iconColor?: string
  /** Содержимое иконки */
  iconContent?: string
}

/**
 * Опции центрирования карты
 */
export interface FitToViewportOptions {
  /** Проверять диапазон зума */
  checkZoomRange?: boolean
  /** Отступ от краев */
  zoomMargin?: number
  /** Использовать отступы карты */
  useMapMargin?: boolean
  /** Длительность анимации */
  duration?: number
  /** Функция обратного вызова после завершения */
  callback?: () => void
}

/**
 * События кластеризатора
 */
export interface ClustererEvents {
  /** Добавление элемента */
  add?: (event: ClustererEvent) => void
  /** Удаление элемента */
  remove?: (event: ClustererEvent) => void
  /** Клик по кластеру */
  click?: (event: ClustererClickEvent) => void
  /** Изменение опций */
  optionschange?: (event: ClustererEvent) => void
  /** Изменение родителя */
  parentchange?: (event: ClustererEvent) => void
}

/**
 * Событие кластеризатора
 */
export interface ClustererEvent {
  /** Оригинальное событие */
  originalEvent?: Event
  /** Целевой объект */
  target: any
  /** Дочерний объект (для add/remove) */
  child?: any
}

/**
 * Событие клика по кластеру
 */
export interface ClustererClickEvent extends ClustererEvent {
  /** Координаты клика */
  coords?: [number, number]
  /** Позиция в пикселях */
  position?: { x: number; y: number }
}

/**
 * Состояние кластеризатора
 */
export interface ClustererState {
  /** Готов ли кластеризатор */
  isReady: boolean
  /** Количество меток */
  placemarksCount: number
  /** Количество кластеров */
  clustersCount: number
  /** Текущий preset */
  preset: ClusterPreset
  /** Размер сетки */
  gridSize: number
  /** Минимальный размер кластера */
  minClusterSize: number
}

/**
 * Класс кластеризатора для Yandex Maps
 */
export class Clusterer {
  /**
   * Конструктор
   * @param map - Экземпляр карты Yandex Maps
   * @param options - Опции кластеризатора
   */
  constructor(map: any, options?: ClustererOptions)
  
  /**
   * Добавляет метку в кластеризатор
   * @param placemark - Метка или массив меток
   */
  add(placemark: Placemark | Placemark[]): Promise<void>
  
  /**
   * Удаляет метку из кластеризатора
   * @param placemark - Метка или массив меток
   */
  remove(placemark: Placemark | Placemark[]): Promise<void>
  
  /**
   * Удаляет все метки из кластеризатора
   */
  removeAll(): Promise<void>
  
  /**
   * Получает все метки
   * @returns Массив меток
   */
  getPlacemarks(): Placemark[]
  
  /**
   * Получает количество меток
   * @returns Количество
   */
  getPlacemarksCount(): number
  
  /**
   * Получает границы всех меток
   * @returns Границы или null
   */
  getBounds(): [[number, number], [number, number]] | null
  
  /**
   * Центрирует карту по всем меткам
   * @param options - Опции центрирования
   */
  fitToViewport(options?: FitToViewportOptions): Promise<void>
  
  /**
   * Устанавливает новые опции
   * @param options - Новые опции
   */
  setOptions(options: Partial<ClustererOptions>): void
  
  /**
   * Получает копию опций
   * @returns Объект с опциями
   */
  getOptions(): ClustererOptions
  
  /**
   * Устанавливает preset стиль
   * @param preset - Название preset стиля
   */
  setPreset(preset: ClusterPreset): void
  
  /**
   * Устанавливает размер сетки кластеризации
   * @param size - Размер в пикселях (10-300)
   */
  setGridSize(size: number): void
  
  /**
   * Устанавливает минимальный размер кластера
   * @param size - Минимальное количество меток (2-100)
   */
  setMinClusterSize(size: number): void
  
  /**
   * Перестраивает кластеры
   */
  refresh(): void
  
  /**
   * Включает balloon у кластеров
   */
  enableBalloon(): void
  
  /**
   * Отключает balloon у кластеров
   */
  disableBalloon(): void
  
  /**
   * Включает хинты у кластеров
   */
  enableHint(): void
  
  /**
   * Отключает хинты у кластеров
   */
  disableHint(): void
  
  /**
   * Добавляет обработчик события
   * @param event - Название события
   * @param handler - Обработчик
   */
  on<K extends keyof ClustererEvents>(
    event: K,
    handler: ClustererEvents[K]
  ): void
  
  /**
   * Удаляет обработчик события
   * @param event - Название события
   * @param handler - Обработчик
   */
  off<K extends keyof ClustererEvents>(
    event: K,
    handler?: ClustererEvents[K]
  ): void
  
  /**
   * Проверяет готовность кластеризатора
   * @returns true если готов
   */
  isReady(): boolean
  
  /**
   * Уничтожает экземпляр и освобождает ресурсы
   */
  destroy(): void
}

/**
 * Фабричная функция для создания кластеризатора
 */
export function createClusterer(
  map: any,
  options?: ClustererOptions
): Clusterer

/**
 * Preset стили для кластеров (константы)
 */
export const CLUSTER_PRESETS: Readonly<{
  BLUE: 'islands#blueClusterIcons'
  RED: 'islands#redClusterIcons'
  DARK_GREEN: 'islands#darkGreenClusterIcons'
  VIOLET: 'islands#violetClusterIcons'
  BLACK: 'islands#blackClusterIcons'
  GRAY: 'islands#grayClusterIcons'
  BROWN: 'islands#brownClusterIcons'
  NIGHT: 'islands#nightClusterIcons'
  DARK_BLUE: 'islands#darkBlueClusterIcons'
  DARK_ORANGE: 'islands#darkOrangeClusterIcons'
  PINK: 'islands#pinkClusterIcons'
  OLIVE: 'islands#oliveClusterIcons'
  INVERTED_BLUE: 'islands#invertedBlueClusterIcons'
  INVERTED_RED: 'islands#invertedRedClusterIcons'
  INVERTED_DARK_GREEN: 'islands#invertedDarkGreenClusterIcons'
  INVERTED_VIOLET: 'islands#invertedVioletClusterIcons'
}>

/**
 * Проверяет поддержку кластеризации в браузере
 */
export function isClustererSupported(): boolean

/**
 * Версия модуля
 */
export const VERSION: string

/**
 * Опции по умолчанию
 */
export const DEFAULT_OPTIONS: Readonly<ClustererOptions>

export default Clusterer