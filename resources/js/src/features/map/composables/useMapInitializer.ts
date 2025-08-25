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
      
      // Создаем карту
      const map = new window.ymaps.Map(containerId, {
        center: [config.center.lat, config.center.lng],
        zoom: config.zoom,
        controls: config.controls || MAP_CONTROLS[config.mode || 'single'],
        behaviors: config.behaviors || (isMobile 
          ? ['drag', 'dblClickZoom', 'multiTouch'] 
          : ['default'])
      })

      // Устанавливаем ограничения зума
      map.options.set('minZoom', MIN_ZOOM)
      map.options.set('maxZoom', MAX_ZOOM)

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