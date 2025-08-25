import { describe, it, expect, vi, beforeEach } from 'vitest'
import { ref } from 'vue'
import { useMapMobileOptimization } from '../useMapMobileOptimization'

// Mock deviceDetector
vi.mock('../../lib/deviceDetector', () => ({
  isMobileDevice: vi.fn()
}))

import { isMobileDevice } from '../../lib/deviceDetector'

describe('useMapMobileOptimization', () => {
  let mockMap: any

  beforeEach(() => {
    vi.clearAllMocks()
    mockMap = {
      behaviors: {
        enable: vi.fn(),
        disable: vi.fn()
      },
      options: {
        set: vi.fn()
      }
    }
  })

  it('should setup mobile optimizations when on mobile device', () => {
    vi.mocked(isMobileDevice).mockReturnValue(true)
    const mapRef = ref(mockMap)
    const { setupMobileOptimizations } = useMapMobileOptimization(mapRef)
    
    setupMobileOptimizations()
    
    expect(mockMap.behaviors.enable).toHaveBeenCalledWith('multiTouch')
    expect(mockMap.options.set).toHaveBeenCalledWith('suppressMapOpenBlock', true)
    expect(mockMap.options.set).toHaveBeenCalledWith('dragInertiaEnable', true)
    expect(mockMap.options.set).toHaveBeenCalledWith('dragInertiaFriction', 0.9)
    expect(mockMap.options.set).toHaveBeenCalledWith('avoidFractionalZoom', false)
    expect(mockMap.options.set).toHaveBeenCalledWith('restrictMapArea', false)
    expect(mockMap.options.set).toHaveBeenCalledWith('nativeFullscreenControl', false)
  })

  it('should not setup optimizations when not on mobile', () => {
    vi.mocked(isMobileDevice).mockReturnValue(false)
    const mapRef = ref(mockMap)
    const { setupMobileOptimizations } = useMapMobileOptimization(mapRef)
    
    setupMobileOptimizations()
    
    expect(mockMap.behaviors.enable).not.toHaveBeenCalled()
    expect(mockMap.options.set).not.toHaveBeenCalled()
  })

  it('should not setup optimizations when map is null', () => {
    vi.mocked(isMobileDevice).mockReturnValue(true)
    const mapRef = ref(null)
    const { setupMobileOptimizations } = useMapMobileOptimization(mapRef)
    
    setupMobileOptimizations()
    
    // Should not throw error
    expect(true).toBe(true)
  })

  it('should enable mobile gestures', () => {
    vi.mocked(isMobileDevice).mockReturnValue(true)
    const mapRef = ref(mockMap)
    const { enableMobileGestures } = useMapMobileOptimization(mapRef)
    
    enableMobileGestures()
    
    expect(mockMap.behaviors.enable).toHaveBeenCalledWith(['drag', 'dblClickZoom', 'multiTouch'])
  })

  it('should disable mobile gestures', () => {
    vi.mocked(isMobileDevice).mockReturnValue(true)
    const mapRef = ref(mockMap)
    const { disableMobileGestures } = useMapMobileOptimization(mapRef)
    
    disableMobileGestures()
    
    expect(mockMap.behaviors.disable).toHaveBeenCalledWith(['drag', 'dblClickZoom', 'multiTouch'])
  })

  it('should not enable gestures when not on mobile', () => {
    vi.mocked(isMobileDevice).mockReturnValue(false)
    const mapRef = ref(mockMap)
    const { enableMobileGestures } = useMapMobileOptimization(mapRef)
    
    enableMobileGestures()
    
    expect(mockMap.behaviors.enable).not.toHaveBeenCalled()
  })

  it('should not disable gestures when not on mobile', () => {
    vi.mocked(isMobileDevice).mockReturnValue(false)
    const mapRef = ref(mockMap)
    const { disableMobileGestures } = useMapMobileOptimization(mapRef)
    
    disableMobileGestures()
    
    expect(mockMap.behaviors.disable).not.toHaveBeenCalled()
  })
})