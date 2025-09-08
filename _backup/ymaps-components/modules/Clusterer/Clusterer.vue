<template>
  <!-- Кластеризатор работает через JavaScript API, не требует DOM элементов -->
  <div v-if="false"></div>
</template>

<script setup lang="ts">
/**
 * Vue 3 компонент для кластеризации меток на Yandex Maps
 * Автоматически группирует близко расположенные метки
 * @module ClustererVue
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
import Clusterer from './Clusterer.js'
import type {
  ClustererOptions,
  ClusterPreset,
  FitToViewportOptions,
  ClusterIconData
} from './Clusterer'
import type { Placemark } from '../Placemark/Placemark'

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
   * Массив меток для кластеризации
   */
  placemarks: {
    type: Array as PropType<Placemark[]>,
    default: () => []
  },
  
  /**
   * Preset стиль кластеров
   */
  preset: {
    type: String as PropType<ClusterPreset>,
    default: 'islands#blueClusterIcons'
  },
  
  /**
   * Размер сетки кластеризации в пикселях
   */
  gridSize: {
    type: Number,
    default: 60,
    validator: (v: number) => v >= 10 && v <= 300
  },
  
  /**
   * Минимальное количество меток для создания кластера
   */
  minClusterSize: {
    type: Number,
    default: 2,
    validator: (v: number) => v >= 2 && v <= 100
  },
  
  /**
   * Максимальный зум для кластеризации
   */
  maxZoom: {
    type: Number,
    default: 16,
    validator: (v: number) => v >= 0 && v <= 23
  },
  
  /**
   * Отступ при клике на кластер
   */
  zoomMargin: {
    type: Number,
    default: 2
  },
  
  /**
   * Отступ между метками в кластере
   */
  margin: {
    type: Number,
    default: 10
  },
  
  /**
   * Показывать balloon у кластеров
   */
  hasBalloon: {
    type: Boolean,
    default: true
  },
  
  /**
   * Показывать хинты у кластеров
   */
  hasHint: {
    type: Boolean,
    default: true
  },
  
  /**
   * Отключить зум при клике на кластер
   */
  disableClickZoom: {
    type: Boolean,
    default: false
  },
  
  /**
   * Скрывать иконку при открытии balloon
   */
  hideIconOnBalloonOpen: {
    type: Boolean,
    default: false
  },
  
  /**
   * Сортировка меток в balloon по алфавиту
   */
  showInAlphabeticalOrder: {
    type: Boolean,
    default: false
  },
  
  /**
   * Учитывать отступы карты
   */
  useMapMargin: {
    type: Boolean,
    default: true
  },
  
  /**
   * Отступ от края viewport
   */
  viewportMargin: {
    type: Number,
    default: 128
  },
  
  /**
   * Максимальная площадь balloon в режиме панели
   */
  balloonPanelMaxMapArea: {
    type: Number,
    default: 700
  },
  
  /**
   * Максимальная высота balloon
   */
  balloonMaxHeight: {
    type: Number,
    default: 200
  },
  
  /**
   * Размер пейджера в balloon
   */
  balloonPagerSize: {
    type: Number,
    default: 5
  },
  
  /**
   * Автоматически центрировать карту по всем меткам
   */
  autoFit: {
    type: Boolean,
    default: false
  },
  
  /**
   * Задержка автоматического центрирования (мс)
   */
  autoFitDelay: {
    type: Number,
    default: 100
  },
  
  /**
   * Кастомная функция для расчета иконки кластера
   */
  calculateClusterIcon: {
    type: Function as PropType<(placemarks: Placemark[]) => ClusterIconData | null>,
    default: null
  },
  
  /**
   * Кастомная функция для содержимого balloon
   */
  createBalloonContent: {
    type: Function as PropType<(placemarks: Placemark[]) => string | HTMLElement>,
    default: null
  },
  
  /**
   * Кастомная функция для хинта
   */
  createHintContent: {
    type: Function as PropType<(placemarks: Placemark[]) => string>,
    default: null
  },
  
  /**
   * Кастомный макет иконки кластера
   */
  clusterIconLayout: {
    type: String,
    default: null
  },
  
  /**
   * Кастомный макет содержимого иконки
   */
  clusterIconContentLayout: {
    type: String,
    default: null
  },
  
  /**
   * Кастомный макет balloon кластера
   */
  clusterBalloonContentLayout: {
    type: String,
    default: null
  }
})

