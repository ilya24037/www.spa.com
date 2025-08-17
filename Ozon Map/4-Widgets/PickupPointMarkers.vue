<template>
  <div class="pickup-points-widget">
    <!-- Контролы фильтрации -->
    <div v-if="showControls" class="pickup-controls">
      <div class="filter-buttons">
        <button
          v-for="filter in filters"
          :key="filter.type"
          class="filter-button"
          :class="{ active: activeFilters.includes(filter.type) }"
          @click="toggleFilter(filter.type)"
        >
          <span class="filter-icon" v-html="filter.icon"></span>
          <span class="filter-label">{{ filter.label }}</span>
          <span v-if="filter.count" class="filter-count">{{ filter.count }}</span>
        </button>
      </div>
      
      <div class="view-controls">
        <button
          class="view-button"
          :class="{ active: viewMode === 'map' }"
          @click="setViewMode('map')"
          title="Показать на карте"
        >
          <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
            <path d="M8 0a8 8 0 1 1 0 16A8 8 0 0 1 8 0zM1.5 8a6.5 6.5 0 1 0 13 0 6.5 6.5 0 0 0-13 0z"/>
          </svg>
        </button>
        <button
          class="view-button"
          :class="{ active: viewMode === 'list' }"
          @click="setViewMode('list')"
          title="Показать списком"
        >
          <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
            <path d="M1 3h14v2H1V3zm0 4h14v2H1V7zm0 4h14v2H1v-2z"/>
          </svg>
        </button>
      </div>
    </div>
    
    <!-- Счетчик пунктов -->
    <div v-if="showCounter" class="pickup-counter">
      <span>Найдено {{ filteredPoints.length }} пунктов выдачи</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import type { Map as MapLibreMap, LngLatBounds } from 'maplibre-gl'

export interface PickupPoint {
  id: string
  name: string
  address: string
  coordinates: [number, number]
  type: 'ozon' | 'postamr' | 'pickup' | 'locker'
  schedule: {
    [key: string]: string // day: hours
  }
  features: string[]
  rating?: number
  distance?: number
  isOpen?: boolean
  cost?: number
  deliveryTime?: string
  photos?: string[]
}

export interface PickupFilter {
  type: string
  label: string
  icon: string
  count?: number
}

export interface PickupPointMarkersProps {
  /** Instance карты MapLibre */
  map?: MapLibreMap
  /** Массив пунктов выдачи */
  points?: PickupPoint[]
  /** Показать элементы управления */
  showControls?: boolean
  /** Показать счетчик */
  showCounter?: boolean
  /** Активные фильтры */
  activeFilters?: string[]
  /** Режим отображения */
  viewMode?: 'map' | 'list'
  /** Максимальное расстояние для отображения */
  maxDistance?: number
  /** Центр для расчета расстояния */
  center?: [number, number]
  /** Кластеризация маркеров */
  enableClustering?: boolean
  /** Минимальный зум для показа маркеров */
  minZoom?: number
}

export interface PickupPointMarkersEmits {
  (e: 'point-selected', point: PickupPoint): void
  (e: 'filter-changed', filters: string[]): void
  (e: 'view-mode-changed', mode: 'map' | 'list'): void
  (e: 'bounds-changed', bounds: LngLatBounds): void
}

const props = withDefaults(defineProps<PickupPointMarkersProps>(), {
  points: () => [],
  showControls: true,
  showCounter: true,
  activeFilters: () => ['ozon', 'postamr', 'pickup', 'locker'],
  viewMode: 'map',
  maxDistance: 50000, // 50km
  enableClustering: true,
  minZoom: 10
})

const emit = defineEmits<PickupPointMarkersEmits>()

// State
const activeFilters = ref([...props.activeFilters])
const viewMode = ref(props.viewMode)
const selectedPoint = ref<PickupPoint | null>(null)
const hoveredPoint = ref<PickupPoint | null>(null)

