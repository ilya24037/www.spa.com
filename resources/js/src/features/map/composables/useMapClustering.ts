import { ref, Ref } from 'vue'
import type { MapMarker, ClustererOptions } from '../types'
import { CLUSTER_GRID_SIZE, MIN_MARKERS_FOR_CLUSTER, MARKER_PRESETS } from '../lib/mapConstants'

export function useMapClustering(map: Ref<any>) {
  const clusterer = ref<any>(null)
  const isClusteringEnabled = ref(true)
  const placemarks = ref<any[]>([])

  /**
   * Создание кластеризатора
   */
  const createClusterer = (options?: Partial<ClustererOptions>) => {
    if (!window.ymaps || !map.value) return null

    const defaultOptions: ClustererOptions = {
      preset: 'islands#invertedBlueClusterIcons',
      groupByCoordinates: false,
      clusterDisableClickZoom: false,
      clusterHideIconOnBalloonOpen: false,
      geoObjectHideIconOnBalloonOpen: false,
      clusterBalloonContentLayout: 'cluster#balloonCarousel',
      clusterBalloonPanelMaxMapArea: 0,
      clusterBalloonContentLayoutWidth: 300,
      clusterBalloonContentLayoutHeight: 200,
      clusterBalloonPagerSize: 5,
      clusterNumbers: [MIN_MARKERS_FOR_CLUSTER],
      clusterMaxZoom: 16,
      gridSize: CLUSTER_GRID_SIZE
    }

    const finalOptions = { ...defaultOptions, ...options }

    // Создаем кластеризатор
    clusterer.value = new window.ymaps.Clusterer(finalOptions)

    // Добавляем кастомный шаблон для балуна кластера
    if (window.ymaps.templateLayoutFactory) {
      clusterer.value.options.set(
        'clusterBalloonItemContentLayout',
        window.ymaps.templateLayoutFactory.createClass(
          '<div class="cluster-balloon-item">' +
          '<h4 class="cluster-balloon-item__title">{{ properties.balloonContentHeader|raw }}</h4>' +
          '<div class="cluster-balloon-item__desc">{{ properties.balloonContentBody|raw }}</div>' +
          '</div>'
        )
      )
    }

    // Добавляем кастомные иконки кластера
    clusterer.value.options.set('clusterIcons', [
      {
        href: createClusterIcon(),
        size: [40, 40],
        offset: [-20, -20]
      }
    ])

    return clusterer.value
  }

  /**
   * Создание SVG иконки для кластера
   */
  const createClusterIcon = (): string => {
    const svg = `
      <svg width="40" height="40" xmlns="http://www.w3.org/2000/svg">
        <circle cx="20" cy="20" r="18" fill="#007BFF" opacity="0.9"/>
        <text x="20" y="26" text-anchor="middle" fill="white" font-size="14" font-weight="bold">
          {{ properties.geoObjects.length }}
        </text>
      </svg>
    `
    return 'data:image/svg+xml;base64,' + btoa(svg)
  }

  /**
   * Добавление маркеров в кластеризатор
   */
  const addMarkersToCluster = (markers: MapMarker[], onMarkerClick?: (marker: MapMarker) => void) => {
    if (!window.ymaps || !map.value) return

    // Очищаем старые метки
    clearMarkers()

    // Фильтруем валидные маркеры
    const validMarkers = markers.filter(marker =>
      marker &&
      typeof marker.lat === 'number' &&
      typeof marker.lng === 'number' &&
      !isNaN(marker.lat) &&
      !isNaN(marker.lng) &&
      marker.lat >= -90 && marker.lat <= 90 &&
      marker.lng >= -180 && marker.lng <= 180
    )

    if (validMarkers.length === 0) return

    // Создаем новые метки
    const newPlacemarks: any[] = []

    validMarkers.forEach(marker => {
      const placemark = new window.ymaps.Placemark(
        [marker.lat, marker.lng],
        {
          balloonContentHeader: marker.title || '',
          balloonContentBody: marker.description || '',
          markerData: marker,
          clusterCaption: marker.title // Для отображения в списке кластера
        },
        {
          preset: marker.icon || MARKER_PRESETS.blue,
          draggable: false
        }
      )

      // Добавляем обработчик клика
      if (onMarkerClick) {
        placemark.events.add('click', () => {
          onMarkerClick(marker)
        })
      }

      newPlacemarks.push(placemark)
      placemarks.value.push(placemark)
    })

    // Добавляем метки в кластеризатор или напрямую на карту
    if (isClusteringEnabled.value && validMarkers.length > MIN_MARKERS_FOR_CLUSTER) {
      if (!clusterer.value) {
        createClusterer()
      }
      clusterer.value.add(newPlacemarks)
      map.value.geoObjects.add(clusterer.value)
    } else {
      // Без кластеризации - добавляем метки напрямую
      newPlacemarks.forEach(placemark => {
        map.value.geoObjects.add(placemark)
      })
    }

    // Автоматическое позиционирование для показа всех маркеров
    if (validMarkers.length > 1) {
      fitMapToMarkers()
    } else if (validMarkers.length === 1) {
      map.value.setCenter([validMarkers[0].lat, validMarkers[0].lng], 15)
    }
  }

  /**
   * Позиционирование карты для показа всех маркеров
   */
  const fitMapToMarkers = () => {
    if (!map.value) return

    if (clusterer.value && isClusteringEnabled.value) {
      map.value.setBounds(clusterer.value.getBounds(), {
        checkZoomRange: true,
        zoomMargin: 40
      })
    } else if (placemarks.value.length > 0) {
      // Вычисляем границы вручную
      const bounds = placemarks.value.reduce((acc, placemark) => {
        const coords = placemark.geometry.getCoordinates()
        return [
          [Math.min(acc[0][0], coords[0]), Math.min(acc[0][1], coords[1])],
          [Math.max(acc[1][0], coords[0]), Math.max(acc[1][1], coords[1])]
        ]
      }, [
        placemarks.value[0].geometry.getCoordinates(),
        placemarks.value[0].geometry.getCoordinates()
      ])

      map.value.setBounds(bounds, {
        checkZoomRange: true,
        zoomMargin: 40
      })
    }
  }

  /**
   * Очистка всех маркеров
   */
  const clearMarkers = () => {
    if (clusterer.value) {
      clusterer.value.removeAll()
      map.value.geoObjects.remove(clusterer.value)
      clusterer.value = null
    }

    placemarks.value.forEach(placemark => {
      map.value.geoObjects.remove(placemark)
    })
    placemarks.value = []
  }

  /**
   * Переключение кластеризации
   */
  const toggleClustering = () => {
    isClusteringEnabled.value = !isClusteringEnabled.value

    // Пересоздаем маркеры с новыми настройками
    if (placemarks.value.length > 0) {
      const tempMarkers = [...placemarks.value]
      clearMarkers()
      // Здесь нужно восстановить маркеры из tempMarkers
      // но нам нужны оригинальные данные MapMarker
    }
  }

  /**
   * Обработчик клика по кластеру
   */
  const handleClusterClick = (e: any): MapMarker[] => {
    const cluster = e.get('target')
    if (cluster.getGeoObjects) {
      const geoObjects = cluster.getGeoObjects()
      return geoObjects.map((obj: any) => obj.properties.get('markerData'))
    }
    return []
  }

  return {
    clusterer,
    isClusteringEnabled,
    placemarks,
    createClusterer,
    addMarkersToCluster,
    clearMarkers,
    toggleClustering,
    fitMapToMarkers,
    handleClusterClick
  }
}