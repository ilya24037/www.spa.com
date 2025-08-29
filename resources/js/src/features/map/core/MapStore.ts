/**
 * MapStore - централизованное реактивное состояние карты
 * Размер: ~80 строк (согласно плану)
 */
import { reactive } from 'vue'

export interface Coordinates {
  lat: number
  lng: number
}

export interface MapMarker {
  id: string | number
  coordinates: Coordinates
  title?: string
  description?: string
  icon?: string
  color?: string
}

export interface MapConfig {
  center?: number[]
  zoom?: number
  controls?: string[]
}

export interface MapPlugin {
  name: string
  install?(map: any, store: MapStore): Promise<void> | void
  destroy?(): void
}

export interface MapStore {
  // State
  ready: boolean
  loading: boolean
  error: string | null
  center: Coordinates
  zoom: number
  address: string | null
  coordinates: Coordinates | null
  markers: Map<string | number, MapMarker>
  
  // Methods
  setReady(ready: boolean): void
  setLoading(loading: boolean): void
  setError(error: string | null): void
  setCenter(center: Coordinates): void
  setZoom(zoom: number): void
  setAddress(address: string | null): void
  setCoordinates(coords: Coordinates | null): void
  
  // Markers
  addMarker(marker: MapMarker): void
  removeMarker(id: string | number): void
  updateMarker(id: string | number, data: Partial<MapMarker>): void
  clearMarkers(): void
  
  // Events
  on(event: string, handler: Function): void
  off(event: string, handler: Function): void
  emit(event: string, data?: any): void
  
  // Map instance
  setMapInstance(map: any): void
  getMapInstance(): any
  
  // Reset
  reset(): void
}

export function createMapStore(): MapStore {
  const state = reactive({
    ready: false,
    loading: true,
    error: null,
    center: { lat: 58.0105, lng: 56.2502 }, // Пермь
    zoom: 14,
    address: null,
    coordinates: null,
    markers: new Map<string | number, MapMarker>(),
    mapInstance: null
  })
  
  const listeners = new Map<string, Set<Function>>()
  
  return {
    // State getters
    get ready() { return state.ready },
    get loading() { return state.loading },
    get error() { return state.error },
    get center() { return state.center },
    get zoom() { return state.zoom },
    get address() { return state.address },
    get coordinates() { return state.coordinates },
    get markers() { return state.markers },
    
    // State setters
    setReady(ready: boolean) {
      state.ready = ready
      state.loading = !ready
      if (ready) state.error = null
      this.emit('ready', ready)
    },
    
    setLoading(loading: boolean) {
      state.loading = loading
    },
    
    setError(error: string | null) {
      state.error = error
      state.loading = false
      this.emit('error', error)
    },
    
    setCenter(center: Coordinates) {
      state.center = center
      this.emit('center-change', center)
    },
    
    setZoom(zoom: number) {
      state.zoom = zoom
      this.emit('zoom-change', zoom)
    },
    
    setAddress(address: string | null) {
      state.address = address
    },
    
    setCoordinates(coords: Coordinates | null) {
      state.coordinates = coords
      this.emit('coordinates-change', coords)
    },
    
    // Marker methods
    addMarker(marker: MapMarker) {
      state.markers.set(marker.id, marker)
      this.emit('marker-add', marker)
    },
    
    removeMarker(id: string | number) {
      const marker = state.markers.get(id)
      if (marker) {
        state.markers.delete(id)
        this.emit('marker-remove', marker)
      }
    },
    
    updateMarker(id: string | number, data: Partial<MapMarker>) {
      const marker = state.markers.get(id)
      if (marker) {
        const updated = { ...marker, ...data }
        state.markers.set(id, updated)
        this.emit('marker-update', updated)
      }
    },
    
    clearMarkers() {
      state.markers.clear()
      this.emit('markers-clear')
    },
    
    // Event system
    on(event: string, handler: Function) {
      if (!listeners.has(event)) {
        listeners.set(event, new Set())
      }
      listeners.get(event)!.add(handler)
    },
    
    off(event: string, handler: Function) {
      const handlers = listeners.get(event)
      if (handlers) {
        handlers.delete(handler)
      }
    },
    
    emit(event: string, data?: any) {
      const handlers = listeners.get(event)
      if (handlers) {
        handlers.forEach(handler => handler(data))
      }
    },
    
    // Map instance
    setMapInstance(map: any) {
      state.mapInstance = map
    },
    
    getMapInstance() {
      return state.mapInstance
    },
    
    // Reset
    reset() {
      state.ready = false
      state.loading = true
      state.error = null
      state.center = { lat: 58.0105, lng: 56.2502 }
      state.zoom = 14
      state.address = null
      state.coordinates = null
      state.markers.clear()
      state.mapInstance = null
      listeners.clear()
    }
  }
}