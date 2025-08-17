<template>
  <div 
    :id="containerId"
    class="widget-container"
    :class="containerClasses"
    :style="containerStyles"
  >
    <!-- Слот для кастомного layout -->
    <slot 
      v-if="$slots.default"
      :widgets="activeWidgets"
      :layout="layout"
      :manager="widgetManager"
    />
    
    <!-- Стандартный layout -->
    <component
      v-else
      :is="layoutComponent"
      :widgets="activeWidgets"
      :config="layout"
      :spacing="layout.spacing"
      @widget-error="handleWidgetError"
    >
      <template #widget="{ widget, index }">
        <WidgetWrapper
          :key="widget.id"
          :widget="widget"
          :container-id="containerId"
          :isolation="isolation"
          :debug="systemConfig.enableDebug"
          @mounted="handleWidgetMounted"
          @unmounted="handleWidgetUnmounted"
          @error="handleWidgetError"
          @interaction="handleWidgetInteraction"
        />
      </template>
    </component>
    
    <!-- Debug панель -->
    <Teleport to="body" v-if="showDebugPanel">
      <div class="widget-debug-panel" :class="{ 'debug-panel--expanded': debugExpanded }">
        <div class="debug-header" @click="toggleDebug">
          <svg width="20" height="20" viewBox="0 0 24 24" class="debug-icon">
            <path fill="currentColor" d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
          </svg>
          <span>Widget System</span>
          <span class="debug-count">{{ activeWidgets.length }}</span>
        </div>
        
        <Transition name="slide">
          <div v-if="debugExpanded" class="debug-content">
            <!-- Статистика контейнера -->
            <div class="debug-stats">
              <div class="stat-group">
                <h4>Container Stats</h4>
                <div class="stat-item">
                  <span class="stat-label">Active Widgets:</span>
                  <span class="stat-value">{{ activeWidgets.length }}</span>
                </div>
                <div class="stat-item">
                  <span class="stat-label">Layout Type:</span>
                  <span class="stat-value">{{ layout.type }}</span>
                </div>
                <div class="stat-item">
                  <span class="stat-label">Isolation:</span>
                  <span class="stat-value">{{ Object.entries(isolation).filter(([k,v]) => v).map(([k]) => k).join(', ') }}</span>
                </div>
              </div>
              
              <!-- Производительность -->
              <div class="stat-group" v-if="analytics.performance.size > 0">
                <h4>Performance</h4>
                <div class="stat-item">
                  <span class="stat-label">Avg Load Time:</span>
                  <span class="stat-value">{{ averageLoadTime }}ms</span>
                </div>
                <div class="stat-item">
                  <span class="stat-label">Memory Usage:</span>
                  <span class="stat-value">{{ totalMemoryUsage }}MB</span>
                </div>
              </div>
            </div>
            
            <!-- Список виджетов -->
            <div class="debug-widgets">
              <h4>Widgets</h4>
              <div class="widget-list">
                <div 
                  v-for="widget in activeWidgets"
                  :key="widget.id"
                  class="widget-item"
                  :class="`widget-item--${widget.state.status}`"
                >
                  <div class="widget-header">
                    <span class="widget-name">{{ widget.widgetId }}</span>
                    <span class="widget-version">v{{ widget.version }}</span>
                    <span class="widget-status" :class="`status-${widget.state.status}`">
                      {{ widget.state.status }}
                    </span>
                  </div>
                  <div class="widget-details">
                    <span class="widget-type">{{ getWidgetType(widget.widgetId) }}</span>
                    <span class="widget-size">{{ getWidgetSize(widget.id) }}KB</span>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Контролы -->
            <div class="debug-controls">
              <button @click="reloadAllWidgets" class="control-btn">
                Reload All
              </button>
              <button @click="clearAnalytics" class="control-btn">
                Clear Analytics
              </button>
              <button @click="exportDebugInfo" class="control-btn">
                Export Debug
              </button>
            </div>
          </div>
        </Transition>
      </div>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, provide, onMounted, onUnmounted, watch } from 'vue'
import WidgetWrapper from './WidgetWrapper.vue'
import GridLayout from './layouts/GridLayout.vue'
import FlexLayout from './layouts/FlexLayout.vue'
import StackLayout from './layouts/StackLayout.vue'
import AbsoluteLayout from './layouts/AbsoluteLayout.vue'
import { useWidgetManager } from './useWidgetManager'
import type {
  WidgetContainer as IWidgetContainer,
  WidgetInstance,
  LayoutConfig,
  IsolationConfig,
  WidgetSystemConfig,
  WidgetAnalytics
} from './WidgetSystem.types'
import { DEFAULT_WIDGET_CONFIG, WIDGET_SYSTEM_EVENTS } from './WidgetSystem.types'