// Computed
const filters = computed((): PickupFilter[] => {
  const filterCounts = props.points.reduce((counts, point) => {
    counts[point.type] = (counts[point.type] || 0) + 1
    return counts
  }, {} as Record<string, number>)

  return [
    {
      type: 'ozon',
      label: 'Ozon',
      icon: '<svg width="12" height="12" viewBox="0 0 12 12" fill="#005bff"><circle cx="6" cy="6" r="6"/></svg>',
      count: filterCounts.ozon || 0
    },
    {
      type: 'postamr',
      label: 'Почта России',
      icon: '<svg width="12" height="12" viewBox="0 0 12 12" fill="#0066cc"><rect width="12" height="12" rx="2"/></svg>',
      count: filterCounts.postamr || 0
    },
    {
      type: 'pickup',
      label: 'ПВЗ',
      icon: '<svg width="12" height="12" viewBox="0 0 12 12" fill="#ff6b35"><polygon points="6,1 11,11 1,11"/></svg>',
      count: filterCounts.pickup || 0
    },
    {
      type: 'locker',
      label: 'Постаматы',
      icon: '<svg width="12" height="12" viewBox="0 0 12 12" fill="#00a651"><rect width="12" height="12" rx="1"/></svg>',
      count: filterCounts.locker || 0
    }
  ]
})

const filteredPoints = computed(() => {
  let filtered = props.points.filter(point => 
    activeFilters.value.includes(point.type)
  )
  
  // Filter by distance if center is provided
  if (props.center && props.maxDistance) {
    filtered = filtered.filter(point => {
      const distance = calculateDistance(props.center!, point.coordinates)
      point.distance = distance
      return distance <= props.maxDistance!
    })
  }
  
  // Sort by distance
  if (props.center) {
    filtered.sort((a, b) => (a.distance || 0) - (b.distance || 0))
  }
  
  return filtered
})

// Methods
const toggleFilter = (type: string) => {
  const index = activeFilters.value.indexOf(type)
  if (index > -1) {
    activeFilters.value.splice(index, 1)
  } else {
    activeFilters.value.push(type)
  }
  
  emit('filter-changed', [...activeFilters.value])
  updateMapMarkers()
}

const setViewMode = (mode: 'map' | 'list') => {
  viewMode.value = mode
  emit('view-mode-changed', mode)
}

const selectPoint = (point: PickupPoint) => {
  selectedPoint.value = point
  
  // Center map on selected point
  if (props.map) {
    props.map.setCenter(point.coordinates)
    props.map.setZoom(15)
  }
  
  emit('point-selected', point)
}

const updateMapMarkers = () => {
  if (!props.map) return
  
  const sourceId = 'pickup-points'
  const clustersSourceId = 'pickup-points-clusters'
  
  // Prepare GeoJSON data
  const geojsonData = {
    type: 'FeatureCollection' as const,
    features: filteredPoints.value.map(point => ({
      type: 'Feature' as const,
      properties: {
        id: point.id,
        name: point.name,
        address: point.address,
        type: point.type,
        isOpen: point.isOpen,
        rating: point.rating,
        distance: point.distance
      },
      geometry: {
        type: 'Point' as const,
        coordinates: point.coordinates
      }
    }))
  }
  
  // Update or add source
  if (props.map.getSource(sourceId)) {
    const source = props.map.getSource(sourceId) as maplibregl.GeoJSONSource
    source.setData(geojsonData)
  } else {
    props.map.addSource(sourceId, {
      type: 'geojson',
      data: geojsonData,
      cluster: props.enableClustering,
      clusterMaxZoom: 14,
      clusterRadius: 50
    })
    
    addMapLayers()
  }
}

