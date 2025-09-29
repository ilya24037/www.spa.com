/**
 * Composable для определения города пользователя по IP-адресу
 * 
 * Возможности:
 * - Автоматическое определение города через DaData API
 * - Кеширование результата на время сессии (sessionStorage)
 * - Graceful degradation с fallback на Москву при ошибках
 * - Privacy by design - только город, никаких персональных данных
 * 
 * @author Claude AI
 * @created 2025-09-08
 */

import { ref, readonly, type Ref } from 'vue'

// Константы конфигурации
const IP_API_URL = 'http://ip-api.com/json/'
const CACHE_KEY = 'spa_user_ip_location'
const CACHE_DURATION = 24 * 60 * 60 * 1000 // 24 часа в миллисекундах

// Fallback координаты (Москва, центр)
const MOSCOW_FALLBACK = {
  city: 'Москва',
  coordinates: { lat: 55.7558, lng: 37.6173 },
  source: 'fallback' as const
}

// TypeScript интерфейсы
interface IpLocationResult {
  city: string
  coordinates: { lat: number; lng: number }
  source: 'ip' | 'fallback' | 'cache'
  timestamp?: number
  error?: string
}

interface IpApiResponse {
  status: string
  country: string
  countryCode: string
  region: string
  regionName: string
  city: string
  zip: string
  lat: number
  lon: number
  timezone: string
  isp: string
  org: string
  as: string
  query: string
}

interface CachedLocation extends IpLocationResult {
  timestamp: number
}

interface UseIpGeolocationReturn {
  detectUserLocation: () => Promise<IpLocationResult>
  clearLocationCache: () => void
  isLocationCached: () => boolean
  isLoading: Readonly<Ref<boolean>>
}

/**
 * Composable для IP-геолокации
 */
export function useIpGeolocation(): UseIpGeolocationReturn {
  // Реактивное состояние загрузки
  const isLoading = ref(false)

  /**
   * Основной метод определения локации пользователя
   */
  const detectUserLocation = async (): Promise<IpLocationResult> => {
    try {
      // 1. Проверяем кеш сессии
      const cachedLocation = getCachedLocation()
      if (cachedLocation && isCacheValid(cachedLocation)) {
        console.log('🗺️ [IP Geolocation] Используем кешированную локацию:', cachedLocation.city)
        return {
          city: cachedLocation.city,
          coordinates: cachedLocation.coordinates,
          source: 'cache'
        }
      }

      // 2. Выполняем запрос к IP-API.com
      console.log('🌍 [IP Geolocation] Запрос к IP-API.com...')
      isLoading.value = true

      // Создаем AbortController для таймаута (совместимость со всеми браузерами)
      const controller = new AbortController()
      const timeoutId = setTimeout(() => controller.abort(), 5000) // 5 секунд

      const response = await fetch(IP_API_URL, {
        method: 'GET',
        headers: {
          'Accept': 'application/json'
        },
        signal: controller.signal
      })

      clearTimeout(timeoutId)

      if (!response.ok) {
        throw new Error(`IP-API.com HTTP ${response.status}: ${response.statusText}`)
      }

      const data: IpApiResponse = await response.json()

      // 3. Парсим и валидируем ответ
      if (data?.status === 'success' && data.city && typeof data.lat === 'number' && typeof data.lon === 'number') {
        const lat = data.lat
        const lng = data.lon

        // Проверяем что координаты валидные (глобальные пределы)
        if (isNaN(lat) || isNaN(lng) || lat < -90 || lat > 90 || lng < -180 || lng > 180) {
          throw new Error(`Некорректные координаты: lat=${lat}, lng=${lng}`)
        }

        const result: IpLocationResult = {
          city: data.city,
          coordinates: { lat, lng },
          source: 'ip',
          timestamp: Date.now()
        }

        // 4. Сохраняем в кеш
        setCachedLocation(result)
        console.log('✅ [IP Geolocation] Город определен:', result.city)

        return result
      } else {
        throw new Error('Некорректный формат ответа от IP-API.com')
      }

    } catch (error) {
      // Логируем ошибку для отладки, но не показываем пользователю
      console.warn('⚠️ [IP Geolocation] Не удалось определить город по IP:', error)

      // 5. Возвращаем fallback на Москву
      console.log('🏛️ [IP Geolocation] Используем fallback на Москву')
      return {
        ...MOSCOW_FALLBACK,
        error: error instanceof Error ? error.message : 'Неизвестная ошибка'
      }
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Получение кешированной локации из sessionStorage
   */
  const getCachedLocation = (): CachedLocation | null => {
    try {
      const cached = sessionStorage.getItem(CACHE_KEY)
      if (!cached) return null

      const parsed: CachedLocation = JSON.parse(cached)
      
      // Валидируем структуру кеша
      if (!parsed.city || !parsed.coordinates || !parsed.timestamp) {
        console.warn('⚠️ [IP Geolocation] Некорректная структура кеша, очищаем')
        clearLocationCache()
        return null
      }

      return parsed
    } catch (error) {
      console.warn('⚠️ [IP Geolocation] Ошибка чтения кеша:', error)
      clearLocationCache()
      return null
    }
  }

  /**
   * Сохранение локации в кеш
   */
  const setCachedLocation = (location: IpLocationResult): void => {
    try {
      const cacheData: CachedLocation = {
        ...location,
        timestamp: Date.now()
      }
      
      sessionStorage.setItem(CACHE_KEY, JSON.stringify(cacheData))
      console.log('💾 [IP Geolocation] Локация сохранена в кеш')
    } catch (error) {
      console.warn('⚠️ [IP Geolocation] Не удалось сохранить в кеш:', error)
      // Не критично, продолжаем работу без кеширования
    }
  }

  /**
   * Проверка валидности кеша (не устарел ли)
   */
  const isCacheValid = (cached: CachedLocation): boolean => {
    const now = Date.now()
    const cacheAge = now - cached.timestamp
    
    return cacheAge < CACHE_DURATION
  }

  /**
   * Очистка кеша локации
   */
  const clearLocationCache = (): void => {
    try {
      sessionStorage.removeItem(CACHE_KEY)
      console.log('🗑️ [IP Geolocation] Кеш локации очищен')
    } catch (error) {
      console.warn('⚠️ [IP Geolocation] Ошибка очистки кеша:', error)
    }
  }

  /**
   * Проверка наличия валидного кеша
   */
  const isLocationCached = (): boolean => {
    const cached = getCachedLocation()
    return cached !== null && isCacheValid(cached)
  }

  // Возвращаем публичный интерфейс composable
  return {
    detectUserLocation,
    clearLocationCache,
    isLocationCached,
    isLoading: readonly(isLoading)
  }
}

// Экспорт типов для использования в других компонентах
export type { IpLocationResult }
