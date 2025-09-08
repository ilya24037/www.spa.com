<template>
  <div 
    v-if="shouldRender"
    ref="controlContainer"
    :class="containerClasses"
    :style="containerStyle"
  >
    <!-- Skeleton loader во время инициализации -->
    <div 
      v-if="isLoading" 
      class="zoom-control-skeleton"
      :style="skeletonStyle"
    >
      <div class="skeleton-button"></div>
      <div v-if="showSlider" class="skeleton-slider"></div>
      <div class="skeleton-button"></div>
    </div>

    <!-- Основной контрол (невидим во время загрузки) -->
    <div 
      :style="{ visibility: isLoading ? 'hidden' : 'visible' }"
      class="zoom-control-content"
    >
      <!-- Контрол создается и управляется JavaScript -->
    </div>

    <!-- Оверлей для состояний disabled/error -->
    <div 
      v-if="hasOverlay"
      class="zoom-control-overlay"
      :class="overlayClasses"
    >
      <div v-if="error" class="error-message">
        <span class="error-icon">⚠️</span>
        <span class="error-text">{{ error }}</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * Vue 3 компонент для ZoomControl
 * Composition API с полной TypeScript типизацией
 * Соответствует принципам CLAUDE.md
 * 
 * @component ZoomControlVue
 * @version 1.0.0
 * @author SPA Platform
 */

import { 
  ref, 
  computed, 
  onMounted, 
  onBeforeUnmount, 
  watch,
  nextTick,
  type Ref,
  type ComputedRef
} from 'vue'
import ZoomControl from './ZoomControl.js'
import type { 
  ZoomControlOptions, 
  ZoomControlSize, 
  ZoomRange,
  ZoomControlEventHandler
} from './ZoomControl.d.ts'

// Props с типизацией и значениями по умолчанию
interface Props {
  /** Экземпляр карты */
  map?: any
  /** Размер контрола */
  size?: ZoomControlSize
  /** Позиция на карте */
  position?: string
  /** Показывать слайдер */
  showSlider?: boolean
  /** Показывать кнопки +/- */
  showButtons?: boolean
  /** Видимость контрола */
  visible?: boolean
  /** Активность контрола */
  enabled?: boolean
  /** Начальный уровень зума */
  zoom?: number
  /** Диапазон зума */
  zoomRange?: ZoomRange
  /** Длительность анимации */
  zoomDuration?: number
  /** Плавная анимация */
  smooth?: boolean
  /** Шаг изменения зума */
  step?: number
  /** Отступы */
  margin?: { top?: number; right?: number; bottom?: number; left?: number }
  /** Z-index */
  zIndex?: number
  /** Дополнительные CSS классы */
  class?: string | string[] | Record<string, boolean>
  /** Inline стили */
  style?: string | Record<string, string | number>
}

const props = withDefaults(defineProps<Props>(), {
  size: 'medium',
  position: 'topLeft',
  showSlider: true,
  showButtons: true,
  visible: true,
  enabled: true,
  zoom: 10,
  zoomDuration: 300,
  smooth: true,
  step: 1,
  zIndex: 1000
})

// Emits с типизацией
interface Emits {
  /** Изменение уровня зума */
  'update:zoom': [zoom: number]
  /** Изменение диапазона зума */
  'update:zoomRange': [range: ZoomRange]
  /** Общее изменение зума */
  'zoomchange': [event: { oldZoom: number; newZoom: number }]
  /** Увеличение зума кнопкой */
  'zoomin': [event: { zoom: number }]
  /** Уменьшение зума кнопкой */
  'zoomout': [event: { zoom: number }]
  /** Начало перетаскивания */
  'dragstart': [event: { zoom: number }]
  /** Перетаскивание */
  'drag': [event: { zoom: number }]
  /** Окончание перетаскивания */
  'dragend': [event: { zoom: number }]
  /** Ошибка */
  'error': [error: Error]
  /** Готовность контрола */
  'ready': [control: ZoomControl]
}

