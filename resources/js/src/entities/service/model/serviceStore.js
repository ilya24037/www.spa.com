import { logger } from '@/src/shared/lib/logger'

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useServiceStore = defineStore('service', () => {
  // State
  const items = ref([])
  const currentItem = ref(null)
  const loading = ref(false)
  const error = ref(null)
  
  // Getters
  const itemsCount = computed(() => items.value.length)
  const hasItems = computed(() => items.value.length > 0)
  
  // Actions
  const fetchItems = async () => {
    loading.value = true
    error.value = null
    
    try {
      // API call here
      // const response = await api.getServices()
      // items.value = response.data
      items.value = []
    } catch (err) {
      error.value = err.message || 'Ошибка загрузки'
      logger.error('Error fetching services:', err)
    } finally {
      loading.value = false
    }
  }
  
  const fetchItem = async (id) => {
    loading.value = true
    error.value = null
    
    try {
      // API call here
      // const response = await api.getService(id)
      // currentItem.value = response.data
      currentItem.value = null
    } catch (err) {
      error.value = err.message || 'Ошибка загрузки'
      logger.error('Error fetching service:', err)
    } finally {
      loading.value = false
    }
  }
  
  const createItem = async (data) => {
    loading.value = true
    error.value = null
    
    try {
      // API call here
      // const response = await api.createService(data)
      // items.value.push(response.data)
      // return response.data
      return null
    } catch (err) {
      error.value = err.message || 'Ошибка создания'
      logger.error('Error creating service:', err)
      throw err
    } finally {
      loading.value = false
    }
  }
  
  const updateItem = async (id, data) => {
    loading.value = true
    error.value = null
    
    try {
      // API call here
      // const response = await api.updateService(id, data)
      // const index = items.value.findIndex(item => item.id === id)
      // if (index !== -1) {
      //   items.value[index] = response.data
      // }
      // return response.data
      return null
    } catch (err) {
      error.value = err.message || 'Ошибка обновления'
      logger.error('Error updating service:', err)
      throw err
    } finally {
      loading.value = false
    }
  }
  
  const deleteItem = async (id) => {
    loading.value = true
    error.value = null
    
    try {
      // API call here
      // await api.deleteService(id)
      items.value = items.value.filter(item => item.id !== id)
    } catch (err) {
      error.value = err.message || 'Ошибка удаления'
      logger.error('Error deleting service:', err)
      throw err
    } finally {
      loading.value = false
    }
  }
  
  const reset = () => {
    items.value = []
    currentItem.value = null
    loading.value = false
    error.value = null
  }
  
  return {
    // State
    items,
    currentItem,
    loading,
    error,
    
    // Getters
    itemsCount,
    hasItems,
    
    // Actions
    fetchItems,
    fetchItem,
    createItem,
    updateItem,
    deleteItem,
    reset
  }
})

export default useServiceStore