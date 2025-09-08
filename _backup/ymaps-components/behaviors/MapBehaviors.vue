<template>
  <!-- Менеджер поведений работает через JavaScript API, не требует DOM элементов -->
  <div v-if="false"></div>
</template>

<script setup lang="ts">
/**
 * Vue 3 компонент для управления поведениями карты Yandex Maps
 * Позволяет гибко настраивать интерактивные возможности карты
 * @module MapBehaviorsVue
 */
import {
  ref,
  computed,
  watch,
  onMounted,
  onUnmounted,
  nextTick,
  type PropType
} from 'vue'
import MapBehaviors from './MapBehaviors.js'
import type {
  MapBehaviorsOptions,
  BehaviorType,
  BehaviorsState,
  DragOptions,
  ZoomOptions,
  MultiTouchOptions,
  ScrollZoomOptions
} from './MapBehaviors'

// Props определение
const props = defineProps({
  /**
   * Экземпляр карты Yandex Maps
   */
  map: {
    type: Object as PropType<any>,
    required: true
  },
  
  /**
   * Перетаскивание карты
   */
  drag: {
    type: Boolean,
    default: true
  },
  
  /**
   * Масштабирование двойным кликом
   */
  dblClickZoom: {
    type: Boolean,
    default: true
  },
  
  /**
   * Мультитач жесты
   */
  multiTouch: {
    type: Boolean,
    default: true
  },
  
  /**
   * Масштабирование колесом мыши
   */
  scrollZoom: {
    type: Boolean,
    default: true
  },
  
  /**
   * Лупа правой кнопкой мыши
   */
  rightMouseMagnifier: {
    type: Boolean,
    default: true
  },
  
  /**
   * Лупа левой кнопкой мыши
   */
  leftMouseMagnifier: {
    type: Boolean,
    default: false
  },
  
  /**
   * Измерение расстояний
   */
  ruler: {
    type: Boolean,
    default: false
  },
  
  /**
   * Редактирование маршрутов
   */
  routeEditor: {
    type: Boolean,
    default: false
  },
  
  /**
   * Опции перетаскивания
   */
  dragOptions: {
    type: Object as PropType<DragOptions>,
    default: () => ({
      inertia: true,
      inertiaDuration: 500,
      cursor: 'grab',
      cursorDragging: 'grabbing'
    })
  },
  
  /**
   * Опции масштабирования
   */
  zoomOptions: {
    type: Object as PropType<ZoomOptions>,
    default: () => ({
      smooth: true,
      duration: 300,
      centering: true
    })
  },
  
  /**
   * Опции мультитач
   */
  multiTouchOptions: {
    type: Object as PropType<MultiTouchOptions>,
    default: () => ({
      tremor: 4,
      preventDefaultAction: true
    })
  },
  
  /**
   * Опции скролла
   */
  scrollZoomOptions: {
    type: Object as PropType<ScrollZoomOptions>,
    default: () => ({
      speed: 1.2,
      smooth: true,
      centering: true
    })
  },
  
  /**
   * Ограничение области карты
   */
  restrictMapArea: {
    type: Array as PropType<[[number, number], [number, number]]>,
    default: null
  },
  
  /**
   * Ограничение диапазона зума [min, max]
   */
  restrictZoomRange: {
    type: Array as PropType<[number, number]>,
    default: null,
    validator: (v: [number, number]) => {
      if (!v) return true
      return v[0] >= 0 && v[1] <= 23 && v[0] < v[1]
    }
  },
  
  /**
   * Начальное состояние блокировки карты
   */
  locked: {
    type: Boolean,
    default: false
  },
  
  /**
   * Отключить все поведения при монтировании
   */
  disableOnMount: {
    type: Boolean,
    default: false
  },
  
  /**
   * Включить мобильную оптимизацию
   */
  mobileOptimization: {
    type: Boolean,
    default: true
  },
  
  /**
   * Показывать подсказки при использовании
   */
  showHints: {
    type: Boolean,
    default: false
  },
  
  /**
   * Задержка срабатывания событий (мс)
   */
  eventThrottle: {
    type: Number,
    default: 100
  }
})

// Emits
const emit = defineEmits<{
  'ready': [manager: MapBehaviors]
  'behaviorEnabled': [behavior: BehaviorType]
  'behaviorDisabled': [behavior: BehaviorType]
  'dragStart': [event: any]
  'drag': [event: any]
  'dragEnd': [event: any]
  'zoomStart': [event: any]
  'zoomChange': [event: any]
  'zoomEnd': [event: any]
  'locked': []
  'unlocked': []
  'stateChange': [state: BehaviorsState]
  'error': [error: Error]
}>()

// Refs
const behaviorManager = ref<MapBehaviors | null>(null)
const isReady = ref(false)
const currentState = ref<BehaviorsState | null>(null)
const isMobile = ref(false)
const throttleTimers = ref<Map<string, number>>(new Map())