// Emits
const emit = defineEmits<{
  'ready': [clusterer: Clusterer]
  'clusterAdd': [cluster: any]
  'clusterRemove': [cluster: any]
  'clusterClick': [cluster: any, event: any]
  'boundsChange': [bounds: [[number, number], [number, number]] | null]
  'placemarksChange': [count: number]
  'error': [error: Error]
}>()

// Refs
const clustererInstance = ref<Clusterer | null>(null)
const isReady = ref(false)
const currentPlacemarksCount = ref(0)
const autoFitTimeout = ref<number | null>(null)

// Computed
const clustererOptions = computed<ClustererOptions>(() => ({
  preset: props.preset,
  gridSize: props.gridSize,
  minClusterSize: props.minClusterSize,
  maxZoom: props.maxZoom,
  zoomMargin: props.zoomMargin,
  margin: props.margin,
  hasBalloon: props.hasBalloon,
  hasHint: props.hasHint,
  clusterDisableClickZoom: props.disableClickZoom,
  clusterHideIconOnBalloonOpen: props.hideIconOnBalloonOpen,
  showInAlphabeticalOrder: props.showInAlphabeticalOrder,
  useMapMargin: props.useMapMargin,
  viewportMargin: props.viewportMargin,
  clusterBalloonPanelMaxMapArea: props.balloonPanelMaxMapArea,
  clusterBalloonMaxHeight: props.balloonMaxHeight,
  clusterBalloonPagerSize: props.balloonPagerSize,
  calculateClusterIcon: props.calculateClusterIcon,
  createBalloonContent: props.createBalloonContent,
  createHintContent: props.createHintContent,
  clusterIconLayout: props.clusterIconLayout,
  clusterIconContentLayout: props.clusterIconContentLayout,
  clusterBalloonContentLayout: props.clusterBalloonContentLayout,
  
  // Callbacks
  onClusterAdd: (cluster: any) => {
    emit('clusterAdd', cluster)
  },
  onClusterRemove: (cluster: any) => {
    emit('clusterRemove', cluster)
  },
  onClusterClick: (cluster: any, event: any) => {
    emit('clusterClick', cluster, event)
  }
}))

// Methods
const createClusterer = async () => {
  try {
    if (!props.map) {
      throw new Error('Map instance is required')
    }
    
    // Создаем кластеризатор
    clustererInstance.value = new Clusterer(props.map, clustererOptions.value)
    
    // Ждем готовности
    await waitForReady()
    
    // Добавляем начальные метки
    if (props.placemarks.length > 0) {
      await addPlacemarks(props.placemarks)
    }
    
    isReady.value = true
    emit('ready', clustererInstance.value)
    
    // Автоматическое центрирование
    if (props.autoFit && props.placemarks.length > 0) {
      scheduleAutoFit()
    }
    
  } catch (error) {
    console.error('Ошибка создания кластеризатора:', error)
    emit('error', error as Error)
  }
}

const waitForReady = (): Promise<void> => {
  return new Promise((resolve) => {
    if (!clustererInstance.value) {
      resolve()
      return
    }
    
    const checkInterval = setInterval(() => {
      if (clustererInstance.value?.isReady()) {
        clearInterval(checkInterval)
        resolve()
      }
    }, 50)
    
    // Таймаут на случай проблем
    setTimeout(() => {
      clearInterval(checkInterval)
      resolve()
    }, 5000)
  })
}

const addPlacemarks = async (placemarks: Placemark[]) => {
  if (!clustererInstance.value || placemarks.length === 0) return
  
  try {
    await clustererInstance.value.add(placemarks)
    currentPlacemarksCount.value = clustererInstance.value.getPlacemarksCount()
    emit('placemarksChange', currentPlacemarksCount.value)
  } catch (error) {
    console.error('Ошибка добавления меток:', error)
    emit('error', error as Error)
  }
}

const removePlacemarks = async (placemarks: Placemark[]) => {
  if (!clustererInstance.value || placemarks.length === 0) return
  
  try {
    await clustererInstance.value.remove(placemarks)
    currentPlacemarksCount.value = clustererInstance.value.getPlacemarksCount()
    emit('placemarksChange', currentPlacemarksCount.value)
  } catch (error) {
    console.error('Ошибка удаления меток:', error)
    emit('error', error as Error)
  }
}

