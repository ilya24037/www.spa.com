import { ref } from 'vue'
import type { Coordinates } from '../types'

export function useAddressGeocoding() {
  const isGeocoding = ref(false)
  const geocodingError = ref<string | null>(null)
  const lastAddress = ref<string>('')
  const lastCoordinates = ref<Coordinates | null>(null)

  // Кеш для геокодирования
  const geocodeCache = new Map<string, { address: string; coords: Coordinates }>()

  /**
   * Получение адреса по координатам (обратное геокодирование)
   */
  const getAddressFromCoords = async (coords: Coordinates): Promise<string> => {
    if (!window.ymaps || !window.ymaps.geocode) {
      geocodingError.value = 'API Яндекс.Карт не загружено'
      return ''
    }

    // Проверяем кеш
    const cacheKey = `${coords.lat},${coords.lng}`
    const cached = geocodeCache.get(cacheKey)
    if (cached) {
      lastAddress.value = cached.address
      return cached.address
    }

    isGeocoding.value = true
    geocodingError.value = null

    try {
      const result = await window.ymaps.geocode([coords.lat, coords.lng], { results: 1 })
      const firstGeoObject = result.geoObjects.get(0)
      
      if (firstGeoObject) {
        const address = firstGeoObject.getAddressLine()
        
        // Сохраняем в кеш
        geocodeCache.set(cacheKey, { address, coords })
        
        lastAddress.value = address
        lastCoordinates.value = coords
        
        return address
      }
      
      geocodingError.value = 'Адрес не найден'
      return ''
    } catch (error) {
      console.error('Ошибка получения адреса:', error)
      geocodingError.value = 'Ошибка при получении адреса'
      return ''
    } finally {
      isGeocoding.value = false
    }
  }

  /**
   * Получение координат по адресу (прямое геокодирование)
   */
  const getCoordsFromAddress = async (address: string): Promise<Coordinates | null> => {
    if (!address || !window.ymaps || !window.ymaps.geocode) {
      geocodingError.value = 'API Яндекс.Карт не загружено'
      return null
    }

    // Проверяем кеш
    const cached = Array.from(geocodeCache.values()).find(item => 
      item.address.toLowerCase().includes(address.toLowerCase())
    )
    if (cached) {
      lastAddress.value = cached.address
      lastCoordinates.value = cached.coords
      return cached.coords
    }

    isGeocoding.value = true
    geocodingError.value = null

    try {
      const result = await window.ymaps.geocode(address, { results: 1 })
      const firstGeoObject = result.geoObjects.get(0)
      
      if (firstGeoObject) {
        const coords = firstGeoObject.geometry.getCoordinates()
        const fullAddress = firstGeoObject.getAddressLine()
        
        const coordinates: Coordinates = {
          lat: coords[0],
          lng: coords[1]
        }
        
        // Сохраняем в кеш
        geocodeCache.set(`${coords[0]},${coords[1]}`, { 
          address: fullAddress, 
          coords: coordinates 
        })
        
        lastAddress.value = fullAddress
        lastCoordinates.value = coordinates
        
        return coordinates
      }
      
      geocodingError.value = 'Адрес не найден'
      return null
    } catch (error) {
      console.error('Ошибка поиска адреса:', error)
      geocodingError.value = 'Ошибка при поиске адреса'
      return null
    } finally {
      isGeocoding.value = false
    }
  }

  /**
   * Поиск адреса и центрирование карты
   */
  const searchAndCenterOnAddress = async (address: string, map: any): Promise<boolean> => {
    const coords = await getCoordsFromAddress(address)
    
    if (coords && map) {
      map.setCenter([coords.lat, coords.lng], 15)
      return true
    }
    
    return false
  }

  /**
   * Получение дополнительной информации о месте
   */
  const getPlaceDetails = async (coords: Coordinates): Promise<any> => {
    if (!window.ymaps || !window.ymaps.geocode) return null

    try {
      const result = await window.ymaps.geocode([coords.lat, coords.lng], { 
        results: 1,
        kind: 'house'
      })
      
      const firstGeoObject = result.geoObjects.get(0)
      if (!firstGeoObject) return null

      const properties = firstGeoObject.properties.getAll()
      const metaData = properties.metaDataProperty?.GeocoderMetaData

      return {
        address: firstGeoObject.getAddressLine(),
        country: firstGeoObject.getCountry(),
        locality: firstGeoObject.getLocalities()[0],
        thoroughfare: firstGeoObject.getThoroughfare(),
        premise: firstGeoObject.getPremise(),
        premiseNumber: firstGeoObject.getPremiseNumber(),
        postalCode: metaData?.Address?.postal_code,
        kind: metaData?.kind,
        precision: metaData?.precision
      }
    } catch (error) {
      console.error('Ошибка получения деталей места:', error)
      return null
    }
  }

  /**
   * Очистка кеша геокодирования
   */
  const clearCache = () => {
    geocodeCache.clear()
  }

  /**
   * Сброс состояния
   */
  const reset = () => {
    isGeocoding.value = false
    geocodingError.value = null
    lastAddress.value = ''
    lastCoordinates.value = null
  }

  return {
    isGeocoding,
    geocodingError,
    lastAddress,
    lastCoordinates,
    getAddressFromCoords,
    getCoordsFromAddress,
    searchAndCenterOnAddress,
    getPlaceDetails,
    clearCache,
    reset
  }
}