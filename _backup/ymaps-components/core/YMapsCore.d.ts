/**
 * TypeScript определения для YMapsCore
 * @module YMapsCore
 */

/**
 * Конфигурация для инициализации API
 */
export interface YMapsCoreConfig {
  /** API ключ от Яндекс */
  apiKey?: string | null
  /** Язык карты */
  lang?: 'ru_RU' | 'en_US' | 'en_RU' | 'ru_UA' | 'uk_UA' | 'tr_TR'
  /** Версия API */
  version?: string
  /** Порядок координат */
  coordorder?: 'latlong' | 'longlat'
  /** Режим загрузки */
  mode?: 'release' | 'debug'
  /** Загружаемые пакеты */
  load?: string
  /** Namespace для API */
  ns?: string
}

/**
 * Опции для создания карты
 */
export interface YMapOptions {
  /** Центр карты [широта, долгота] */
  center: [number, number]
  /** Уровень масштабирования (0-23) */
  zoom: number
  /** Элементы управления */
  controls?: string[]
  /** Поведения карты */
  behaviors?: string[]
  /** Дополнительные опции */
  extra?: Record<string, any>
}

/**
 * Данные о созданной карте
 */
export interface MapData {
  /** Уникальный идентификатор */
  id: string
  /** Экземпляр карты */
  map: any
  /** DOM контейнер */
  container: HTMLElement
  /** Опции карты */
  options: YMapOptions
  /** Загруженные модули */
  modules: Set<string>
  /** Объекты на карте */
  objects: Map<string, any>
}

/**
 * Основной класс для работы с Yandex Maps
 */
export class YMapsCore {
  /** Конфигурация */
  readonly config: YMapsCoreConfig

  /**
   * Конструктор
   * @param config - Конфигурация для инициализации
   */
  constructor(config?: YMapsCoreConfig)

  /**
   * Загружает Yandex Maps API
   * @returns Промис с объектом ymaps
   */
  loadAPI(): Promise<any>

  /**
   * Создает экземпляр карты
   * @param container - ID контейнера или DOM элемент
   * @param options - Опции карты
   * @returns Промис с экземпляром карты
   */
  createMap(
    container: string | HTMLElement, 
    options?: Partial<YMapOptions>
  ): Promise<any>

  /**
   * Уничтожает карту и освобождает ресурсы
   * @param mapOrId - Экземпляр карты или ID контейнера
   */
  destroyMap(mapOrId: string | any): void

  /**
   * Получает карту по ID контейнера
   * @param containerId - ID контейнера
   * @returns Экземпляр карты или null
   */
  getMap(containerId: string): any | null

  /**
   * Получает все созданные карты
   * @returns Коллекция всех карт
   */
  getAllMaps(): Map<string, any>

  /**
   * Загружает дополнительный модуль
   * @param moduleName - Имя модуля
   * @returns Промис с загруженным модулем
   */
  loadModule(moduleName: string): Promise<any>

  /**
   * Проверяет, загружен ли API
   * @returns true если API загружено
   */
  isAPILoaded(): boolean

  /**
   * Получает глобальный объект ymaps
   * @returns Объект ymaps или null
   */
  getYMaps(): any | null

  /**
   * Устанавливает центр карты
   * @param mapId - ID контейнера карты
   * @param center - Координаты центра [lat, lng]
   * @param zoom - Уровень зума (опционально)
   */
  setCenter(
    mapId: string, 
    center: [number, number], 
    zoom?: number | null
  ): void

  /**
   * Получает текущий центр карты
   * @param mapId - ID контейнера карты
   * @returns Координаты центра или null
   */
  getCenter(mapId: string): [number, number] | null

  /**
   * Устанавливает зум карты
   * @param mapId - ID контейнера карты
   * @param zoom - Уровень зума
   */
  setZoom(mapId: string, zoom: number): void

  /**
   * Получает текущий зум карты
   * @param mapId - ID контейнера карты
   * @returns Уровень зума или null
   */
  getZoom(mapId: string): number | null

  /**
   * Устанавливает границы карты
   * @param mapId - ID контейнера карты
   * @param bounds - Границы [[lat1, lng1], [lat2, lng2]]
   * @param options - Дополнительные опции
   */
  setBounds(
    mapId: string, 
    bounds: [[number, number], [number, number]], 
    options?: Record<string, any>
  ): void

  /**
   * Получает текущие границы карты
   * @param mapId - ID контейнера карты
   * @returns Границы или null
   */
  getBounds(mapId: string): [[number, number], [number, number]] | null
}

export default YMapsCore