/**
 * Плагин геолокации
 * Добавляет кнопку определения местоположения и автоопределение
 * Размер: 40 строк
 */
import type { MapPlugin, MapStore, Coordinates } from '../core/MapStore'

export class GeolocationPlugin implements MapPlugin {
  name = 'geolocation'
  private control: any = null
  private map: any = null

  constructor(private options: any = {}) {
    this.options = {
      autoDetect: false,
      showButton: true,
      ...options
    }
  }

  async install(map: any, store: MapStore) {
    this.map = map

    // Добавляем кнопку геолокации
    if (this.options.showButton && window.ymaps) {
      this.control = new window.ymaps.control.GeolocationControl({
        options: {
          float: 'right',
          position: {
            bottom: 20,
            right: 20
          }
        }
      })

      map.controls.add(this.control)

      // Обработчик успешной геолокации
      this.control.events.add('locationchange', (e: any) => {
        const position = e.get('position')
        const coords: Coordinates = {
          lat: position[0],
          lng: position[1]
        }
        
        store.setCenter(coords)
        store.emit('geolocation-success', coords)
      })
    }

    // Автоопределение при загрузке
    if (this.options.autoDetect) {
      this.detectLocation(store)
    }
  }

  private async detectLocation(store: MapStore) {
    if (!navigator.geolocation) {
      console.warn('[GeolocationPlugin] Geolocation not supported')
      return
    }

    navigator.geolocation.getCurrentPosition(
      (position) => {
        const coords: Coordinates = {
          lat: position.coords.latitude,
          lng: position.coords.longitude
        }
        
        this.map.setCenter([coords.lat, coords.lng], 15)
        store.setCenter(coords)
        store.emit('geolocation-auto-detected', coords)
      },
      (error) => {
        console.warn('[GeolocationPlugin] Auto-detect failed:', error)
      }
    )
  }

  destroy() {
    if (this.control && this.map) {
      this.map.controls.remove(this.control)
      this.control = null
    }
  }
}