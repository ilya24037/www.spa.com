<!-- 
  VirtualScroll - Компонент виртуального скроллинга
  Оптимизирует отображение больших списков, рендеря только видимые элементы
-->
<template>
  <div 
    ref="scrollContainer"
    class="virtual-scroll"
    :style="containerStyle"
    @scroll="handleScroll"
  >
    <!-- Spacer для сохранения высоты скролла -->
    <div 
      class="virtual-scroll__spacer"
      :style="spacerStyle"
    />
    
    <!-- Видимые элементы -->
    <div 
      class="virtual-scroll__content"
      :style="contentStyle"
    >
      <div
        v-for="item in visibleItems"
        :key="getItemKey(item)"
        class="virtual-scroll__item"
        :style="getItemStyle(item)"
      >
        <slot 
          name="item" 
          :item="item.data"
          :index="item.index"
        />
      </div>
    </div>
    
    <!-- Индикатор позиции скролла -->
    <Transition name="fade">
      <div 
        v-if="showScrollIndicator && scrollProgress > 0"
        class="virtual-scroll__indicator"
      >
        <div class="virtual-scroll__indicator-bar">
          <div 
            class="virtual-scroll__indicator-thumb"
            :style="{ height: `${scrollProgress * 100}%` }"
          />
        </div>
        <div class="virtual-scroll__indicator-text">
          {{ currentRange.start + 1 }}–{{ currentRange.end }} из {{ items.length }}
        </div>
      </div>
    </Transition>
    
    <!-- Loader при подгрузке -->
    <div 
      v-if="loading && hasMore"
      class="virtual-scroll__loader"
    >
      <div class="animate-pulse flex space-x-2">
        <div class="w-3 h-3 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0ms"></div>
        <div class="w-3 h-3 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 150ms"></div>
        <div class="w-3 h-3 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 300ms"></div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'

interface Props {
  // Массив элементов для отображения
  items: any[]
  // Высота одного элемента (фиксированная или функция)
  itemHeight: number | ((index: number) => number)
  // Высота контейнера
  containerHeight?: number
  // Буфер элементов сверху и снизу
  buffer?: number
  // Порог для подгрузки новых данных
  loadMoreThreshold?: number
  // Флаг загрузки
  loading?: boolean
  // Есть ли еще данные для загрузки
  hasMore?: boolean
  // Показывать индикатор позиции
  showScrollIndicator?: boolean
  // Ключ для элементов
  itemKey?: string | ((item: any) => string | number)
  // Режим отображения (list или grid)
  mode?: 'list' | 'grid'
  // Количество колонок для grid
  gridColumns?: number
}

const props = withDefaults(defineProps<Props>(), {
  containerHeight: 600,
  buffer: 3,
  loadMoreThreshold: 100,
  loading: false,
  hasMore: false,
  showScrollIndicator: true,
  itemKey: 'id',
  mode: 'list',
  gridColumns: 3
})

const emit = defineEmits<{
  'load-more': []
  'scroll': [position: number]
}>()

// Refs
const scrollContainer = ref<HTMLElement>()
const scrollTop = ref(0)
const containerHeightPx = ref(props.containerHeight)

// Вычисляемые размеры
const itemHeightPx = computed(() => {
  if (typeof props.itemHeight === 'function') {
    // Для динамической высоты возвращаем среднее значение
    return props.itemHeight(0)
  }
  return props.itemHeight
})

const totalHeight = computed(() => {
  if (props.mode === 'grid') {
    const rows = Math.ceil(props.items.length / props.gridColumns)
    return rows * itemHeightPx.value
  }
  
  if (typeof props.itemHeight === 'function') {
    // Для динамической высоты суммируем все высоты
    return props.items.reduce((sum, _, index) => {
      return sum + (props.itemHeight as Function)(index)
    }, 0)
  }
  
  return props.items.length * itemHeightPx.value
})

// Видимые элементы
const visibleRange = computed(() => {
  const visibleCount = Math.ceil(containerHeightPx.value / itemHeightPx.value)
  const startIndex = Math.floor(scrollTop.value / itemHeightPx.value) - props.buffer
  const endIndex = startIndex + visibleCount + props.buffer * 2
  
  return {
    start: Math.max(0, startIndex),
    end: Math.min(props.items.length, endIndex)
  }
})

const visibleItems = computed(() => {
  const { start, end } = visibleRange.value
  
  return props.items.slice(start, end).map((data, i) => ({
    data,
    index: start + i,
    top: (start + i) * itemHeightPx.value
  }))
})

const currentRange = computed(() => {
  const visibleCount = Math.ceil(containerHeightPx.value / itemHeightPx.value)
  const currentStart = Math.floor(scrollTop.value / itemHeightPx.value)
  
  return {
    start: currentStart,
    end: Math.min(currentStart + visibleCount, props.items.length)
  }
})

