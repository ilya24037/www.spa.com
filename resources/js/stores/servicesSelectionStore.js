/**
 * Централизованный store для выбора услуг
 * Архитектура уровня маркетплейсов (Ozon/Avito)
 */

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useServicesSelectionStore = defineStore('servicesSelection', () => {
  // === СОСТОЯНИЕ ===
  
  // Нормализованная структура данных (как у Ozon) - Map для O(1) доступа
  const selections = ref(new Map())
  const priceUpdates = ref(new Map()) // Для оптимистичных обновлений
  const lastApiCall = ref(Date.now())
  
  // === COMPUTED ===
  
  /**
   * Общее количество выбранных услуг
   */
  const totalSelected = computed(() => selections.value.size)
  
  /**
   * Общая сумма всех выбранных услуг
   */
  const totalPrice = computed(() => {
    let sum = 0
    selections.value.forEach(selection => {
      sum += Number(selection.price) || 0
    })
    return sum
  })
  
  /**
   * Получение выбранных услуг по категориям
   */
  const selectionsByCategory = computed(() => {
    const result = {}
    selections.value.forEach((data, serviceId) => {
      const { categoryId } = data
      if (!result[categoryId]) {
        result[categoryId] = []
      }
      result[categoryId].push(data)
    })
    return result
  })
  
  /**
   * Статистика выбора по категориям
   */
  const categoryStats = computed(() => {
    const stats = {}
    selections.value.forEach((data, serviceId) => {
      const { categoryId } = data
      if (!stats[categoryId]) {
        stats[categoryId] = { count: 0, total: 0 }
      }
      stats[categoryId].count++
      stats[categoryId].total += Number(data.price) || 0
    })
    return stats
  })
  
  // === ГЕТТЕРЫ ===
  
  /**
   * Проверка выбрана ли услуга
   */
  const isServiceSelected = (serviceId) => {
    return selections.value.has(serviceId)
  }
  
  /**
   * Получение данных услуги
   */
  const getServiceData = (serviceId) => {
    return selections.value.get(serviceId) || {
      enabled: false,
      price: '',
      comment: '',
      serviceId,
      categoryId: null
    }
  }
  
  /**
   * Получение количества выбранных в категории
   */
  const getCategorySelectedCount = (categoryId) => {
    let count = 0
    selections.value.forEach(data => {
      if (data.categoryId === categoryId) {
        count++
      }
    })
    return count
  }
  
  // === ДЕЙСТВИЯ ===
  
  /**
   * Переключение услуги (как у Wildberries)
   */
  const toggleService = (serviceId, categoryId, initialData = {}) => {
    if (selections.value.has(serviceId)) {
      // Убираем услугу
      selections.value.delete(serviceId)
    } else {
      // Добавляем услугу
      selections.value.set(serviceId, {
        serviceId,
        categoryId,
        enabled: true,
        price: initialData.price || '',
        comment: initialData.comment || '',
        timestamp: Date.now(),
        ...initialData
      })
    }
  }
  
  /**
   * Обновление цены услуги с оптимистичным UI (как у Ozon)
   */
  const updateServicePrice = async (serviceId, price) => {
    const oldSelection = selections.value.get(serviceId)
    if (!oldSelection) return
    
    // Оптимистичное обновление UI
    const newSelection = { ...oldSelection, price }
    selections.value.set(serviceId, newSelection)
    
    try {
      // Здесь был бы API вызов для сохранения
      // await api.updateServicePrice(serviceId, price)
      await new Promise(resolve => setTimeout(resolve, 100))
      lastApiCall.value = Date.now()
    } catch (error) {
      // Откат при ошибке API
      selections.value.set(serviceId, oldSelection)
      throw error
    }
  }
  
  /**
   * Обновление комментария услуги
   */
  const updateServiceComment = (serviceId, comment) => {
    const selection = selections.value.get(serviceId)
    if (selection) {
      selections.value.set(serviceId, { ...selection, comment })
    }
  }
  
  /**
   * Массовые операции для категории (как у Avito)
   */
  const selectAllInCategory = (categoryId, services) => {
    services.forEach(service => {
      if (!selections.value.has(service.id)) {
        toggleService(service.id, categoryId, {
          price: service.defaultPrice || '',
          comment: ''
        })
      }
    })
  }
  
  const clearAllInCategory = (categoryId, services) => {
    services.forEach(service => {
      if (selections.value.has(service.id)) {
        const selection = selections.value.get(service.id)
        if (selection.categoryId === categoryId) {
          selections.value.delete(service.id)
        }
      }
    })
  }
  
  /**
   * Полная очистка всех услуг
   */
  const clearAllServices = () => {
    selections.value.clear()
    priceUpdates.value.clear()
  }
  
  /**
   * Массовое обновление услуг (для инициализации)
   */
  const initializeServices = (servicesData) => {
    selections.value.clear()
    
    Object.entries(servicesData || {}).forEach(([categoryId, categoryServices]) => {
      Object.entries(categoryServices || {}).forEach(([serviceId, serviceData]) => {
        if (serviceData && serviceData.enabled) {
          selections.value.set(serviceId, {
            serviceId,
            categoryId,
            enabled: true,
            price: serviceData.price || '',
            comment: serviceData.price_comment || '',
            timestamp: Date.now()
          })
        }
      })
    })
  }
  
  /**
   * Получение данных для отправки на сервер
   */
  const getFormattedData = () => {
    const result = {}
    selections.value.forEach((data, serviceId) => {
      const { categoryId } = data
      if (!result[categoryId]) {
        result[categoryId] = {}
      }
      result[categoryId][serviceId] = {
        enabled: data.enabled,
        price: data.price,
        price_comment: data.comment
      }
    })
    return result
  }
  
  /**
   * Получение массива выбранных услуг
   */
  const getSelectedServices = () => {
    return Array.from(selections.value.values())
  }
  
  /**
   * Сброс состояния store
   */
  const resetStore = () => {
    selections.value.clear()
    priceUpdates.value.clear()
    lastApiCall.value = Date.now()
  }
  
  return {
    // Состояние
    selections,
    
    // Computed
    totalSelected,
    totalPrice,
    selectionsByCategory,
    categoryStats,
    
    // Геттеры
    isServiceSelected,
    getServiceData,
    getCategorySelectedCount,
    
    // Действия
    toggleService,
    updateServicePrice,
    updateServiceComment,
    selectAllInCategory,
    clearAllInCategory,
    clearAllServices,
    initializeServices,
    getFormattedData,
    getSelectedServices,
    resetStore
  }
})