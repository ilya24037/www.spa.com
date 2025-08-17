/**
 * OZON Map Icons
 * 
 * Коллекция SVG иконок для карты:
 * - Иконки элементов управления
 * - Маркеры пунктов выдачи
 * - Иконки действий и интерфейса
 */

// Control Icons
export { default as ZoomInIcon } from './zoom-in.svg'
export { default as ZoomOutIcon } from './zoom-out.svg'
export { default as CompassIcon } from './compass.svg'
export { default as GeolocateIcon } from './geolocate.svg'
export { default as FullscreenIcon } from './fullscreen.svg'
export { default as FullscreenExitIcon } from './fullscreen-exit.svg'

// Interface Icons
export { default as SearchIcon } from './search.svg'
export { default as CloseIcon } from './close.svg'
export { default as LocationIcon } from './location.svg'
export { default as DirectionsIcon } from './directions.svg'
export { default as ShareIcon } from './share.svg'
export { default as CopyIcon } from './copy.svg'
export { default as StarIcon } from './star.svg'

// Pickup Point Icons
export { default as PickupOzonIcon } from './pickup-ozon.svg'
export { default as PickupPostamrIcon } from './pickup-postamr.svg'
export { default as PickupPvzIcon } from './pickup-pvz.svg'
export { default as PickupLockerIcon } from './pickup-locker.svg'

/**
 * Получение иконки пункта выдачи по типу
 */
export function getPickupIcon(type: 'ozon' | 'postamr' | 'pickup' | 'locker'): string {
  const iconMap = {
    ozon: './pickup-ozon.svg',
    postamr: './pickup-postamr.svg',
    pickup: './pickup-pvz.svg',
    locker: './pickup-locker.svg'
  }
  
  return iconMap[type] || iconMap.pickup
}

/**
 * Получение цвета для типа пункта выдачи
 */
export function getPickupColor(type: 'ozon' | 'postamr' | 'pickup' | 'locker'): string {
  const colorMap = {
    ozon: '#005bff',
    postamr: '#0066cc',
    pickup: '#ff6b35',
    locker: '#00a651'
  }
  
  return colorMap[type] || colorMap.pickup
}

/**
 * Создание SVG иконки как строка
 */
export function createSVGIcon(
  path: string,
  options: {
    width?: number
    height?: number
    fill?: string
    viewBox?: string
  } = {}
): string {
  const {
    width = 24,
    height = 24,
    fill = 'currentColor',
    viewBox = `0 0 ${width} ${height}`
  } = options

  return `<svg xmlns="http://www.w3.org/2000/svg" width="${width}" height="${height}" viewBox="${viewBox}" fill="${fill}">
    <path d="${path}"/>
  </svg>`
}

/**
 * Создание маркера для карты
 */
export function createMapMarker(
  color: string = '#005bff',
  size: number = 24
): string {
  return `<svg xmlns="http://www.w3.org/2000/svg" width="${size}" height="${size * 1.25}" viewBox="0 0 24 30">
    <path d="M12 0C5.4 0 0 5.4 0 12c0 9 12 18 12 18s12-9 12-18c0-6.6-5.4-12-12-12z" fill="${color}"/>
    <circle cx="12" cy="12" r="6" fill="white"/>
    <circle cx="12" cy="12" r="3" fill="${color}"/>
  </svg>`
}

/**
 * Преобразование SVG в Data URL
 */
export function svgToDataURL(svg: string): string {
  const encoded = encodeURIComponent(svg)
  return `data:image/svg+xml;charset=utf-8,${encoded}`
}

/**
 * Получение всех доступных иконок
 */
export function getAllIcons() {
  return {
    controls: {
      'zoom-in': './zoom-in.svg',
      'zoom-out': './zoom-out.svg',
      'compass': './compass.svg',
      'geolocate': './geolocate.svg',
      'fullscreen': './fullscreen.svg',
      'fullscreen-exit': './fullscreen-exit.svg'
    },
    interface: {
      'search': './search.svg',
      'close': './close.svg',
      'location': './location.svg',
      'directions': './directions.svg',
      'share': './share.svg',
      'copy': './copy.svg',
      'star': './star.svg'
    },
    pickup: {
      'ozon': './pickup-ozon.svg',
      'postamr': './pickup-postamr.svg',
      'pvz': './pickup-pvz.svg',
      'locker': './pickup-locker.svg'
    }
  }
}