const updatePlacemarks = async (newPlacemarks: Placemark[], oldPlacemarks: Placemark[]) => {
  if (!clustererInstance.value) return
  
  try {
    // Находим разницу
    const toRemove = oldPlacemarks.filter(pm => !newPlacemarks.includes(pm))
    const toAdd = newPlacemarks.filter(pm => !oldPlacemarks.includes(pm))
    
    // Удаляем старые
    if (toRemove.length > 0) {
      await removePlacemarks(toRemove)
    }
    
    // Добавляем новые
    if (toAdd.length > 0) {
      await addPlacemarks(toAdd)
    }
    
    // Автоцентрирование при изменении
    if (props.autoFit) {
      scheduleAutoFit()
    }
    
  } catch (error) {
    console.error('Ошибка обновления меток:', error)
    emit('error', error as Error)
  }
}

const scheduleAutoFit = () => {
  // Отменяем предыдущий таймаут
  if (autoFitTimeout.value) {
    clearTimeout(autoFitTimeout.value)
  }
  
  // Планируем новый
  autoFitTimeout.value = window.setTimeout(() => {
    fitToViewport()
    autoFitTimeout.value = null
  }, props.autoFitDelay)
}

const fitToViewport = async (options?: FitToViewportOptions) => {
  if (!clustererInstance.value) return
  
  try {
    await clustererInstance.value.fitToViewport(options)
    
    const bounds = clustererInstance.value.getBounds()
    emit('boundsChange', bounds)
  } catch (error) {
    console.error('Ошибка центрирования карты:', error)
    emit('error', error as Error)
  }
}

const refresh = () => {
  if (!clustererInstance.value) return
  clustererInstance.value.refresh()
}

const removeAll = async () => {
  if (!clustererInstance.value) return
  
  try {
    await clustererInstance.value.removeAll()
    currentPlacemarksCount.value = 0
    emit('placemarksChange', 0)
  } catch (error) {
    console.error('Ошибка удаления всех меток:', error)
    emit('error', error as Error)
  }
}

const getBounds = () => {
  if (!clustererInstance.value) return null
  return clustererInstance.value.getBounds()
}

const getPlacemarksCount = () => {
  if (!clustererInstance.value) return 0
  return clustererInstance.value.getPlacemarksCount()
}

const getClustererInstance = () => clustererInstance.value

// Lifecycle
onMounted(async () => {
  await nextTick()
  await createClusterer()
})

onUnmounted(() => {
  if (autoFitTimeout.value) {
    clearTimeout(autoFitTimeout.value)
  }
  
  if (clustererInstance.value) {
    clustererInstance.value.destroy()
    clustererInstance.value = null
  }
})

// Watchers
watch(() => props.placemarks, (newVal, oldVal) => {
  if (isReady.value) {
    updatePlacemarks(newVal, oldVal || [])
  }
}, { deep: true })

watch(() => props.preset, (newVal) => {
  if (clustererInstance.value) {
    clustererInstance.value.setPreset(newVal)
  }
})

watch(() => props.gridSize, (newVal) => {
  if (clustererInstance.value) {
    clustererInstance.value.setGridSize(newVal)
  }
})

watch(() => props.minClusterSize, (newVal) => {
  if (clustererInstance.value) {
    clustererInstance.value.setMinClusterSize(newVal)
  }
})

watch(() => props.hasBalloon, (newVal) => {
  if (clustererInstance.value) {
    if (newVal) {
      clustererInstance.value.enableBalloon()
    } else {
      clustererInstance.value.disableBalloon()
    }
  }
})

watch(() => props.hasHint, (newVal) => {
  if (clustererInstance.value) {
    if (newVal) {
      clustererInstance.value.enableHint()
    } else {
      clustererInstance.value.disableHint()
    }
  }
})

watch(() => props.maxZoom, (newVal) => {
  if (clustererInstance.value) {
    clustererInstance.value.setOptions({ maxZoom: newVal })
    clustererInstance.value.refresh()
  }
})

watch(() => props.disableClickZoom, (newVal) => {
  if (clustererInstance.value) {
    clustererInstance.value.setOptions({ clusterDisableClickZoom: newVal })
  }
})

// Expose public methods
defineExpose({
  fitToViewport,
  refresh,
  removeAll,
  getBounds,
  getPlacemarksCount,
  getClustererInstance,
  addPlacemarks,
  removePlacemarks
})
</script>

<style scoped>
/* Кластеризатор не требует стилей - работает через Yandex Maps API */
</style>