const emit = defineEmits<Emits>()

// Reactive состояние
const controlContainer: Ref<HTMLElement | null> = ref(null)
const zoomControlInstance: Ref<ZoomControl | null> = ref(null)
const isLoading: Ref<boolean> = ref(true)
const error: Ref<string | null> = ref(null)
const currentZoom: Ref<number> = ref(props.zoom)
const currentZoomRange: Ref<ZoomRange | null> = ref(props.zoomRange || null)

// Computed свойства
const shouldRender: ComputedRef<boolean> = computed(() => {
  return props.visible !== false
})

const containerClasses: ComputedRef<string[]> = computed(() => {
  const classes = ['ymaps-zoom-control-vue']
  
  if (props.size) {
    classes.push(`ymaps-zoom-control-vue--${props.size}`)
  }
  
  if (!props.enabled) {
    classes.push('ymaps-zoom-control-vue--disabled')
  }
  
  if (error.value) {
    classes.push('ymaps-zoom-control-vue--error')
  }
  
  if (isLoading.value) {
    classes.push('ymaps-zoom-control-vue--loading')
  }

  // Добавляем пользовательские классы
  if (props.class) {
    if (typeof props.class === 'string') {
      classes.push(...props.class.split(' '))
    } else if (Array.isArray(props.class)) {
      classes.push(...props.class)
    } else {
      Object.entries(props.class).forEach(([className, shouldAdd]) => {
        if (shouldAdd) classes.push(className)
      })
    }
  }
  
  return classes
})

const containerStyle: ComputedRef<Record<string, string | number>> = computed(() => {
  const style: Record<string, string | number> = {}
  
  if (props.zIndex) {
    style.zIndex = props.zIndex
  }

  // Применяем пользовательские стили
  if (props.style) {
    if (typeof props.style === 'string') {
      // Парсим строковые стили (упрощенная версия)
      props.style.split(';').forEach(rule => {
        const [property, value] = rule.split(':').map(s => s.trim())
        if (property && value) {
          style[property] = value
        }
      })
    } else {
      Object.assign(style, props.style)
    }
  }
  
  return style
})

const skeletonStyle: ComputedRef<Record<string, string>> = computed(() => {
  const sizeConfig = {
    small: { buttonSize: '28px', sliderHeight: '60px' },
    medium: { buttonSize: '34px', sliderHeight: '80px' },
    large: { buttonSize: '40px', sliderHeight: '100px' }
  }
  
  const config = sizeConfig[props.size] || sizeConfig.medium
  
  return {
    '--button-size': config.buttonSize,
    '--slider-height': config.sliderHeight
  }
})

const hasOverlay: ComputedRef<boolean> = computed(() => {
  return !props.enabled || !!error.value
})

const overlayClasses: ComputedRef<string[]> = computed(() => {
  const classes = []
  
  if (!props.enabled) {
    classes.push('overlay--disabled')
  }
  
  if (error.value) {
    classes.push('overlay--error')
  }
  
  return classes
})

// Методы для работы с контролом
const createControl = async (): Promise<void> => {
  try {
    if (!controlContainer.value || zoomControlInstance.value) {
      return
    }

    // Подготавливаем опции
    const options: ZoomControlOptions = {
      position: props.position,
      size: props.size,
      showSlider: props.showSlider,
      showButtons: props.showButtons,
      zoomDuration: props.zoomDuration,
      smooth: props.smooth,
      step: props.step,
      visible: props.visible,
      enabled: props.enabled,
      zIndex: props.zIndex,
      margin: props.margin
    }

    // Создаем экземпляр контрола
    const control = new ZoomControl(options)
    
    // Настраиваем обработчики событий
    setupControlEventHandlers(control)
    
    // Если есть карта, добавляем контрол на неё
    if (props.map) {
      await control.addToMap(props.map)
    }
    
    // Устанавливаем начальные значения
    if (props.zoomRange) {
      control.setZoomRange(props.zoomRange.min, props.zoomRange.max)
    }
    
    if (props.zoom !== control.getZoom()) {
      await control.setZoom(props.zoom)
    }

    zoomControlInstance.value = control
    isLoading.value = false
    error.value = null
    
    // Уведомляем о готовности
    emit('ready', control)
    
  } catch (err) {
    const errorMessage = err instanceof Error ? err.message : 'Неизвестная ошибка'
    error.value = errorMessage
    isLoading.value = false
    emit('error', err instanceof Error ? err : new Error(errorMessage))
  }
}

