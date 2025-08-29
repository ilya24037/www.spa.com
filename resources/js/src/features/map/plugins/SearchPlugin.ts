/**
 * SearchPlugin - Плагин поиска по адресу
 * Обеспечивает геокодирование и обратное геокодирование
 */

import type { MapPlugin, MapStore, Coordinates } from '../core/MapStore'

export class SearchPlugin implements MapPlugin {
  name = 'search'
  private map: any = null
  private store: MapStore | null = null

  /**
   * Установка плагина
   */
  async install(map: any, store: MapStore) {
    this.map = map
    this.store = store

    // Обратное геокодирование при клике на карту
    store.on('coordinates-change', this.handleCoordinatesChange.bind(this))
    
    // Поиск по адресу
    store.on('search-address', this.searchAddress.bind(this))
    
    console.log('[SearchPlugin] Installed')
  }

  /**
   * Обратное геокодирование - получение адреса по координатам
   */
  private async handleCoordinatesChange(coords: Coordinates | null) {
    if (!coords || !window.ymaps) return

    try {
      const geocodeResult = await window.ymaps.geocode([coords.lat, coords.lng], {
        results: 1,
        kind: 'house'
      })
      
      const firstGeoObject = geocodeResult.geoObjects.get(0)
      
      if (firstGeoObject) {
        const address = firstGeoObject.getAddressLine()
        this.store?.setAddress(address)
        this.store?.emit('address-found', { address, coordinates: coords })
      }
    } catch (error) {
      console.error('[SearchPlugin] Geocoding error:', error)
      this.store?.emit('search-error', 'Failed to get address')
    }
  }

  /**
   * Прямое геокодирование - поиск координат по адресу
   */
  async searchAddress(address: string): Promise<Coordinates | null> {
    if (!window.ymaps || !address) return null

    try {
      const geocodeResult = await window.ymaps.geocode(address, {
        results: 1,
        boundedBy: this.map?.getBounds(),
        strictBounds: false
      })
      
      const firstGeoObject = geocodeResult.geoObjects.get(0)
      
      if (firstGeoObject) {
        const coords = firstGeoObject.geometry.getCoordinates()
        const coordinates: Coordinates = {
          lat: coords[0],
          lng: coords[1]
        }
        
        // Обновляем карту и store
        if (this.map) {
          this.map.setCenter(coords, 16)
        }
        
        if (this.store) {
          this.store.setCoordinates(coordinates)
          this.store.setAddress(firstGeoObject.getAddressLine())
        }
        
        return coordinates
      }
    } catch (error) {
      console.error('[SearchPlugin] Search error:', error)
      this.store?.emit('search-error', 'Address not found')
    }
    
    return null
  }

  /**
   * Уничтожение плагина
   */
  destroy() {
    this.map = null
    this.store = null
    console.log('[SearchPlugin] Destroyed')
  }
}