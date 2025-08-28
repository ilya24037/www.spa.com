<template>
  <div class="map-markers-manager">
    <!-- Этот компонент не рендерит UI, только управляет маркерами -->
  </div>
</template>

<script setup lang="ts">
import { watch, onMounted, onUnmounted } from 'vue'
import type { MapMarker } from '../../types'

interface Props {
  map: any | null
  markers: MapMarker[]
  clusterize?: boolean
  onMarkerClick?: (marker: MapMarker) => void
  onClusterClick?: (markers: MapMarker[]) => void
}

const props = withDefaults(defineProps<Props>(), {
  markers: () => [],
  clusterize: false
})

let clusterer: any = null
let placemarks: any[] = []

// Создание маркера
const createPlacemark = (marker: MapMarker) => {
  if (!window.ymaps || !props.map) return null
  
  const placemark = new window.ymaps.Placemark(
    [marker.lat, marker.lng],
    {
      balloonContentHeader: marker.title || '',
      balloonContentBody: marker.description || '',
      hintContent: marker.title || ''
    },
    {
      preset: marker.icon || 'islands#blueIcon',
      draggable: false
    }
  )
  
  if (props.onMarkerClick) {
    placemark.events.add('click', () => {
      props.onMarkerClick?.(marker)
    })
  }
  
  return placemark
}

// Очистка маркеров
const clearMarkers = () => {
  if (!props.map) return
  
  // Удаляем кластеризатор
  if (clusterer) {
    props.map.geoObjects.remove(clusterer)
    clusterer = null
  }
  
  // Удаляем отдельные маркеры
  placemarks.forEach(placemark => {
    props.map.geoObjects.remove(placemark)
  })
  placemarks = []
}

// Обновление маркеров
const updateMarkers = () => {
  if (!props.map || !window.ymaps) return
  
  clearMarkers()
  
  if (props.markers.length === 0) return
  
  // Создаем маркеры
  const newPlacemarks = props.markers
    .map(marker => createPlacemark(marker))
    .filter(Boolean)
  
  if (props.clusterize && newPlacemarks.length > 1) {
    // Используем кластеризацию
    clusterer = new window.ymaps.Clusterer({
      preset: 'islands#invertedBlueClusterIcons',
      groupByCoordinates: false,
      clusterDisableClickZoom: false,
      clusterBalloonContentLayout: 'cluster#balloonCarousel',
      clusterBalloonPanelMaxMapArea: 0,
      clusterBalloonContentLayoutWidth: 300,
      clusterBalloonContentLayoutHeight: 200,
      clusterBalloonPagerSize: 5
    })
    
    clusterer.add(newPlacemarks)
    props.map.geoObjects.add(clusterer)
    
    // Обработка клика по кластеру
    if (props.onClusterClick) {
      clusterer.events.add('click', (e: any) => {
        const cluster = e.get('target')
        if (cluster.getGeoObjects) {
          const geoObjects = cluster.getGeoObjects()
          const clusterMarkers = geoObjects.map((obj: any) => {
            const coords = obj.geometry.getCoordinates()
            return props.markers.find(m => 
              m.lat === coords[0] && m.lng === coords[1]
            )
          }).filter(Boolean)
          
          props.onClusterClick?.(clusterMarkers)
        }
      })
    }
  } else {
    // Добавляем маркеры без кластеризации
    newPlacemarks.forEach(placemark => {
      props.map.geoObjects.add(placemark)
    })
    placemarks = newPlacemarks
  }
}

// Watchers
watch(() => props.map, (newMap) => {
  if (newMap) {
    updateMarkers()
  }
})

watch(() => props.markers, () => {
  updateMarkers()
}, { deep: true })

watch(() => props.clusterize, () => {
  updateMarkers()
})

// Lifecycle
onMounted(() => {
  if (props.map) {
    updateMarkers()
  }
})

onUnmounted(() => {
  clearMarkers()
})

// Экспорт методов
defineExpose({
  updateMarkers,
  clearMarkers
})
</script>