const scrollProgress = computed(() => {
  const maxScroll = totalHeight.value - containerHeightPx.value
  return maxScroll > 0 ? scrollTop.value / maxScroll : 0
})

// Стили
const containerStyle = computed(() => ({
  height: `${containerHeightPx.value}px`,
  position: 'relative' as const,
  overflow: 'auto' as const
}))

const spacerStyle = computed(() => ({
  height: `${totalHeight.value}px`,
  position: 'absolute' as const,
  top: 0,
  left: 0,
  right: 0,
  zIndex: -1
}))

const contentStyle = computed(() => ({
  transform: `translateY(${visibleRange.value.start * itemHeightPx.value}px)`,
  position: 'absolute' as const,
  top: 0,
  left: 0,
  right: 0,
  display: props.mode === 'grid' ? 'grid' : 'block',
  gridTemplateColumns: props.mode === 'grid' ? `repeat(${props.gridColumns}, 1fr)` : undefined,
  gap: props.mode === 'grid' ? '1rem' : undefined
}))

// Методы
const handleScroll = (event: Event) => {
  const target = event.target as HTMLElement
  scrollTop.value = target.scrollTop
  
  emit('scroll', scrollTop.value)
  
  // Проверяем необходимость подгрузки
  if (props.hasMore && !props.loading) {
    const scrollBottom = totalHeight.value - scrollTop.value - containerHeightPx.value
    if (scrollBottom < props.loadMoreThreshold) {
      emit('load-more')
    }
  }
}

const getItemKey = (item: any) => {
  if (typeof props.itemKey === 'function') {
    return props.itemKey(item.data)
  }
  return item.data[props.itemKey] || item.index
}

const getItemStyle = (item: any) => {
  if (props.mode === 'grid') {
    return {}
  }
  
  const height = typeof props.itemHeight === 'function'
    ? props.itemHeight(item.index)
    : itemHeightPx.value
    
  return {
    height: `${height}px`,
    position: 'absolute' as const,
    top: `${item.top}px`,
    left: 0,
    right: 0
  }
}

// Публичные методы
const scrollToIndex = (index: number) => {
  if (scrollContainer.value) {
    const position = index * itemHeightPx.value
    scrollContainer.value.scrollTop = position
  }
}

const scrollToTop = () => {
  if (scrollContainer.value) {
    scrollContainer.value.scrollTop = 0
  }
}

// Обновление высоты контейнера при ресайзе
const updateContainerHeight = () => {
  if (scrollContainer.value) {
    containerHeightPx.value = scrollContainer.value.clientHeight || props.containerHeight
  }
}

// Lifecycle
onMounted(() => {
  updateContainerHeight()
  window.addEventListener('resize', updateContainerHeight)
})

onUnmounted(() => {
  window.removeEventListener('resize', updateContainerHeight)
})

// Экспортируем методы
defineExpose({
  scrollToIndex,
  scrollToTop
})
</script>

<style scoped>
.virtual-scroll {
  position: relative;
  overflow-y: auto;
  overflow-x: hidden;
}

/* Кастомный скроллбар */
.virtual-scroll::-webkit-scrollbar {
  width: 8px;
}

.virtual-scroll::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 4px;
}

.virtual-scroll::-webkit-scrollbar-thumb {
  background: #cbd5e0;
  border-radius: 4px;
}

.virtual-scroll::-webkit-scrollbar-thumb:hover {
  background: #a0aec0;
}

/* Индикатор позиции */
.virtual-scroll__indicator {
  position: fixed;
  right: 20px;
  top: 50%;
  transform: translateY(-50%);
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 8px 12px;
  background: rgba(0, 0, 0, 0.8);
  color: white;
  border-radius: 8px;
  font-size: 12px;
  z-index: 1000;
  pointer-events: none;
}

.virtual-scroll__indicator-bar {
  width: 4px;
  height: 60px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 2px;
  overflow: hidden;
}

.virtual-scroll__indicator-thumb {
  width: 100%;
  background: #3b82f6;
  border-radius: 2px;
  transition: height 0.1s ease-out;
}

.virtual-scroll__indicator-text {
  white-space: nowrap;
}

/* Loader */
.virtual-scroll__loader {
  position: absolute;
  bottom: 20px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 10;
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

/* Smooth scroll для iOS */
@supports (-webkit-touch-callout: none) {
  .virtual-scroll {
    -webkit-overflow-scrolling: touch;
  }
}

/* Оптимизация для grid режима */
.virtual-scroll__content[style*="grid"] .virtual-scroll__item {
  position: relative !important;
  top: auto !important;
  height: auto !important;
}
</style>