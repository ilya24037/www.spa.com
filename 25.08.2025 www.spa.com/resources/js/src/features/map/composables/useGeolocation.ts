import { ref, Ref } from 'vue'
import type { Coordinates } from '../types'
import { GEOLOCATION_TIMEOUT } from '../lib/mapConstants'

export function useGeolocation() {
  const userLocation = ref<Coordinates | null>(null)
  const locationActive = ref(false)
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  /**
   * Определение местоположения через браузер
   */
  const detectLocationByBrowser = (): Promise<Coordinates | null> => {
    return new Promise((resolve) => {
      if (!navigator.geolocation) {
        error.value = 'Геолокация не поддерживается браузером'
        resolve(null)
        return
      }

      navigator.geolocation.getCurrentPosition(
        (position) => {
          const coords = {
            lat: position.coords.latitude,
            lng: position.coords.longitude
          }
          userLocation.value = coords
          locationActive.value = true
          error.value = null
          resolve(coords)
        },
        (err) => {
          error.value = err.message
          resolve(null)
        },
        { 
          timeout: GEOLOCATION_TIMEOUT,
          enableHighAccuracy: true 
        }
      )
    })
  }

  /**
   * Определение местоположения по IP
   */
  const detectLocationByIP = async (): Promise<Coordinates | null> => {
    try {
      const response = await fetch('https://ipapi.co/json/')
      const data = await response.json()
      
      if (data.latitude && data.longitude) {
        const coords = {
          lat: parseFloat(data.latitude),
          lng: parseFloat(data.longitude)
        }
        userLocation.value = coords
        error.value = null
        return coords
      }
    } catch (err) {
      error.value = 'Не удалось определить местоположение по IP'
      console.error('Ошибка определения местоположения по IP:', err)
    }
    
    return null
  }

  /**
   * Определение местоположения любым доступным способом
   */
  const detectLocation = async (): Promise<Coordinates | null> => {
    isLoading.value = true
    error.value = null
    
    try {
      // Сначала пробуем через браузер
      const browserLocation = await detectLocationByBrowser()
      if (browserLocation) {
        return browserLocation
      }
      
      // Затем через IP
      const ipLocation = await detectLocationByIP()
      if (ipLocation) {
        return ipLocation
      }
      
      error.value = 'Не удалось определить местоположение'
      return null
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Центрирование на местоположении пользователя
   */
  const centerOnUserLocation = async (map: any): Promise<void> => {
    if (!map) return
    
    const location = await detectLocation()
    if (location) {
      map.setCenter([location.lat, location.lng], 15)
      
      // Добавляем круг для обозначения местоположения
      if (window.ymaps) {
        const circle = new window.ymaps.Circle(
          [[location.lat, location.lng], 100], // центр и радиус в метрах
          {
            hintContent: 'Вы здесь'
          },
          {
            fillColor: '#3B82F677',
            strokeColor: '#3B82F6',
            strokeOpacity: 0.8,
            strokeWidth: 2
          }
        )
        map.geoObjects.add(circle)
      }
    }
  }

  /**
   * Сброс местоположения
   */
  const resetLocation = () => {
    userLocation.value = null
    locationActive.value = false
    error.value = null
  }

  return {
    userLocation,
    locationActive,
    isLoading,
    error,
    detectLocationByBrowser,
    detectLocationByIP,
    detectLocation,
    centerOnUserLocation,
    resetLocation
  }
}