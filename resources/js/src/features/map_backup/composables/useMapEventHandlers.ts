import { ref, type Ref } from 'vue'
import type { Coordinates } from '../types'

export interface MapEventHandlersOptions {
  onBoundsChange?: (bounds: number[][]) => void
  onCenterChange?: (center: Coordinates) => void
  onZoomChange?: (zoom: number) => void
  onClick?: (coords: Coordinates) => void
  onReady?: (map: any) => void
  onError?: (error: string) => void
}

export function useMapEventHandlers(emit: any, options: MapEventHandlersOptions = {}) {
  const lastBounds = ref<number[][] | null>(null)
  const lastCenter = ref<Coordinates | null>(null)
  const lastZoom = ref<number | null>(null)
  
  let boundsChangeTimeout: NodeJS.Timeout | null = null

  // Handle map ready event
  const handleMapReady = (map: any) => {
    options.onReady?.(map)
    emit('ready', map)
  }

  // Handle bounds change with debouncing
  const handleBoundsChange = (bounds: number[][]) => {
    if (boundsChangeTimeout) {
      clearTimeout(boundsChangeTimeout)
    }
    
    boundsChangeTimeout = setTimeout(() => {
      lastBounds.value = bounds
      options.onBoundsChange?.(bounds)
      emit('bounds-change', bounds)
    }, 300)
  }

  // Handle center change
  const handleCenterChange = (center: Coordinates) => {
    lastCenter.value = center
    options.onCenterChange?.(center)
    emit('center-change', center)
  }

  // Handle zoom change
  const handleZoomChange = (zoom: number) => {
    lastZoom.value = zoom
    options.onZoomChange?.(zoom)
    emit('zoom-change', zoom)
  }

  // Handle map click
  const handleMapClick = (coords: Coordinates) => {
    options.onClick?.(coords)
    emit('click', coords)
  }

  // Handle map error
  const handleMapError = (error: string) => {
    options.onError?.(error)
    emit('error', error)
  }

  // Setup all event handlers for a map instance
  const setupEventHandlers = (map: any) => {
    if (!map || !window.ymaps) return

    // Bounds change event
    map.events.add('boundschange', () => {
      const bounds = map.getBounds()
      handleBoundsChange(bounds)
      
      const center = map.getCenter()
      handleCenterChange({ lat: center[0], lng: center[1] })
      
      const zoom = map.getZoom()
      handleZoomChange(zoom)
    })

    // Click event
    map.events.add('click', (e: any) => {
      const coords = e.get('coords')
      handleMapClick({ lat: coords[0], lng: coords[1] })
    })
  }

  // Cleanup
  const cleanup = () => {
    if (boundsChangeTimeout) {
      clearTimeout(boundsChangeTimeout)
      boundsChangeTimeout = null
    }
  }

  return {
    // State
    lastBounds: lastBounds as Readonly<Ref<number[][] | null>>,
    lastCenter: lastCenter as Readonly<Ref<Coordinates | null>>,
    lastZoom: lastZoom as Readonly<Ref<number | null>>,
    
    // Handlers
    handleMapReady,
    handleBoundsChange,
    handleCenterChange,
    handleZoomChange,
    handleMapClick,
    handleMapError,
    
    // Setup
    setupEventHandlers,
    cleanup
  }
}