interface Props {
  containerId: string
  widgets: string[]
  layout?: LayoutConfig
  isolation?: IsolationConfig
  config?: Partial<WidgetSystemConfig>
  debug?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  layout: () => DEFAULT_WIDGET_CONFIG.defaultLayout!,
  isolation: () => DEFAULT_WIDGET_CONFIG.defaultIsolation!,
  config: () => ({}),
  debug: false
})

const emit = defineEmits<{
  'container-ready': [container: IWidgetContainer]
  'widget-loaded': [widget: WidgetInstance]
  'widget-error': [error: Error, widgetId: string]
  'analytics-update': [analytics: WidgetAnalytics]
}>()

// Композабл менеджера виджетов
const widgetManager = useWidgetManager({
  ...DEFAULT_WIDGET_CONFIG,
  ...props.config,
  enableDebug: props.debug
})

// Состояние
const container = ref<IWidgetContainer>()
const debugExpanded = ref(false)
const analytics = ref<WidgetAnalytics>({
  usage: new Map(),
  performance: new Map(),
  errors: new Map()
})

// Provide для дочерних компонентов
provide('widgetManager', widgetManager)
provide('containerId', props.containerId)
provide('isolation', props.isolation)

// Computed
const systemConfig = computed(() => ({
  ...DEFAULT_WIDGET_CONFIG,
  ...props.config
}))

const showDebugPanel = computed(() => 
  props.debug && systemConfig.value.enableDebug
)

const activeWidgets = computed(() => {
  return container.value?.widgets.filter(w => w.state.status !== 'destroyed') || []
})

const containerClasses = computed(() => [
  'widget-container',
  `layout-${props.layout.type}`,
  {
    'container--responsive': props.layout.responsive,
    'container--debug': props.debug,
    'container--isolated': Object.values(props.isolation).some(Boolean)
  }
])

const containerStyles = computed(() => {
  const styles: any = {}
  
  if (props.layout.spacing) {
    styles['--container-gap'] = `${props.layout.spacing.gap}px`
    styles['--container-padding'] = `${props.layout.spacing.padding}px`
    styles['--container-margin'] = `${props.layout.spacing.margin}px`
  }
  
  if (props.isolation.css) {
    styles.isolation = 'isolate'
  }
  
  return styles
})

const layoutComponent = computed(() => {
  switch (props.layout.type) {
    case 'grid': return GridLayout
    case 'flex': return FlexLayout
    case 'stack': return StackLayout
    case 'absolute': return AbsoluteLayout
    default: return FlexLayout
  }
})

const averageLoadTime = computed(() => {
  const performances = Array.from(analytics.value.performance.values())
  if (performances.length === 0) return 0
  
  const total = performances.reduce((sum, p) => sum + p.loadTime, 0)
  return Math.round(total / performances.length)
})

const totalMemoryUsage = computed(() => {
  const performances = Array.from(analytics.value.performance.values())
  const total = performances.reduce((sum, p) => sum + (p.memoryUsage || 0), 0)
  return (total / 1024 / 1024).toFixed(2) // Convert to MB
})

// Методы
const initializeContainer = async () => {
  try {
    container.value = await widgetManager.createContainer({
      id: props.containerId,
      widgets: [],
      layout: props.layout,
      isolation: props.isolation,
      communication: {
        eventBus: true,
        postMessage: props.isolation.js,
        sharedState: !props.isolation.js,
        api: true
      }
    })
    
    // Загрузка виджетов
    for (const widgetId of props.widgets) {
      await loadWidget(widgetId)
    }
    
    emit('container-ready', container.value)
  } catch (error) {
    console.error('Failed to initialize container:', error)
    handleWidgetError(error as Error, 'container')
  }
}

const loadWidget = async (widgetId: string, version?: string) => {
  try {
    const widget = await widgetManager.loadWidget(widgetId, version)
    const instance = await widgetManager.createInstance(widget, {
      containerId: props.containerId
    })
    
    if (container.value) {
      container.value.widgets.push(instance)
      emit('widget-loaded', instance)
      
      // Обновляем аналитику
      updateAnalytics(instance)
    }
  } catch (error) {
    console.error(`Failed to load widget ${widgetId}:`, error)
    handleWidgetError(error as Error, widgetId)
  }
}

const unloadWidget = async (widgetId: string) => {
  if (!container.value) return
  
  const index = container.value.widgets.findIndex(w => w.id === widgetId)
  if (index !== -1) {
    const widget = container.value.widgets[index]
    await widgetManager.destroyInstance(widget)
    container.value.widgets.splice(index, 1)
    
    // Удаляем из аналитики
    analytics.value.usage.delete(widgetId)
    analytics.value.performance.delete(widgetId)
    analytics.value.errors.delete(widgetId)
  }
}

const reloadWidget = async (widgetId: string) => {
  await unloadWidget(widgetId)
  await loadWidget(widgetId)
}

