/**
 * MarkersPlugin - Плагин управления маркерами
 * Отвечает за добавление, удаление и управление маркерами
 * Поддерживает режимы single и multiple
 * Размер: ~70 строк (согласно плану)
 */

import type { MapPlugin, MapStore, MapMarker, Coordinates } from '../core/MapStore'

export interface MarkersOptions {
  mode?: 'single' | 'multiple'
  draggable?: boolean
  preset?: string
}

export class MarkersPlugin implements MapPlugin {
  name = 'markers'
  private map: any = null
  private store: MapStore | null = null
  private markers: Map<string | number, any> = new Map()
  private singleMarker: any = null

  constructor(private options: MarkersOptions = {}) {
    this.options = {
      mode: 'single',
      draggable: true,
      preset: 'islands#blueIcon',
      ...options
    }
  }

  /**
   * Установка плагина
   */
  async install(map: any, store: MapStore) {
    this.map = map
    this.store = store

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
    store.on('marker-add', this.addMarker.bind(this))
    store.on('marker-remove', this.removeMarker.bind(this))
    store.on('markers-clear', this.clearMarkers.bind(this))
    
    console.log(`[MarkersPlugin] Installed in ${this.options.mode} mode`)
  }

  /**
   * Установка одиночного маркера
   */
  setSingleMarker(coords: Coordinates) {
    if (!window.ymaps || !this.map) return

    // Удаляем старый маркер
    if (this.singleMarker) {
      this.map.geoObjects.remove(this.singleMarker)
    }

    // Создаем новый
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

    this.map.geoObjects.add(this.singleMarker)
    
    // Обновляем store
    this.store?.setCoordinates(coords)
  }

  /**
   * Добавление маркера в режиме multiple
   */
  addMarker(marker: MapMarker) {
    if (this.options.mode !== 'multiple' || !window.ymaps || !this.map) return

    const placemark = new window.ymaps.Placemark(
      [marker.coordinates.lat, marker.coordinates.lng],
      {
        balloonContentHeader: marker.title,
        balloonContentBody: marker.description,
        hintContent: marker.title
      },
      {
        preset: marker.icon || this.options.preset,
        iconColor: marker.color
      }
    )

    // Обработчик клика
    placemark.events.add('click', () => {
      this.store?.emit('marker-click', marker)
    })

    this.markers.set(marker.id, placemark)
    this.map.geoObjects.add(placemark)
  }

  /**
   * Удаление маркера
   */
  removeMarker(markerId: string | number | MapMarker) {
    const id = typeof markerId === 'object' ? markerId.id : markerId
    const placemark = this.markers.get(id)
    if (placemark && this.map) {
      this.map.geoObjects.remove(placemark)
      this.markers.delete(id)
    }
  }

  /**
   * Очистка всех маркеров
   */
  clearMarkers() {
    if (this.singleMarker && this.map) {
      this.map.geoObjects.remove(this.singleMarker)
      this.singleMarker = null
    }

    for (const placemark of this.markers.values()) {
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
    this.clearMarkers()
    this.map = null
    this.store = null
    console.log('[MarkersPlugin] Destroyed')
  }
}