const setupControlEventHandlers = (control: ZoomControl): void => {
  // Изменение зума
  const handleZoomChange: ZoomControlEventHandler<'zoomchange'> = (event) => {
    currentZoom.value = event.newZoom
    emit('update:zoom', event.newZoom)
    emit('zoomchange', { oldZoom: event.oldZoom, newZoom: event.newZoom })
  }
  
  const handleZoomIn: ZoomControlEventHandler<'zoomin'> = (event) => {
    emit('zoomin', { zoom: event.zoom })
  }
  
  const handleZoomOut: ZoomControlEventHandler<'zoomout'> = (event) => {
    emit('zoomout', { zoom: event.zoom })
  }
  
  const handleDragStart: ZoomControlEventHandler<'dragstart'> = (event) => {
    emit('dragstart', { zoom: event.zoom })
  }
  
  const handleDrag: ZoomControlEventHandler<'drag'> = (event) => {
    emit('drag', { zoom: event.zoom })
  }
  
  const handleDragEnd: ZoomControlEventHandler<'dragend'> = (event) => {
    emit('dragend', { zoom: event.zoom })
  }

  // Привязываем обработчики
  control.on('zoomchange', handleZoomChange)
  control.on('zoomin', handleZoomIn)
  control.on('zoomout', handleZoomOut)
  control.on('dragstart', handleDragStart)
  control.on('drag', handleDrag)
  control.on('dragend', handleDragEnd)
}

const destroyControl = async (): Promise<void> => {
  if (zoomControlInstance.value) {
    try {
      zoomControlInstance.value.destroy()
    } catch (err) {
      console.error('ZoomControlVue: ошибка уничтожения контрола:', err)
    } finally {
      zoomControlInstance.value = null
    }
  }
}

const recreateControl = async (): Promise<void> => {
  await destroyControl()
  await nextTick()
  await createControl()
}

// Публичные методы компонента (через expose)
const getControl = (): ZoomControl | null => {
  return zoomControlInstance.value
}

const getZoom = (): number => {
  return zoomControlInstance.value?.getZoom() ?? currentZoom.value
}

const setZoom = async (zoom: number): Promise<void> => {
  if (zoomControlInstance.value) {
    await zoomControlInstance.value.setZoom(zoom)
  } else {
    currentZoom.value = zoom
  }
}

const zoomIn = async (): Promise<void> => {
  if (zoomControlInstance.value) {
    await zoomControlInstance.value.zoomIn()
  }
}

const zoomOut = async (): Promise<void> => {
  if (zoomControlInstance.value) {
    await zoomControlInstance.value.zoomOut()
  }
}

const getZoomRange = (): ZoomRange | null => {
  return zoomControlInstance.value?.getZoomRange() ?? currentZoomRange.value
}

const setZoomRange = (min: number, max: number): void => {
  if (zoomControlInstance.value) {
    zoomControlInstance.value.setZoomRange(min, max)
  } else {
    currentZoomRange.value = { min, max }
  }
}

// Expose методы для parent компонентов
defineExpose({
  getControl,
  getZoom,
  setZoom,
  zoomIn,
  zoomOut,
  getZoomRange,
  setZoomRange,
  recreate: recreateControl
})

// Watchers для реактивных обновлений
watch(() => props.map, async (newMap, oldMap) => {
  if (newMap !== oldMap) {
    if (zoomControlInstance.value) {
      if (oldMap) {
        await zoomControlInstance.value.removeFromMap()
      }
      if (newMap) {
        await zoomControlInstance.value.addToMap(newMap)
      }
    }
  }
}, { immediate: false })