const addMapLayers = () => {
  if (!props.map) return
  
  const sourceId = 'pickup-points'
  
  // Clusters
  if (props.enableClustering) {
    props.map.addLayer({
      id: 'pickup-clusters',
      type: 'circle',
      source: sourceId,
      filter: ['has', 'point_count'],
      paint: {
        'circle-color': [
          'step',
          ['get', 'point_count'],
          '#51bbd6',
          100,
          '#f1f075',
          750,
          '#f28cb1'
        ],
        'circle-radius': [
          'step',
          ['get', 'point_count'],
          20,
          100,
          30,
          750,
          40
        ]
      }
    })
    
    props.map.addLayer({
      id: 'pickup-cluster-count',
      type: 'symbol',
      source: sourceId,
      filter: ['has', 'point_count'],
      layout: {
        'text-field': '{point_count_abbreviated}',
        'text-font': ['DIN Offc Pro Medium', 'Arial Unicode MS Bold'],
        'text-size': 12
      }
    })
  }
  
  // Individual points
  props.map.addLayer({
    id: 'pickup-points-symbols',
    type: 'symbol',
    source: sourceId,
    filter: props.enableClustering ? ['!', ['has', 'point_count']] : null,
    layout: {
      'icon-image': [
        'match',
        ['get', 'type'],
        'ozon', 'pickup-ozon',
        'postamr', 'pickup-postamr',
        'pickup', 'pickup-pvz',
        'locker', 'pickup-locker',
        'pickup-default'
      ],
      'icon-size': 0.8,
      'icon-anchor': 'bottom',
      'text-field': ['get', 'name'],
      'text-font': ['DIN Offc Pro Medium', 'Arial Unicode MS Bold'],
      'text-offset': [0, -2],
      'text-anchor': 'top',
      'text-size': 11,
      'text-max-width': 8
    },
    paint: {
      'text-color': '#333',
      'text-halo-color': '#fff',
      'text-halo-width': 1
    },
    minzoom: props.minZoom
  })
  
  // Click handlers
  props.map.on('click', 'pickup-points-symbols', (e) => {
    if (e.features && e.features[0]) {
      const feature = e.features[0]
      const pointId = feature.properties?.id
      const point = props.points.find(p => p.id === pointId)
      if (point) {
        selectPoint(point)
      }
    }
  })
  
  if (props.enableClustering) {
    props.map.on('click', 'pickup-clusters', (e) => {
      if (e.features && e.features[0]) {
        const features = props.map!.queryRenderedFeatures(e.point, {
          layers: ['pickup-clusters']
        })
        const clusterId = features[0].properties?.cluster_id
        const source = props.map!.getSource('pickup-points') as maplibregl.GeoJSONSource
        
        source.getClusterExpansionZoom(clusterId, (err, zoom) => {
          if (err) return
          
          props.map!.easeTo({
            center: (features[0].geometry as any).coordinates,
            zoom: zoom
          })
        })
      }
    })
  }
  
  // Hover effects
  props.map.on('mouseenter', 'pickup-points-symbols', () => {
    props.map!.getCanvas().style.cursor = 'pointer'
  })
  
  props.map.on('mouseleave', 'pickup-points-symbols', () => {
    props.map!.getCanvas().style.cursor = ''
  })
}

const calculateDistance = (coords1: [number, number], coords2: [number, number]): number => {
  const R = 6371000 // Earth's radius in meters
  const lat1Rad = coords1[1] * Math.PI / 180
  const lat2Rad = coords2[1] * Math.PI / 180
  const deltaLatRad = (coords2[1] - coords1[1]) * Math.PI / 180
  const deltaLonRad = (coords2[0] - coords1[0]) * Math.PI / 180

  const a = Math.sin(deltaLatRad / 2) * Math.sin(deltaLatRad / 2) +
    Math.cos(lat1Rad) * Math.cos(lat2Rad) *
    Math.sin(deltaLonRad / 2) * Math.sin(deltaLonRad / 2)
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a))

  return R * c
}

const loadMarkerIcons = () => {
  if (!props.map) return
  
  const icons = [
    { id: 'pickup-ozon', color: '#005bff' },
    { id: 'pickup-postamr', color: '#0066cc' },
    { id: 'pickup-pvz', color: '#ff6b35' },
    { id: 'pickup-locker', color: '#00a651' },
    { id: 'pickup-default', color: '#666' }
  ]
  
  icons.forEach(icon => {
    const svg = `
      <svg width="24" height="30" viewBox="0 0 24 30" xmlns="http://www.w3.org/2000/svg">
        <path d="M12 0C5.4 0 0 5.4 0 12c0 9 12 18 12 18s12-9 12-18c0-6.6-5.4-12-12-12z" fill="${icon.color}"/>
        <circle cx="12" cy="12" r="6" fill="white"/>
        <circle cx="12" cy="12" r="3" fill="${icon.color}"/>
      </svg>
    `
    
    const image = new Image()
    image.onload = () => {
      if (!props.map!.hasImage(icon.id)) {
        props.map!.addImage(icon.id, image)
      }
    }
    image.src = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svg)
  })
}

