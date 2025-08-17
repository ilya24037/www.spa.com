/**
 * Утилиты для работы с картой
 */

import type { Map as MapLibreMap, LngLatBounds, LngLatBoundsLike } from 'maplibre-gl'

/**
 * Проверка, загружена ли карта
 */
export function isMapLoaded(map: MapLibreMap | null): boolean {
  return map !== null && map.loaded()
}

/**
 * Проверка, находится ли точка в видимой области карты
 */
export function isPointInView(
  map: MapLibreMap,
  coordinates: [number, number]
): boolean {
  const bounds = map.getBounds()
  const [lng, lat] = coordinates
  
  return lng >= bounds.getWest() && 
         lng <= bounds.getEast() && 
         lat >= bounds.getSouth() && 
         lat <= bounds.getNorth()
}

/**
 * Получение центра границ
 */
export function getBoundsCenter(bounds: LngLatBoundsLike): [number, number] {
  if (Array.isArray(bounds)) {
    const [[west, south], [east, north]] = bounds
    return [(west + east) / 2, (south + north) / 2]
  }
  
  const center = (bounds as LngLatBounds).getCenter()
  return [center.lng, center.lat]
}

/**
 * Расширение границ на заданное расстояние
 */
export function expandBounds(
  bounds: LngLatBoundsLike, 
  paddingPercent: number = 0.1
): [[number, number], [number, number]] {
  let west: number, south: number, east: number, north: number

  if (Array.isArray(bounds)) {
    [[west, south], [east, north]] = bounds
  } else {
    const boundsObj = bounds as LngLatBounds
    west = boundsObj.getWest()
    south = boundsObj.getSouth()
    east = boundsObj.getEast()
    north = boundsObj.getNorth()
  }

  const deltaLng = (east - west) * paddingPercent
  const deltaLat = (north - south) * paddingPercent

  return [
    [west - deltaLng, south - deltaLat],
    [east + deltaLng, north + deltaLat]
  ]
}

/**
 * Создание границ из массива координат
 */
export function createBoundsFromCoordinates(
  coordinates: [number, number][]
): [[number, number], [number, number]] {
  if (coordinates.length === 0) {
    throw new Error('Coordinates array is empty')
  }

  let minLng = Infinity, minLat = Infinity
  let maxLng = -Infinity, maxLat = -Infinity

  coordinates.forEach(([lng, lat]) => {
    minLng = Math.min(minLng, lng)
    minLat = Math.min(minLat, lat)
    maxLng = Math.max(maxLng, lng)
    maxLat = Math.max(maxLat, lat)
  })

  return [[minLng, minLat], [maxLng, maxLat]]
}

/**
 * Добавление источника GeoJSON на карту
 */
export function addGeoJSONSource(
  map: MapLibreMap,
  sourceId: string,
  data: any,
  options: any = {}
) {
  if (map.getSource(sourceId)) {
    (map.getSource(sourceId) as any).setData(data)
  } else {
    map.addSource(sourceId, {
      type: 'geojson',
      data,
      ...options
    })
  }
}

/**
 * Безопасное удаление слоя карты
 */
export function removeLayer(map: MapLibreMap, layerId: string) {
  if (map.getLayer(layerId)) {
    map.removeLayer(layerId)
  }
}

/**
 * Безопасное удаление источника карты
 */
export function removeSource(map: MapLibreMap, sourceId: string) {
  if (map.getSource(sourceId)) {
    map.removeSource(sourceId)
  }
}

/**
 * Безопасное добавление изображения на карту
 */
export async function addImageToMap(
  map: MapLibreMap,
  imageId: string,
  imagePath: string | HTMLImageElement
): Promise<void> {
  return new Promise((resolve, reject) => {
    if (map.hasImage(imageId)) {
      resolve()
      return
    }

    if (typeof imagePath === 'string') {
      map.loadImage(imagePath, (error, image) => {
        if (error) {
          reject(error)
        } else if (image) {
          map.addImage(imageId, image)
          resolve()
        }
      })
    } else {
      map.addImage(imageId, imagePath)
      resolve()
    }
  })
}

/**
 * Получение пикселей из координат
 */
export function coordinatesToPixels(
  map: MapLibreMap,
  coordinates: [number, number]
): [number, number] {
  const point = map.project(coordinates)
  return [point.x, point.y]
}

/**
 * Получение координат из пикселей
 */
export function pixelsToCoordinates(
  map: MapLibreMap,
  pixels: [number, number]
): [number, number] {
  const lngLat = map.unproject(pixels)
  return [lngLat.lng, lngLat.lat]
}

/**
 * Анимированное перемещение карты к точке
 */
