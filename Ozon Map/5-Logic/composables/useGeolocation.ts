/**
 * Composable для работы с геолокацией
 */

import { ref, computed, onUnmounted } from 'vue'
import type { Ref } from 'vue'

export interface GeolocationState {
  /** Поддерживается ли геолокация */
  isSupported: Ref<boolean>
  /** Текущая позиция */
  position: Ref<GeolocationPosition | null>
  /** Ошибка геолокации */
  error: Ref<GeolocationPositionError | null>
  /** Состояние загрузки */
  isLoading: Ref<boolean>
  /** Активно ли отслеживание */
  isWatching: Ref<boolean>
  /** Точность позиции */
  accuracy: Ref<number | null>
  /** Координаты */
  coordinates: Ref<[number, number] | null>
  /** Timestamp последнего обновления */
  timestamp: Ref<number | null>
}

export interface GeolocationOptions extends PositionOptions {
  /** Включить высокую точность */
  enableHighAccuracy?: boolean
  /** Таймаут в миллисекундах */
  timeout?: number
  /** Максимальный возраст кеша в миллисекундах */
  maximumAge?: number
}

export interface GeolocationMethods {
  /** Получить текущую позицию */
  getCurrentPosition: (options?: GeolocationOptions) => Promise<GeolocationPosition>
  /** Начать отслеживание позиции */
  watchPosition: (
    successCallback?: (position: GeolocationPosition) => void,
    errorCallback?: (error: GeolocationPositionError) => void,
    options?: GeolocationOptions
  ) => number
  /** Остановить отслеживание позиции */
  stopWatching: (watchId?: number) => void
  /** Очистить ошибки */
  clearError: () => void
  /** Очистить позицию */
  clearPosition: () => void
}

export interface UseGeolocationReturn extends GeolocationState, GeolocationMethods {}

/**
 * Composable для работы с геолокацией
 */
export function useGeolocation(
  defaultOptions: GeolocationOptions = {}
): UseGeolocationReturn {
  // State
  const isSupported = ref(!!navigator.geolocation)
  const position = ref<GeolocationPosition | null>(null)
  const error = ref<GeolocationPositionError | null>(null)
  const isLoading = ref(false)
  const isWatching = ref(false)
  const accuracy = ref<number | null>(null)
  const timestamp = ref<number | null>(null)

  // Watch IDs для отслеживания
  const watchIds = new Set<number>()

  // Default options
  const options: GeolocationOptions = {
    enableHighAccuracy: true,
    timeout: 10000,
    maximumAge: 60000,
    ...defaultOptions
  }

  // Computed
  const coordinates = computed<[number, number] | null>(() => {
    if (!position.value) return null
    const { longitude, latitude } = position.value.coords
    return [longitude, latitude]
  })

  // Methods
  const updatePosition = (newPosition: GeolocationPosition) => {
    position.value = newPosition
    accuracy.value = newPosition.coords.accuracy
    timestamp.value = newPosition.timestamp
    error.value = null
  }

  const updateError = (newError: GeolocationPositionError) => {
    error.value = newError
    isLoading.value = false
    console.error('Geolocation error:', getErrorMessage(newError))
  }

  const getCurrentPosition = (customOptions?: GeolocationOptions): Promise<GeolocationPosition> => {
    return new Promise((resolve, reject) => {
      if (!isSupported.value) {
        const error = new Error('Геолокация не поддерживается') as any
        error.code = 0
        reject(error)
        return
      }

      isLoading.value = true
      error.value = null

      const positionOptions = { ...options, ...customOptions }

      navigator.geolocation.getCurrentPosition(
        (pos) => {
          updatePosition(pos)
          isLoading.value = false
          resolve(pos)
        },
        (err) => {
          updateError(err)
          reject(err)
        },
        positionOptions
      )
    })
  }

  const watchPosition = (
    successCallback?: (position: GeolocationPosition) => void,
    errorCallback?: (error: GeolocationPositionError) => void,
    customOptions?: GeolocationOptions
  ): number => {
    if (!isSupported.value) {
      const error = new Error('Геолокация не поддерживается') as any
      error.code = 0
      if (errorCallback) {
        errorCallback(error)
      } else {
        updateError(error)
      }
      return -1
    }

    const positionOptions = { ...options, ...customOptions }

    const watchId = navigator.geolocation.watchPosition(
      (pos) => {
        updatePosition(pos)
        if (successCallback) {
          successCallback(pos)
        }
      },
      (err) => {
        if (errorCallback) {
          errorCallback(err)
        } else {
          updateError(err)
        }
      },
      positionOptions
    )

    watchIds.add(watchId)
    isWatching.value = true

    return watchId
  }

  const stopWatching = (watchId?: number) => {
    if (watchId !== undefined) {
      navigator.geolocation.clearWatch(watchId)
      watchIds.delete(watchId)
    } else {
      // Остановить все отслеживания
      watchIds.forEach(id => {
        navigator.geolocation.clearWatch(id)
      })
      watchIds.clear()
    }

    if (watchIds.size === 0) {
      isWatching.value = false
    }
  }

  const clearError = () => {
    error.value = null
  }

  const clearPosition = () => {
    position.value = null
    accuracy.value = null
    timestamp.value = null
  }

  // Helper function
  const getErrorMessage = (error: GeolocationPositionError): string => {
    switch (error.code) {
      case error.PERMISSION_DENIED:
        return 'Доступ к геолокации запрещен пользователем'
      case error.POSITION_UNAVAILABLE:
        return 'Информация о местоположении недоступна'
      case error.TIMEOUT:
        return 'Превышено время ожидания при получении геолокации'
      default:
        return error.message || 'Неизвестная ошибка геолокации'
    }
  }

  // Cleanup
  onUnmounted(() => {
    stopWatching()
  })

  return {
    // State
    isSupported,
    position,
    error,
    isLoading,
    isWatching,
    accuracy,
    coordinates,
    timestamp,

    // Methods
    getCurrentPosition,
    watchPosition,
    stopWatching,
    clearError,
    clearPosition
  }
}

