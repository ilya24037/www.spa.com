<!-- 
  Изолированный виджет Catalog по принципу Ozon
  Демонстрирует каталог товаров/услуг
-->
<template>
  <div class="catalog-widget">
    
    <!-- Фильтры (опционально) -->
    <div v-if="showFilters" class="catalog-widget__filters mb-6">
      <div class="bg-white rounded-lg border p-4">
        <h3 class="font-medium text-gray-500 mb-3">Фильтры</h3>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <select 
            v-model="localFilters.category"
            @change="handleFilterChange"
            class="border rounded px-3 py-2 text-sm"
          >
            <option value="">Все категории</option>
            <option value="massage">Массаж</option>
            <option value="spa">SPA</option>
            <option value="beauty">Красота</option>
          </select>
          
          <input 
            v-model.number="localFilters.priceMin"
            @input="handleFilterChange"
            type="number"
            placeholder="Цена от"
            class="border rounded px-3 py-2 text-sm"
          />
          
          <input 
            v-model.number="localFilters.priceMax"
            @input="handleFilterChange"
            type="number"
            placeholder="Цена до"
            class="border rounded px-3 py-2 text-sm"
          />
          
          <select 
            v-model="localFilters.sortBy"
            @change="handleFilterChange"
            class="border rounded px-3 py-2 text-sm"
          >
            <option value="popular">По популярности</option>
            <option value="price">По цене</option>
            <option value="rating">По рейтингу</option>
            <option value="date">По дате</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Loading состояние -->
    <div v-if="isLoading && items.length === 0" class="catalog-widget__loading">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div 
          v-for="i in 6" 
          :key="i"
          class="animate-pulse bg-white rounded-lg border p-4"
        >
          <div class="h-48 bg-gray-500 rounded mb-4"></div>
          <div class="h-4 bg-gray-500 rounded w-3/4 mb-2"></div>
          <div class="h-4 bg-gray-500 rounded w-1/2"></div>
        </div>
      </div>
    </div>

    <!-- Error состояние -->
    <div v-else-if="hasError" class="catalog-widget__error">
      <div class="bg-red-50 border border-red-200 rounded-lg p-6 text-center">
        <div class="text-red-800 font-medium mb-2">Ошибка загрузки каталога</div>
        <div class="text-red-600 text-sm mb-4">{{ error }}</div>
        <button 
          @click="retry"
          class="bg-red-600 text-white px-4 py-2 rounded text-sm hover:bg-red-700 transition-colors"
        >
          Попробовать еще раз
        </button>
      </div>
    </div>

    <!-- Пустое состояние -->
    <div v-else-if="isEmpty" class="catalog-widget__empty">
      <div class="bg-gray-500 border border-gray-500 rounded-lg p-8 text-center">
        <div class="text-gray-500 font-medium mb-2">Ничего не найдено</div>
        <div class="text-gray-500 text-sm">Попробуйте изменить фильтры поиска</div>
      </div>
    </div>

    <!-- Основной контент -->
    <div v-else class="catalog-widget__content">
      
      <!-- Промо товары -->
      <div v-if="promotedItems.length > 0" class="mb-8">
        <h3 class="text-lg font-medium text-gray-500 mb-4 flex items-center">
          <span class="text-yellow-500 mr-2">⭐</span>
          Рекомендуемые
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <CatalogItemCard
            v-for="item in promotedItems"
            :key="`promoted-${item.id}`"
            :item="item"
            :is-promoted="true"
            @click="handleItemClick(item)"
            @master-click="handleMasterClick(item.masterId)"
          />
        </div>
      </div>

      <!-- Обычные товары -->
      <div>
        <h3 v-if="promotedItems.length > 0" class="text-lg font-medium text-gray-500 mb-4">
          Все услуги
        </h3>
        
        <div 
          :class="[
            'grid gap-4',
            layout === 'list' 
              ? 'grid-cols-1' 
              : 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3'
          ]"
        >
          <CatalogItemCard
            v-for="item in regularItems"
            :key="item.id"
            :item="item"
            :layout="layout"
            @click="handleItemClick(item)"
            @master-click="handleMasterClick(item.masterId)"
          />
        </div>
      </div>

      <!-- Загрузка еще -->
      <div v-if="hasMore" class="mt-8 text-center">
        <button
          v-if="!isLoading"
          @click="loadMore"
          class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors"
        >
          Загрузить еще ({{ totalCount - items.length }} остается)
        </button>
        
        <div v-else class="animate-pulse">
          <div class="h-10 bg-gray-500 rounded-lg w-48 mx-auto"></div>
        </div>
      </div>

      <!-- Счетчик -->
      <div class="mt-6 text-center text-sm text-gray-500">
        Показано {{ items.length }} из {{ totalCount }} услуг
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * Изолированный виджет Catalog
 * Принципы Ozon: самодостаточность, изоляция, переиспользование
 */

