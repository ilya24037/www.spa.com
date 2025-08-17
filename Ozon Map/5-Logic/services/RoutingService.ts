/**
 * Сервис для работы с маршрутизацией
 */

export interface RoutePoint {
  coordinates: [number, number]
  name?: string
  address?: string
}

export interface RouteOptions {
  /** Тип маршрута */
  profile?: 'driving' | 'walking' | 'cycling' | 'truck'
  /** Избегать платных дорог */
  avoidTolls?: boolean
  /** Избегать магистралей */
  avoidHighways?: boolean
  /** Избегать паромов */
  avoidFerries?: boolean
  /** Язык инструкций */
  language?: string
  /** Единицы измерения */
  units?: 'metric' | 'imperial'
  /** Альтернативные маршруты */
  alternatives?: boolean
  /** Максимальное количество альтернатив */
  maxAlternatives?: number
}

export interface RouteStep {
  /** Координаты точек шага */
  coordinates: [number, number][]
  /** Инструкция */
  instruction: string
  /** Расстояние шага в метрах */
  distance: number
  /** Время шага в секундах */
  duration: number
  /** Тип маневра */
  maneuver: {
    type: string
    modifier?: string
    exit?: number
  }
  /** Название дороги */
  roadName?: string
  /** Направление */
  direction?: string
}

export interface Route {
  /** Уникальный ID маршрута */
  id: string
  /** Координаты всего маршрута */
  coordinates: [number, number][]
  /** Общее расстояние в метрах */
  distance: number
  /** Общее время в секундах */
  duration: number
  /** Шаги маршрута */
  steps: RouteStep[]
  /** Границы маршрута */
  bounds: [[number, number], [number, number]]
  /** Является ли альтернативным */
  isAlternative?: boolean
  /** Описание маршрута */
  summary?: string
}

export interface RoutingResult {
  /** Основной маршрут */
  routes: Route[]
  /** Точки маршрута */
  waypoints: RoutePoint[]
  /** Время запроса */
  timestamp: number
}

export interface RoutingProvider {
  name: string
  calculateRoute(
    start: RoutePoint,
    end: RoutePoint,
    waypoints?: RoutePoint[],
    options?: RouteOptions
  ): Promise<RoutingResult>
}

/**
 * Провайдер для OSRM (Open Source Routing Machine)
 */
export class OSRMProvider implements RoutingProvider {
  name = 'osrm'
  private baseUrl = 'https://router.project-osrm.org'

  async calculateRoute(
    start: RoutePoint,
    end: RoutePoint,
    waypoints: RoutePoint[] = [],
    options: RouteOptions = {}
  ): Promise<RoutingResult> {
    const {
      profile = 'driving',
      alternatives = false,
      maxAlternatives = 3
    } = options

    // Собираем все точки маршрута
    const allPoints = [start, ...waypoints, end]
    const coordinates = allPoints.map(point => point.coordinates)
    const coordinatesStr = coordinates.map(([lng, lat]) => `${lng},${lat}`).join(';')

    const params = new URLSearchParams({
      geometries: 'geojson',
      overview: 'full',
      steps: 'true',
      alternatives: alternatives.toString(),
      ...(alternatives && { number_of_alternatives: maxAlternatives.toString() })
    })

    const url = `${this.baseUrl}/route/v1/${profile}/${coordinatesStr}?${params}`

    try {
      const response = await fetch(url)
      
      if (!response.ok) {
        throw new Error(`OSRM API error: ${response.status}`)
      }

      const data = await response.json()

      if (data.code !== 'Ok') {
        throw new Error(data.message || 'Маршрут не найден')
      }

      return this.transformOSRMResult(data, allPoints)
    } catch (error) {
      console.error('OSRM routing error:', error)
      throw new Error('Ошибка построения маршрута')
    }
  }

  private transformOSRMResult(data: any, waypoints: RoutePoint[]): RoutingResult {
    const routes = data.routes.map((route: any, index: number) => ({
      id: `osrm_${Date.now()}_${index}`,
      coordinates: route.geometry.coordinates,
      distance: route.distance,
      duration: route.duration,
      steps: this.transformOSRMSteps(route.legs),
      bounds: this.calculateBounds(route.geometry.coordinates),
      isAlternative: index > 0,
      summary: `${this.formatDistance(route.distance)}, ${this.formatDuration(route.duration)}`
    }))

    return {
      routes,
      waypoints,
      timestamp: Date.now()
    }
  }

