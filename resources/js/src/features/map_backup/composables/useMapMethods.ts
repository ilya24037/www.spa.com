import { type Ref } from 'vue'
import type { Coordinates } from '../types'

export function useMapMethods(map: Ref<any | null>) {
  const setCenter = (center: Coordinates, zoom?: number) => {
    if (!map.value) return
    map.value.setCenter([center.lat, center.lng], zoom)
  }

  const setZoom = (zoom: number) => {
    if (!map.value) return
    map.value.setZoom(zoom)
  }

  const getBounds = (): number[][] | null => {
    if (!map.value) return null
    return map.value.getBounds()
  }

  const getCenter = (): Coordinates | null => {
    if (!map.value) return null
    const center = map.value.getCenter()
    return { lat: center[0], lng: center[1] }
  }

  const getZoom = (): number | null => {
    if (!map.value) return null
    return map.value.getZoom()
  }

  const autoDetectLocation = async (): Promise<Coordinates | null> => {
    if (!map.value || !window.ymaps) return null
    
    try {
      const result = await window.ymaps.geolocation.get({ provider: 'yandex' })
      const coords = result.geoObjects.get(0).geometry.getCoordinates()
      if (coords && coords.length === 2) {
        const location = { lat: coords[0], lng: coords[1] }
        setCenter(location)
        return location
      }
    } catch (error) {
      console.warn('Не удалось определить местоположение автоматически')
    }
    return null
  }

  return {
    setCenter,
    setZoom,
    getBounds,
    getCenter,
    getZoom,
    autoDetectLocation,
    getMap: () => map.value
  }
}