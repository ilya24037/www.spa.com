/**
 * MapStore - Централизованное хранилище состояния карты
 * Паттерн: Reactive Store (Vue 3)
 * Принцип KISS: простое и понятное API
 */

import { reactive, ref, computed } from 'vue'
import type { Ref, ComputedRef } from 'vue'

// Типы
export interface Coordinates {
  lat: number
  lng: number
}

export interface MapMarker {
  id: string
  coordinates: Coordinates
  title?: string
  description?: string
  preset?: string
  color?: string
  data?: any
}

export interface MapState {
  isLoading: boolean
  isReady: boolean
  error: string | null
  center: Coordinates
  zoom: number
  bounds: any | null
  address: string
  coordinates: Coordinates | null
}

// Event emitter для плагинов
type EventCallback = (...args: any[]) => void

export class MapStore {
  // Состояние карты
  private state = reactive<MapState>({
    isLoading: true,
    isReady: false,
    error: null,
    center: { lat: 58.0105, lng: 56.2502 }, // Пермь по умолчанию
    zoom: 14,
    bounds: null,
    address: '',
    coordinates: null
  })

  // Маркеры
  private markers = ref<Map<string, MapMarker>>(new Map())

  // Инстанс карты
  private mapInstance = ref<any>(null)

  // События для плагинов
  private events = new Map<string, EventCallback[]>()

  // Getters
  get isLoading() {
    return this.state.isLoading
  }

  get isReady() {
    return this.state.isReady
  }

  get error() {
    return this.state.error
  }

  get center() {
    return this.state.center
  }

  get zoom() {
    return this.state.zoom
  }

  get address() {
    return this.state.address
  }

  get coordinates() {
    return this.state.coordinates
  }

  get markersArray() {
    return Array.from(this.markers.value.values())
  }

  // Setters для состояния
  setLoading(loading: boolean) {
    this.state.isLoading = loading
  }

  setReady(ready: boolean) {
    this.state.isReady = ready
    if (ready) {
      this.state.isLoading = false
      this.state.error = null
    }
  }

  setError(error: string | null) {
    this.state.error = error
    this.state.isLoading = false
  }

  setCenter(center: Coordinates) {
    this.state.center = center
  }

  setZoom(zoom: number) {
    this.state.zoom = zoom
  }

  setAddress(address: string) {
    this.state.address = address
  }

  setCoordinates(coords: Coordinates | null) {
    this.state.coordinates = coords
    this.emit('coordinates-change', coords)
  }

  setMapInstance(instance: any) {
    this.mapInstance.value = instance
  }

  getMapInstance() {
    return this.mapInstance.value
  }

  // Управление маркерами
  addMarker(marker: MapMarker) {
    this.markers.value.set(marker.id, marker)
    this.emit('markers-add', marker)
  }

  removeMarker(id: string) {
    const marker = this.markers.value.get(id)
    if (marker) {
      this.markers.value.delete(id)
      this.emit('markers-remove', id)
    }
  }

  clearMarkers() {
    this.markers.value.clear()
    this.emit('markers-clear')
  }

  updateMarkers(markers: MapMarker[]) {
    this.markers.value.clear()
    markers.forEach(marker => this.markers.value.set(marker.id, marker))
    this.emit('markers-change', markers)
  }

  // Event system для плагинов
  on(event: string, callback: EventCallback) {
    if (!this.events.has(event)) {
      this.events.set(event, [])
    }
    this.events.get(event)!.push(callback)
  }

  off(event: string, callback: EventCallback) {
    const callbacks = this.events.get(event)
    if (callbacks) {
      const index = callbacks.indexOf(callback)
      if (index > -1) {
        callbacks.splice(index, 1)
      }
    }
  }

  emit(event: string, ...args: any[]) {
    const callbacks = this.events.get(event)
    if (callbacks) {
      callbacks.forEach(callback => callback(...args))
    }
  }

  // Сброс состояния
  reset() {
    this.state.isLoading = true
    this.state.isReady = false
    this.state.error = null
    this.state.address = ''
    this.state.coordinates = null
    this.clearMarkers()
    this.events.clear()
  }
}

/**
 * Создать новый экземпляр store для карты
 */
export function createMapStore() {
  return new MapStore()
}