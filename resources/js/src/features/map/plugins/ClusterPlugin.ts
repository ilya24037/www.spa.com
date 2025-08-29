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
  groupByCoordinates?: boolean
  clusterDisableClickZoom?: boolean
  clusterHideIconOnBalloonOpen?: boolean
  geoObjectHideIconOnBalloonOpen?: boolean
  clusterBalloonContentLayout?: string
  clusterBalloonPanelMaxMapArea?: number
  clusterBalloonContentLayoutWidth?: number
  clusterBalloonContentLayoutHeight?: number
  clusterBalloonPagerSize?: number
  clusterBalloonPagerVisible?: boolean
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
      groupByCoordinates: false,
      clusterDisableClickZoom: false,
      clusterHideIconOnBalloonOpen: false,
      geoObjectHideIconOnBalloonOpen: false,
      clusterBalloonContentLayout: 'cluster#balloonCarousel',
      clusterBalloonPanelMaxMapArea: 0,
      clusterBalloonContentLayoutWidth: 200,
      clusterBalloonContentLayoutHeight: 130,
      clusterBalloonPagerSize: 5,
      clusterBalloonPagerVisible: true,
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

    // Создаем кластеризатор с полными опциями
    this.clusterer = new window.ymaps.Clusterer({
      preset: this.options.preset,
      groupByCoordinates: this.options.groupByCoordinates,
      clusterDisableClickZoom: this.options.clusterDisableClickZoom,
      clusterHideIconOnBalloonOpen: this.options.clusterHideIconOnBalloonOpen,
      geoObjectHideIconOnBalloonOpen: this.options.geoObjectHideIconOnBalloonOpen,
      clusterBalloonContentLayout: this.options.clusterBalloonContentLayout,
      clusterBalloonPanelMaxMapArea: this.options.clusterBalloonPanelMaxMapArea,
      clusterBalloonContentLayoutWidth: this.options.clusterBalloonContentLayoutWidth,
      clusterBalloonContentLayoutHeight: this.options.clusterBalloonContentLayoutHeight,
      clusterBalloonPagerSize: this.options.clusterBalloonPagerSize,
      clusterBalloonPagerVisible: this.options.clusterBalloonPagerVisible,
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
    store.on('marker-update', this.handleMarkerUpdate.bind(this))
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
   * Обновление маркера в кластере
   */
  private handleMarkerUpdate(marker: MapMarker) {
    // Для обновления маркера в кластере нужно его удалить и добавить заново
    const geoObject = this.geoObjects.find(obj => 
      obj.properties.get('markerId') === marker.id
    )
    
    if (geoObject && this.clusterer) {
      // Удаляем старый
      this.clusterer.remove(geoObject)
      const index = this.geoObjects.indexOf(geoObject)
      if (index > -1) {
        this.geoObjects.splice(index, 1)
      }
      
      // Добавляем обновленный
      this.handleMarkerAdd(marker)
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
   * Обновление опций кластеризатора
   */
  updateOptions(options: Partial<ClusterOptions>) {
    this.options = { ...this.options, ...options }
    
    if (this.clusterer) {
      // Применяем новые опции к существующему кластеризатору
      Object.entries(options).forEach(([key, value]) => {
        if (this.clusterer.options.get(key) !== undefined) {
          this.clusterer.options.set(key, value)
        }
      })
    }
  }

  /**
   * Получение всех кластеров
   */
  getClusters() {
    if (!this.clusterer) return []
    return this.clusterer.getClusters()
  }

  /**
   * Перерисовка кластеров
   */
  refresh() {
    if (this.clusterer) {
      // Принудительная перерисовка кластеров
      const objects = [...this.geoObjects]
      this.clusterer.removeAll()
      this.clusterer.add(objects)
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