// Computed
const behaviorOptions = computed<MapBehaviorsOptions>(() => ({
  drag: props.drag,
  dblClickZoom: props.dblClickZoom,
  multiTouch: props.multiTouch,
  scrollZoom: props.scrollZoom,
  rightMouseButtonMagnifier: props.rightMouseMagnifier,
  leftMouseButtonMagnifier: props.leftMouseMagnifier,
  ruler: props.ruler,
  routeEditor: props.routeEditor,
  dragOptions: props.dragOptions,
  zoomOptions: props.zoomOptions,
  multiTouchOptions: props.multiTouchOptions,
  scrollZoomOptions: props.scrollZoomOptions,
  restrictMapArea: props.restrictMapArea,
  restrictZoomRange: props.restrictZoomRange,
  
  // Callbacks с throttle
  onBehaviorEnabled: (behavior: BehaviorType) => {
    throttleEmit('behaviorEnabled', behavior)
  },
  onBehaviorDisabled: (behavior: BehaviorType) => {
    throttleEmit('behaviorDisabled', behavior)
  },
  onDragStart: (event: any) => {
    emit('dragStart', event)
    updateState()
  },
  onDrag: (event: any) => {
    throttleEmit('drag', event)
  },
  onDragEnd: (event: any) => {
    emit('dragEnd', event)
    updateState()
  },
  onZoomStart: (event: any) => {
    emit('zoomStart', event)
    updateState()
  },
  onZoomChange: (event: any) => {
    throttleEmit('zoomChange', event)
  },
  onZoomEnd: (event: any) => {
    emit('zoomEnd', event)
    updateState()
  }
}))

// Methods
const detectMobile = () => {
  isMobile.value = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(
    navigator.userAgent
  ) || window.innerWidth < 768
}

const throttleEmit = (event: string, data: any) => {
  if (props.eventThrottle <= 0) {
    emit(event as any, data)
    return
  }
  
  const existingTimer = throttleTimers.value.get(event)
  if (existingTimer) {
    clearTimeout(existingTimer)
  }
  
  const timer = window.setTimeout(() => {
    emit(event as any, data)
    throttleTimers.value.delete(event)
  }, props.eventThrottle)
  
  throttleTimers.value.set(event, timer)
}

const createBehaviorManager = async () => {
  try {
    if (!props.map) {
      throw new Error('Map instance is required')
    }
    
    // Детектим мобильное устройство
    detectMobile()
    
    // Применяем мобильную оптимизацию
    const options = { ...behaviorOptions.value }
    
    if (props.mobileOptimization && isMobile.value) {
      // Отключаем тяжелые функции на мобильных
      options.rightMouseButtonMagnifier = false
      options.leftMouseButtonMagnifier = false
      options.ruler = false
      options.routeEditor = false
      
      // Оптимизируем настройки
      if (options.dragOptions) {
        options.dragOptions.inertiaDuration = 300
      }
      if (options.zoomOptions) {
        options.zoomOptions.duration = 200
      }
    }
    
    // Создаем менеджер поведений
    behaviorManager.value = new MapBehaviors(props.map, options)
    
    // Применяем начальные настройки
    if (props.disableOnMount) {
      behaviorManager.value.disableAll()
    }
    
    if (props.locked) {
      behaviorManager.value.lock()
    }
    
    isReady.value = true
    currentState.value = behaviorManager.value.getState()
    
    emit('ready', behaviorManager.value)
    
  } catch (error) {
    console.error('Ошибка создания behavior manager:', error)
    emit('error', error as Error)
  }
}

const updateState = () => {
  if (!behaviorManager.value) return
  
  currentState.value = behaviorManager.value.getState()
  emit('stateChange', currentState.value)
}

const enable = (behavior: BehaviorType | BehaviorType[]) => {
  if (!behaviorManager.value) return
  behaviorManager.value.enable(behavior)
  updateState()
}

const disable = (behavior: BehaviorType | BehaviorType[]) => {
  if (!behaviorManager.value) return
  behaviorManager.value.disable(behavior)
  updateState()
}

const enableDrag = () => {
  if (!behaviorManager.value) return
  behaviorManager.value.enableDrag()
  updateState()
}

const disableDrag = () => {
  if (!behaviorManager.value) return
  behaviorManager.value.disableDrag()
  updateState()
}

const enableScrollZoom = () => {
  if (!behaviorManager.value) return
  behaviorManager.value.enableScrollZoom()
  updateState()
}

const disableScrollZoom = () => {
  if (!behaviorManager.value) return
  behaviorManager.value.disableScrollZoom()
  updateState()
}

const enableAll = () => {
  if (!behaviorManager.value) return
  behaviorManager.value.enableAll()
  updateState()
}

