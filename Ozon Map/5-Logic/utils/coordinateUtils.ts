/**
 * Утилиты для работы с координатами
 */

/**
 * Валидация координат
 */
export function isValidCoordinates(coordinates: any): coordinates is [number, number] {
  return Array.isArray(coordinates) &&
         coordinates.length === 2 &&
         typeof coordinates[0] === 'number' &&
         typeof coordinates[1] === 'number' &&
         coordinates[0] >= -180 && coordinates[0] <= 180 &&
         coordinates[1] >= -90 && coordinates[1] <= 90
}

/**
 * Валидация долготы
 */
export function isValidLongitude(lng: number): boolean {
  return typeof lng === 'number' && lng >= -180 && lng <= 180
}

/**
 * Валидация широты
 */
export function isValidLatitude(lat: number): boolean {
  return typeof lat === 'number' && lat >= -90 && lat <= 90
}

/**
 * Нормализация долготы к диапазону [-180, 180]
 */
export function normalizeLongitude(lng: number): number {
  while (lng > 180) lng -= 360
  while (lng < -180) lng += 360
  return lng
}

/**
 * Ограничение широты к диапазону [-90, 90]
 */
export function clampLatitude(lat: number): number {
  return Math.max(-90, Math.min(90, lat))
}

/**
 * Нормализация координат
 */
export function normalizeCoordinates(coordinates: [number, number]): [number, number] {
  const [lng, lat] = coordinates
  return [normalizeLongitude(lng), clampLatitude(lat)]
}

/**
 * Расчет расстояния между двумя точками (формула Haversine)
 */
export function calculateDistance(
  coords1: [number, number],
  coords2: [number, number]
): number {
  const R = 6371000 // Радиус Земли в метрах
  const [lng1, lat1] = coords1
  const [lng2, lat2] = coords2

  const φ1 = (lat1 * Math.PI) / 180
  const φ2 = (lat2 * Math.PI) / 180
  const Δφ = ((lat2 - lat1) * Math.PI) / 180
  const Δλ = ((lng2 - lng1) * Math.PI) / 180

  const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
            Math.cos(φ1) * Math.cos(φ2) *
            Math.sin(Δλ / 2) * Math.sin(Δλ / 2)
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a))

  return R * c
}

/**
 * Расчет bearing (направления) между двумя точками
 */
export function calculateBearing(
  coords1: [number, number],
  coords2: [number, number]
): number {
  const [lng1, lat1] = coords1
  const [lng2, lat2] = coords2

  const φ1 = (lat1 * Math.PI) / 180
  const φ2 = (lat2 * Math.PI) / 180
  const Δλ = ((lng2 - lng1) * Math.PI) / 180

  const y = Math.sin(Δλ) * Math.cos(φ2)
  const x = Math.cos(φ1) * Math.sin(φ2) - Math.sin(φ1) * Math.cos(φ2) * Math.cos(Δλ)

  const θ = Math.atan2(y, x)

  return ((θ * 180) / Math.PI + 360) % 360
}

/**
 * Расчет промежуточной точки между двумя координатами
 */
export function interpolateCoordinates(
  coords1: [number, number],
  coords2: [number, number],
  fraction: number
): [number, number] {
  const [lng1, lat1] = coords1
  const [lng2, lat2] = coords2

  const φ1 = (lat1 * Math.PI) / 180
  const φ2 = (lat2 * Math.PI) / 180
  const λ1 = (lng1 * Math.PI) / 180
  const λ2 = (lng2 * Math.PI) / 180

  // Расстояние между точками
  const Δφ = φ2 - φ1
  const Δλ = λ2 - λ1
  const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
            Math.cos(φ1) * Math.cos(φ2) *
            Math.sin(Δλ / 2) * Math.sin(Δλ / 2)
  const δ = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a))

  const A = Math.sin((1 - fraction) * δ) / Math.sin(δ)
  const B = Math.sin(fraction * δ) / Math.sin(δ)

  const x = A * Math.cos(φ1) * Math.cos(λ1) + B * Math.cos(φ2) * Math.cos(λ2)
  const y = A * Math.cos(φ1) * Math.sin(λ1) + B * Math.cos(φ2) * Math.sin(λ2)
  const z = A * Math.sin(φ1) + B * Math.sin(φ2)

  const φ3 = Math.atan2(z, Math.sqrt(x * x + y * y))
  const λ3 = Math.atan2(y, x)

  return [(λ3 * 180) / Math.PI, (φ3 * 180) / Math.PI]
}