/**
 * Composable для расчета расстояния между координатами
 */
export function useDistanceCalculator() {
  /**
   * Расчет расстояния между двумя точками (формула Haversine)
   */
  const calculateDistance = (
    coords1: [number, number],
    coords2: [number, number]
  ): number => {
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

  /**
   * Форматирование расстояния для отображения
   */
  const formatDistance = (distance: number): string => {
    if (distance < 1000) {
      return `${Math.round(distance)} м`
    } else if (distance < 10000) {
      return `${(distance / 1000).toFixed(1)} км`
    } else {
      return `${Math.round(distance / 1000)} км`
    }
  }

  /**
   * Расчет bearing (направления) между двумя точками
   */
  const calculateBearing = (
    coords1: [number, number],
    coords2: [number, number]
  ): number => {
    const [lon1, lat1] = coords1
    const [lon2, lat2] = coords2

    const φ1 = lat1 * Math.PI / 180
    const φ2 = lat2 * Math.PI / 180
    const Δλ = (lon2 - lon1) * Math.PI / 180

    const y = Math.sin(Δλ) * Math.cos(φ2)
    const x = Math.cos(φ1) * Math.sin(φ2) - Math.sin(φ1) * Math.cos(φ2) * Math.cos(Δλ)

    const θ = Math.atan2(y, x)

    return (θ * 180 / Math.PI + 360) % 360
  }

  /**
   * Получение направления по компасу
   */
  const getCompassDirection = (bearing: number): string => {
    const directions = ['С', 'СВ', 'В', 'ЮВ', 'Ю', 'ЮЗ', 'З', 'СЗ']
    const index = Math.round(bearing / 45) % 8
    return directions[index]
  }

  /**
   * Проверка, находится ли точка в радиусе
   */
  const isWithinRadius = (
    center: [number, number],
    point: [number, number],
    radius: number
  ): boolean => {
    const distance = calculateDistance(center, point)
    return distance <= radius
  }

  /**
   * Поиск ближайшей точки из массива
   */
  const findClosestPoint = (
    target: [number, number],
    points: Array<{ coordinates: [number, number]; [key: string]: any }>
  ) => {
    if (points.length === 0) return null

    let closest = points[0]
    let minDistance = calculateDistance(target, closest.coordinates)

    for (let i = 1; i < points.length; i++) {
      const distance = calculateDistance(target, points[i].coordinates)
      if (distance < minDistance) {
        minDistance = distance
        closest = points[i]
      }
    }

    return { point: closest, distance: minDistance }
  }

  /**
   * Сортировка точек по расстоянию
   */
  const sortByDistance = <T extends { coordinates: [number, number] }>(
    target: [number, number],
    points: T[]
  ): Array<T & { distance: number }> => {
    return points
      .map(point => ({
        ...point,
        distance: calculateDistance(target, point.coordinates)
      }))
      .sort((a, b) => a.distance - b.distance)
  }

  return {
    calculateDistance,
    formatDistance,
    calculateBearing,
    getCompassDirection,
    isWithinRadius,
    findClosestPoint,
    sortByDistance
  }
}

/**
 * Composable для работы с ограничениями геолокации
 */
export function useGeolocationPermissions() {
  const permissionState = ref<PermissionState | null>(null)
  const isPermissionGranted = computed(() => permissionState.value === 'granted')
  const isPermissionDenied = computed(() => permissionState.value === 'denied')
  const isPermissionPrompt = computed(() => permissionState.value === 'prompt')

  const checkPermission = async (): Promise<PermissionState> => {
    if (!navigator.permissions) {
      // Fallback для браузеров без Permissions API
      return 'granted'
    }

    try {
      const permission = await navigator.permissions.query({ name: 'geolocation' as PermissionName })
      permissionState.value = permission.state
      
      // Слушаем изменения разрешения
      permission.onchange = () => {
        permissionState.value = permission.state
      }
      
      return permission.state
    } catch (error) {
      console.warn('Unable to check geolocation permission:', error)
      return 'granted' // Fallback
    }
  }

  const requestPermission = async (): Promise<boolean> => {
    try {
      await new Promise<GeolocationPosition>((resolve, reject) => {
        navigator.geolocation.getCurrentPosition(resolve, reject, {
          timeout: 1000,
          maximumAge: Infinity
        })
      })
      return true
    } catch (error) {
      return false
    }
  }

  return {
    permissionState,
    isPermissionGranted,
    isPermissionDenied,
    isPermissionPrompt,
    checkPermission,
    requestPermission
  }
}