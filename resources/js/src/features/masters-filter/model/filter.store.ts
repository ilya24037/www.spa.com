import { logger } from '@/src/shared/lib/logger'

/**
 * Store для фильтрации мастеров
 * Реализует сложную логику фильтрации с поддержкой:
 * - Множественного выбора услуг
 * - Диапазона цен
 * - Геолокации и радиуса поиска
 * - Рейтинга и отзывов
 * - Времени работы
 * - Типа размещения (дом/салон)
 */

import { defineStore } from 'pinia'
import { ref, computed, watch } from 'vue'
import type { 
  FilterState, 
  LocationFilter, 
  PriceRange,
  WorkingHoursFilter,
  Master,
  FilterOptions,
  ServiceLocationType,
  SortingType
} from '../model/types'

export const useFilterStore = defineStore('masters-filter', () => {
  
  // =================== СОСТОЯНИЕ ===================
  
  const filters = ref<FilterState>({
    services: [], // ID выбранных услуг
    priceRange: {
      min: 0,
      max: 10000
    },
    location: {
      address: '',
      lat: null,
      lng: null,
      radius: 5 // км
    },
    rating: {
      min: 0,
      onlyWithReviews: false
    },
    workingHours: {
      day: null, // 'today', 'tomorrow', конкретная дата
      timeFrom: null,
      timeTo: null
    },
    serviceLocation: [], // 'home', 'salon'
    availability: {
      availableToday: false,
      availableTomorrow: false,
      availableThisWeek: false
    },
    sorting: 'relevance' // 'relevance', 'rating', 'price_asc', 'price_desc', 'distance'
  })

  const isLoading = ref(false)
  const error = ref<string | null>(null)
  
  // Опции для селектов (загружаются с сервера)
  const options = ref<FilterOptions>({
    services: [],
    priceRanges: [
      { min: 0, max: 1000, label: 'До 1000 ₽' },
      { min: 1000, max: 3000, label: '1000 - 3000 ₽' },
      { min: 3000, max: 5000, label: '3000 - 5000 ₽' },
      { min: 5000, max: 10000, label: '5000 - 10000 ₽' },
      { min: 10000, max: null, label: 'От 10000 ₽' }
    ],
    districts: [],
    metros: []
  })

  // История примененных фильтров для кнопки "Назад"
  const filterHistory = ref<FilterState[]>([])
  
  // =================== ВЫЧИСЛЯЕМЫЕ ===================
  
  // Количество активных фильтров для бейджа
  const activeFiltersCount = computed(() => {
    let count = 0
    
    if (filters.value.services.length > 0) count++
    if (filters.value.priceRange.min > 0 || filters.value.priceRange.max < 10000) count++
    if (filters.value.location.address) count++
    if (filters.value.rating.min > 0) count++
    if (filters.value.workingHours.day) count++
    if (filters.value.serviceLocation.length > 0) count++
    if (filters.value.availability.availableToday || 
        filters.value.availability.availableTomorrow || 
        filters.value.availability.availableThisWeek) count++
    
    return count
  })

  // Есть ли активные фильтры
  const hasActiveFilters = computed(() => activeFiltersCount.value > 0)

  // Текст для кнопки "Применить фильтры"
  const applyButtonText = computed(() => {
    const count = activeFiltersCount.value
    if (count === 0) return 'Показать всех мастеров'
    return `Применить фильтры (${count})`
  })

  // Выбранные услуги как объекты
  const selectedServices = computed(() => {
    return options.value.services.filter(service => 
      filters.value.services.includes(service.id)
    )
  })

  // Строка с выбранными услугами для отображения
  const selectedServicesText = computed(() => {
    const services = selectedServices.value
    if (services.length === 0) return 'Любые услуги'
    if (services.length === 1) return services[0].name
    if (services.length <= 3) return services.map(s => s.name).join(', ')
    return `${services[0].name} и еще ${services.length - 1}`
  })

  // Диапазон цен как строка
  const priceRangeText = computed(() => {
    const { min, max } = filters.value.priceRange
    if (min === 0 && max === 10000) return 'Любая цена'
    if (min === 0) return `До ${max} ₽`
    if (max === 10000) return `От ${min} ₽`
    return `${min} - ${max} ₽`
  })

  // =================== ДЕЙСТВИЯ ===================

  // 🔄 Загрузить опции фильтров с сервера
  async function loadFilterOptions() {
    isLoading.value = true
    error.value = null

    try {
      const response = await fetch('/api/masters/filters/options', {
        headers: {
          'Accept': 'application/json'
        }
      })

      if (!response.ok) {
        throw new Error('Не удалось загрузить опции фильтров')
      }

      const data = await response.json()
      options.value = {
        ...options.value,
        ...data
      }

    } catch (err) {
      error.value = err instanceof Error ? err.message : 'Ошибка загрузки фильтров'
      logger.error('❌ Ошибка загрузки опций фильтров:', err)
    } finally {
      isLoading.value = false
    }
  }

  // 🎛️ Установить фильтр услуг
  function setServiceFilter(serviceIds: number[]) {
    filters.value.services = [...serviceIds]
  }

  // ➕ Добавить услугу к фильтру
  function addServiceToFilter(serviceId: number) {
    if (!filters.value.services.includes(serviceId)) {
      filters.value.services.push(serviceId)
    }
  }

  // ➖ Убрать услугу из фильтра
  function removeServiceFromFilter(serviceId: number) {
    const index = filters.value.services.indexOf(serviceId)
    if (index > -1) {
      filters.value.services.splice(index, 1)
    }
  }

  // 💰 Установить диапазон цен
  function setPriceRange(min: number, max: number) {
    filters.value.priceRange = { min, max }
  }

  // 📍 Установить локацию
  function setLocation(address: string, lat?: number, lng?: number, radius?: number) {
    filters.value.location = {
      address,
      lat: lat || null,
      lng: lng || null,
      radius: radius || filters.value.location.radius
    }
  }

  // ⭐ Установить фильтр рейтинга
  function setRatingFilter(min: number, onlyWithReviews: boolean = false) {
    filters.value.rating = { min, onlyWithReviews }
  }

  // 🕐 Установить фильтр времени работы
  function setWorkingHoursFilter(day: string | null, timeFrom?: string, timeTo?: string) {
    filters.value.workingHours = {
      day,
      timeFrom: timeFrom || null,
      timeTo: timeTo || null
    }
  }

  // 🏠 Установить тип размещения
  function setServiceLocationFilter(locations: ServiceLocationType[]) {
    filters.value.serviceLocation = [...locations]
  }

  // 📅 Установить фильтр доступности
  function setAvailabilityFilter(
    availableToday: boolean, 
    availableTomorrow: boolean, 
    availableThisWeek: boolean
  ) {
    filters.value.availability = {
      availableToday,
      availableTomorrow,
      availableThisWeek
    }
  }

  // 🔄 Установить сортировку
  function setSorting(sorting: SortingType) {
    filters.value.sorting = sorting
  }

  // 🧹 Сбросить все фильтры
  function resetFilters() {
    // Сохраняем текущее состояние в историю
    addToHistory()
    
    filters.value = {
      services: [],
      priceRange: { min: 0, max: 10000 },
      location: { address: '', lat: null, lng: null, radius: 5 },
      rating: { min: 0, onlyWithReviews: false },
      workingHours: { day: null, timeFrom: null, timeTo: null },
      serviceLocation: [],
      availability: {
        availableToday: false,
        availableTomorrow: false,
        availableThisWeek: false
      },
      sorting: 'relevance'
    }
  }

  // 🧹 Сбросить конкретный фильтр
  function resetFilter(filterName: keyof FilterState) {
    addToHistory()
    
    switch (filterName) {
      case 'services':
        filters.value.services = []
        break
      case 'priceRange':
        filters.value.priceRange = { min: 0, max: 10000 }
        break
      case 'location':
        filters.value.location = { address: '', lat: null, lng: null, radius: 5 }
        break
      case 'rating':
        filters.value.rating = { min: 0, onlyWithReviews: false }
        break
      case 'workingHours':
        filters.value.workingHours = { day: null, timeFrom: null, timeTo: null }
        break
      case 'serviceLocation':
        filters.value.serviceLocation = []
        break
      case 'availability':
        filters.value.availability = {
          availableToday: false,
          availableTomorrow: false,
          availableThisWeek: false
        }
        break
    }
  }

  // 📚 Добавить в историю
  function addToHistory() {
    filterHistory.value.push(JSON.parse(JSON.stringify(filters.value)))
    // Ограничиваем историю 10 записями
    if (filterHistory.value.length > 10) {
      filterHistory.value.shift()
    }
  }

  // ↩️ Вернуться к предыдущему состоянию
  function goBack() {
    if (filterHistory.value.length > 0) {
      const previousState = filterHistory.value.pop()
      if (previousState) {
        filters.value = previousState
      }
    }
  }

  // 🔍 Получить строку запроса для API
  function getQueryParams(): Record<string, any> {
    const params: Record<string, any> = {}

    if (filters.value.services.length > 0) {
      params.services = filters.value.services.join(',')
    }

    if (filters.value.priceRange.min > 0) {
      params.price_min = filters.value.priceRange.min
    }
    
    if (filters.value.priceRange.max < 10000) {
      params.price_max = filters.value.priceRange.max
    }

    if (filters.value.location.lat && filters.value.location.lng) {
      params.lat = filters.value.location.lat
      params.lng = filters.value.location.lng
      params.radius = filters.value.location.radius
    }

    if (filters.value.rating.min > 0) {
      params.rating_min = filters.value.rating.min
    }

    if (filters.value.rating.onlyWithReviews) {
      params.only_with_reviews = 1
    }

    if (filters.value.workingHours.day) {
      params.available_day = filters.value.workingHours.day
      if (filters.value.workingHours.timeFrom) {
        params.time_from = filters.value.workingHours.timeFrom
      }
      if (filters.value.workingHours.timeTo) {
        params.time_to = filters.value.workingHours.timeTo
      }
    }

    if (filters.value.serviceLocation.length > 0) {
      params.service_location = filters.value.serviceLocation.join(',')
    }

    if (filters.value.availability.availableToday) {
      params.available_today = 1
    }

    if (filters.value.availability.availableTomorrow) {
      params.available_tomorrow = 1
    }

    if (filters.value.availability.availableThisWeek) {
      params.available_this_week = 1
    }

    params.sort = filters.value.sorting

    return params
  }

  // 💾 Сохранить фильтры в localStorage
  function saveFiltersToStorage() {
    try {
      localStorage.setItem('spa-masters-filters', JSON.stringify(filters.value))
    } catch (err) {
      logger.warn('Не удалось сохранить фильтры:', { metadata: { data: err } })
    }
  }

  // 📖 Загрузить фильтры из localStorage
  function loadFiltersFromStorage() {
    try {
      const saved = localStorage.getItem('spa-masters-filters')
      if (saved) {
        const parsed = JSON.parse(saved)
        // Мержим с дефолтными значениями
        filters.value = {
          ...filters.value,
          ...parsed
        }
      }
    } catch (err) {
      logger.warn('Не удалось загрузить сохраненные фильтры:', { metadata: { data: err } })
    }
  }

  // =================== WATCHERS ===================

  // Автосохранение при изменении фильтров
  watch(
    filters,
    () => {
      saveFiltersToStorage()
    },
    { deep: true }
  )

  // =================== ВОЗВРАЩАЕМ ИНТЕРФЕЙС ===================

  return {
    // Состояние
    filters,
    isLoading,
    error,
    options,
    filterHistory,

    // Вычисляемые
    activeFiltersCount,
    hasActiveFilters,
    applyButtonText,
    selectedServices,
    selectedServicesText,
    priceRangeText,

    // Действия
    loadFilterOptions,
    setServiceFilter,
    addServiceToFilter,
    removeServiceFromFilter,
    setPriceRange,
    setLocation,
    setRatingFilter,
    setWorkingHoursFilter,
    setServiceLocationFilter,
    setAvailabilityFilter,
    setSorting,
    resetFilters,
    resetFilter,
    addToHistory,
    goBack,
    getQueryParams,
    saveFiltersToStorage,
    loadFiltersFromStorage
  }
})