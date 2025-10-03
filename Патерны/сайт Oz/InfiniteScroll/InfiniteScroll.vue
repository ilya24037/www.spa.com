<template>
  <div 
    ref="scrollContainer"
    class="infinite-scroll"
    :class="{
      'infinite-scroll--loading': isLoading,
      'infinite-scroll--complete': isComplete,
      'infinite-scroll--error': hasError,
      'infinite-scroll--virtual': virtualScroll
    }"
    @scroll="handleScroll"
  >
    <!-- Верхний sentinel для двунаправленного скролла -->
    <div 
      v-if="direction === 'both'"
      ref="topSentinel"
      class="infinite-scroll__sentinel infinite-scroll__sentinel--top"
      :style="sentinelStyles"
    />
    
    <!-- Виртуальный скролл spacer -->
    <div 
      v-if="virtualScroll"
      class="infinite-scroll__spacer-top"
      :style="{ height: `${virtualData.offsetTop}px` }"
    />
    
    <!-- Основной контент -->
    <div class="infinite-scroll__content">
      <!-- Слот для элементов -->
      <slot 
        :items="displayItems"
        :is-loading="isLoading"
        :is-complete="isComplete"
      >
        <div 
          v-for="(item, index) in displayItems"
          :key="getItemKey(item, index)"
          class="infinite-scroll__item"
        >
          <slot name="item" :item="item" :index="index">
            {{ item }}
          </slot>
        </div>
      </slot>
      
      <!-- Индикатор загрузки -->
      <Transition name="fade">
        <div 
          v-if="isLoading && showLoader"
          class="infinite-scroll__loader"
        >
          <slot name="loader">
            <div class="loader-spinner">
              <svg width="40" height="40" viewBox="0 0 40 40">
                <circle
                  cx="20"
                  cy="20"
                  r="18"
                  fill="none"
                  stroke="#005bff"
                  stroke-width="3"
                  stroke-dasharray="90"
                  stroke-dashoffset="20"
                  class="loader-circle"
                />
              </svg>
              <span class="loader-text">{{ loadingText }}</span>
            </div>
          </slot>
        </div>
      </Transition>
      
      <!-- Сообщение об ошибке -->
      <Transition name="fade">
        <div 
          v-if="hasError && showError"
          class="infinite-scroll__error"
        >
          <slot name="error" :error="lastError" :retry="retry">
            <div class="error-message">
              <svg width="24" height="24" viewBox="0 0 24 24" class="error-icon">
                <path 
                  fill="#ff4444" 
                  d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"
                />
              </svg>
              <p>{{ errorMessage }}</p>
              <button @click="retry" class="error-retry">
                Попробовать снова
              </button>
            </div>
          </slot>
        </div>
      </Transition>
      
      <!-- Сообщение о завершении -->
      <Transition name="fade">
        <div 
          v-if="isComplete && showEndMessage && !hasError"
          class="infinite-scroll__end"
        >
          <slot name="end">
            <p class="end-message">{{ endMessage }}</p>
          </slot>
        </div>
      </Transition>
      
      <!-- Пустое состояние -->
      <Transition name="fade">
        <div 
          v-if="!isLoading && displayItems.length === 0 && showEmpty"
          class="infinite-scroll__empty"
        >
          <slot name="empty">
            <div class="empty-state">
              <svg width="120" height="120" viewBox="0 0 120 120" class="empty-icon">
                <circle cx="60" cy="60" r="50" fill="#f0f2f5"/>
                <path 
                  fill="#9ca0a5" 
                  d="M60 40v20m0 10h.01M60 110c27.6 0 50-22.4 50-50S87.6 10 60 10 10 32.4 10 60s22.4 50 50 50z"
                />
              </svg>
              <p class="empty-text">Ничего не найдено</p>
            </div>
          </slot>
        </div>
      </Transition>
    </div>
    
    <!-- Виртуальный скролл spacer -->
    <div 
      v-if="virtualScroll"
      class="infinite-scroll__spacer-bottom"
      :style="{ height: `${virtualData.offsetBottom}px` }"
    />
    
    <!-- Нижний sentinel -->
    <div 
      ref="bottomSentinel"
      class="infinite-scroll__sentinel infinite-scroll__sentinel--bottom"
      :style="sentinelStyles"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import type { 
  InfiniteScrollConfig,
  InfiniteScrollState,
  LoadPageParams,
  PageResponse,
  VirtualScrollData,
  ScrollEvent
} from './InfiniteScroll.types'
import { 
  DEFAULT_CONFIG,
  DEFAULT_PAGINATION,
  DEFAULT_VIRTUAL_SCROLL
} from './InfiniteScroll.types'

