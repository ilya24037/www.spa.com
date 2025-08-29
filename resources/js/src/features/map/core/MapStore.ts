/**
 * MapStore - Реактивное хранилище состояния карты
 * Управляет состоянием карты и обеспечивает коммуникацию между плагинами
 */

import { reactive, ref } from 'vue'

// Типы
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
  data?: any
}

export interface MapConfig {
  center?: Coordinates
  zoom?: number
  controls?: string[]
  behaviors?: string[]
  [key: string]: any
}

export interface MapPlugin {
  name: string
  install?: (map: any, store: MapStore) => void | Promise<void>
  destroy?: () => void
}

// Интерфейс Store
export interface MapStore {
  // Состояние
  ready: boolean
  loading: boolean
  error: string | null
  
  // Данные
  center: Coordinates
  zoom: number
  coordinates: Coordinates | null
  address: string | null
  markers: Map<string | number, MapMarker>
  
  // Методы
  setReady: (ready: boolean) => void
  setLoading: (loading: boolean) => void
  setError: (error: string | null) => void
  setCenter: (center: Coordinates) => void
  setZoom: (zoom: number) => void
  setCoordinates: (coords: Coordinates | null) => void
  setAddress: (address: string | null) => void
  
  // Маркеры
  addMarker: (marker: MapMarker) => void
  removeMarker: (id: string | number) => void
  updateMarker: (id: string | number, data: Partial<MapMarker>) => void
  clearMarkers: () => void
  
  // События
  on: (event: string, handler: Function) => void
  off: (event: string, handler: Function) => void
  emit: (event: string, ...args: any[]) => void
  
  // Инстанс карты
  getMapInstance: () => any
  setMapInstance: (map: any) => void
  
  // Сброс
  reset: () => void
}

/**
 * Создание нового хранилища карты
 */
export function createMapStore(): MapStore {
  // Состояние
  const state = reactive({
    ready: false,
    loading: true,
    error: null as string | null,
    center: { lat: 58.0105, lng: 56.2502 } as Coordinates, // Пермь по умолчанию
    zoom: 14,
    coordinates: null as Coordinates | null,
    address: null as string | null
  })

  // Маркеры
  const markers = ref(new Map<string | number, MapMarker>())

  // Инстанс карты
  let mapInstance: any = null

  // События
  const listeners = new Map<string, Set<Function>>()

  const on = (event: string, handler: Function) => {
    if (!listeners.has(event)) {
      listeners.set(event, new Set())
    }
    listeners.get(event)!.add(handler)
  }

  const off = (event: string, handler: Function) => {
    const handlers = listeners.get(event)
    if (handlers) {
      handlers.delete(handler)
    }
  }

  const emit = (event: string, ...args: any[]) => {
    const handlers = listeners.get(event)
    if (handlers) {
      handlers.forEach(handler => handler(...args))
    }
  }

  // Реализация интерфейса MapStore
  return {
    // Состояние (геттеры)
    get ready() { return state.ready },
    get loading() { return state.loading },
    get error() { return state.error },
    get center() { return state.center },
    get zoom() { return state.zoom },
    get coordinates() { return state.coordinates },
    get address() { return state.address },
    get markers() { return markers.value },

    // Методы установки состояния
    setReady(ready: boolean) {
      state.ready = ready
      if (ready) {
        state.loading = false
        state.error = null
      }
      emit('ready', ready)
    },

    setLoading(loading: boolean) {
      state.loading = loading
      emit('loading', loading)
    },

    setError(error: string | null) {
      state.error = error
      state.loading = false
      emit('error', error)
    },

    setCenter(center: Coordinates) {
      state.center = center
      emit('center-change', center)
    },

    setZoom(zoom: number) {
      state.zoom = zoom
      emit('zoom-change', zoom)
    },

    setCoordinates(coords: Coordinates | null) {
      state.coordinates = coords
      emit('coordinates-change', coords)
    },

    setAddress(address: string | null) {
      state.address = address
      emit('address-change', address)
    },

    // Управление маркерами
    addMarker(marker: MapMarker) {
      markers.value.set(marker.id, marker)
      emit('marker-add', marker)
    },

    removeMarker(id: string | number) {
      const marker = markers.value.get(id)
      if (marker) {
        markers.value.delete(id)
        emit('marker-remove', marker)
      }
    },

    updateMarker(id: string | number, data: Partial<MapMarker>) {
      const marker = markers.value.get(id)
      if (marker) {
        const updated = { ...marker, ...data }
        markers.value.set(id, updated)
        emit('marker-update', updated)
      }
    },

    clearMarkers() {
      markers.value.clear()
      emit('markers-clear')
    },

    // События
    on,
    off,
    emit,

    // Инстанс карты
    getMapInstance() {
      return mapInstance
    },

    setMapInstance(map: any) {
      mapInstance = map
      emit('map-instance-set', map)
    },

    // Сброс
    reset() {
      state.ready = false
      state.loading = true
      state.error = null
      state.coordinates = null
      state.address = null
      markers.value.clear()
      mapInstance = null
      listeners.clear()
    }
  }
}