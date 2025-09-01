import type { MapPlugin, MapStore, MapMarker, Coordinates } from '../core/MapStore'
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
      preset: 'islands#blueIcon',
      ...options
    }
  }

  install(map: any, store: MapStore) {
    this.map = map
    this.store = store
    
    if (this.options.mode === 'single') {
      // Обработчик клика по карте
      map.events.add('click', (e: any) => {
        const coords = e.get('coords')
        this.setSingleMarker({ lat: coords[0], lng: coords[1] })
      })
      
      // Обработчик изменения координат через store (например, при поиске адреса)
      store.on('coordinates-change', (coords: Coordinates) => {
        console.log('[MarkersPlugin] Получены новые координаты из store:', coords)
        if (coords) {
          this.setSingleMarker(coords)
        }
      })
    }
    
    store.on('markers-add', this.addMarker.bind(this))
    store.on('markers-remove', this.removeMarker.bind(this))
    store.on('markers-clear', this.clearMarkers.bind(this))
  }

  setSingleMarker(coords: Coordinates) {
    console.log('[MarkersPlugin] Установка маркера на координаты:', coords)
    
    // Удаляем старый маркер если есть
    if (this.singleMarker) {
      this.map.geoObjects.remove(this.singleMarker)
      this.singleMarker = null
    }
    
    // Создаем новый маркер
    this.singleMarker = new ymaps.Placemark(
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
    
    // Добавляем обработчик перетаскивания
    if (this.options.draggable) {
      this.singleMarker.events.add('dragend', () => {
        const newCoords = this.singleMarker.geometry.getCoordinates()
        const coordinates = { lat: newCoords[0], lng: newCoords[1] }
        this.store?.setCoordinates(coordinates)
        this.store?.emit('marker-moved', coordinates)
      })
    }
    
    // Добавляем маркер на карту
    this.map.geoObjects.add(this.singleMarker)
    
    // Форсируем перерисовку карты
    if (this.map.container) {
      this.map.container.fitToViewport()
    }
    
    console.log('[MarkersPlugin] Маркер добавлен на карту')
    
    // Обновляем координаты в store (но не эмитим событие чтобы избежать цикла)
    if (this.store) {
      this.store.coordinates = coords
    }
  }

  addMarker(marker: MapMarker) {
    if (this.options.mode !== 'multiple') return
    const placemark = new ymaps.Placemark(
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
    placemark.events.add('click', () => {
      this.store?.emit('marker-click', marker)
    })
    this.markers.set(marker.id, placemark)
    this.map.geoObjects.add(placemark)
  }

  removeMarker(id: string) {
    const placemark = this.markers.get(id)
    if (placemark) {
      this.map.geoObjects.remove(placemark)
      this.markers.delete(id)
    }
  }

  clearMarkers() {
    if (this.singleMarker) {
      this.map.geoObjects.remove(this.singleMarker)
      this.singleMarker = null
    }
    for (const placemark of this.markers.values()) {
      this.map.geoObjects.remove(placemark)
    }
    this.markers.clear()
  }

  destroy() {
    this.clearMarkers()
  }
}