/**
 * Получение точки на заданном расстоянии и направлении
 */
export function getDestinationPoint(
  origin: [number, number],
  distance: number,
  bearing: number
): [number, number] {
  const R = 6371000 // Радиус Земли в метрах
  const [lng, lat] = origin

  const φ1 = (lat * Math.PI) / 180
  const λ1 = (lng * Math.PI) / 180
  const θ = (bearing * Math.PI) / 180

  const δ = distance / R

  const φ2 = Math.asin(
    Math.sin(φ1) * Math.cos(δ) + Math.cos(φ1) * Math.sin(δ) * Math.cos(θ)
  )

  const λ2 = λ1 + Math.atan2(
    Math.sin(θ) * Math.sin(δ) * Math.cos(φ1),
    Math.cos(δ) - Math.sin(φ1) * Math.sin(φ2)
  )

  return [((λ2 * 180) / Math.PI + 540) % 360 - 180, (φ2 * 180) / Math.PI]
}

/**
 * Создание окружности из центра и радиуса
 */
export function createCircle(
  center: [number, number],
  radius: number,
  steps: number = 64
): [number, number][] {
  const coordinates: [number, number][] = []

  for (let i = 0; i < steps; i++) {
    const bearing = (360 / steps) * i
    const point = getDestinationPoint(center, radius, bearing)
    coordinates.push(point)
  }

  // Замыкаем круг
  coordinates.push(coordinates[0])

  return coordinates
}

/**
 * Проверка, находится ли точка внутри многоугольника
 */
export function isPointInPolygon(
  point: [number, number],
  polygon: [number, number][]
): boolean {
  const [x, y] = point
  let inside = false

  for (let i = 0, j = polygon.length - 1; i < polygon.length; j = i++) {
    const [xi, yi] = polygon[i]
    const [xj, yj] = polygon[j]

    if (((yi > y) !== (yj > y)) && (x < ((xj - xi) * (y - yi)) / (yj - yi) + xi)) {
      inside = !inside
    }
  }

  return inside
}

/**
 * Проверка, находится ли точка в окружности
 */
export function isPointInCircle(
  point: [number, number],
  center: [number, number],
  radius: number
): boolean {
  const distance = calculateDistance(point, center)
  return distance <= radius
}

/**
 * Получение границ (bounding box) для массива координат
 */
export function getBoundingBox(
  coordinates: [number, number][]
): [[number, number], [number, number]] {
  if (coordinates.length === 0) {
    throw new Error('Coordinates array is empty')
  }

  let minLng = Infinity
  let minLat = Infinity
  let maxLng = -Infinity
  let maxLat = -Infinity

  coordinates.forEach(([lng, lat]) => {
    minLng = Math.min(minLng, lng)
    minLat = Math.min(minLat, lat)
    maxLng = Math.max(maxLng, lng)
    maxLat = Math.max(maxLat, lat)
  })

  return [[minLng, minLat], [maxLng, maxLat]]
}

/**
 * Упрощение линии по алгоритму Дугласа-Пекера
 */
export function simplifyLine(
  coordinates: [number, number][],
  tolerance: number = 0.0001
): [number, number][] {
  if (coordinates.length <= 2) return coordinates

  // Находим точку с максимальным расстоянием от прямой
  let maxDistance = 0
  let maxIndex = 0

  const start = coordinates[0]
  const end = coordinates[coordinates.length - 1]

  for (let i = 1; i < coordinates.length - 1; i++) {
    const distance = perpendicularDistance(coordinates[i], start, end)
    if (distance > maxDistance) {
      maxDistance = distance
      maxIndex = i
    }
  }

  // Если максимальное расстояние больше толерантности, рекурсивно упрощаем
  if (maxDistance > tolerance) {
    const leftPart = simplifyLine(coordinates.slice(0, maxIndex + 1), tolerance)
    const rightPart = simplifyLine(coordinates.slice(maxIndex), tolerance)

    return [...leftPart.slice(0, -1), ...rightPart]
  } else {
    return [start, end]
  }
}

/**
 * Расчет перпендикулярного расстояния от точки до прямой
 */