  private transformOSRMSteps(legs: any[]): RouteStep[] {
    const steps: RouteStep[] = []

    legs.forEach(leg => {
      leg.steps?.forEach((step: any) => {
        steps.push({
          coordinates: step.geometry.coordinates,
          instruction: step.maneuver.instruction || this.getManeuverInstruction(step.maneuver),
          distance: step.distance,
          duration: step.duration,
          maneuver: {
            type: step.maneuver.type,
            modifier: step.maneuver.modifier,
            exit: step.maneuver.exit
          },
          roadName: step.name,
          direction: this.getDirection(step.maneuver.bearing_after)
        })
      })
    })

    return steps
  }

  private getManeuverInstruction(maneuver: any): string {
    const { type, modifier } = maneuver

    const instructions: Record<string, string> = {
      'depart': 'Начните движение',
      'turn': modifier === 'left' ? 'Поверните налево' : 'Поверните направо',
      'continue': 'Продолжайте движение прямо',
      'arrive': 'Прибытие в пункт назначения',
      'merge': 'Перестройтесь',
      'ramp': 'Съезд',
      'roundabout': 'Въезд на круговое движение',
      'roundabout exit': 'Съезд с кругового движения'
    }

    return instructions[type] || 'Продолжайте движение'
  }

  private getDirection(bearing: number): string {
    if (bearing >= 337.5 || bearing < 22.5) return 'север'
    if (bearing >= 22.5 && bearing < 67.5) return 'северо-восток'
    if (bearing >= 67.5 && bearing < 112.5) return 'восток'
    if (bearing >= 112.5 && bearing < 157.5) return 'юго-восток'
    if (bearing >= 157.5 && bearing < 202.5) return 'юг'
    if (bearing >= 202.5 && bearing < 247.5) return 'юго-запад'
    if (bearing >= 247.5 && bearing < 292.5) return 'запад'
    if (bearing >= 292.5 && bearing < 337.5) return 'северо-запад'
    return ''
  }

  private calculateBounds(coordinates: [number, number][]): [[number, number], [number, number]] {
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

  private formatDistance(meters: number): string {
    if (meters < 1000) {
      return `${Math.round(meters)} м`
    }
    return `${(meters / 1000).toFixed(1)} км`
  }

  private formatDuration(seconds: number): string {
    const hours = Math.floor(seconds / 3600)
    const minutes = Math.floor((seconds % 3600) / 60)

    if (hours > 0) {
      return `${hours} ч ${minutes} мин`
    }
    return `${minutes} мин`
  }
}

/**
 * Провайдер для GraphHopper
 */
export class GraphHopperProvider implements RoutingProvider {
  name = 'graphhopper'
  private baseUrl = 'https://graphhopper.com/api/1'
  private apiKey: string

  constructor(apiKey: string) {
    this.apiKey = apiKey
  }

  async calculateRoute(
    start: RoutePoint,
    end: RoutePoint,
    waypoints: RoutePoint[] = [],
    options: RouteOptions = {}
  ): Promise<RoutingResult> {
    const {
      profile = 'driving',
      avoidTolls = false,
      avoidHighways = false,
      language = 'ru',
      alternatives = false
    } = options

    const allPoints = [start, ...waypoints, end]
    const points = allPoints.map(point => point.coordinates)

    const params = new URLSearchParams({
      key: this.apiKey,
      vehicle: profile,
      locale: language,
      instructions: 'true',
      calc_points: 'true',
      debug: 'false',
      elevation: 'false',
      points_encoded: 'false'
    })

    if (avoidTolls) params.append('ch.disable', 'true')
    if (avoidHighways) params.append('avoid', 'motorway')
    if (alternatives) params.append('alternative_route.max_paths', '3')

    // Добавляем точки
    points.forEach(([lng, lat]) => {
      params.append('point', `${lat},${lng}`)
    })

    try {
      const response = await fetch(`${this.baseUrl}/route?${params}`)
      
      if (!response.ok) {
        throw new Error(`GraphHopper API error: ${response.status}`)
      }

      const data = await response.json()

      if (data.info?.errors) {
        throw new Error(data.info.errors[0].message)
      }

      return this.transformGraphHopperResult(data, allPoints)
    } catch (error) {
      console.error('GraphHopper routing error:', error)
      throw new Error('Ошибка построения маршрута')
    }
  }

  private transformGraphHopperResult(data: any, waypoints: RoutePoint[]): RoutingResult {
    const routes = data.paths.map((path: any, index: number) => ({
      id: `graphhopper_${Date.now()}_${index}`,
      coordinates: path.points.coordinates,
      distance: path.distance,
      duration: path.time / 1000, // GraphHopper возвращает время в миллисекундах
      steps: this.transformGraphHopperSteps(path.instructions),
      bounds: this.calculateBounds(path.points.coordinates),
      isAlternative: index > 0,
      summary: `${this.formatDistance(path.distance)}, ${this.formatDuration(path.time / 1000)}`
    }))

    return {
      routes,
      waypoints,
      timestamp: Date.now()
    }
  }