export function animateToCoordinates(
  map: MapLibreMap,
  coordinates: [number, number],
  options: {
    zoom?: number
    duration?: number
    essential?: boolean
  } = {}
) {
  const {
    zoom = map.getZoom(),
    duration = 1000,
    essential = true
  } = options

  map.flyTo({
    center: coordinates,
    zoom,
    duration,
    essential
  })
}

/**
 * Анимированное масштабирование к границам
 */
export function animateToBounds(
  map: MapLibreMap,
  bounds: LngLatBoundsLike,
  options: {
    padding?: number | { top: number; bottom: number; left: number; right: number }
    duration?: number
    maxZoom?: number
  } = {}
) {
  const {
    padding = 50,
    duration = 1000,
    maxZoom = 16
  } = options

  map.fitBounds(bounds, {
    padding,
    duration,
    maxZoom
  })
}

/**
 * Получение URL снимка карты
 */
export function getMapSnapshot(map: MapLibreMap): string {
  const canvas = map.getCanvas()
  return canvas.toDataURL()
}

/**
 * Скачивание снимка карты
 */
export function downloadMapSnapshot(
  map: MapLibreMap,
  filename: string = 'map-snapshot.png'
) {
  const dataURL = getMapSnapshot(map)
  const link = document.createElement('a')
  link.download = filename
  link.href = dataURL
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}

/**
 * Проверка поддержки WebGL
 */
export function isWebGLSupported(): boolean {
  try {
    const canvas = document.createElement('canvas')
    const context = canvas.getContext('webgl') || canvas.getContext('experimental-webgl')
    return !!context
  } catch (e) {
    return false
  }
}

/**
 * Получение информации о производительности карты
 */
export function getMapPerformanceInfo(map: MapLibreMap) {
  return {
    isLoaded: map.loaded(),
    isMoving: map.isMoving(),
    isZooming: map.isZooming(),
    isRotating: map.isRotating(),
    center: map.getCenter(),
    zoom: map.getZoom(),
    bearing: map.getBearing(),
    pitch: map.getPitch(),
    bounds: map.getBounds(),
    canvasSize: {
      width: map.getCanvas().width,
      height: map.getCanvas().height
    }
  }
}

/**
 * Подписка на изменения карты
 */
export function watchMapChanges(
  map: MapLibreMap,
  callback: (info: ReturnType<typeof getMapPerformanceInfo>) => void,
  events: string[] = ['moveend', 'zoomend', 'rotateend', 'pitchend']
) {
  const handler = () => callback(getMapPerformanceInfo(map))
  
  events.forEach(event => {
    map.on(event, handler)
  })

  // Возвращаем функцию для отписки
  return () => {
    events.forEach(event => {
      map.off(event, handler)
    })
  }
}

/**
 * Дебаунс функция для оптимизации событий карты
 */
export function debounce<T extends (...args: any[]) => any>(
  func: T,
  wait: number,
  immediate: boolean = false
): T {
  let timeout: NodeJS.Timeout | null = null
  
  return ((...args: Parameters<T>) => {
    const later = () => {
      timeout = null
      if (!immediate) func(...args)
    }
    
    const callNow = immediate && !timeout
    
    if (timeout) clearTimeout(timeout)
    timeout = setTimeout(later, wait)
    
    if (callNow) func(...args)
  }) as T
}

/**
 * Тротлинг функция для ограничения частоты вызовов
 */
export function throttle<T extends (...args: any[]) => any>(
  func: T,
  limit: number
): T {
  let inThrottle: boolean = false
  
  return ((...args: Parameters<T>) => {
    if (!inThrottle) {
      func(...args)
      inThrottle = true
      setTimeout(() => inThrottle = false, limit)
    }
  }) as T
}

/**
 * Создание объекта Feature для GeoJSON
 */
export function createGeoJSONFeature(
  coordinates: [number, number],
  properties: Record<string, any> = {},
  geometryType: 'Point' | 'LineString' | 'Polygon' = 'Point'
) {
  return {
    type: 'Feature' as const,
    properties,
    geometry: {
      type: geometryType,
      coordinates: geometryType === 'Point' ? coordinates : [coordinates]
    }
  }
}

/**
 * Создание коллекции Feature для GeoJSON
 */
export function createGeoJSONFeatureCollection(
  features: Array<{
    coordinates: [number, number] | [number, number][]
    properties?: Record<string, any>
    geometryType?: 'Point' | 'LineString' | 'Polygon'
  }>
) {
  return {
    type: 'FeatureCollection' as const,
    features: features.map(({ coordinates, properties = {}, geometryType = 'Point' }) =>
      createGeoJSONFeature(
        Array.isArray(coordinates[0]) ? coordinates[0] as [number, number] : coordinates as [number, number],
        properties,
        geometryType
      )
    )
  }
}