/**
 * MarkerPlugin - Плагин для работы с маркерами
 * Базовая функциональность добавления/удаления маркеров
 */

import type { MapPlugin, MapStore, MapMarker, Coordinates } from '../core/MapStore'

export class MarkerPlugin implements MapPlugin {
  name = 'markers'
  private map: any = null
  private store: MapStore | null = null
  private markers = new Map<string | number, any>()

  /**
   * Установка плагина
   */
  async install(map: any, store: MapStore) {
    this.map = map
    this.store = store

    // Подписываемся на события store
    store.on('marker-add', this.handleMarkerAdd.bind(this))
    store.on('marker-remove', this.handleMarkerRemove.bind(this))
    store.on('marker-update', this.handleMarkerUpdate.bind(this))
    store.on('markers-clear', this.handleClearMarkers.bind(this))
    
    console.log('[MarkerPlugin] Installed')
  }

  /**
   * Добавление маркера на карту
   */
  private handleMarkerAdd(marker: MapMarker) {
    if (!window.ymaps || !this.map) return

    const placemark = new window.ymaps.Placemark(
      [marker.coordinates.lat, marker.coordinates.lng],
      {
        balloonContentHeader: marker.title || '',
        balloonContentBody: marker.description || '',
        hintContent: marker.title || ''
      },
      {
        preset: marker.icon || 'islands#redDotIcon'
      }
    )

    // Обработчик клика
    placemark.events.add('click', () => {
      this.store?.emit('marker-click', marker)
    })

    this.map.geoObjects.add(placemark)
    this.markers.set(marker.id, placemark)
  }

  /**
   * Удаление маркера с карты
   */
  private handleMarkerRemove(marker: MapMarker) {
    const placemark = this.markers.get(marker.id)
    if (placemark && this.map) {
      this.map.geoObjects.remove(placemark)
      this.markers.delete(marker.id)
    }
  }

  /**
   * Обновление маркера
   */
  private handleMarkerUpdate(marker: MapMarker) {
    const placemark = this.markers.get(marker.id)
    if (!placemark) return

    // Обновляем координаты
    placemark.geometry.setCoordinates([
      marker.coordinates.lat,
      marker.coordinates.lng
    ])

    // Обновляем свойства
    placemark.properties.set({
      balloonContentHeader: marker.title || '',
      balloonContentBody: marker.description || '',
      hintContent: marker.title || ''
    })

    // Обновляем иконку
    if (marker.icon) {
      placemark.options.set('preset', marker.icon)
    }
  }

  /**
   * Очистка всех маркеров
   */
  private handleClearMarkers() {
    for (const [id, placemark] of this.markers) {
      if (this.map) {
        this.map.geoObjects.remove(placemark)
      }
    }
    this.markers.clear()
  }

  /**
   * Уничтожение плагина
   */
  destroy() {
    this.handleClearMarkers()
    this.map = null
    this.store = null
    console.log('[MarkerPlugin] Destroyed')
  }
}