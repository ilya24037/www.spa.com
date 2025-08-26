/**
 * Store для фильтрации мастеров
 * Feature MastersFilter (FSD архитектура)
 */

import { defineStore } from 'pinia'
import { ref, reactive, computed } from 'vue'

export const useMastersFilterStore = defineStore('masters-filter', () => {
    // === СОСТОЯНИЕ ===
  
    // Основные фильтры
    const filters = reactive({
    // Поиск
        search: '',
    
        // Цена
        price_from: null,
        price_to: null,
    
        // Локация
        city: null,
        district: null,
        metro: null,
    
        // Категории
        categories: [],
    
        // Рейтинг
        rating: null,
    
        // Дополнительные параметры
        verified: false,
        premium: false,
        online: false,
        home_service: false,
        online_booking: false,
    
        // Сортировка
        sort_by: 'rating',
        sort_order: 'desc'
    })
  
    // Сохраненные фильтры для сравнения изменений
    const originalFilters = ref({ ...filters })
  
    // === ВЫЧИСЛЯЕМЫЕ СВОЙСТВА ===
  
    /**
   * Есть ли активные фильтры
   */
    const hasActiveFilters = computed(() => {
        return (
            filters.search ||
      filters.price_from !== null ||
      filters.price_to !== null ||
      filters.city ||
      filters.district ||
      filters.metro ||
      filters.categories.length > 0 ||
      filters.rating !== null ||
      filters.verified ||
      filters.premium ||
      filters.online ||
      filters.home_service ||
      filters.online_booking
        )
    })
  
    /**
   * Количество активных фильтров
   */
    const activeFiltersCount = computed(() => {
        let count = 0
    
        if (filters.search) count++
        if (filters.price_from !== null || filters.price_to !== null) count++
        if (filters.city) count++
        if (filters.district) count++
        if (filters.metro) count++
        if (filters.categories.length > 0) count++
        if (filters.rating !== null) count++
        if (filters.verified) count++
        if (filters.premium) count++
        if (filters.online) count++
        if (filters.home_service) count++
        if (filters.online_booking) count++
    
        return count
    })
  
    /**
   * Есть ли несохраненные изменения
   */
    const hasChanges = computed(() => {
        return JSON.stringify(filters) !== JSON.stringify(originalFilters.value)
    })
  
    /**
   * Параметры для API запроса
   */
    const apiParams = computed(() => {
        const params = {}
    
        // Добавляем только непустые значения
        Object.entries(filters).forEach(([key, value]) => {
            if (value !== null && value !== '' && value !== false && 
          !(Array.isArray(value) && value.length === 0)) {
                params[key] = value
            }
        })
    
        return params
    })
  
    // === ДЕЙСТВИЯ ===
  
    /**
   * Обновить фильтр
   */
    const updateFilter = (key, value) => {
        if (key in filters) {
            filters[key] = value
      
            // Автоматически сбрасываем зависимые фильтры
            if (key === 'city') {
                filters.district = null
                filters.metro = null
            }
        }
    }
  
    /**
   * Обновить несколько фильтров одновременно
   */
    const updateFilters = (newFilters) => {
        Object.entries(newFilters).forEach(([key, value]) => {
            updateFilter(key, value)
        })
    }
  
    /**
   * Сбросить все фильтры
   */
    const resetFilters = () => {
        filters.search = ''
        filters.price_from = null
        filters.price_to = null
        filters.city = null
        filters.district = null
        filters.metro = null
        filters.categories = []
        filters.rating = null
        filters.verified = false
        filters.premium = false
        filters.online = false
        filters.home_service = false
        filters.online_booking = false
        filters.sort_by = 'rating'
        filters.sort_order = 'desc'
    }
  
    /**
   * Сбросить конкретную группу фильтров
   */
    const resetFilterGroup = (group) => {
        switch (group) {
        case 'price':
            filters.price_from = null
            filters.price_to = null
            break
        
        case 'location':
            filters.city = null
            filters.district = null
            filters.metro = null
            break
        
        case 'categories':
            filters.categories = []
            break
        
        case 'additional':
            filters.verified = false
            filters.premium = false
            filters.online = false
            filters.home_service = false
            filters.online_booking = false
            break
        }
    }
  
    /**
   * Сохранить текущие фильтры как оригинальные
   */
    const saveFilters = () => {
        originalFilters.value = { ...filters }
    }
  
    /**
   * Отменить изменения (вернуть к сохраненным)
   */
    const revertFilters = () => {
        Object.assign(filters, originalFilters.value)
    }
  
    /**
   * Установить быстрые фильтры
   */
    const setQuickFilter = (type) => {
        resetFilters()
    
        switch (type) {
        case 'premium':
            filters.premium = true
            break
        
        case 'verified':
            filters.verified = true
            break
        
        case 'online':
            filters.online = true
            break
        
        case 'top-rated':
            filters.rating = 4
            filters.sort_by = 'rating'
            filters.sort_order = 'desc'
            break
        
        case 'most-popular':
            filters.sort_by = 'reviews_count'
            filters.sort_order = 'desc'
            break
        
        case 'newest':
            filters.sort_by = 'created_at'
            filters.sort_order = 'desc'
            break
        }
    }
  
    /**
   * Установить сортировку
   */
    const setSorting = (sortBy, sortOrder = 'desc') => {
        filters.sort_by = sortBy
        filters.sort_order = sortOrder
    }
  
    /**
   * Переключить порядок сортировки
   */
    const toggleSortOrder = () => {
        filters.sort_order = filters.sort_order === 'asc' ? 'desc' : 'asc'
    }
  
    // Возвращаем публичный API
    return {
    // Состояние
        filters,
    
        // Вычисляемые свойства
        hasActiveFilters,
        activeFiltersCount,
        hasChanges,
        apiParams,
    
        // Действия
        updateFilter,
        updateFilters,
        resetFilters,
        resetFilterGroup,
        saveFilters,
        revertFilters,
        setQuickFilter,
        setSorting,
        toggleSortOrder
    }
})