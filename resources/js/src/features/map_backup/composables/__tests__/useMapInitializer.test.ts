import { describe, it, expect, vi, beforeEach } from 'vitest'
import { useMapInitializer } from '../useMapInitializer'
import type { MapConfig } from '../../types'

// Mock yandexMapsLoader
vi.mock('../../lib/yandexMapsLoader', () => ({
  loadYandexMaps: vi.fn(() => Promise.resolve())
}))

// Mock deviceDetector
vi.mock('../../lib/deviceDetector', () => ({
  isMobileDevice: vi.fn(() => false)
}))

describe('useMapInitializer', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    // Mock window.ymaps
    global.window = {
      ymaps: {
        Map: vi.fn().mockImplementation(() => ({
          options: {
            set: vi.fn()
          }
        }))
      }
    } as any
  })

  it('should initialize with loading state', () => {
    const { loading, loadingText, mapReady } = useMapInitializer()
    
    expect(loading.value).toBe(true)
    expect(loadingText.value).toBe('Инициализация карты...')
    expect(mapReady.value).toBe(false)
  })

  it('should create map with correct config', async () => {
    const { initMap } = useMapInitializer()
    
    const config: MapConfig = {
      apiKey: 'test-key',
      center: { lat: 55.75, lng: 37.61 },
      zoom: 10,
      mode: 'single'
    }

    // Create mock container
    document.body.innerHTML = '<div id="test-map"></div>'
    
    const callbacks = {
      onReady: vi.fn(),
      onError: vi.fn()
    }

    const map = await initMap('test-map', config, callbacks)
    
    expect(window.ymaps.Map).toHaveBeenCalledWith(
      'test-map',
      expect.objectContaining({
        center: [55.75, 37.61],
        zoom: 10
      })
    )
    expect(callbacks.onReady).toHaveBeenCalledWith(map)
  })

  it('should handle container not found error', async () => {
    const { initMap } = useMapInitializer()
    
    const config: MapConfig = {
      apiKey: 'test-key',
      center: { lat: 55.75, lng: 37.61 },
      zoom: 10
    }

    const callbacks = {
      onError: vi.fn()
    }

    await expect(initMap('non-existent', config, callbacks)).rejects.toThrow()
    expect(callbacks.onError).toHaveBeenCalledWith('Контейнер карты не найден')
  })

  it('should update loading states correctly', async () => {
    const { loading, loadingText, mapReady, initMap } = useMapInitializer()
    
    expect(loading.value).toBe(true)
    expect(loadingText.value).toBe('Инициализация карты...')
    
    document.body.innerHTML = '<div id="test-map"></div>'
    
    await initMap('test-map', {
      apiKey: 'test',
      center: { lat: 0, lng: 0 },
      zoom: 10
    })
    
    expect(loading.value).toBe(false)
    expect(mapReady.value).toBe(true)
  })

  it('should set min and max zoom limits', async () => {
    const { initMap } = useMapInitializer()
    
    document.body.innerHTML = '<div id="test-map"></div>'
    
    const mockSetOptions = vi.fn()
    global.window.ymaps.Map = vi.fn().mockImplementation(() => ({
      options: {
        set: mockSetOptions
      }
    }))
    
    await initMap('test-map', {
      apiKey: 'test',
      center: { lat: 0, lng: 0 },
      zoom: 10
    })
    
    expect(mockSetOptions).toHaveBeenCalledWith('minZoom', expect.any(Number))
    expect(mockSetOptions).toHaveBeenCalledWith('maxZoom', expect.any(Number))
  })
})