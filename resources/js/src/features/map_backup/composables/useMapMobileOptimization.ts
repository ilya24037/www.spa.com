import { type Ref } from 'vue'
import { isMobileDevice } from '../lib/deviceDetector'

export function useMapMobileOptimization(map: Ref<any | null>) {
  const setupMobileOptimizations = () => {
    if (!map.value || !isMobileDevice()) return
    
    // Включаем мультитач для мобильных
    map.value.behaviors.enable('multiTouch')
    
    // Отключаем открытие балуна карты при клике (для быстрого отклика)
    map.value.options.set('suppressMapOpenBlock', true)
    
    // Включаем инерцию для плавного движения
    map.value.options.set('dragInertiaEnable', true)
    map.value.options.set('dragInertiaFriction', 0.9)
    
    // Оптимизация рендеринга для мобильных
    map.value.options.set('avoidFractionalZoom', false)
    map.value.options.set('restrictMapArea', false)
    
    // Уменьшаем задержку для touch событий
    map.value.options.set('nativeFullscreenControl', false)
  }

  const enableMobileGestures = () => {
    if (!map.value || !isMobileDevice()) return
    
    map.value.behaviors.enable(['drag', 'dblClickZoom', 'multiTouch'])
  }

  const disableMobileGestures = () => {
    if (!map.value || !isMobileDevice()) return
    
    map.value.behaviors.disable(['drag', 'dblClickZoom', 'multiTouch'])
  }

  return {
    setupMobileOptimizations,
    enableMobileGestures,
    disableMobileGestures
  }
}