const disableAll = () => {
  if (!behaviorManager.value) return
  behaviorManager.value.disableAll()
  updateState()
}

const lock = () => {
  if (!behaviorManager.value) return
  behaviorManager.value.lock()
  emit('locked')
  updateState()
}

const unlock = () => {
  if (!behaviorManager.value) return
  behaviorManager.value.unlock()
  emit('unlocked')
  updateState()
}

const isLocked = () => {
  if (!behaviorManager.value) return false
  return behaviorManager.value.isLocked()
}

const reset = () => {
  if (!behaviorManager.value) return
  behaviorManager.value.reset()
  updateState()
}

const setRestrictMapArea = (bounds: [[number, number], [number, number]]) => {
  if (!behaviorManager.value) return
  behaviorManager.value.setRestrictMapArea(bounds)
}

const removeRestrictMapArea = () => {
  if (!behaviorManager.value) return
  behaviorManager.value.removeRestrictMapArea()
}

const setZoomRange = (minZoom: number, maxZoom: number) => {
  if (!behaviorManager.value) return
  behaviorManager.value.setZoomRange(minZoom, maxZoom)
}

const removeZoomRange = () => {
  if (!behaviorManager.value) return
  behaviorManager.value.removeZoomRange()
}

const getEnabled = () => {
  if (!behaviorManager.value) return []
  return behaviorManager.value.getEnabled()
}

const isEnabled = (behavior: BehaviorType) => {
  if (!behaviorManager.value) return false
  return behaviorManager.value.isEnabled(behavior)
}

const getState = () => currentState.value

const getBehaviorManager = () => behaviorManager.value

// Lifecycle
onMounted(async () => {
  await nextTick()
  await createBehaviorManager()
  
  // Слушаем изменение размера окна для мобильной оптимизации
  if (props.mobileOptimization) {
    window.addEventListener('resize', detectMobile)
  }
})

onUnmounted(() => {
  // Очищаем throttle таймеры
  throttleTimers.value.forEach(timer => clearTimeout(timer))
  throttleTimers.value.clear()
  
  // Удаляем слушатель resize
  if (props.mobileOptimization) {
    window.removeEventListener('resize', detectMobile)
  }
  
  // Уничтожаем менеджер
  if (behaviorManager.value) {
    behaviorManager.value.destroy()
    behaviorManager.value = null
  }
})

// Watchers
watch(() => props.drag, (newVal) => {
  if (!behaviorManager.value) return
  if (newVal) {
    behaviorManager.value.enableDrag()
  } else {
    behaviorManager.value.disableDrag()
  }
})

watch(() => props.dblClickZoom, (newVal) => {
  if (!behaviorManager.value) return
  if (newVal) {
    behaviorManager.value.enableDblClickZoom()
  } else {
    behaviorManager.value.disableDblClickZoom()
  }
})

watch(() => props.multiTouch, (newVal) => {
  if (!behaviorManager.value) return
  if (newVal) {
    behaviorManager.value.enableMultiTouch()
  } else {
    behaviorManager.value.disableMultiTouch()
  }
})

watch(() => props.scrollZoom, (newVal) => {
  if (!behaviorManager.value) return
  if (newVal) {
    behaviorManager.value.enableScrollZoom()
  } else {
    behaviorManager.value.disableScrollZoom()
  }
})

watch(() => props.ruler, (newVal) => {
  if (!behaviorManager.value) return
  if (newVal) {
    behaviorManager.value.enableRuler()
  } else {
    behaviorManager.value.disableRuler()
  }
})

watch(() => props.locked, (newVal) => {
  if (!behaviorManager.value) return
  if (newVal) {
    lock()
  } else {
    unlock()
  }
})

watch(() => props.restrictMapArea, (newVal) => {
  if (!behaviorManager.value) return
  if (newVal) {
    behaviorManager.value.setRestrictMapArea(newVal)
  } else {
    behaviorManager.value.removeRestrictMapArea()
  }
}, { deep: true })

watch(() => props.restrictZoomRange, (newVal) => {
  if (!behaviorManager.value) return
  if (newVal) {
    behaviorManager.value.setZoomRange(newVal[0], newVal[1])
  } else {
    behaviorManager.value.removeZoomRange()
  }
})

// Expose public methods
defineExpose({
  enable,
  disable,
  enableDrag,
  disableDrag,
  enableScrollZoom,
  disableScrollZoom,
  enableAll,
  disableAll,
  lock,
  unlock,
  isLocked,
  reset,
  setRestrictMapArea,
  removeRestrictMapArea,
  setZoomRange,
  removeZoomRange,
  getEnabled,
  isEnabled,
  getState,
  getBehaviorManager
})
</script>

<style scoped>
/* Менеджер поведений не требует стилей - работает через Yandex Maps API */
</style>