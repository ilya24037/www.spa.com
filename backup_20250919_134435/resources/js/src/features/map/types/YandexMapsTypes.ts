/**
 * Типы для работы с нативным Yandex Maps API
 * Это типы для прямого взаимодействия с ymaps объектами
 */

// Глобальные типы для window.ymaps
declare global {
  interface Window {
    ymaps: YMapsAPI
  }
}

// Основной интерфейс Yandex Maps API
export interface YMapsAPI {
  ready: (callback: () => void) => void
  Map: new (container: string | HTMLElement, state: MapState, options?: MapOptions) => YMap
  Placemark: new (coordinates: number[], properties?: PlacemarkProperties, options?: PlacemarkOptions) => YPlacemark
  Clusterer: new (options?: ClustererOptions) => YClusterer
  geocode: (query: string | number[], options?: GeocodeOptions) => Promise<YGeocodeResult>
  suggest: (query: string, options?: SuggestOptions) => Promise<YSuggestResult[]>
  control: {
    ZoomControl: new (options?: ControlOptions) => YControl
    SearchControl: new (options?: SearchControlOptions) => YSearchControl
    FullscreenControl: new (options?: ControlOptions) => YControl
    GeolocationControl: new (options?: GeolocationControlOptions) => YControl
  }
  behavior: {
    default: string
    drag: string
    scrollZoom: string
    dblClickZoom: string
    multiTouch: string
  }
  util: {
    bounds: {
      getCenterAndZoom: (bounds: number[][], containerSize: number[]) => { center: number[], zoom: number }
    }
  }
}

// Состояние карты при создании
export interface MapState {
  center: number[]
  zoom: number
  controls?: string[]
  behaviors?: string[]
}

// Опции карты
export interface MapOptions {
  minZoom?: number
  maxZoom?: number
  restrictMapArea?: number[][]
  suppressMapOpenBlock?: boolean
  yandexMapDisablePoiInteractivity?: boolean
}

// Интерфейс карты
export interface YMap {
  events: YEventManager
  geoObjects: YGeoObjectCollection
  controls: YControlCollection
  behaviors: YBehaviorManager
  
  // Методы управления
  getCenter(): number[]
  setCenter(center: number[], zoom?: number, options?: PanOptions): Promise<void>
  getZoom(): number
  setZoom(zoom: number, options?: ZoomOptions): Promise<void>
  getBounds(options?: BoundsOptions): number[][]
  setBounds(bounds: number[][], options?: FitBoundsOptions): Promise<void>
  
  // Уничтожение
  destroy(): void
  
  // Контейнер
  getContainer(): HTMLElement
}

// Опции перемещения карты
export interface PanOptions {
  duration?: number
  timingFunction?: string
  safe?: boolean
  preciseZoom?: boolean
  checkZoomRange?: boolean
}

export interface ZoomOptions {
  duration?: number
  timingFunction?: string
  checkZoomRange?: boolean
}

export interface FitBoundsOptions extends PanOptions {
  margin?: number[]
  zoomMargin?: number
}

export interface BoundsOptions {
  margin?: number[]
}

// Менеджер событий
export interface YEventManager {
  add(eventName: string, handler: (event: YEvent) => void): void
  remove(eventName: string, handler: (event: YEvent) => void): void
  removeAll(): void
}

// Объект события
export interface YEvent {
  get(key: string): any
  getType(): string
  preventDefault(): void
  stopPropagation(): void
}

// Коллекция гео-объектов
export interface YGeoObjectCollection {
  add(geoObject: YGeoObject): void
  remove(geoObject: YGeoObject): void
  removeAll(): void
  each(callback: (geoObject: YGeoObject, index: number) => void): void
  getIterator(): YIterator
  getLength(): number
  get(index: number): YGeoObject
}

// Базовый гео-объект
export interface YGeoObject {
  events: YEventManager
  properties: YPropertyManager
  options: YOptionManager
  geometry: YGeometry
}

// Метка (Placemark)
export interface YPlacemark extends YGeoObject {
  balloon: YBalloon
}

// Свойства меток
export interface PlacemarkProperties {
  balloonContent?: string
  balloonContentHeader?: string
  balloonContentBody?: string
  balloonContentFooter?: string
  hintContent?: string
  iconContent?: string
  [key: string]: any
}