interface Props extends InfiniteScrollConfig {
  items?: any[]
  pageSize?: number
  totalItems?: number
  loadMore: (params: LoadPageParams) => Promise<PageResponse<any>>
  getItemKey?: (item: any, index: number) => string | number
  showLoader?: boolean
  showEndMessage?: boolean
  showError?: boolean
  showEmpty?: boolean
  loadingText?: string
  errorMessage?: string
  endMessage?: string
}

const props = withDefaults(defineProps<Props>(), {
  ...DEFAULT_CONFIG,
  items: () => [],
  pageSize: DEFAULT_PAGINATION.pageSize,
  showLoader: true,
  showEndMessage: true,
  showError: true,
  showEmpty: true,
  loadingText: 'Загрузка...',
  errorMessage: 'Произошла ошибка при загрузке',
  endMessage: 'Все товары загружены',
  getItemKey: (item: any, index: number) => item.id || index
})

const emit = defineEmits<{
  'scroll': [event: ScrollEvent]
  'reach-end': []
  'reach-start': []
  'error': [error: Error]
  'load-complete': []
}>()

// Refs
const scrollContainer = ref<HTMLElement>()
const topSentinel = ref<HTMLElement>()
const bottomSentinel = ref<HTMLElement>()

// Состояние
const state = ref<InfiniteScrollState>({
  isLoading: false,
  isComplete: false,
  hasError: false,
  currentPage: 1,
  itemsLoaded: 0,
  scrollPosition: 0,
  viewportHeight: 0,
  retryCount: 0
})

const allItems = ref<any[]>([...props.items])
const lastError = ref<Error | null>(null)
const abortController = ref<AbortController | null>(null)

// IntersectionObserver
let topObserver: IntersectionObserver | null = null
let bottomObserver: IntersectionObserver | null = null

// Throttle/debounce таймеры
let scrollTimer: number | null = null
let loadTimer: number | null = null

// Виртуальный скролл
const virtualData = ref<VirtualScrollData>({
  startIndex: 0,
  endIndex: 0,
  visibleItems: [],
  offsetTop: 0,
  offsetBottom: 0,
  totalHeight: 0
})

// Computed
const isLoading = computed(() => state.value.isLoading)
const isComplete = computed(() => state.value.isComplete)
const hasError = computed(() => state.value.hasError)
const direction = computed(() => props.direction || 'vertical')

// Отображаемые элементы
const displayItems = computed(() => {
  if (props.virtualScroll) {
    return virtualData.value.visibleItems
  }
  return allItems.value
})

// Стили для sentinel
const sentinelStyles = computed(() => ({
  height: '1px',
  pointerEvents: 'none' as const,
  visibility: 'hidden' as const
}))

// Загрузка следующей страницы
const loadNextPage = async () => {
  if (state.value.isLoading || state.value.isComplete || state.value.hasError) {
    return
  }
  
  state.value.isLoading = true
  
  // Отмена предыдущего запроса
  if (abortController.value) {
    abortController.value.abort()
  }
  abortController.value = new AbortController()
  
  try {
    const params: LoadPageParams = {
      page: state.value.currentPage,
      pageSize: props.pageSize!,
      offset: allItems.value.length,
      signal: abortController.value.signal
    }
    
    const response = await props.loadMore(params)
    
    // Обновление состояния
    allItems.value = [...allItems.value, ...response.items]
    state.value.currentPage++
    state.value.itemsLoaded = allItems.value.length
    state.value.totalItems = response.totalItems
    state.value.totalPages = response.totalPages
    
    // Проверка завершения
    if (!response.hasNext || allItems.value.length >= response.totalItems) {
      state.value.isComplete = true
      emit('load-complete')
    }
    
    // Сброс счетчика ошибок
    state.value.retryCount = 0
    state.value.hasError = false
    lastError.value = null
    
    // Обновление виртуального скролла
    if (props.virtualScroll) {
      await nextTick()
      updateVirtualScroll()
    }
    
  } catch (error) {
    if (error instanceof Error && error.name === 'AbortError') {
      return // Игнорируем отмененные запросы
    }
    
    state.value.hasError = true
    lastError.value = error as Error
    emit('error', error as Error)
    
  } finally {
    state.value.isLoading = false
    abortController.value = null
  }
}

