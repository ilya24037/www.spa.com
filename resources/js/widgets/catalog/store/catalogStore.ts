/**
 * Изолированное состояние виджета Catalog
 */

import { defineStore } from 'pinia'
import { ref, computed, readonly } from 'vue'
import { logger } from '@/src/shared/utils/logger'
import type { 
  CatalogItem, 
  CatalogFilter, 
  CatalogWidgetState 
} from '../types/catalog.types'

export const useCatalogWidgetStore = defineStore('catalog-widget', () => {
  // === СОСТОЯНИЕ ===
  const state = ref<CatalogWidgetState>({
    items: [],
    isLoading: false,
    error: null,
    filters: {},
    totalCount: 0,
    currentPage: 1,
    hasMore: true
  })

  // === COMPUTED ===
  const isLoaded = computed(() => !state.value.isLoading && state.value.items.length > 0)
  const hasError = computed(() => state.value.error !== null)
  const isEmpty = computed(() => !state.value.isLoading && state.value.items.length === 0)
  
  const promotedItems = computed(() => 
    state.value.items.filter(item => item.isPromoted)
  )
  
  const regularItems = computed(() => 
    state.value.items.filter(item => !item.isPromoted)
  )

  // === ACTIONS ===

  /**
   * Загрузить каталог
   */
  async function loadCatalog(filters: CatalogFilter = {}, page = 1) {
    try {
      state.value.isLoading = true
      state.value.error = null
      
      // Если новые фильтры - сбрасываем список
      if (page === 1) {
        state.value.items = []
      }
      
      state.value.filters = { ...state.value.filters, ...filters }
      state.value.currentPage = page
      
      // Мок данные для демонстрации
      const mockItems = generateMockItems(filters, page)
      
      if (page === 1) {
        state.value.items = mockItems
      } else {
        state.value.items.push(...mockItems)
      }
      
      state.value.totalCount = 50 // Мок общее количество
      state.value.hasMore = page * 12 < state.value.totalCount
      
      trackWidgetEvent('catalog_loaded', { 
        filters, 
        page, 
        itemsCount: mockItems.length 
      })
      
    } catch (error) {
      state.value.error = error instanceof Error ? error.message : 'Ошибка загрузки каталога'
      logger.error('[CatalogWidget] Load error:', error)
    } finally {
      state.value.isLoading = false
    }
  }

  /**
   * Загрузить еще элементы
   */
  async function loadMore() {
    if (state.value.hasMore && !state.value.isLoading) {
      await loadCatalog(state.value.filters, state.value.currentPage + 1)
    }
  }

  /**
   * Применить фильтры
   */
  async function applyFilters(filters: CatalogFilter) {
    await loadCatalog(filters, 1)
  }

  /**
   * Очистить ошибку
   */
  function clearError() {
    state.value.error = null
  }

  /**
   * Сброс состояния
   */
  function reset() {
    state.value = {
      items: [],
      isLoading: false,
      error: null,
      filters: {},
      totalCount: 0,
      currentPage: 1,
      hasMore: true
    }
  }

  /**
   * Генерация мок данных
   */
  function generateMockItems(filters: CatalogFilter, page: number): CatalogItem[] {
    const itemsPerPage = 12
    const startIndex = (page - 1) * itemsPerPage
    
    return Array.from({ length: itemsPerPage }, (_, i) => ({
      id: startIndex + i + 1,
      title: `Массаж ${getRandomMassageType()} ${startIndex + i + 1}`,
      description: `Профессиональный массаж с использованием современных техник`,
      price: Math.floor(Math.random() * 3000) + 1000,
      image: `https://picsum.photos/300/200?random=${startIndex + i + 1}`,
      category: filters.category || getRandomCategory(),
      masterId: Math.floor(Math.random() * 20) + 1,
      masterName: `Мастер ${Math.floor(Math.random() * 20) + 1}`,
      rating: Math.round((Math.random() * 2 + 3) * 10) / 10,
      location: getRandomLocation(),
      isPromoted: Math.random() > 0.8
    }))
  }

  function getRandomMassageType(): string {
    const types = ['релаксационный', 'лечебный', 'спортивный', 'антицеллюлитный', 'лимфодренажный']
    return types[Math.floor(Math.random() * types.length)] || 'релаксационный'
  }

  function getRandomCategory(): string {
    const categories = ['massage', 'spa', 'beauty', 'wellness']
    return categories[Math.floor(Math.random() * categories.length)] || 'massage'
  }

  function getRandomLocation(): string {
    const locations = ['Москва', 'СПб', 'Казань', 'Новосибирск', 'Екатеринбург']
    return locations[Math.floor(Math.random() * locations.length)] || 'Москва'
  }

  /**
   * Отслеживание событий виджета
   */
  function trackWidgetEvent(event: string, data: Record<string, unknown>) {
    if (typeof window !== 'undefined') {
      window.dispatchEvent(new CustomEvent('widget-analytics', {
        detail: {
          widget: 'catalog',
          event,
          data,
          timestamp: Date.now()
        }
      }))
    }
  }

  return {
    // State
    state: readonly(state),
    
    // Computed
    isLoaded,
    hasError,
    isEmpty,
    promotedItems,
    regularItems,
    
    // Actions
    loadCatalog,
    loadMore,
    applyFilters,
    clearError,
    reset,
    
    // Events
    trackWidgetEvent
  }
})

export type CatalogWidgetStore = ReturnType<typeof useCatalogWidgetStore>