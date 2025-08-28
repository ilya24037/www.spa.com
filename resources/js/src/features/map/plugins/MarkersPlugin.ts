/**
 * MarkersPlugin - Плагин управления маркерами
 * Отвечает за добавление, удаление и управление маркерами на карте
 */

import type { MapPlugin, MapStore, MapMarker, Coordinates } from '../types'
import { MARKER_PRESETS, MARKER_COLORS } from '../utils/mapConstants'

export class MarkersPlugin implements MapPlugin {
  name = 'markers'
  private map: any = null
  private store: MapStore | null = null
  private markers: Map<string, any> = new Map()
  private singleMarker: any = null

  constructor(private options: any = {}) {
    this.options = {
      mode: 'single', // 'single' | 'multiple'
      draggable: true,
      preset: MARKER_PRESETS.DEFAULT,
      ...options
    }
  }

  /**
   * Установка плагина
   */
  async install(map: any, store: MapStore) {
    this.map = map
    this.store = store

    console.log(`[MarkersPlugin] Установлен в режиме: ${this.options.mode}`)

    // В режиме single добавляем маркер при клике
    if (this.options.mode === 'single') {
      map.events.add('click', (e: any) => {
        const coords = e.get('coords')
        this.setSingleMarker({
          lat: coords[0],
          lng: coords[1]
        })
      })
    }

    // Слушаем изменения в store
    store.on('markers-add', this.addMarker.bind(this))
    store.on('markers-remove', this.removeMarker.bind(this))
    store.on('markers-clear', this.clearMarkers.bind(this))
    store.on('markers-change', this.updateMarkers.bind(this))
  }

  /**
   * Установка одиночного маркера
   */
  setSingleMarker(coords: Coordinates) {
    if (!this.map || !window.ymaps) return

    // Удаляем старый маркер
    if (this.singleMarker) {
      this.map.geoObjects.remove(this.singleMarker)
    }

    // Создаем новый маркер
    this.singleMarker = new window.ymaps.Placemark(
      [coords.lat, coords.lng],
      {
        balloonContentHeader: 'Выбранное место',
        balloonContentBody: this.store?.address || 'Загрузка адреса...'
      },
      {
        preset: this.options.preset,
        draggable: this.options.draggable
      }
    )

    // Обработчик перетаскивания
    if (this.options.draggable) {
      this.singleMarker.events.add('dragend', () => {
        const newCoords = this.singleMarker.geometry.getCoordinates()
        const coordinates: Coordinates = {
          lat: newCoords[0],
          lng: newCoords[1]
        }
        
        this.store?.setCoordinates(coordinates)
        this.store?.emit('marker-moved', coordinates)
      })
    }

    // Добавляем на карту
    this.map.geoObjects.add(this.singleMarker)
    
    // Обновляем store
    this.store?.setCoordinates(coords)
    
    console.log(`[MarkersPlugin] Установлен маркер: ${coords.lat}, ${coords.lng}`)
  }

  /**
   * Добавление маркера в режиме multiple
   */
  addMarker(marker: MapMarker) {
    if (this.options.mode !== 'multiple' || !this.map || !window.ymaps) return

    const placemark = new window.ymaps.Placemark(
      [marker.coordinates.lat, marker.coordinates.lng],
      {
        balloonContentHeader: marker.title,
        balloonContentBody: marker.description,
        hintContent: marker.title,
        markerId: marker.id
      },
      {
        preset: marker.preset || this.options.preset,
        iconColor: marker.color || MARKER_COLORS.DEFAULT
      }
    )

    // Обработчик клика
    placemark.events.add('click', () => {
      this.store?.emit('marker-click', marker)
    })

    this.markers.set(marker.id, placemark)
    this.map.geoObjects.add(placemark)
    
    console.log(`[MarkersPlugin] Добавлен маркер: ${marker.id}`)
  }

  /**
   * Удаление маркера
   */
  removeMarker(id: string) {
    const placemark = this.markers.get(id)
    if (placemark && this.map) {
      this.map.geoObjects.remove(placemark)
      this.markers.delete(id)
      console.log(`[MarkersPlugin] Удален маркер: ${id}`)
    }
  }

  /**
   * Обновление всех маркеров
   */
  updateMarkers(markers: MapMarker[]) {
    // Очищаем старые маркеры
    this.clearMarkers()
    
    // Добавляем новые
    markers.forEach(marker => this.addMarker(marker))
    
    console.log(`[MarkersPlugin] Обновлено маркеров: ${markers.length}`)
  }

  /**
   * Очистка всех маркеров
   */
  clearMarkers() {
    if (!this.map) return

    // Удаляем одиночный маркер
    if (this.singleMarker) {
      this.map.geoObjects.remove(this.singleMarker)
      this.singleMarker = null
    }

    // Удаляем множественные маркеры
    for (const placemark of this.markers.values()) {
      this.map.geoObjects.remove(placemark)
    }
    this.markers.clear()
    
    console.log('[MarkersPlugin] Все маркеры очищены')
  }

  /**
   * Уничтожение плагина
   */
  destroy() {
    this.clearMarkers()
    this.map = null
    this.store = null
  }
}