// Повтор после ошибки
const retry = async () => {
  if (state.value.retryCount >= (props.retryAttempts || 3)) {
    return
  }
  
  state.value.retryCount++
  state.value.hasError = false
  
  // Задержка перед повтором
  await new Promise(resolve => 
    setTimeout(resolve, props.retryDelay || 1000)
  )
  
  await loadNextPage()
}

// Обработка скролла
const handleScroll = () => {
  if (!scrollContainer.value) return
  
  const container = scrollContainer.value
  const scrollEvent: ScrollEvent = {
    scrollTop: container.scrollTop,
    scrollLeft: container.scrollLeft,
    scrollHeight: container.scrollHeight,
    scrollWidth: container.scrollWidth,
    clientHeight: container.clientHeight,
    clientWidth: container.clientWidth,
    direction: container.scrollTop > state.value.scrollPosition ? 'down' : 'up',
    velocity: Math.abs(container.scrollTop - state.value.scrollPosition),
    timestamp: Date.now()
  }
  
  state.value.scrollPosition = container.scrollTop
  state.value.viewportHeight = container.clientHeight
  
  emit('scroll', scrollEvent)
  
  // Обновление виртуального скролла
  if (props.virtualScroll) {
    if (scrollTimer) clearTimeout(scrollTimer)
    scrollTimer = window.setTimeout(() => {
      updateVirtualScroll()
    }, props.throttleDelay || 100)
  }
  
  // Проверка достижения конца (для стратегии scroll)
  if (props.strategy === 'scroll') {
    const threshold = props.threshold || 200
    const reachedBottom = 
      container.scrollTop + container.clientHeight >= 
      container.scrollHeight - threshold
    
    if (reachedBottom) {
      if (loadTimer) clearTimeout(loadTimer)
      loadTimer = window.setTimeout(() => {
        loadNextPage()
      }, props.debounceDelay || 150)
    }
  }
}

// Обновление виртуального скролла
const updateVirtualScroll = () => {
  if (!scrollContainer.value || !props.virtualScroll) return
  
  const container = scrollContainer.value
  const itemHeight = props.estimatedItemHeight || DEFAULT_VIRTUAL_SCROLL.estimatedItemHeight!
  const overscan = props.overscan || DEFAULT_VIRTUAL_SCROLL.overscan!
  
  const scrollTop = container.scrollTop
  const viewportHeight = container.clientHeight
  
  // Вычисление видимого диапазона
  const startIndex = Math.max(0, Math.floor(scrollTop / itemHeight) - overscan)
  const endIndex = Math.min(
    allItems.value.length - 1,
    Math.ceil((scrollTop + viewportHeight) / itemHeight) + overscan
  )
  
  // Обновление виртуальных данных
  virtualData.value = {
    startIndex,
    endIndex,
    visibleItems: allItems.value.slice(startIndex, endIndex + 1),
    offsetTop: startIndex * itemHeight,
    offsetBottom: (allItems.value.length - endIndex - 1) * itemHeight,
    totalHeight: allItems.value.length * itemHeight
  }
}

// Настройка IntersectionObserver
const setupObservers = () => {
  if (props.strategy !== 'intersection') return
  
  const options = {
    root: scrollContainer.value,
    rootMargin: props.rootMargin || '100px',
    threshold: 0
  }
  
  // Observer для нижнего sentinel
  if (bottomSentinel.value) {
    bottomObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting && !state.value.isLoading) {
          loadNextPage()
        }
      })
    }, options)
    
    bottomObserver.observe(bottomSentinel.value)
  }
  
  // Observer для верхнего sentinel (двунаправленный скролл)
  if (props.direction === 'both' && topSentinel.value) {
    topObserver = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          emit('reach-start')
        }
      })
    }, options)
    
    topObserver.observe(topSentinel.value)
  }
}