const reloadAllWidgets = async () => {
  if (!container.value) return
  
  const widgetIds = container.value.widgets.map(w => w.widgetId)
  
  // Удаляем все виджеты
  for (const widget of [...container.value.widgets]) {
    await widgetManager.destroyInstance(widget)
  }
  container.value.widgets = []
  
  // Загружаем заново
  for (const widgetId of widgetIds) {
    await loadWidget(widgetId)
  }
}

const updateAnalytics = (instance: WidgetInstance) => {
  const widgetId = instance.widgetId
  
  // Usage stats
  const usage = analytics.value.usage.get(widgetId) || {
    loads: 0,
    renders: 0,
    interactions: 0,
    timeSpent: 0,
    lastUsed: new Date()
  }
  usage.loads++
  usage.lastUsed = new Date()
  analytics.value.usage.set(widgetId, usage)
  
  // Performance stats
  const performance = analytics.value.performance.get(widgetId) || {
    loadTime: 0,
    renderTime: 0,
    memoryUsage: 0,
    bundleSize: 0,
    cacheHits: 0
  }
  analytics.value.performance.set(widgetId, performance)
  
  emit('analytics-update', analytics.value)
}

const clearAnalytics = () => {
  analytics.value = {
    usage: new Map(),
    performance: new Map(),
    errors: new Map()
  }
}

const exportDebugInfo = () => {
  const debugInfo = {
    container: container.value,
    analytics: {
      usage: Object.fromEntries(analytics.value.usage),
      performance: Object.fromEntries(analytics.value.performance),
      errors: Object.fromEntries(analytics.value.errors)
    },
    config: systemConfig.value,
    timestamp: Date.now()
  }
  
  const blob = new Blob([JSON.stringify(debugInfo, null, 2)], { type: 'application/json' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `widget-debug-${props.containerId}-${Date.now()}.json`
  a.click()
  URL.revokeObjectURL(url)
}

const getWidgetType = (widgetId: string): string => {
  const widget = widgetManager.registry.widgets.get(widgetId)
  return widget?.type || 'unknown'
}

const getWidgetSize = (instanceId: string): number => {
  const performance = analytics.value.performance.get(instanceId)
  return Math.round((performance?.bundleSize || 0) / 1024)
}

// Обработчики событий
const handleWidgetMounted = (instance: WidgetInstance) => {
  const usage = analytics.value.usage.get(instance.widgetId)
  if (usage) {
    usage.renders++
    analytics.value.usage.set(instance.widgetId, usage)
  }
  
  widgetManager.eventBus.emit(WIDGET_SYSTEM_EVENTS.WIDGET_MOUNTED, instance)
}

const handleWidgetUnmounted = (instance: WidgetInstance) => {
  widgetManager.eventBus.emit(WIDGET_SYSTEM_EVENTS.WIDGET_UNMOUNTED, instance)
}

const handleWidgetError = (error: Error, widgetId: string) => {
  console.error(`Widget error in ${widgetId}:`, error)
  
  // Обновляем статистику ошибок
  const errorStats = analytics.value.errors.get(widgetId) || {
    count: 0,
    types: {},
    lastError: error,
    resolution: 'pending'
  }
  errorStats.count++
  errorStats.lastError = error
  errorStats.types[error.name] = (errorStats.types[error.name] || 0) + 1
  analytics.value.errors.set(widgetId, errorStats)
  
  emit('widget-error', error, widgetId)
  widgetManager.eventBus.emit(WIDGET_SYSTEM_EVENTS.WIDGET_ERROR, { error, widgetId })
}

const handleWidgetInteraction = (event: any) => {
  const usage = analytics.value.usage.get(event.widgetId)
  if (usage) {
    usage.interactions++
    analytics.value.usage.set(event.widgetId, usage)
  }
}

const toggleDebug = () => {
  debugExpanded.value = !debugExpanded.value
}

// Watchers
watch(() => props.widgets, async (newWidgets, oldWidgets) => {
  if (!container.value) return
  
  // Определяем какие виджеты добавить/удалить
  const toAdd = newWidgets.filter(id => !oldWidgets?.includes(id))
  const toRemove = oldWidgets?.filter(id => !newWidgets.includes(id)) || []
  
  // Удаляем
  for (const widgetId of toRemove) {
    const instance = container.value.widgets.find(w => w.widgetId === widgetId)
    if (instance) {
      await unloadWidget(instance.id)
    }
  }
  
  // Добавляем
  for (const widgetId of toAdd) {
    await loadWidget(widgetId)
  }
}, { deep: true })

// Lifecycle
onMounted(() => {
  initializeContainer()
})

onUnmounted(() => {
  if (container.value) {
    widgetManager.destroyContainer(container.value.id)
  }
})

// Экспорт методов
defineExpose({
  loadWidget,
  unloadWidget,
  reloadWidget,
  reloadAllWidgets,
  getAnalytics: () => analytics.value,
  getContainer: () => container.value
})
</script>

<style scoped>
/* Основной контейнер */
.widget-container {
  position: relative;
  width: 100%;
  height: 100%;
  padding: var(--container-padding, 16px);
  margin: var(--container-margin, 0);
}

/* Layout типы */
.layout-grid {
  display: grid;
  gap: var(--container-gap, 16px);
}

.layout-flex {
  display: flex;
  gap: var(--container-gap, 16px);
}

.layout-stack {
  display: flex;
  flex-direction: column;
  gap: var(--container-gap, 16px);
}

.layout-absolute {
  position: relative;
}

/* Responsive */
.container--responsive {
  width: 100%;
  max-width: 100%;
}

@media (max-width: 768px) {
  .container--responsive.layout-grid {
    grid-template-columns: 1fr;
  }
  
  .container--responsive.layout-flex {
    flex-direction: column;
  }
}

/* Debug режим */
.container--debug {
  border: 2px dashed #ffa500;
  background: rgba(255, 165, 0, 0.05);
}

.container--debug::before {
  content: 'DEBUG CONTAINER';
  position: absolute;
  top: -2px;
  left: -2px;
  padding: 2px 8px;
  background: #ffa500;
  color: white;
  font-size: 10px;
  font-weight: bold;
  z-index: 1000;
}

/* Изоляция */
.container--isolated {
  contain: layout style paint;
}

/* Debug панель */
.widget-debug-panel {
  position: fixed;
  top: 20px;
  right: 20px;
  width: 400px;
  max-width: 90vw;
  background: white;
  border: 1px solid #e1e3e6;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  z-index: 9999;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  font-size: 14px;
}

.debug-panel--expanded {
  height: 600px;
  max-height: 80vh;
  display: flex;
  flex-direction: column;
}

/* Debug header */
.debug-header {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 16px;
  background: #f6f7f8;
  border-bottom: 1px solid #e1e3e6;
  border-radius: 8px 8px 0 0;
  cursor: pointer;
  user-select: none;
}

.debug-icon {
  color: #ffa500;
}

.debug-count {
  margin-left: auto;
  padding: 2px 8px;
  background: #005bff;
  color: white;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
}

/* Debug content */
.debug-content {
  flex: 1;
  overflow-y: auto;
  padding: 16px;
}

/* Статистика */
.debug-stats {
  display: flex;
  flex-direction: column;
  gap: 16px;
  margin-bottom: 16px;
}

.stat-group {
  padding: 12px;
  background: #f6f7f8;
  border-radius: 6px;
}

.stat-group h4 {
  margin: 0 0 8px;
  font-size: 14px;
  font-weight: 600;
  color: #001a34;
}

.stat-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 4px;
}

.stat-label {
  color: #70757a;
  font-size: 12px;
}

.stat-value {
  font-weight: 600;
  color: #001a34;
  font-size: 12px;
}

/* Список виджетов */
.debug-widgets h4 {
  margin: 0 0 12px;
  font-size: 14px;
  font-weight: 600;
  color: #001a34;
}

.widget-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
  max-height: 200px;
  overflow-y: auto;
}

