<template>
  <div class="product-grid-widget" :data-widget-id="widgetId" :data-tracking="trackingOn">
    <!-- Заголовок виджета (опционально) -->
    <div v-if="title" class="widget-header">
      <h2 class="widget-title">{{ title }}</h2>
    </div>

    <!-- Сетка товаров -->
    <div 
      ref="gridContainer"
      class="product-grid"
      :style="gridStyles"
    >
      <slot />
    </div>

    <!-- Индикатор загрузки -->
    <div v-if="isLoading" class="loading-indicator">
      <div class="spinner"></div>
      <span>Загружаем товары...</span>
    </div>

    <!-- Пустое состояние -->
    <div v-if="!isLoading && isEmpty" class="empty-state">
      <svg class="empty-icon" width="64" height="64" viewBox="0 0 24 24">
        <path fill="currentColor" d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14z"/>
      </svg>
      <p class="empty-text">Товары не найдены</p>
    </div>

    <!-- Индикатор дозагрузки -->
    <div 
      v-if="hasMore && !isLoading" 
      ref="loadMoreTrigger"
      class="load-more-trigger"
      :style="{ height: '1px' }"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import type { ProductGridOptions, WidgetConfig } from './ProductGrid.types'
import { useProductGrid } from './useProductGrid'

// Props из конфигурации Ozon
interface Props {
  // Из Ozon widgetOptions
  columnsCount?: number // default: 5
  ratio?: string // default: "3:4"
  
  // Из Ozon params
  algo?: string
  itemsOnPage?: number // default: 30
  allowCPM?: boolean
  offset?: number // default: 40
  paginationExtraEmptyPage?: boolean
  usePagination?: boolean
  
  // Widget config
  widgetId?: string
  widgetToken?: string
  version?: number
  vertical?: string // "products"
  trackingOn?: boolean
  
  // Дополнительные
  title?: string
  autoLoad?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  columnsCount: 5,
  ratio: '3:4',
  algo: '1',
  itemsOnPage: 30,
  allowCPM: true,
  offset: 40,
  paginationExtraEmptyPage: true,
  usePagination: true,
  widgetId: 'skuGridSimple-default',
  version: 1,
  vertical: 'products',
  trackingOn: true,
  autoLoad: true
})

const emit = defineEmits<{
  'load-more': [params: { offset: number, limit: number }]
  'widget-view': [widgetId: string]
  'widget-ready': [config: WidgetConfig]
}>()

// Refs
const gridContainer = ref<HTMLElement>()
const loadMoreTrigger = ref<HTMLElement>()

// Composable с логикой Ozon
const {
  isLoading,
  hasMore,
  isEmpty,
  currentOffset,
  loadNextPage,
  resetGrid,
  trackTimeSpent
} = useProductGrid({
  itemsOnPage: props.itemsOnPage,
  initialOffset: props.offset,
  algo: props.algo
})

// Стили сетки из Ozon
const gridStyles = computed(() => {
  const [widthRatio, heightRatio] = props.ratio.split(':').map(Number)
  return {
    '--columns': props.columnsCount,
    '--aspect-ratio': `${widthRatio} / ${heightRatio}`,
    display: 'grid',
    gridTemplateColumns: `repeat(var(--columns), 1fr)`,
    gap: '16px',
    position: 'relative'
  }
})

// Intersection Observer для бесконечной прокрутки (как в Ozon)
let observer: IntersectionObserver | null = null

const setupInfiniteScroll = () => {
  if (!props.usePagination || !loadMoreTrigger.value) return
  
  observer = new IntersectionObserver(
    (entries) => {
      const [entry] = entries
      if (entry.isIntersecting && !isLoading.value && hasMore.value) {
        handleLoadMore()
      }
    },
    {
      root: null,
      rootMargin: '100px', // Загружаем заранее
      threshold: 0.1
    }
  )
  
  observer.observe(loadMoreTrigger.value)
}

// Загрузка следующей страницы
const handleLoadMore = async () => {
  const params = {
    offset: currentOffset.value,
    limit: props.itemsOnPage
  }
  
  emit('load-more', params)
  await loadNextPage()
}

// Трекинг виджета (из Ozon)
const initWidgetTracking = () => {
  if (!props.trackingOn) return
  
  // Отправляем событие просмотра виджета
  emit('widget-view', props.widgetId)
  
  // Начинаем отсчет времени
  trackTimeSpent()
}

// Конфигурация виджета (как в Ozon)
const widgetConfig = computed<WidgetConfig>(() => ({
  component: 'skuGridSimple',
  params: JSON.stringify({
    algo: props.algo,
    itemsOnPage: props.itemsOnPage,
    allowCPM: props.allowCPM,
    offset: props.offset,
    paginationExtraEmptyPage: props.paginationExtraEmptyPage,
    usePagination: props.usePagination
  }),
  stateId: props.widgetId,
  version: props.version,
  vertical: props.vertical,
  widgetToken: props.widgetToken,
  trackingOn: props.trackingOn,
  name: 'shelf.infiniteScroll',
  isTrackView: true,
  isTrackingOn: props.trackingOn
}))

// Lifecycle
onMounted(() => {
  if (props.autoLoad) {
    setupInfiniteScroll()
  }
  initWidgetTracking()
  emit('widget-ready', widgetConfig.value)
})

onUnmounted(() => {
  if (observer) {
    observer.disconnect()
  }
})

// Watchers
watch(() => props.itemsOnPage, () => {
  resetGrid()
})

// Expose для родительского компонента
defineExpose({
  loadNextPage,
  resetGrid,
  widgetConfig
})
</script>

<style scoped>
/* Стили в стиле Ozon */
.product-grid-widget {
  position: relative;
  width: 100%;
}

.widget-header {
  margin-bottom: 24px;
}

.widget-title {
  font-size: 24px;
  font-weight: 700;
  line-height: 32px;
  color: #001a34;
  margin: 0;
}

.product-grid {
  width: 100%;
}

/* Адаптивность как в Ozon */
@media (max-width: 1399px) {
  .product-grid {
    --columns: 4 !important;
  }
}

@media (max-width: 1023px) {
  .product-grid {
    --columns: 3 !important;
  }
}

@media (max-width: 767px) {
  .product-grid {
    --columns: 2 !important;
    gap: 12px;
  }
}

/* Индикатор загрузки */
.loading-indicator {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 32px;
  color: #70757a;
}

.spinner {
  width: 24px;
  height: 24px;
  border: 2px solid #e1e3e6;
  border-top-color: #005bff;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* Пустое состояние */
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 64px 24px;
  text-align: center;
}

.empty-icon {
  color: #e1e3e6;
  margin-bottom: 16px;
}

.empty-text {
  font-size: 16px;
  line-height: 24px;
  color: #70757a;
  margin: 0;
}

/* Триггер для загрузки */
.load-more-trigger {
  width: 100%;
  pointer-events: none;
}

/* Hover эффекты для карточек внутри */
.product-grid :deep(.product-card) {
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.product-grid :deep(.product-card:hover) {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(0, 26, 52, 0.08);
}
</style>