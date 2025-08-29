/**
 * ClusterPlugin - Плагин кластеризации маркеров
 * Группирует близкие маркеры в кластеры
 */

import type { MapPlugin, MapStore, MapMarker } from '../core/MapStore'

export interface ClusterOptions {
  gridSize?: number
  minClusterSize?: number
  maxZoom?: number
  preset?: string
}

export class ClusterPlugin implements MapPlugin {
  name = 'cluster'
  private map: any = null
  private store: MapStore | null = null
  private clusterer: any = null
  private geoObjects: any[] = []

  constructor(private options: ClusterOptions = {}) {
    this.options = {
      gridSize: 64,
      minClusterSize: 2,
      maxZoom: 18,
      preset: 'islands#invertedRedClusterIcons',
      ...options
    }
  }

  /**
   * Установка плагина
   */
  async install(map: any, store: MapStore) {
    this.map = map
    this.store = store

    if (!window.ymaps) {
      console.error('[ClusterPlugin] Yandex Maps API not loaded')
      return
    }

    // Создаем кластеризатор
    this.clusterer = new window.ymaps.Clusterer({
      preset: this.options.preset,
      groupByCoordinates: false,
      clusterDisableClickZoom: false,
      clusterHideIconOnBalloonOpen: false,
      geoObjectHideIconOnBalloonOpen: false,
      gridSize: this.options.gridSize,
      minClusterSize: this.options.minClusterSize,
      maxZoom: this.options.maxZoom
    })

    // Обработчик клика по кластеру
    this.clusterer.events.add('click', (e: any) => {
      const target = e.get('target')
      if (target.getGeoObjects) {
        const geoObjects = target.getGeoObjects()
        const markers = geoObjects.map((obj: any) => obj.markerData).filter(Boolean)
        store.emit('cluster-click', markers)
      }
    })

    // Добавляем кластеризатор на карту
    map.geoObjects.add(this.clusterer)

    // Подписываемся на события маркеров
    store.on('marker-add', this.handleMarkerAdd.bind(this))
    store.on('marker-remove', this.handleMarkerRemove.bind(this))
    store.on('markers-clear', this.handleClearMarkers.bind(this))

    console.log('[ClusterPlugin] Installed with options:', this.options)
  }

  /**
   * Добавление маркера в кластер
   */
  private handleMarkerAdd(marker: MapMarker) {
    if (!this.clusterer || !window.ymaps) return

    const placemark = new window.ymaps.Placemark(
      [marker.coordinates.lat, marker.coordinates.lng],
      {
        balloonContentHeader: marker.title || '',
        balloonContentBody: marker.description || '',
        hintContent: marker.title || '',
        markerId: marker.id
      },
      {
        preset: marker.icon || 'islands#redIcon'
      }
    )

    // Сохраняем ссылку на данные маркера
    placemark.markerData = marker

    // Обработчик клика по маркеру
    placemark.events.add('click', () => {
      this.store?.emit('marker-click', marker)
    })

    this.clusterer.add(placemark)
    this.geoObjects.push(placemark)
  }

  /**
   * Удаление маркера из кластера
   */
  private handleMarkerRemove(marker: MapMarker) {
    const geoObject = this.geoObjects.find(obj => 
      obj.properties.get('markerId') === marker.id
    )
    
    if (geoObject && this.clusterer) {
      this.clusterer.remove(geoObject)
      const index = this.geoObjects.indexOf(geoObject)
      if (index > -1) {
        this.geoObjects.splice(index, 1)
      }
    }
  }

  /**
   * Очистка всех маркеров
   */
  private handleClearMarkers() {
    if (this.clusterer) {
      this.clusterer.removeAll()
      this.geoObjects = []
    }
  }

  /**
   * Уничтожение плагина
   */
  destroy() {
    if (this.clusterer && this.map) {
      this.clusterer.removeAll()
      this.map.geoObjects.remove(this.clusterer)
    }
    
    this.clusterer = null
    this.geoObjects = []
    this.map = null
    this.store = null
    
    console.log('[ClusterPlugin] Destroyed')
  }
}