// Опции меток
export interface PlacemarkOptions {
  preset?: string
  iconColor?: string
  iconImageHref?: string
  iconImageSize?: number[]
  iconImageOffset?: number[]
  draggable?: boolean
  visible?: boolean
  zIndex?: number
  [key: string]: any
}

// Менеджер свойств
export interface YPropertyManager {
  get(key: string, defaultValue?: any): any
  set(key: string, value: any): void
  setAll(properties: Record<string, any>): void
  unset(key: string): void
  unsetAll(): void
}

// Менеджер опций
export interface YOptionManager {
  get(key: string, defaultValue?: any): any
  set(key: string, value: any): void
  setAll(options: Record<string, any>): void
  unset(key: string): void
  unsetAll(): void
}

// Геометрия
export interface YGeometry {
  getCoordinates(): number[]
  setCoordinates(coordinates: number[]): void
  getBounds(): number[][]
  getType(): string
}

// Balloon (всплывающее окно)
export interface YBalloon {
  open(position?: number[]): void
  close(): void
  isOpen(): boolean
  setPosition(position: number[]): void
  getPosition(): number[]
}

// Итератор
export interface YIterator {
  getNext(): YGeoObject | null
  hasNext(): boolean
}

// Кластеризатор
export interface YClusterer extends YGeoObject {
  add(geoObjects: YGeoObject[]): void
  remove(geoObjects: YGeoObject[]): void
  removeAll(): void
  getClusters(): YCluster[]
}

// Опции кластеризатора
export interface ClustererOptions {
  preset?: string
  groupByCoordinates?: boolean
  clusterDisableClickZoom?: boolean
  clusterHideIconOnBalloonOpen?: boolean
  geoObjectHideIconOnBalloonOpen?: boolean
  gridSize?: number
  minClusterSize?: number
  maxZoom?: number
  zoomMargin?: number
  clusterIconColor?: string
}

// Кластер
export interface YCluster extends YGeoObject {
  getGeoObjects(): YGeoObject[]
  getBounds(): number[][]
}

// Результат геокодирования
export interface YGeocodeResult {
  geoObjects: YGeoObjectCollection
  metaData: YGeocodeMetaData
}

export interface YGeocodeMetaData {
  geocoder: {
    request: string
    found: number
    results: number
    skip: number
  }
}

// Опции геокодирования
export interface GeocodeOptions {
  results?: number
  skip?: number
  boundedBy?: number[][]
  strictBounds?: boolean
  kind?: string
}

// Результат саджеста
export interface YSuggestResult {
  displayName: string
  value: string
  type: string
}

// Опции саджеста
export interface SuggestOptions {
  results?: number
  boundedBy?: number[][]
  strictBounds?: boolean
  kind?: string
}

// Контролы
export interface YControl {
  events: YEventManager
  options: YOptionManager
}

export interface YControlCollection {
  add(control: YControl | string, options?: ControlOptions): void
  remove(control: YControl | string): void
  get(index: number | string): YControl
  each(callback: (control: YControl, index: number) => void): void
}

export interface ControlOptions {
  float?: 'left' | 'right' | 'none'
  position?: {
    top?: number | string
    left?: number | string
    right?: number | string
    bottom?: number | string
  }
  maxWidth?: number[]
  visible?: boolean
}

// Контрол поиска
export interface YSearchControl extends YControl {
  search(query: string): Promise<void>
  getResultsArray(): YGeoObject[]
  showResult(index: number): void
  hideResult(): void
}

export interface SearchControlOptions extends ControlOptions {
  placeholderContent?: string
  size?: 'small' | 'medium' | 'large'
  provider?: string
  kind?: string
}

// Контрол геолокации
export interface GeolocationControlOptions extends ControlOptions {
  noPlacemark?: boolean
  trackLocationTimeout?: number
  trackLocationAccuracy?: boolean
}

// Менеджер поведений
export interface YBehaviorManager {
  get(name: string): YBehavior
  isEnabled(name: string): boolean
  enable(name: string | string[]): void
  disable(name: string | string[]): void
}

// Поведение
export interface YBehavior {
  events: YEventManager
  isEnabled(): boolean
  enable(): void
  disable(): void
}

// Экспорт всех типов
export type {
  YMapsAPI,
  YMap,
  YPlacemark,
  YClusterer,
  YControl,
  YGeocodeResult,
  YSuggestResult
}