/**
 * Тесты для MapLoader
 */
import { MapLoader } from '../MapLoader'

describe('MapLoader', () => {
  let loader: MapLoader
  
  beforeEach(() => {
    loader = MapLoader.getInstance()
    loader.reset()
    delete (window as any).ymaps
  })

  it('should be singleton', () => {
    const loader1 = MapLoader.getInstance()
    const loader2 = MapLoader.getInstance()
    expect(loader1).toBe(loader2)
  })

  it('should load Yandex Maps API', async () => {
    // Mock script loading
    const mockYmaps = { ready: jest.fn(cb => cb()) }
    ;(window as any).ymaps = mockYmaps

    const result = await loader.load('test-key')
    expect(result).toBe(mockYmaps)
  })

  it('should cache load promise', async () => {
    const mockYmaps = { ready: jest.fn(cb => cb()) }
    ;(window as any).ymaps = mockYmaps

    const promise1 = loader.load('test-key')
    const promise2 = loader.load('test-key')
    
    expect(promise1).toBe(promise2)
  })

  it('should handle load errors', async () => {
    // Mock script error
    jest.spyOn(document.head, 'appendChild').mockImplementation((script: any) => {
      setTimeout(() => script.onerror(), 0)
      return script
    })

    await expect(loader.load('test-key')).rejects.toThrow('Failed to load')
  })

  it('should return cached ymaps if already loaded', async () => {
    const mockYmaps = { ready: jest.fn(cb => cb()) }
    ;(window as any).ymaps = mockYmaps

    // First load
    await loader.load('test-key')
    
    // Second load should return immediately
    const result = await loader.load('test-key')
    expect(result).toBe(mockYmaps)
  })

  it('should reset state properly', async () => {
    const mockYmaps = { ready: jest.fn(cb => cb()) }
    ;(window as any).ymaps = mockYmaps

    await loader.load('test-key')
    loader.reset()
    
    // After reset, should create new promise
    const promise1 = loader.load('test-key')
    const promise2 = loader.load('test-key')
    expect(promise1).toBe(promise2)
  })
})