import { describe, it, expect, vi, beforeEach } from 'vitest'
import { ref } from 'vue'
import { useMapMethods } from '../useMapMethods'

describe('useMapMethods', () => {
  let mockMap: any

  beforeEach(() => {
    vi.clearAllMocks()
    mockMap = {
      setCenter: vi.fn(),
      setZoom: vi.fn(),
      getBounds: vi.fn(() => [[55, 37], [56, 38]]),
      getCenter: vi.fn(() => [55.75, 37.61]),
      getZoom: vi.fn(() => 10)
    }
  })

  it('should set center with coordinates', () => {
    const mapRef = ref(mockMap)
    const { setCenter } = useMapMethods(mapRef)
    
    setCenter({ lat: 55.75, lng: 37.61 })
    
    expect(mockMap.setCenter).toHaveBeenCalledWith([55.75, 37.61], undefined)
  })

  it('should set center with coordinates and zoom', () => {
    const mapRef = ref(mockMap)
    const { setCenter } = useMapMethods(mapRef)
    
    setCenter({ lat: 55.75, lng: 37.61 }, 12)
    
    expect(mockMap.setCenter).toHaveBeenCalledWith([55.75, 37.61], 12)
  })

  it('should not set center when map is null', () => {
    const mapRef = ref(null)
    const { setCenter } = useMapMethods(mapRef)
    
    setCenter({ lat: 55.75, lng: 37.61 })
    
    // Should not throw error
    expect(true).toBe(true)
  })

  it('should set zoom level', () => {
    const mapRef = ref(mockMap)
    const { setZoom } = useMapMethods(mapRef)
    
    setZoom(15)
    
    expect(mockMap.setZoom).toHaveBeenCalledWith(15)
  })

  it('should get map bounds', () => {
    const mapRef = ref(mockMap)
    const { getBounds } = useMapMethods(mapRef)
    
    const bounds = getBounds()
    
    expect(bounds).toEqual([[55, 37], [56, 38]])
    expect(mockMap.getBounds).toHaveBeenCalled()
  })

  it('should return null bounds when map is null', () => {
    const mapRef = ref(null)
    const { getBounds } = useMapMethods(mapRef)
    
    const bounds = getBounds()
    
    expect(bounds).toBeNull()
  })

  it('should get map center as Coordinates', () => {
    const mapRef = ref(mockMap)
    const { getCenter } = useMapMethods(mapRef)
    
    const center = getCenter()
    
    expect(center).toEqual({ lat: 55.75, lng: 37.61 })
    expect(mockMap.getCenter).toHaveBeenCalled()
  })

  it('should return null center when map is null', () => {
    const mapRef = ref(null)
    const { getCenter } = useMapMethods(mapRef)
    
    const center = getCenter()
    
    expect(center).toBeNull()
  })

  it('should get zoom level', () => {
    const mapRef = ref(mockMap)
    const { getZoom } = useMapMethods(mapRef)
    
    const zoom = getZoom()
    
    expect(zoom).toBe(10)
    expect(mockMap.getZoom).toHaveBeenCalled()
  })

  it('should return null zoom when map is null', () => {
    const mapRef = ref(null)
    const { getZoom } = useMapMethods(mapRef)
    
    const zoom = getZoom()
    
    expect(zoom).toBeNull()
  })

  it('should auto detect location successfully', async () => {
    const mapRef = ref(mockMap)
    const { autoDetectLocation } = useMapMethods(mapRef)
    
    // Mock window.ymaps.geolocation
    global.window = {
      ymaps: {
        geolocation: {
          get: vi.fn(() => Promise.resolve({
            geoObjects: {
              get: vi.fn(() => ({
                geometry: {
                  getCoordinates: vi.fn(() => [55.75, 37.61])
                }
              }))
            }
          }))
        }
      }
    } as any
    
    const location = await autoDetectLocation()
    
    expect(location).toEqual({ lat: 55.75, lng: 37.61 })
    expect(mockMap.setCenter).toHaveBeenCalledWith([55.75, 37.61], undefined)
  })

  it('should handle auto detect location failure gracefully', async () => {
    const mapRef = ref(mockMap)
    const { autoDetectLocation } = useMapMethods(mapRef)
    
    // Mock console.warn to check if warning is logged
    const warnSpy = vi.spyOn(console, 'warn').mockImplementation(() => {})
    
    // Mock window.ymaps.geolocation to reject
    global.window = {
      ymaps: {
        geolocation: {
          get: vi.fn(() => Promise.reject(new Error('Geolocation failed')))
        }
      }
    } as any
    
    const location = await autoDetectLocation()
    
    expect(location).toBeNull()
    expect(warnSpy).toHaveBeenCalledWith('Не удалось определить местоположение автоматически')
    
    warnSpy.mockRestore()
  })

  it('should return null when autoDetectLocation called without map', async () => {
    const mapRef = ref(null)
    const { autoDetectLocation } = useMapMethods(mapRef)
    
    const location = await autoDetectLocation()
    
    expect(location).toBeNull()
  })

  it('should get map instance', () => {
    const mapRef = ref(mockMap)
    const { getMap } = useMapMethods(mapRef)
    
    const map = getMap()
    
    expect(map).toBe(mockMap)
  })
})