/**
 * Тесты для MapStore
 */
import { createMapStore } from '../MapStore'
import type { MapStore, MapMarker, Coordinates } from '../MapStore'

describe('MapStore', () => {
  let store: MapStore
  
  beforeEach(() => {
    store = createMapStore()
  })

  describe('State management', () => {
    it('should initialize with default state', () => {
      expect(store.ready).toBe(false)
      expect(store.loading).toBe(true)
      expect(store.error).toBe(null)
      expect(store.center).toEqual({ lat: 58.0105, lng: 56.2502 })
      expect(store.zoom).toBe(14)
    })

    it('should set ready state', () => {
      store.setReady(true)
      expect(store.ready).toBe(true)
      expect(store.loading).toBe(false)
      expect(store.error).toBe(null)
    })

    it('should set error state', () => {
      store.setError('Test error')
      expect(store.error).toBe('Test error')
      expect(store.loading).toBe(false)
    })

    it('should update center', () => {
      const newCenter: Coordinates = { lat: 55.7558, lng: 37.6173 }
      store.setCenter(newCenter)
      expect(store.center).toEqual(newCenter)
    })

    it('should update zoom', () => {
      store.setZoom(16)
      expect(store.zoom).toBe(16)
    })
  })

  describe('Marker management', () => {
    const testMarker: MapMarker = {
      id: 'test-1',
      coordinates: { lat: 55.7558, lng: 37.6173 },
      title: 'Test Marker',
      description: 'Test Description'
    }

    it('should add marker', () => {
      store.addMarker(testMarker)
      expect(store.markers.has('test-1')).toBe(true)
      expect(store.markers.get('test-1')).toEqual(testMarker)
    })

    it('should remove marker', () => {
      store.addMarker(testMarker)
      store.removeMarker('test-1')
      expect(store.markers.has('test-1')).toBe(false)
    })

    it('should update marker', () => {
      store.addMarker(testMarker)
      store.updateMarker('test-1', { title: 'Updated Title' })
      
      const updated = store.markers.get('test-1')
      expect(updated?.title).toBe('Updated Title')
      expect(updated?.description).toBe('Test Description')
    })

    it('should clear all markers', () => {
      store.addMarker(testMarker)
      store.addMarker({ ...testMarker, id: 'test-2' })
      
      store.clearMarkers()
      expect(store.markers.size).toBe(0)
    })
  })

  describe('Event system', () => {
    it('should emit and listen to events', () => {
      const handler = jest.fn()
      store.on('test-event', handler)
      store.emit('test-event', 'data')
      
      expect(handler).toHaveBeenCalledWith('data')
    })

    it('should remove event listeners', () => {
      const handler = jest.fn()
      store.on('test-event', handler)
      store.off('test-event', handler)
      store.emit('test-event', 'data')
      
      expect(handler).not.toHaveBeenCalled()
    })

    it('should emit marker events', () => {
      const handler = jest.fn()
      store.on('marker-add', handler)
      
      const marker: MapMarker = {
        id: 'test',
        coordinates: { lat: 0, lng: 0 }
      }
      
      store.addMarker(marker)
      expect(handler).toHaveBeenCalledWith(marker)
    })

    it('should emit state change events', () => {
      const readyHandler = jest.fn()
      const centerHandler = jest.fn()
      
      store.on('ready', readyHandler)
      store.on('center-change', centerHandler)
      
      store.setReady(true)
      store.setCenter({ lat: 1, lng: 1 })
      
      expect(readyHandler).toHaveBeenCalledWith(true)
      expect(centerHandler).toHaveBeenCalledWith({ lat: 1, lng: 1 })
    })
  })

  describe('Map instance', () => {
    it('should store and retrieve map instance', () => {
      const mockMap = { id: 'test-map' }
      store.setMapInstance(mockMap)
      expect(store.getMapInstance()).toBe(mockMap)
    })
  })

  describe('Reset', () => {
    it('should reset all state', () => {
      // Изменяем состояние
      store.setReady(true)
      store.setCenter({ lat: 1, lng: 1 })
      store.addMarker({
        id: 'test',
        coordinates: { lat: 0, lng: 0 }
      })
      store.setMapInstance({ id: 'map' })
      
      // Сбрасываем
      store.reset()
      
      // Проверяем
      expect(store.ready).toBe(false)
      expect(store.loading).toBe(true)
      expect(store.error).toBe(null)
      expect(store.markers.size).toBe(0)
      expect(store.getMapInstance()).toBe(null)
    })
  })
})