// Очистка observers
const cleanupObservers = () => {
  if (topObserver) {
    topObserver.disconnect()
    topObserver = null
  }
  if (bottomObserver) {
    bottomObserver.disconnect()
    bottomObserver = null
  }
}

// Сброс состояния
const reset = () => {
  state.value = {
    isLoading: false,
    isComplete: false,
    hasError: false,
    currentPage: 1,
    itemsLoaded: 0,
    scrollPosition: 0,
    viewportHeight: 0,
    retryCount: 0
  }
  allItems.value = [...props.items]
  lastError.value = null
  
  if (scrollContainer.value) {
    scrollContainer.value.scrollTop = 0
  }
}

// Lifecycle
onMounted(() => {
  setupObservers()
  
  // Автоматическая загрузка первой страницы
  if (props.strategy === 'auto' && allItems.value.length === 0) {
    loadNextPage()
  }
})

onUnmounted(() => {
  cleanupObservers()
  
  if (scrollTimer) clearTimeout(scrollTimer)
  if (loadTimer) clearTimeout(loadTimer)
  
  if (abortController.value) {
    abortController.value.abort()
  }
})

// Watchers
watch(() => props.items, (newItems) => {
  allItems.value = [...newItems]
})

// Экспорт методов
defineExpose({
  loadNextPage,
  retry,
  reset,
  state: computed(() => state.value),
  items: computed(() => allItems.value)
})
</script>

<style scoped>
/* Основной контейнер */
.infinite-scroll {
  position: relative;
  height: 100%;
  overflow-y: auto;
  overflow-x: hidden;
  -webkit-overflow-scrolling: touch;
}

.infinite-scroll--virtual {
  will-change: scroll-position;
}

/* Контент */
.infinite-scroll__content {
  position: relative;
  min-height: 100%;
}

/* Sentinel элементы */
.infinite-scroll__sentinel {
  position: relative;
  width: 100%;
  height: 1px;
  pointer-events: none;
  visibility: hidden;
}

/* Spacers для виртуального скролла */
.infinite-scroll__spacer-top,
.infinite-scroll__spacer-bottom {
  width: 100%;
  pointer-events: none;
}

/* Элементы списка */
.infinite-scroll__item {
  transition: opacity 0.3s ease;
}

.infinite-scroll__item:enter {
  opacity: 0;
  transform: translateY(20px);
}

/* Индикатор загрузки */
.infinite-scroll__loader {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 32px;
  text-align: center;
}

.loader-spinner {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
}

.loader-circle {
  animation: rotate 1s linear infinite;
}

@keyframes rotate {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.loader-text {
  font-size: 14px;
  color: #70757a;
}

/* Сообщение об ошибке */
.infinite-scroll__error {
  padding: 32px;
  text-align: center;
}

.error-message {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
}

.error-icon {
  width: 48px;
  height: 48px;
}

.error-retry {
  padding: 8px 16px;
  background: #005bff;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: background 0.2s ease;
}

.error-retry:hover {
  background: #0046cc;
}

/* Сообщение о завершении */
.infinite-scroll__end {
  padding: 32px;
  text-align: center;
}

.end-message {
  font-size: 14px;
  color: #9ca0a5;
}

/* Пустое состояние */
.infinite-scroll__empty {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 400px;
  padding: 48px;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
  text-align: center;
}

.empty-icon {
  opacity: 0.3;
}

.empty-text {
  font-size: 16px;
  color: #9ca0a5;
}

/* Анимации */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Адаптивность */
@media (max-width: 768px) {
  .infinite-scroll__loader,
  .infinite-scroll__error,
  .infinite-scroll__end,
  .infinite-scroll__empty {
    padding: 24px;
  }
}

/* Скроллбар стилизация */
.infinite-scroll::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

.infinite-scroll::-webkit-scrollbar-track {
  background: #f0f2f5;
  border-radius: 4px;
}

.infinite-scroll::-webkit-scrollbar-thumb {
  background: #c1c4c9;
  border-radius: 4px;
}

.infinite-scroll::-webkit-scrollbar-thumb:hover {
  background: #9ca0a5;
}
</style>