function perpendicularDistance(
  point: [number, number],
  lineStart: [number, number],
  lineEnd: [number, number]
): number {
  const [x0, y0] = point
  const [x1, y1] = lineStart
  const [x2, y2] = lineEnd

  const A = x2 - x1
  const B = y2 - y1
  const C = x1 - x0
  const D = y1 - y0

  const dot = A * C + B * D
  const lenSq = A * A + B * B

  if (lenSq === 0) {
    // Линия является точкой
    return Math.sqrt(C * C + D * D)
  }

  const param = dot / lenSq

  let xx: number, yy: number

  if (param < 0) {
    xx = x1
    yy = y1
  } else if (param > 1) {
    xx = x2
    yy = y2
  } else {
    xx = x1 + param * A
    yy = y1 + param * B
  }

  const dx = x0 - xx
  const dy = y0 - yy

  return Math.sqrt(dx * dx + dy * dy)
}

/**
 * Форматирование координат для отображения
 */
export function formatCoordinates(
  coordinates: [number, number],
  precision: number = 6,
  format: 'decimal' | 'dms' = 'decimal'
): string {
  const [lng, lat] = coordinates

  if (format === 'decimal') {
    return `${lat.toFixed(precision)}, ${lng.toFixed(precision)}`
  }

  // Degrees, Minutes, Seconds format
  const formatDMS = (coord: number, isLatitude: boolean): string => {
    const absolute = Math.abs(coord)
    const degrees = Math.floor(absolute)
    const minutes = Math.floor((absolute - degrees) * 60)
    const seconds = ((absolute - degrees - minutes / 60) * 3600).toFixed(2)

    const direction = isLatitude
      ? coord >= 0 ? 'N' : 'S'
      : coord >= 0 ? 'E' : 'W'

    return `${degrees}°${minutes}'${seconds}"${direction}`
  }

  return `${formatDMS(lat, true)}, ${formatDMS(lng, false)}`
}

/**
 * Парсинг координат из строки
 */
export function parseCoordinates(input: string): [number, number] | null {
  // Удаляем лишние пробелы и приводим к нижнему регистру
  const cleaned = input.trim().toLowerCase()

  // Пробуем разные форматы
  const patterns = [
    // Десятичные градусы: "55.7558, 37.6176" или "55.7558,37.6176"
    /^(-?\d+\.?\d*)\s*,\s*(-?\d+\.?\d*)$/,
    // Десятичные градусы с пробелом: "55.7558 37.6176"
    /^(-?\d+\.?\d*)\s+(-?\d+\.?\d*)$/,
    // DMS формат: "55°45'20.88"N, 37°37'3.36"E"
    /^(\d+)°(\d+)'([\d.]+)"([ns])\s*,?\s*(\d+)°(\d+)'([\d.]+)"([ew])$/i
  ]

  for (const pattern of patterns) {
    const match = cleaned.match(pattern)
    if (match) {
      if (match.length === 3) {
        // Десятичные градусы
        const lat = parseFloat(match[1])
        const lng = parseFloat(match[2])

        if (isValidLatitude(lat) && isValidLongitude(lng)) {
          return [lng, lat]
        }
      } else if (match.length === 9) {
        // DMS формат
        const latDeg = parseInt(match[1])
        const latMin = parseInt(match[2])
        const latSec = parseFloat(match[3])
        const latDir = match[4]

        const lngDeg = parseInt(match[5])
        const lngMin = parseInt(match[6])
        const lngSec = parseFloat(match[7])
        const lngDir = match[8]

        let lat = latDeg + latMin / 60 + latSec / 3600
        let lng = lngDeg + lngMin / 60 + lngSec / 3600

        if (latDir === 's') lat = -lat
        if (lngDir === 'w') lng = -lng

        if (isValidLatitude(lat) && isValidLongitude(lng)) {
          return [lng, lat]
        }
      }
    }
  }

  return null
}

/**
 * Преобразование координат из одной проекции в другую
 */
export function transformCoordinates(
  coordinates: [number, number],
  fromProjection: 'wgs84' | 'web_mercator',
  toProjection: 'wgs84' | 'web_mercator'
): [number, number] {
  if (fromProjection === toProjection) {
    return coordinates
  }

  const [x, y] = coordinates

  if (fromProjection === 'wgs84' && toProjection === 'web_mercator') {
    // WGS84 to Web Mercator
    const lng = x * 20037508.34 / 180
    const lat = Math.log(Math.tan((90 + y) * Math.PI / 360)) / (Math.PI / 180) * 20037508.34 / 180
    return [lng, lat]
  }

  if (fromProjection === 'web_mercator' && toProjection === 'wgs84') {
    // Web Mercator to WGS84
    const lng = x * 180 / 20037508.34
    const lat = Math.atan(Math.exp(y * Math.PI / 20037508.34)) * 360 / Math.PI - 90
    return [lng, lat]
  }

  return coordinates
}