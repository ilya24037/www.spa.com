import { ref } from 'vue'
import { loadYandexMaps } from '../lib/yandexMapsLoader'
import { isMobileDevice } from '../lib/deviceDetector'
import type { MapConfig, Coordinates } from '../types'
import { MAP_CONTROLS, MIN_ZOOM, MAX_ZOOM } from '../lib/mapConstants'

export function useMapInitializer() {
  const loading = ref(true)
  const loadingText = ref('Инициализация карты...')
  const mapReady = ref(false)

  const initMap = async (
    containerId: string,
    config: MapConfig,
    callbacks?: {
      onReady?: (map: any) => void
      onError?: (error: string) => void
    }
  ) => {
    console.log('🗺️ useMapInitializer: Starting SIMPLIFIED initMap...', { containerId })
    
    try {
      loadingText.value = 'Загрузка карты...'
      await loadYandexMaps(config.apiKey)
      
      // Ждем рендеринга DOM
      await new Promise(resolve => setTimeout(resolve, 100))
      
      const container = document.getElementById(containerId)
      if (!container) {
        throw new Error('Контейнер карты не найден')
      }

      const isMobile = isMobileDevice()
      
      // Создаем карту (простая версия как в старом коде)
      const controls = config.mode === 'single' 
        ? ['zoomControl', 'typeSelector'] 
        : ['zoomControl', 'searchControl']
      
      const map = new window.ymaps.Map(containerId, {
        center: [config.center.lat, config.center.lng],
        zoom: config.zoom,
        controls,
        behaviors: isMobile 
          ? ['drag', 'dblClickZoom', 'multiTouch'] 
          : ['default']
      })

      // Устанавливаем ограничения зума после создания карты
      map.options.set('minZoom', MIN_ZOOM)
      map.options.set('maxZoom', MAX_ZOOM)
      
      // Дополнительные настройки для мобильных устройств
      if (isMobile) {
        map.behaviors.enable('multiTouch')
        map.options.set('suppressMapOpenBlock', true)
        map.options.set('dragInertiaEnable', true)
      }

      console.log('🗺️ useMapInitializer: Map created successfully!')
      loading.value = false
      mapReady.value = true
      
      callbacks?.onReady?.(map)
      return map
      
    } catch (error) {
      console.error('Ошибка инициализации карты:', error)
      loading.value = false
      mapReady.value = false
      const errorMsg = error instanceof Error ? error.message : 'Ошибка инициализации карты'
      callbacks?.onError?.(errorMsg)
      throw error
    }
  }

  return {
    loading,
    loadingText,
    mapReady,
    initMap
  }
}