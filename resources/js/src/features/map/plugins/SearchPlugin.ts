/**
 * Плагин поиска по адресу и обратного геокодинга
 * Размер: 50 строк
 */
import type { MapPlugin, MapStore, Coordinates } from '../core/MapStore'

export class SearchPlugin implements MapPlugin {
  name = 'search'
  private geocoder: any = null
  private searchControl: any = null
  private map: any = null

  constructor(private options: any = {}) {
    this.options = {
      showSearchControl: false,
      reverseGeocode: true,
      ...options
    }
  }

  async install(map: any, store: MapStore) {
    this.map = map

    if (!window.ymaps) return

    // Добавляем контрол поиска
    if (this.options.showSearchControl) {
      this.searchControl = new window.ymaps.control.SearchControl({
        options: {
          float: 'left',
          floatIndex: 100,
          noPlacemark: true
        }
      })
      map.controls.add(this.searchControl)

      // Обработчик результата поиска
      this.searchControl.events.add('resultselect', (e: any) => {
        const index = e.get('index')
        this.searchControl.getResult(index).then((res: any) => {
          const coords = res.geometry.getCoordinates()
          const address = res.properties.get('text')
          
          store.setCoordinates({
            lat: coords[0],
            lng: coords[1]
          })
          store.setAddress(address)
          store.emit('search-result', { address, coords })
        })
      })
    }

    // Обратное геокодирование при клике
    if (this.options.reverseGeocode) {
      map.events.add('click', (e: any) => {
        const coords = e.get('coords')
        this.reverseGeocode(coords, store)
      })
    }
  }

  async reverseGeocode(coords: number[], store: MapStore) {
    if (!window.ymaps) return

    try {
      const geocodeResult = await window.ymaps.geocode(coords)
      const firstGeoObject = geocodeResult.geoObjects.get(0)
      
      if (firstGeoObject) {
        const address = firstGeoObject.getAddressLine()
        store.setAddress(address)
        store.emit('address-found', {
          address,
          coords: {
            lat: coords[0],
            lng: coords[1]
          }
        })
      }
    } catch (error) {
      console.error('[SearchPlugin] Reverse geocode failed:', error)
    }
  }

  async searchAddress(address: string): Promise<Coordinates | null> {
    if (!window.ymaps) return null

    try {
      const geocodeResult = await window.ymaps.geocode(address)
      const firstGeoObject = geocodeResult.geoObjects.get(0)
      
      if (firstGeoObject) {
        const coords = firstGeoObject.geometry.getCoordinates()
        return {
          lat: coords[0],
          lng: coords[1]
        }
      }
    } catch (error) {
      console.error('[SearchPlugin] Address search failed:', error)
    }
    
    return null
  }

  destroy() {
    if (this.searchControl && this.map) {
      this.map.controls.remove(this.searchControl)
      this.searchControl = null
    }
  }
}