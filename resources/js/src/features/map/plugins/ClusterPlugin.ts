/**
 * ClusterPlugin - Плагин кластеризации маркеров
 * Группирует близкие маркеры в кластеры при уменьшении масштаба
 */

import type { MapPlugin, MapMarker, MapStore } from '../types'

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
      groupByCoordinates: false,
      clusterBalloonContentLayout: 'cluster#balloonCarousel',
      ...options
    }
  }

  /**
   * Установка плагина
   */
  async install(map: any, store: MapStore) {
    if (!window.ymaps) {
      console.warn('[ClusterPlugin] Yandex Maps API не загружен')
      return
    }

    this.map = map
    this.store = store

    console.log('[ClusterPlugin] Установлен')

    // Создаем кластеризатор
    this.clusterer = new window.ymaps.Clusterer(this.options)

    // Обработчик клика по кластеру
    this.clusterer.events.add('click', (e: any) => {
      const cluster = e.get('target')
      
      // Если это кластер
      if (cluster.getGeoObjects) {
        const geoObjects = cluster.getGeoObjects()
        const markers: MapMarker[] = []
        
        // Собираем данные маркеров
        geoObjects.forEach((obj: any) => {
          const markerId = obj.properties.get('markerId')
          const markerData = obj.properties.get('markerData')
          if (markerId) {
            markers.push({
              id: markerId,
              coordinates: {
                lat: obj.geometry.getCoordinates()[0],
                lng: obj.geometry.getCoordinates()[1]
              },
              title: obj.properties.get('balloonContentHeader'),
              description: obj.properties.get('balloonContentBody'),
              data: markerData
            })
          }
        })
        
        // Эмитируем событие
        if (this.store && markers.length > 0) {
          this.store.emit('cluster-click', markers)
          console.log(`[ClusterPlugin] Клик по кластеру: ${markers.length} маркеров`)
        }
      }
    })

    // Добавляем на карту
    map.geoObjects.add(this.clusterer)

    // Слушаем изменение маркеров
    store.on('markers-change', this.updateMarkers.bind(this))
    store.on('cluster-update', this.updateMarkers.bind(this))
  }

  /**
   * Обновление маркеров в кластеризаторе
   */
  updateMarkers(markers: MapMarker[]) {
    if (!this.clusterer || !window.ymaps) return

    console.log(`[ClusterPlugin] Обновление кластеров: ${markers.length} маркеров`)

    // Очищаем старые маркеры
    this.clusterer.removeAll()

    // Создаем placemarks для кластеризатора
    const placemarks = markers.map(marker => {
      const placemark = new window.ymaps.Placemark(
        [marker.coordinates.lat, marker.coordinates.lng],
        {
          balloonContentHeader: marker.title || '',
          balloonContentBody: marker.description || '',
          hintContent: marker.title || '',
          markerId: marker.id,
          markerData: marker.data
        },
        {
          preset: marker.preset || 'islands#blueIcon',
          iconColor: marker.color || '#0095b6'
        }
      )

      // Обработчик клика по отдельному маркеру
      placemark.events.add('click', (e: any) => {
        // Останавливаем всплытие события к кластеру
        e.stopPropagation()
        
        if (this.store) {
          this.store.emit('marker-click', marker)
          console.log(`[ClusterPlugin] Клик по маркеру: ${marker.id}`)
        }
      })

      return placemark
    })

    // Добавляем в кластеризатор
    this.clusterer.add(placemarks)
    
    // Обновляем границы карты для показа всех маркеров
    if (markers.length > 0 && this.map) {
      this.map.setBounds(this.clusterer.getBounds(), {
        checkZoomRange: true,
        duration: 300
      })
    }
  }

  /**
   * Очистка кластеров
   */
  clearClusters() {
    if (this.clusterer) {
      this.clusterer.removeAll()
      console.log('[ClusterPlugin] Кластеры очищены')
    }
  }

  /**
   * Уничтожение плагина
   */
  destroy() {
    if (this.clusterer && this.map) {
      this.clearClusters()
      this.map.geoObjects.remove(this.clusterer)
      this.clusterer = null
    }
    this.map = null
    this.store = null
  }
}