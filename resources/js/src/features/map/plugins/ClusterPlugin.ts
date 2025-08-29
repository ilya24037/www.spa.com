/**
 * Плагин кластеризации маркеров
 * Группирует близкие маркеры в кластеры при уменьшении масштаба
 * Размер: 60 строк
 */
import type { MapPlugin, MapMarker, MapStore } from '../core/MapStore'

export class ClusterPlugin implements MapPlugin {
  name = 'cluster'
  private clusterer: any = null
  private map: any = null
  private store: MapStore | null = null

  constructor(private options: any = {}) {
    this.options = {
      preset: 'islands#invertedVioletClusterIcons',
      clusterDisableClickZoom: true,
      clusterOpenBalloonOnClick: false,
      gridSize: 64,
      ...options
    }
  }

  async install(map: any, store: MapStore) {
    this.map = map
    this.store = store

    if (!window.ymaps) return

    // Создаем кластеризатор
    this.clusterer = new window.ymaps.Clusterer(this.options)

    // Обработчик клика по кластеру
    this.clusterer.events.add('click', (e: any) => {
      const cluster = e.get('target')
      const markers = cluster.getGeoObjects()
      
      // Эмитируем событие через store
      if (this.store) {
        this.store.emit('cluster-click', markers)
      }
    })

    // Добавляем на карту
    map.geoObjects.add(this.clusterer)

    // Следим за изменением маркеров
    if (store) {
      store.on('markers-change', this.updateMarkers.bind(this))
    }
  }

  updateMarkers(markers: MapMarker[]) {
    if (!this.clusterer || !window.ymaps) return

    // Очищаем старые маркеры
    this.clusterer.removeAll()

    // Добавляем новые
    const placemarks = markers.map(marker => {
      const placemark = new window.ymaps.Placemark(
        [marker.coordinates.lat, marker.coordinates.lng],
        {
          balloonContentHeader: marker.title,
          balloonContentBody: marker.description,
          markerId: marker.id
        },
        {
          preset: marker.icon || 'islands#blueIcon',
          iconColor: marker.color || '#0095b6'
        }
      )

      // Обработчик клика по маркеру
      placemark.events.add('click', () => {
        if (this.store) {
          this.store.emit('marker-click', marker)
        }
      })

      return placemark
    })

    this.clusterer.add(placemarks)
  }

  destroy() {
    if (this.clusterer && this.map) {
      this.map.geoObjects.remove(this.clusterer)
      this.clusterer = null
    }
  }
}