// Watchers
watch(() => props.points, () => {
  updateMapMarkers()
}, { deep: true })

watch(() => props.map, (newMap) => {
  if (newMap) {
    loadMarkerIcons()
    updateMapMarkers()
  }
}, { immediate: true })

watch(activeFilters, () => {
  updateMapMarkers()
}, { deep: true })

// Lifecycle
onMounted(() => {
  if (props.map) {
    loadMarkerIcons()
    updateMapMarkers()
  }
})

onUnmounted(() => {
  if (props.map) {
    // Clean up layers and sources
    const layersToRemove = [
      'pickup-clusters',
      'pickup-cluster-count', 
      'pickup-points-symbols'
    ]
    
    layersToRemove.forEach(layerId => {
      if (props.map!.getLayer(layerId)) {
        props.map!.removeLayer(layerId)
      }
    })
    
    if (props.map.getSource('pickup-points')) {
      props.map.removeSource('pickup-points')
    }
  }
})
</script>

<style scoped>
.pickup-points-widget {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.pickup-controls {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  padding: 16px;
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.filter-buttons {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.filter-button {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 12px;
  border: 1px solid #e0e0e0;
  border-radius: 6px;
  background: white;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 13px;
  white-space: nowrap;
}

.filter-button:hover {
  border-color: #ccc;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.filter-button.active {
  border-color: var(--ozon-primary, #005bff);
  background: var(--ozon-primary, #005bff);
  color: white;
}

.filter-icon {
  display: flex;
  align-items: center;
  justify-content: center;
}

.filter-label {
  font-weight: 500;
}

.filter-count {
  font-size: 11px;
  background: rgba(255, 255, 255, 0.2);
  padding: 2px 6px;
  border-radius: 10px;
  min-width: 16px;
  text-align: center;
}

.filter-button.active .filter-count {
  background: rgba(255, 255, 255, 0.3);
}

.view-controls {
  display: flex;
  gap: 4px;
  border: 1px solid #e0e0e0;
  border-radius: 6px;
  overflow: hidden;
}

.view-button {
  padding: 8px 10px;
  border: none;
  background: white;
  cursor: pointer;
  transition: all 0.2s;
  color: #666;
}

.view-button:hover {
  background: #f5f5f5;
  color: #333;
}

.view-button.active {
  background: var(--ozon-primary, #005bff);
  color: white;
}

.pickup-counter {
  padding: 12px 16px;
  background: #f8f9fa;
  border-radius: 6px;
  font-size: 13px;
  color: #666;
  text-align: center;
}

/* Mobile Optimizations */
@media (max-width: 768px) {
  .pickup-controls {
    flex-direction: column;
    align-items: stretch;
    gap: 12px;
  }
  
  .filter-buttons {
    justify-content: center;
  }
  
  .filter-button {
    flex: 1;
    justify-content: center;
    min-width: 0;
  }
  
  .filter-label {
    display: none;
  }
  
  .view-controls {
    align-self: center;
  }
}

/* Dark Theme */
@media (prefers-color-scheme: dark) {
  .pickup-controls {
    background: #2a2a2a;
  }
  
  .filter-button {
    background: #2a2a2a;
    border-color: #444;
    color: white;
  }
  
  .filter-button:hover {
    border-color: #555;
    background: #333;
  }
  
  .view-button {
    background: #2a2a2a;
    color: #ccc;
  }
  
  .view-button:hover {
    background: #333;
    color: white;
  }
  
  .pickup-counter {
    background: #333;
    color: #ccc;
  }
  
  .view-controls {
    border-color: #444;
  }
}
</style>