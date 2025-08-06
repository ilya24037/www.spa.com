/**
 * useAdaptiveGrid - Композабл для управления адаптивной сеткой
 * Обеспечивает логику переключения видов, сортировки и адаптивности
 */

import { ref, computed, watch, onMounted, onUnmounted } from 'vue'

export type GridView = 'grid' | 'list'
export type GridDensity = 'comfortable' | 'compact'  
export type GridSort = 'popular' | 'price-asc' | 'price-desc' | 'rating' | 'date'

export interface GridConfig {
  defaultView?: GridView
  defaultDensity?: GridDensity
  defaultSort?: GridSort
  defaultColumns?: number | 'auto'
  enableViewToggle?: boolean
  enableDensityToggle?: boolean
  enableColumnControl?: boolean
  saveToLocalStorage?: boolean
  storageKey?: string
}

export interface GridBreakpoints {
  mobile: number
  tablet: number
  desktop: number
  wide: number
}

export function useAdaptiveGrid(config: GridConfig = {}) {
  // === КОНФИГУРАЦИЯ ===
  
  const {
    defaultView = 'grid',
    defaultDensity = 'comfortable',
    defaultSort = 'popular',
    defaultColumns = 'auto',
    enableViewToggle = true,
    enableDensityToggle = true,
    enableColumnControl = true,
    saveToLocalStorage = true,
    storageKey = 'adaptive-grid-preferences'
  } = config

  // === РЕАКТИВНОЕ СОСТОЯНИЕ ===
  
  const currentView = ref<GridView>(defaultView)
  const currentDensity = ref<GridDensity>(defaultDensity)
  const currentSort = ref<GridSort>(defaultSort)
  const currentColumns = ref<number | 'auto'>(defaultColumns)
  const screenWidth = ref(typeof window !== 'undefined' ? window.innerWidth : 1024)

  // === БРЕЙКПОИНТЫ ===
  
  const breakpoints: GridBreakpoints = {
    mobile: 640,
    tablet: 768,
    desktop: 1024,
    wide: 1280
  }

  // === COMPUTED PROPERTIES ===

  const deviceType = computed(() => {
    if (screenWidth.value < breakpoints.mobile) return 'mobile'
    if (screenWidth.value < breakpoints.tablet) return 'tablet'
    if (screenWidth.value < breakpoints.desktop) return 'desktop'
    return 'wide'
  })

  const isMobile = computed(() => deviceType.value === 'mobile')
  const isTablet = computed(() => deviceType.value === 'tablet')
  const isDesktop = computed(() => ['desktop', 'wide'].includes(deviceType.value))

  // Автоматическое определение количества колонок
  const autoColumns = computed(() => {
    if (currentView.value === 'list') return 1
    
    switch (deviceType.value) {
      case 'mobile': return 1
      case 'tablet': return 2
      case 'desktop': return 3
      case 'wide': return currentDensity.value === 'compact' ? 5 : 4
      default: return 3
    }
  })

  const effectiveColumns = computed(() => {
    return currentColumns.value === 'auto' ? autoColumns.value : currentColumns.value
  })

  // CSS классы для сетки
  const gridClasses = computed(() => {
    const classes: string[] = []
    
    // Базовый класс
    if (currentView.value === 'list') {
      classes.push('list-view')
    } else {
      classes.push('grid-view')
    }
    
    // Плотность
    if (currentDensity.value === 'compact') {
      classes.push('compact-grid')
    } else {
      classes.push('service-grid')
    }
    
    // Дополнительные модификаторы
    classes.push(`device-${deviceType.value}`)
    classes.push(`cols-${effectiveColumns.value}`)
    
    return classes
  })

  // CSS переменные для настройки
  const gridStyles = computed(() => {
    const styles: Record<string, string> = {}
    
    // Переопределяем количество колонок если не auto
    if (currentColumns.value !== 'auto') {
      styles['--grid-columns'] = String(currentColumns.value)
    }
    
    // Настройка отступов в зависимости от плотности
    if (currentDensity.value === 'compact') {
      styles['--grid-gap'] = '0.75rem'
    } else {
      styles['--grid-gap'] = isMobile.value ? '1rem' : '1.5rem'
    }
    
    return styles
  })

  // === МЕТОДЫ ===

  function setView(view: GridView) {
    currentView.value = view
    savePreferences()
  }

  function setDensity(density: GridDensity) {
    currentDensity.value = density
    savePreferences()
  }

  function setSort(sort: GridSort) {
    currentSort.value = sort
    savePreferences()
  }

  function setColumns(columns: number | 'auto') {
    currentColumns.value = columns
    savePreferences()
  }

  function toggleView() {
    setView(currentView.value === 'grid' ? 'list' : 'grid')
  }

  function toggleDensity() {
    setDensity(currentDensity.value === 'comfortable' ? 'compact' : 'comfortable')
  }

  // === СОХРАНЕНИЕ НАСТРОЕК ===

  function savePreferences() {
    if (!saveToLocalStorage || typeof window === 'undefined') return
    
    const preferences = {
      view: currentView.value,
      density: currentDensity.value,
      sort: currentSort.value,
      columns: currentColumns.value,
      timestamp: Date.now()
    }
    
    try {
      localStorage.setItem(storageKey, JSON.stringify(preferences))
    } catch (error) {
      console.warn('[useAdaptiveGrid] Failed to save preferences:', error)
    }
  }

  function loadPreferences() {
    if (!saveToLocalStorage || typeof window === 'undefined') return
    
    try {
      const stored = localStorage.getItem(storageKey)
      if (!stored) return
      
      const preferences = JSON.parse(stored)
      
      // Проверяем что настройки не слишком старые (30 дней)
      const maxAge = 30 * 24 * 60 * 60 * 1000
      if (Date.now() - preferences.timestamp > maxAge) {
        localStorage.removeItem(storageKey)
        return
      }
      
      if (preferences.view) currentView.value = preferences.view
      if (preferences.density) currentDensity.value = preferences.density
      if (preferences.sort) currentSort.value = preferences.sort
      if (preferences.columns) currentColumns.value = preferences.columns
      
    } catch (error) {
      console.warn('[useAdaptiveGrid] Failed to load preferences:', error)
      localStorage.removeItem(storageKey)
    }
  }

  // === АДАПТИВНОСТЬ ===

  function handleResize() {
    screenWidth.value = window.innerWidth
    
    // На мобильных устройствах принудительно переключаем в список
    if (isMobile.value && currentView.value === 'grid' && enableViewToggle) {
      currentView.value = 'list'
    }
    
    // На десктопе возвращаем сетку если был список только из-за мобильного
    if (isDesktop.value && currentView.value === 'list' && !isForcedListView.value) {
      currentView.value = 'grid'
    }
  }

  // Флаг принудительного списочного вида
  const isForcedListView = ref(false)

  // === PERFORMANCE ===

  // Debounce для resize
  let resizeTimeout: number | null = null
  function debouncedResize() {
    if (resizeTimeout) clearTimeout(resizeTimeout)
    resizeTimeout = window.setTimeout(handleResize, 150)
  }

  // === ANALYTICS ===

  function trackGridChange(type: string, value: any) {
    if (typeof window !== 'undefined' && window.gtag) {
      window.gtag('event', 'grid_change', {
        event_category: 'UI',
        event_label: type,
        custom_parameter_1: value,
        custom_parameter_2: deviceType.value
      })
    }
  }

  // === WATCHERS ===

  watch(currentView, (newView) => {
    trackGridChange('view', newView)
  })

  watch(currentDensity, (newDensity) => {
    trackGridChange('density', newDensity)
  })

  watch(currentSort, (newSort) => {
    trackGridChange('sort', newSort)
  })

  // === ЖИЗНЕННЫЙ ЦИКЛ ===

  onMounted(() => {
    loadPreferences()
    
    if (typeof window !== 'undefined') {
      window.addEventListener('resize', debouncedResize)
      handleResize() // Начальная проверка
    }
  })

  onUnmounted(() => {
    if (typeof window !== 'undefined') {
      window.removeEventListener('resize', debouncedResize)
    }
    
    if (resizeTimeout) {
      clearTimeout(resizeTimeout)
    }
  })

  // === СОРТИРОВКА ===

  function applySorting<T extends Record<string, any>>(items: T[]): T[] {
    const sorted = [...items]
    
    switch (currentSort.value) {
      case 'price-asc':
        return sorted.sort((a, b) => (a.price || 0) - (b.price || 0))
      
      case 'price-desc':
        return sorted.sort((a, b) => (b.price || 0) - (a.price || 0))
      
      case 'rating':
        return sorted.sort((a, b) => (b.rating || 0) - (a.rating || 0))
      
      case 'date':
        return sorted.sort((a, b) => {
          const dateA = new Date(a.created_at || a.date || 0).getTime()
          const dateB = new Date(b.created_at || b.date || 0).getTime()
          return dateB - dateA
        })
      
      case 'popular':
      default:
        return sorted.sort((a, b) => {
          // Популярность = рейтинг * количество отзывов + бонус за премиум
          const popularityA = (a.rating || 0) * (a.reviews_count || 1) + (a.is_premium ? 100 : 0)
          const popularityB = (b.rating || 0) * (b.reviews_count || 1) + (b.is_premium ? 100 : 0)
          return popularityB - popularityA
        })
    }
  }

  // === ВОЗВРАЩАЕМЫЙ API ===

  return {
    // Состояние
    currentView,
    currentDensity,
    currentSort,
    currentColumns,
    
    // Информация об устройстве
    deviceType,
    isMobile,
    isTablet,
    isDesktop,
    screenWidth,
    
    // Вычисляемые значения
    effectiveColumns,
    gridClasses,
    gridStyles,
    
    // Методы управления
    setView,
    setDensity,
    setSort,
    setColumns,
    toggleView,
    toggleDensity,
    
    // Утилиты
    applySorting,
    savePreferences,
    loadPreferences,
    
    // Конфигурация
    enableViewToggle,
    enableDensityToggle,
    enableColumnControl,
    
    // Брейкпоинты
    breakpoints
  }
}