import { ref, computed, onMounted, watch } from 'vue'
import type { 
    CatalogWidgetProps, 
    CatalogWidgetEmits,
    CatalogFilter,
    CatalogItem
} from './types/catalog.types'
import { useCatalogWidgetStore } from './store/catalogStore'
import { defineAsyncComponent } from 'vue'

// Ленивая загрузка компонентов
const CatalogItemCard = defineAsyncComponent(
    () => import('./components/CatalogItemCard.vue')
)

// === PROPS И EVENTS ===
const props = withDefaults(defineProps<CatalogWidgetProps>(), {
    limit: 20,
    showFilters: true,
    showPagination: true,
    layout: 'grid',
    compact: false
})

const emit = defineEmits<{
  'item-selected': [item: CatalogItem]
  'filter-changed': [filters: CatalogFilter]
  'master-clicked': [masterId: number]
  'load-more': []
}>()

// === STORE ===
const store = useCatalogWidgetStore()

// === ЛОКАЛЬНОЕ СОСТОЯНИЕ ===
const localFilters = ref<CatalogFilter>({
    category: props.category,
    sortBy: 'popular',
    sortOrder: 'desc'
})

// === COMPUTED ===
const items = computed(() => store.state.items)
const isLoading = computed(() => store.state.isLoading)
const hasError = computed(() => store.hasError)
const error = computed(() => store.state.error)
const isEmpty = computed(() => store.isEmpty)
const promotedItems = computed(() => store.promotedItems)
const regularItems = computed(() => store.regularItems)
const hasMore = computed(() => store.state.hasMore)
const totalCount = computed(() => store.state.totalCount)
const showFilters = computed(() => props.showFilters && !props.compact)
const layout = computed(() => props.compact ? 'list' : props.layout)

// === МЕТОДЫ ===

/**
 * Инициализация виджета
 */
async function initialize() {
    await store.loadCatalog(localFilters.value)
}

/**
 * Обработка изменения фильтров
 */
function handleFilterChange() {
    // Debounce для производительности
    clearTimeout(filterTimeout)
    filterTimeout = setTimeout(async () => {
        await store.applyFilters(localFilters.value)
        emit('filter-changed', localFilters.value)
    }, 300)
}

let filterTimeout: number

/**
 * Загрузить еще элементов
 */
async function loadMore() {
    await store.loadMore()
    emit('load-more')
}

/**
 * Повтор при ошибке
 */
async function retry() {
    store.clearError()
    await initialize()
}

/**
 * Клик по элементу каталога
 */
function handleItemClick(item: CatalogItem) {
    store.trackWidgetEvent('item_clicked', { 
        itemId: item.id, 
        category: item.category,
        price: item.price 
    })
  
    emit('item-selected', item)
}

/**
 * Клик по мастеру
 */
function handleMasterClick(masterId: number) {
    store.trackWidgetEvent('master_clicked', { masterId })
    emit('master-clicked', masterId)
}

// === РЕАКТИВНОСТЬ ===

// Перезагружаем при изменении категории
watch(
    () => props.category,
    (newCategory) => {
        localFilters.value.category = newCategory
        handleFilterChange()
    }
)

// === ЖИЗНЕННЫЙ ЦИКЛ ===

onMounted(() => {
    initialize()
})
</script>

<style scoped>
.catalog-widget {
  @apply w-full;
  
  /* CSS переменные из дизайн-токенов */
  --widget-primary: var(--color-blue-500);
  --widget-surface: var(--bg-surface);
  --widget-spacing: var(--spacing-4);
}

.catalog-widget__filters {
  @apply sticky top-0 z-10;
}

.catalog-widget__content {
  min-height: 400px;
}

/* Адаптивность */
@media (max-width: 768px) {
  .catalog-widget__filters .grid {
    @apply grid-cols-1 gap-2;
  }
}
</style>