/**
 * TypeScript определения для модуля Balloon
 * @module Balloon
 */

/**
 * Позиция на карте
 */
export type BalloonPosition = 
  | [number, number]  // [latitude, longitude]
  | { lat: number; lng: number }
  | { latitude: number; longitude: number }

/**
 * Содержимое всплывающего окна
 */
export type BalloonContent = 
  | string 
  | HTMLElement 
  | {
      header?: string
      body?: string
      footer?: string
    }

/**
 * Опции всплывающего окна
 */
export interface BalloonOptions {
  // Визуальные настройки
  /** Показывать кнопку закрытия */
  closeButton?: boolean
  /** Максимальная ширина в пикселях */
  maxWidth?: number
  /** Максимальная высота в пикселях */
  maxHeight?: number
  /** Минимальная ширина в пикселях */
  minWidth?: number
  /** Минимальная высота в пикселях */
  minHeight?: number
  /** Смещение от точки привязки [x, y] */
  offset?: [number, number]
  
  // Поведение
  /** Автоматическое панорамирование карты при открытии */
  autoPan?: boolean
  /** Отступ от краев карты при автопанорамировании */
  autoPanMargin?: number
  /** Длительность автопанорамирования в миллисекундах */
  autoPanDuration?: number
  /** Проверять диапазон зума при автопанорамировании */
  autoPanCheckZoomRange?: boolean
  /** Использовать margin карты при автопанорамировании */
  autoPanUseMapMargin?: boolean
  
  // Режимы отображения
  /** Минимальная площадь карты для режима панели (px²) */
  panelMaxMapArea?: number
  /** Максимальная высота панели (доля от высоты карты) */
  panelMaxHeightRatio?: number
  
  // Интерактивность
  /** Модель интерактивности */
  interactivityModel?: 'default' | 'layer' | 'opaque' | 'transparent' | 'geoObject'
  /** Скрывать иконку при открытии balloon */
  hideIconOnBalloonOpen?: boolean
  /** Оставлять открытым при изменении масштаба */
  stayOpenOnZoom?: boolean
  
  // Макеты
  /** Макет всплывающего окна */
  layout?: string | null
  /** Макет содержимого */
  contentLayout?: string | null
  /** Макет панели */
  panelLayout?: string | null
  
  // Z-индекс
  /** Приоритет отображения */
  zIndex?: number
  
  // Временные задержки
  /** Задержка открытия в миллисекундах */
  openTimeout?: number
  /** Задержка закрытия в миллисекундах */
  closeTimeout?: number
  
  // Обработчики событий
  /** Обработчик события открытия */
  onopen?: (event: BalloonEvent) => void
  /** Обработчик события закрытия */
  onclose?: (event: BalloonEvent) => void
  /** Обработчик закрытия пользователем */
  onuserclose?: (event: BalloonEvent) => void
  /** Обработчик начала автопанорамирования */
  onautopanstart?: () => void
  /** Обработчик завершения автопанорамирования */
  onautopancomplete?: () => void
}

/**
 * Событие balloon
 */
export interface BalloonEvent {
  /** Позиция balloon */
  position: [number, number] | null
  /** Содержимое balloon */
  content: BalloonContent | null
}

/**
 * Опции для метода open
 */
export interface BalloonOpenOptions extends BalloonOptions {
  /** Дополнительные пользовательские данные */
  [key: string]: any
}

/**
 * Состояние balloon
 */
export interface BalloonState {
  /** Открыт ли balloon */
  isOpen: boolean
  /** Активен ли режим панели */
  isPanelMode: boolean
  /** Текущая позиция */
  position: [number, number] | null
  /** Текущее содержимое */
  content: BalloonContent | null
}

/**
 * Класс всплывающего окна для Yandex Maps
 */
export class Balloon {
  /**
   * Конструктор
   * @param map - Экземпляр карты Yandex Maps
   * @param options - Опции всплывающего окна
   */
  constructor(map: any, options?: BalloonOptions)
  
  /**
   * Открывает всплывающее окно
   * @param position - Позиция на карте
   * @param content - Содержимое окна
   * @param options - Дополнительные опции
   * @returns Промис завершения открытия
   */
  open(
    position: BalloonPosition, 
    content: BalloonContent, 
    options?: BalloonOpenOptions
  ): Promise<void>
  
  /**
   * Закрывает всплывающее окно
   * @param force - Принудительное закрытие без задержки
   * @returns Промис завершения закрытия
   */
  close(force?: boolean): Promise<void>
  
  /**
   * Устанавливает позицию окна
   * @param position - Новая позиция
   */
  setPosition(position: BalloonPosition): void
  
  /**
   * Устанавливает содержимое окна
   * @param content - Новое содержимое
   */
  setContent(content: BalloonContent): void
  
  /**
   * Выполняет автопанорамирование карты к окну
   * @returns Промис завершения панорамирования
   */
  autoPan(): Promise<void>
  
  /**
   * Проверяет, открыто ли окно
   * @returns true если окно открыто
   */
  isOpen(): boolean
  
  /**
   * Получает текущую позицию
   * @returns Координаты или null
   */
  getPosition(): [number, number] | null
  
  /**
   * Получает текущее содержимое
   * @returns Содержимое или null
   */
  getContent(): BalloonContent | null
  
  /**
   * Получает копию опций
   * @returns Объект с опциями
   */
  getOptions(): BalloonOptions
  
  /**
   * Устанавливает новые опции
   * @param options - Новые опции (будут объединены с текущими)
   */
  setOptions(options: Partial<BalloonOptions>): void
  
  /**
   * Уничтожает экземпляр и освобождает ресурсы
   */
  destroy(): void
}

/**
 * Фабричная функция для создания Balloon
 */
export function createBalloon(
  map: any, 
  options?: BalloonOptions
): Balloon

/**
 * Проверяет поддержку Balloon в браузере
 */
export function isBalloonSupported(): boolean

/**
 * Версия модуля
 */
export const VERSION: string

/**
 * Опции по умолчанию
 */
export const DEFAULT_OPTIONS: Readonly<BalloonOptions>

export default Balloon