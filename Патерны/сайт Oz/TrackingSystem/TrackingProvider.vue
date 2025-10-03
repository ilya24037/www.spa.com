<template>
  <div class="tracking-provider">
    <!-- Основной контент -->
    <slot />
    
    <!-- Debug панель (только в debug режиме) -->
    <Teleport to="body" v-if="showDebugPanel">
      <div class="tracking-debug-panel" :class="{ 'tracking-debug-panel--expanded': debugExpanded }">
        <div class="debug-header" @click="toggleDebug">
          <svg width="20" height="20" viewBox="0 0 24 24" class="debug-icon">
            <path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
          </svg>
          <span>Tracking Debug</span>
          <span class="debug-count">{{ eventCount }}</span>
          <button @click.stop="clearDebug" class="debug-clear">Clear</button>
        </div>
        
        <Transition name="slide">
          <div v-if="debugExpanded" class="debug-content">
            <!-- Статистика -->
            <div class="debug-stats">
              <div class="stat-item">
                <span class="stat-label">Total Events:</span>
                <span class="stat-value">{{ stats.totalEvents }}</span>
              </div>
              <div class="stat-item">
                <span class="stat-label">Success Rate:</span>
                <span class="stat-value">{{ stats.successRate.toFixed(1) }}%</span>
              </div>
              <div class="stat-item">
                <span class="stat-label">Queue Size:</span>
                <span class="stat-value">{{ eventQueue.events.length }}</span>
              </div>
              <div class="stat-item">
                <span class="stat-label">Errors:</span>
                <span class="stat-value" :class="{ 'stat-error': stats.errors > 0 }">
                  {{ stats.errors }}
                </span>
              </div>
            </div>
            
            <!-- Последние события -->
            <div class="debug-events">
              <h4>Recent Events</h4>
              <div class="events-list">
                <div 
                  v-for="(item, index) in recentDebugLogs"
                  :key="index"
                  class="event-item"
                  :class="`event-item--${item.status}`"
                >
                  <div class="event-header">
                    <span class="event-type">{{ item.event.actionType }}</span>
                    <span class="event-time">{{ formatTime(item.timestamp) }}</span>
                    <span class="event-status" :class="`status-${item.status}`">
                      {{ item.status }}
                    </span>
                  </div>
                  <div v-if="expandedEvents.has(index)" class="event-details">
                    <pre>{{ JSON.stringify(item.event.custom, null, 2) }}</pre>
                  </div>
                  <button 
                    @click="toggleEventDetails(index)"
                    class="event-toggle"
                  >
                    {{ expandedEvents.has(index) ? 'Hide' : 'Show' }} Details
                  </button>
                </div>
              </div>
            </div>
            
            <!-- Контролы -->
            <div class="debug-controls">
              <button @click="forceFlush" :disabled="isLoading" class="control-btn">
                Force Flush
              </button>
              <button @click="exportData" class="control-btn">
                Export Data
              </button>
              <button @click="toggleTracking" class="control-btn">
                {{ settings.enabled ? 'Disable' : 'Enable' }} Tracking
              </button>
            </div>
          </div>
        </Transition>
      </div>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, provide, onMounted, watch } from 'vue'
import { useTracking } from './useTracking'
import type { TrackingConfig } from './TrackingSystem.types'

interface Props {
  config?: Partial<TrackingConfig>
  debug?: boolean
  autoTrack?: boolean
  trackClicks?: boolean
  trackScrolls?: boolean
  trackPageViews?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  debug: false,
  autoTrack: true,
  trackClicks: true,
  trackScrolls: true,
  trackPageViews: true
})

// Инициализация системы трекинга
const tracking = useTracking(props.config)

// Состояние debug панели
const debugExpanded = ref(false)
const expandedEvents = ref(new Set<number>())
const isLoading = ref(false)

// Provide tracking для дочерних компонентов
provide('tracking', tracking)

// Computed
const showDebugPanel = computed(() => props.debug && tracking.settings.value.debug)
const eventCount = computed(() => tracking.eventQueue.value.events.length)
const stats = computed(() => tracking.stats.value)
const eventQueue = computed(() => tracking.eventQueue.value)
const settings = computed(() => tracking.settings.value)

const recentDebugLogs = computed(() => {
  return tracking.debugLog.value.slice(-10).reverse()
})

// Методы debug панели
const toggleDebug = () => {
  debugExpanded.value = !debugExpanded.value
}

const clearDebug = () => {
  tracking.clearTrackingData()
}

const toggleEventDetails = (index: number) => {
  if (expandedEvents.value.has(index)) {
    expandedEvents.value.delete(index)
  } else {
    expandedEvents.value.add(index)
  }
}

const forceFlush = async () => {
  isLoading.value = true
  try {
    await tracking.flush()
  } finally {
    isLoading.value = false
  }
}

const exportData = () => {
  const data = tracking.exportTrackingData()
  const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `tracking-data-${Date.now()}.json`
  a.click()
  URL.revokeObjectURL(url)
}

const toggleTracking = () => {
  tracking.updateSettings({
    enabled: !tracking.settings.value.enabled
  })
}

const formatTime = (timestamp: number): string => {
  const date = new Date(timestamp)
  return date.toLocaleTimeString()
}