  private transformGraphHopperSteps(instructions: any[]): RouteStep[] {
    return instructions.map(instruction => ({
      coordinates: [], // GraphHopper не предоставляет координаты для каждого шага
      instruction: instruction.text,
      distance: instruction.distance,
      duration: instruction.time / 1000,
      maneuver: {
        type: this.mapGraphHopperSign(instruction.sign),
        modifier: instruction.modifier
      },
      roadName: instruction.street_name,
      direction: ''
    }))
  }

  private mapGraphHopperSign(sign: number): string {
    const signMap: Record<number, string> = {
      0: 'continue',
      1: 'turn',
      2: 'turn',
      3: 'turn',
      4: 'arrive',
      5: 'depart',
      6: 'roundabout',
      7: 'roundabout exit'
    }
    return signMap[sign] || 'continue'
  }

  private calculateBounds(coordinates: [number, number][]): [[number, number], [number, number]] {
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

  private formatDistance(meters: number): string {
    if (meters < 1000) {
      return `${Math.round(meters)} м`
    }
    return `${(meters / 1000).toFixed(1)} км`
  }

  private formatDuration(seconds: number): string {
    const hours = Math.floor(seconds / 3600)
    const minutes = Math.floor((seconds % 3600) / 60)

    if (hours > 0) {
      return `${hours} ч ${minutes} мин`
    }
    return `${minutes} мин`
  }
}

/**
 * Основной сервис маршрутизации
 */
export class RoutingService {
  private providers: Map<string, RoutingProvider> = new Map()
  private defaultProvider: string

  constructor(defaultProvider: string = 'osrm') {
    this.defaultProvider = defaultProvider
    
    // Добавляем OSRM по умолчанию
    this.addProvider(new OSRMProvider())
  }

  addProvider(provider: RoutingProvider) {
    this.providers.set(provider.name, provider)
  }

  setDefaultProvider(name: string) {
    if (!this.providers.has(name)) {
      throw new Error(`Provider ${name} not found`)
    }
    this.defaultProvider = name
  }

  async calculateRoute(
    start: RoutePoint,
    end: RoutePoint,
    waypoints: RoutePoint[] = [],
    options: RouteOptions = {},
    providerName?: string
  ): Promise<RoutingResult> {
    const provider = this.providers.get(providerName || this.defaultProvider)
    if (!provider) {
      throw new Error(`Provider ${providerName || this.defaultProvider} not found`)
    }

    return provider.calculateRoute(start, end, waypoints, options)
  }

  getAvailableProviders(): string[] {
    return Array.from(this.providers.keys())
  }

  /**
   * Создание простого маршрута по прямой линии
   */
  createStraightRoute(start: RoutePoint, end: RoutePoint): Route {
    const coordinates: [number, number][] = [start.coordinates, end.coordinates]
    const distance = this.calculateDistance(start.coordinates, end.coordinates)
    const duration = distance / 1.4 // Примерная скорость 1.4 м/с (5 км/ч)

    return {
      id: `straight_${Date.now()}`,
      coordinates,
      distance,
      duration,
      steps: [
        {
          coordinates,
          instruction: `Двигайтесь к ${end.name || 'пункту назначения'}`,
          distance,
          duration,
          maneuver: { type: 'depart' }
        }
      ],
      bounds: [
        [
          Math.min(start.coordinates[0], end.coordinates[0]),
          Math.min(start.coordinates[1], end.coordinates[1])
        ],
        [
          Math.max(start.coordinates[0], end.coordinates[0]),
          Math.max(start.coordinates[1], end.coordinates[1])
        ]
      ],
      summary: `${this.formatDistance(distance)} по прямой`
    }
  }

  private calculateDistance(coords1: [number, number], coords2: [number, number]): number {
    const R = 6371000 // Радиус Земли в метрах
    const [lon1, lat1] = coords1
    const [lon2, lat2] = coords2

    const φ1 = lat1 * Math.PI / 180
    const φ2 = lat2 * Math.PI / 180
    const Δφ = (lat2 - lat1) * Math.PI / 180
    const Δλ = (lon2 - lon1) * Math.PI / 180

    const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
              Math.cos(φ1) * Math.cos(φ2) *
              Math.sin(Δλ / 2) * Math.sin(Δλ / 2)
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a))

    return R * c
  }

  private formatDistance(meters: number): string {
    if (meters < 1000) {
      return `${Math.round(meters)} м`
    }
    return `${(meters / 1000).toFixed(1)} км`
  }
}

// Создаем экземпляр сервиса по умолчанию
export const routingService = new RoutingService()

// Экспортируем типы
export type { RouteOptions, RoutePoint, Route, RoutingResult }