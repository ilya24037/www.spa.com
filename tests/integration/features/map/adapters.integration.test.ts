import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { YandexMapAdapter } from '@/src/features/map/adapters/YandexMapAdapter'
import { MapControlsAdapter } from '@/src/features/map/adapters/MapControlsAdapter'
import { MapMarkersAdapter } from '@/src/features/map/adapters/MapMarkersAdapter'
import type { MapMarker } from '@/src/features/map/core/MapStore'

describe('Map Adapters Integration', () => {
  let mapAdapter: YandexMapAdapter
  let controlsAdapter: MapControlsAdapter
  let markersAdapter: MapMarkersAdapter
  let container: HTMLDivElement

  beforeEach(() => {
    // Создаем контейнер для карты
    container = document.createElement('div')
    container.id = 'integration-test-map'
    document.body.appendChild(container)
    
    // Инициализируем адаптеры
    mapAdapter = new YandexMapAdapter()
    controlsAdapter = new MapControlsAdapter(mapAdapter)
    markersAdapter = new MapMarkersAdapter(mapAdapter)
  })

  afterEach(() => {
    // Очищаем ресурсы
    markersAdapter?.destroy()
    controlsAdapter?.destroy()
    mapAdapter?.destroy()
    
    // Удаляем контейнер
    document.body.removeChild(container)
  })

  describe('Full workflow integration', () => {
    it('should initialize all adapters and work together', async () => {
      // 1. Инициализация карты
      await mapAdapter.initialize('integration-test-map', {
        center: [58.01046, 56.25017],
        zoom: 14
      })
      
      expect(mapAdapter.isReady()).toBe(true)
      expect(mapAdapter.getInstance()).toBeTruthy()
      
      // 2. Добавление контролов
      controlsAdapter.initialize({
        zoomControl: true,
        typeSelector: true,
        fullscreenControl: true
      })
      
      expect(controlsAdapter.getControl('zoomControl')).toBeTruthy()
      expect(controlsAdapter.getControl('typeSelector')).toBeTruthy()
      
      // 3. Инициализация системы маркеров
      markersAdapter.initialize(false)
      
      // 4. Добавление маркеров
      const testMarkers: MapMarker[] = [
        { id: '1', coordinates: { lat: 58.01, lng: 56.25 }, title: 'Marker 1' },
        { id: '2', coordinates: { lat: 58.02, lng: 56.26 }, title: 'Marker 2' },
        { id: '3', coordinates: { lat: 58.00, lng: 56.24 }, title: 'Marker 3' }
      ]
      
      testMarkers.forEach(marker => {
        markersAdapter.addMarker(marker)
      })
      
      expect(markersAdapter.getAllMarkers().size).toBe(3)
      
      // 5. Взаимодействие с картой
      mapAdapter.setCenter({ lat: 58.015, lng: 56.255 }, 15)
      expect(mapAdapter.getZoom()).toBe(15)
      
      // 6. Управление контролами
      controlsAdapter.toggleControl('zoomControl', false)
      
      // 7. Обновление маркера
      markersAdapter.updateMarker('1', {
        title: 'Updated Marker 1',
        coordinates: { lat: 58.015, lng: 56.255 }
      })
      
      // 8. Удаление маркера
      markersAdapter.removeMarker('3')
      expect(markersAdapter.getAllMarkers().size).toBe(2)
    })

    it('should handle adapter destruction in correct order', async () => {
      await mapAdapter.initialize('integration-test-map', {})
      controlsAdapter.initialize()
      markersAdapter.initialize(true) // с кластеризацией
      
      // Добавляем данные
      markersAdapter.addMarker({ id: '1', coordinates: { lat: 58, lng: 56 } })
      
      // Уничтожаем в правильном порядке
      markersAdapter.destroy()
      expect(markersAdapter.getAllMarkers().size).toBe(0)
      
      controlsAdapter.destroy()
      expect(controlsAdapter.getControl('zoomControl')).toBeUndefined()
      
      mapAdapter.destroy()
      expect(mapAdapter.getInstance()).toBeNull()
      expect(mapAdapter.isReady()).toBe(false)
    })
  })

  describe('Event coordination', () => {
    it('should coordinate events between adapters', async () => {
      await mapAdapter.initialize('integration-test-map', {})
      markersAdapter.initialize(false)
      
      const clickHandler = vi.fn()
      const markerClickHandler = vi.fn()
      
      // Подписка на события карты
      mapAdapter.on('click', clickHandler)
      
      // Добавление маркера с обработчиком
      const marker: MapMarker = {
        id: 'event-test',
        coordinates: { lat: 58, lng: 56 },
        title: 'Event Test'
      }
      
      markersAdapter.addMarker(marker)
      markersAdapter.setMarkerEventHandler('event-test', 'click', markerClickHandler)
      
      // Проверяем, что обработчики установлены
      const placemark = markersAdapter.getMarker('event-test')
      expect(placemark).toBeTruthy()
      expect(placemark.events.add).toHaveBeenCalledWith('click', markerClickHandler)
    })
  })

  describe('Performance with multiple markers', () => {
    it('should handle large number of markers efficiently', async () => {
      await mapAdapter.initialize('integration-test-map', {})
      markersAdapter.initialize(true) // с кластеризацией для производительности
      
      const startTime = performance.now()
      
      // Добавляем 100 маркеров
      for (let i = 0; i < 100; i++) {
        markersAdapter.addMarker({
          id: `marker-${i}`,
          coordinates: {
            lat: 58 + Math.random() * 0.1,
            lng: 56 + Math.random() * 0.1
          },
          title: `Marker ${i}`
        })
      }
      
      const endTime = performance.now()
      const duration = endTime - startTime
      
      expect(markersAdapter.getAllMarkers().size).toBe(100)
      expect(duration).toBeLessThan(1000) // Должно выполниться менее чем за 1 секунду
      
      // Очистка всех маркеров
      const clearStartTime = performance.now()
      markersAdapter.clearMarkers()
      const clearEndTime = performance.now()
      
      expect(markersAdapter.getAllMarkers().size).toBe(0)
      expect(clearEndTime - clearStartTime).toBeLessThan(100) // Очистка должна быть быстрой
    })
  })

  describe('Error recovery', () => {
    it('should handle initialization errors gracefully', async () => {
      // Попытка инициализации с несуществующим контейнером
      await expect(mapAdapter.initialize('non-existent', {})).rejects.toThrow()
      
      // Адаптеры должны работать даже если карта не инициализирована
      expect(() => controlsAdapter.initialize()).not.toThrow()
      expect(() => markersAdapter.initialize()).not.toThrow()
      
      // Операции должны быть безопасными
      expect(() => mapAdapter.setCenter({ lat: 58, lng: 56 })).not.toThrow()
      expect(() => markersAdapter.addMarker({ id: '1', coordinates: { lat: 58, lng: 56 } })).not.toThrow()
      expect(() => controlsAdapter.toggleControl('zoomControl', false)).not.toThrow()
    })

    it('should recover from partial initialization', async () => {
      // Инициализируем только карту
      await mapAdapter.initialize('integration-test-map', {})
      
      // Маркеры должны работать даже если не инициализированы
      markersAdapter.addMarker({ id: '1', coordinates: { lat: 58, lng: 56 } })
      
      // Теперь инициализируем маркеры
      markersAdapter.initialize(false)
      
      // Добавляем еще маркеры
      markersAdapter.addMarker({ id: '2', coordinates: { lat: 59, lng: 57 } })
      
      // Оба маркера должны быть доступны
      expect(markersAdapter.getAllMarkers().size).toBeGreaterThanOrEqual(1)
    })
  })

  describe('State consistency', () => {
    it('should maintain consistent state across operations', async () => {
      await mapAdapter.initialize('integration-test-map', {
        center: [58.01046, 56.25017],
        zoom: 14
      })
      
      const initialCenter = mapAdapter.getCenter()
      const initialZoom = mapAdapter.getZoom()
      
      // Изменяем состояние
      mapAdapter.setCenter({ lat: 55.75, lng: 37.62 })
      mapAdapter.setZoom(10)
      
      // Проверяем новое состояние
      const newCenter = mapAdapter.getCenter()
      const newZoom = mapAdapter.getZoom()
      
      expect(newCenter).not.toEqual(initialCenter)
      expect(newZoom).not.toBe(initialZoom)
      expect(newZoom).toBe(10)
      
      // Возвращаем исходное состояние
      mapAdapter.setCenter(initialCenter!, initialZoom)
      
      expect(mapAdapter.getCenter()).toEqual(initialCenter)
      expect(mapAdapter.getZoom()).toBe(initialZoom)
    })

    it('should maintain markers state consistency', async () => {
      await mapAdapter.initialize('integration-test-map', {})
      markersAdapter.initialize(false)
      
      // Добавляем маркеры
      const markers = [
        { id: 'a', coordinates: { lat: 58, lng: 56 }, title: 'A' },
        { id: 'b', coordinates: { lat: 59, lng: 57 }, title: 'B' },
        { id: 'c', coordinates: { lat: 60, lng: 58 }, title: 'C' }
      ]
      
      markers.forEach(m => markersAdapter.addMarker(m))
      expect(markersAdapter.getAllMarkers().size).toBe(3)
      
      // Обновляем маркер
      markersAdapter.updateMarker('b', { title: 'B Updated' })
      expect(markersAdapter.getAllMarkers().size).toBe(3)
      
      // Удаляем маркер
      markersAdapter.removeMarker('a')
      expect(markersAdapter.getAllMarkers().size).toBe(2)
      expect(markersAdapter.getMarker('a')).toBeUndefined()
      expect(markersAdapter.getMarker('b')).toBeTruthy()
      
      // Очищаем все
      markersAdapter.clearMarkers()
      expect(markersAdapter.getAllMarkers().size).toBe(0)
    })
  })
})