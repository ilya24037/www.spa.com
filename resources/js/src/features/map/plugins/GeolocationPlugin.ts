/**
 * GeolocationPlugin - Плагин геолокации
 * Добавляет кнопку определения местоположения и автоопределение
 */

import type { MapPlugin, MapStore, Coordinates } from '../types'

export class GeolocationPlugin implements MapPlugin {
  name = 'geolocation'
  private control: any = null
  private map: any = null
  private store: MapStore | null = null

  constructor(private options: any = {}) {
    this.options = {
      autoDetect: false,
      showButton: true,
      zoom: 16,
      ...options
    }
  }

  /**
   * Установка плагина
   */
  async install(map: any, store: MapStore) {
    this.map = map
    this.store = store

    console.log('[GeolocationPlugin] Установлен')

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
        
        // Центрируем карту
        map.setCenter(position, this.options.zoom)
        
        // Обновляем store
        store.setCenter(coords)
        store.setCoordinates(coords)
        store.emit('geolocation-success', coords)
        
        console.log(`[GeolocationPlugin] Местоположение определено: ${coords.lat}, ${coords.lng}`)
      })

      // Обработчик ошибки геолокации
      this.control.events.add('locationerror', (e: any) => {
        const error = e.get('error')
        console.error('[GeolocationPlugin] Ошибка геолокации:', error)
        store.emit('geolocation-error', error.message)
      })
    }

    // Автоопределение при загрузке
    if (this.options.autoDetect) {
      this.detectLocation()
    }

    // Слушаем запросы геолокации из store
    store.on('geolocation-detect', this.detectLocation.bind(this))
  }

  /**
   * Определение местоположения через браузер API
   */
  private async detectLocation() {
    if (!navigator.geolocation) {
      console.warn('[GeolocationPlugin] Геолокация не поддерживается браузером')
      this.store?.emit('geolocation-error', 'Геолокация не поддерживается')
      return
    }

    console.log('[GeolocationPlugin] Запрос местоположения...')

    navigator.geolocation.getCurrentPosition(
      (position) => {
        const coords: Coordinates = {
          lat: position.coords.latitude,
          lng: position.coords.longitude
        }
        
        // Центрируем карту
        if (this.map) {
          this.map.setCenter([coords.lat, coords.lng], this.options.zoom)
        }
        
        // Обновляем store
        if (this.store) {
          this.store.setCenter(coords)
          this.store.setCoordinates(coords)
          this.store.emit('geolocation-auto-detected', coords)
        }
        
        console.log(`[GeolocationPlugin] Автоопределение: ${coords.lat}, ${coords.lng}`)
      },
      (error) => {
        console.warn('[GeolocationPlugin] Ошибка автоопределения:', error)
        
        let errorMessage = 'Не удалось определить местоположение'
        switch (error.code) {
          case error.PERMISSION_DENIED:
            errorMessage = 'Доступ к геолокации запрещен'
            break
          case error.POSITION_UNAVAILABLE:
            errorMessage = 'Информация о местоположении недоступна'
            break
          case error.TIMEOUT:
            errorMessage = 'Превышено время ожидания геолокации'
            break
        }
        
        this.store?.emit('geolocation-error', errorMessage)
      },
      {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 60000
      }
    )
  }

  /**
   * Уничтожение плагина
   */
  destroy() {
    if (this.control && this.map) {
      this.map.controls.remove(this.control)
      this.control = null
    }
    this.map = null
    this.store = null
  }
}