watch(() => props.visible, (visible) => {
  if (zoomControlInstance.value) {
    if (visible) {
      zoomControlInstance.value.show()
    } else {
      zoomControlInstance.value.hide()
    }
  }
})

watch(() => props.enabled, (enabled) => {
  if (zoomControlInstance.value) {
    if (enabled) {
      zoomControlInstance.value.enable()
    } else {
      zoomControlInstance.value.disable()
    }
  }
})

watch(() => props.zoom, async (newZoom) => {
  if (zoomControlInstance.value && newZoom !== zoomControlInstance.value.getZoom()) {
    await zoomControlInstance.value.setZoom(newZoom)
  }
})

watch(() => props.zoomRange, (newRange) => {
  if (newRange && zoomControlInstance.value) {
    zoomControlInstance.value.setZoomRange(newRange.min, newRange.max)
  }
}, { deep: true })

// Watchers для пересоздания контрола при изменении критических опций
watch([
  () => props.size,
  () => props.position,
  () => props.showSlider,
  () => props.showButtons,
  () => props.margin
], async () => {
  if (zoomControlInstance.value) {
    await recreateControl()
  }
}, { deep: true })

// Lifecycle hooks
onMounted(async () => {
  await createControl()
})

onBeforeUnmount(async () => {
  await destroyControl()
})
</script>

<style scoped>
.ymaps-zoom-control-vue {
  position: relative;
  display: inline-block;
  pointer-events: auto;
}

.ymaps-zoom-control-vue--loading {
  pointer-events: none;
}

.ymaps-zoom-control-vue--disabled {
  opacity: 0.6;
}

.ymaps-zoom-control-vue--error {
  opacity: 0.8;
}

/* Skeleton loader стили */
.zoom-control-skeleton {
  display: flex;
  flex-direction: column;
  background: rgba(255, 255, 255, 0.9);
  border-radius: 3px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0,0,0,0.3);
}

.skeleton-button {
  width: var(--button-size, 34px);
  height: var(--button-size, 34px);
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
}

.skeleton-slider {
  width: var(--button-size, 34px);
  height: var(--slider-height, 80px);
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  animation-delay: 0.1s;
}

.skeleton-button + .skeleton-button,
.skeleton-button + .skeleton-slider,
.skeleton-slider + .skeleton-button {
  border-top: 1px solid #e0e0e0;
}

@keyframes shimmer {
  0% {
    background-position: -200% 0;
  }
  100% {
    background-position: 200% 0;
  }
}

/* Overlay стили */
.zoom-control-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.8);
  backdrop-filter: blur(2px);
  -webkit-backdrop-filter: blur(2px);
  border-radius: 3px;
  z-index: 1;
}

.overlay--disabled {
  cursor: not-allowed;
}

.overlay--error {
  background: rgba(255, 0, 0, 0.1);
}

.error-message {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 12px;
  color: #d32f2f;
  text-align: center;
  padding: 4px;
}

.error-icon {
  font-size: 14px;
}

.error-text {
  font-weight: 500;
}

/* Адаптивность */
@media (max-width: 768px) {
  .zoom-control-skeleton,
  .zoom-control-overlay {
    --button-size: 40px;
  }
  
  .error-message {
    font-size: 11px;
  }
}

/* Accessibility */
@media (prefers-reduced-motion: reduce) {
  .skeleton-button,
  .skeleton-slider {
    animation: none;
    background: #e0e0e0;
  }
}

/* Темная тема */
@media (prefers-color-scheme: dark) {
  .skeleton-button,
  .skeleton-slider {
    background: linear-gradient(90deg, #333 25%, #444 50%, #333 75%);
  }
  
  .zoom-control-overlay {
    background: rgba(0, 0, 0, 0.8);
  }
  
  .error-message {
    color: #f44336;
  }
}
</style>