.widget-item {
  padding: 8px;
  background: #f6f7f8;
  border-radius: 6px;
  border-left: 3px solid transparent;
}

.widget-item--ready {
  border-left-color: #00a854;
}

.widget-item--loading {
  border-left-color: #ffa500;
}

.widget-item--error {
  border-left-color: #f91155;
}

.widget-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 4px;
}

.widget-name {
  font-weight: 600;
  color: #001a34;
  font-size: 13px;
}

.widget-version {
  color: #70757a;
  font-size: 11px;
}

.widget-status {
  margin-left: auto;
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 10px;
  font-weight: 600;
  text-transform: uppercase;
}

.status-ready {
  background: #d4edda;
  color: #155724;
}

.status-loading {
  background: #fff3cd;
  color: #856404;
}

.status-error {
  background: #f8d7da;
  color: #721c24;
}

.widget-details {
  display: flex;
  gap: 12px;
  font-size: 11px;
  color: #9ca0a5;
}

/* Контролы */
.debug-controls {
  display: flex;
  gap: 8px;
  padding-top: 16px;
  border-top: 1px solid #e1e3e6;
  margin-top: 16px;
}

.control-btn {
  flex: 1;
  padding: 8px 12px;
  background: #005bff;
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.control-btn:hover {
  background: #0046cc;
}

/* Анимации */
.slide-enter-active,
.slide-leave-active {
  transition: all 0.3s ease;
}

.slide-enter-from {
  max-height: 0;
  opacity: 0;
}

.slide-leave-to {
  max-height: 0;
  opacity: 0;
}
</style>