// Автоматический трекинг
const setupAutoTracking = () => {
  if (!props.autoTrack) return
  
  // Трекинг просмотра страницы
  if (props.trackPageViews) {
    tracking.track('view', {
      url: window.location.href,
      title: document.title
    })
  }
  
  // Трекинг кликов
  if (props.trackClicks) {
    document.addEventListener('click', (event) => {
      const target = event.target as HTMLElement
      
      // Пропускаем клики по debug панели
      if (target.closest('.tracking-debug-panel')) return
      
      // Получаем информацию о клике
      const clickData = {
        tagName: target.tagName,
        className: target.className,
        id: target.id,
        text: target.textContent?.slice(0, 100),
        href: (target as HTMLAnchorElement).href,
        x: event.clientX,
        y: event.clientY
      }
      
      // Определяем тип клика
      if (event.button === 0) {
        tracking.track('click', clickData)
      } else if (event.button === 1) {
        tracking.track('aux_click', clickData)
      } else if (event.button === 2) {
        tracking.track('right_click', clickData)
      }
    })
  }
  
  // Трекинг скролла
  if (props.trackScrolls) {
    let scrollTimer: number | null = null
    
    window.addEventListener('scroll', () => {
      if (scrollTimer) clearTimeout(scrollTimer)
      
      scrollTimer = window.setTimeout(() => {
        tracking.trackScroll({
          scrollTop: window.scrollY,
          scrollHeight: document.documentElement.scrollHeight,
          clientHeight: window.innerHeight
        })
      }, 150)
    })
  }
  
  // Трекинг видимости страницы
  document.addEventListener('visibilitychange', () => {
    tracking.track('view', {
      visible: !document.hidden,
      state: document.visibilityState
    })
  })
  
  // Трекинг ошибок
  window.addEventListener('error', (event) => {
    tracking.track('error', {
      message: event.message,
      filename: event.filename,
      lineno: event.lineno,
      colno: event.colno,
      error: event.error?.stack
    })
  })
  
  // Трекинг unhandled promise rejections
  window.addEventListener('unhandledrejection', (event) => {
    tracking.track('error', {
      type: 'unhandledrejection',
      reason: event.reason,
      promise: event.promise
    })
  })
}

// Lifecycle
onMounted(() => {
  setupAutoTracking()
})

// Watch для изменения настроек debug
watch(() => props.debug, (newDebug) => {
  tracking.updateSettings({ debug: newDebug })
})

// Экспорт tracking для внешнего использования
defineExpose({
  tracking
})
</script>

<style scoped>
/* Debug панель */
.tracking-debug-panel {
  position: fixed;
  bottom: 20px;
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

.tracking-debug-panel--expanded {
  height: 600px;
  max-height: 80vh;
  display: flex;
  flex-direction: column;
}

/* Header */
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
  color: #00a854;
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

.debug-clear {
  padding: 4px 8px;
  background: transparent;
  border: 1px solid #e1e3e6;
  border-radius: 4px;
  font-size: 12px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.debug-clear:hover {
  background: #fff;
  border-color: #9ca0a5;
}

/* Content */
.debug-content {
  flex: 1;
  overflow-y: auto;
  padding: 16px;
}

/* Статистика */
.debug-stats {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
  margin-bottom: 16px;
  padding: 12px;
  background: #f6f7f8;
  border-radius: 6px;
}

.stat-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.stat-label {
  color: #70757a;
  font-size: 12px;
}

.stat-value {
  font-weight: 600;
  color: #001a34;
}

.stat-error {
  color: #f91155;
}

/* События */
.debug-events {
  margin-bottom: 16px;
}

.debug-events h4 {
  margin: 0 0 12px;
  font-size: 14px;
  font-weight: 600;
  color: #001a34;
}

.events-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
  max-height: 300px;
  overflow-y: auto;
}

.event-item {
  padding: 8px;
  background: #f6f7f8;
  border-radius: 6px;
  border-left: 3px solid transparent;
}

.event-item--pending {
  border-left-color: #ffa500;
}

.event-item--sent {
  border-left-color: #00a854;
}

.event-item--failed {
  border-left-color: #f91155;
}

.event-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 4px;
}

.event-type {
  font-weight: 600;
  color: #001a34;
}

.event-time {
  color: #70757a;
  font-size: 12px;
}

.event-status {
  margin-left: auto;
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
}

.status-pending {
  background: #fff3cd;
  color: #856404;
}

.status-sent {
  background: #d4edda;
  color: #155724;
}

.status-failed {
  background: #f8d7da;
  color: #721c24;
}

.event-details {
  margin-top: 8px;
  padding: 8px;
  background: white;
  border-radius: 4px;
  font-size: 12px;
  overflow-x: auto;
}

.event-details pre {
  margin: 0;
  font-family: 'Monaco', 'Courier New', monospace;
  font-size: 11px;
  color: #001a34;
}

.event-toggle {
  margin-top: 4px;
  padding: 2px 6px;
  background: transparent;
  border: none;
  color: #005bff;
  font-size: 12px;
  cursor: pointer;
  text-decoration: underline;
}

/* Контролы */
.debug-controls {
  display: flex;
  gap: 8px;
  padding-top: 16px;
  border-top: 1px solid #e1e3e6;
}

.control-btn {
  flex: 1;
  padding: 8px 12px;
  background: #005bff;
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.control-btn:hover {
  background: #0046cc;
}

.control-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
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

/* Скроллбар */
.debug-content::-webkit-scrollbar,
.events-list::-webkit-scrollbar {
  width: 6px;
}

.debug-content::-webkit-scrollbar-track,
.events-list::-webkit-scrollbar-track {
  background: #f0f2f5;
  border-radius: 3px;
}

.debug-content::-webkit-scrollbar-thumb,
.events-list::-webkit-scrollbar-thumb {
  background: #c1c4c9;
  border-radius: 3px;
}

/* Адаптивность */
@media (max-width: 768px) {
  .tracking-debug-panel {
    width: calc(100vw - 40px);
    bottom: 10px;
    right: 10px;
    left: 10px;
  }
  
  .debug-stats {
    grid-template-columns: 1fr;
  }
  
  .debug-controls {
    flex-direction: column;
  }
}
</style>