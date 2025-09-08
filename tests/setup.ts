import { config } from '@vue/test-utils'
import { vi } from 'vitest'

// Global test setup
config.global.mocks = {
  $t: (key: string) => key,
  $page: {
    props: {}
  }
}

// Mock window.ymaps for map tests
global.window = global.window || {}
global.window.ymaps = {
  ready: (callback: Function) => callback(),
  Map: vi.fn().mockImplementation((id: string, config: any) => {
    return {
      id,
      config,
      setCenter: vi.fn(),
      setZoom: vi.fn(),
      getCenter: vi.fn(() => [58.01046, 56.25017]),
      getZoom: vi.fn(() => 12),
      destroy: vi.fn(),
      events: {
        add: vi.fn(),
        remove: vi.fn()
      },
      behaviors: {
        disable: vi.fn(),
        enable: vi.fn()
      },
      options: {
        set: vi.fn()
      },
      geoObjects: {
        add: vi.fn(),
        remove: vi.fn()
      }
    }
  }),
  Placemark: class MockPlacemark {
    constructor(public coords: number[], public properties: any, public options: any) {}
  },
  Clusterer: class MockClusterer {
    add = vi.fn()
    removeAll = vi.fn()
  }
}

// Mock console methods for cleaner test output
global.console = {
  ...console,
  log: vi.fn(),
  warn: vi.fn(),
  error: vi.fn()
}