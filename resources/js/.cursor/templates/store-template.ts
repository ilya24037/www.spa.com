import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { Ref } from 'vue'

// Types
interface Item {
  id: number
  title: string
  description?: string
  isActive: boolean
  createdAt: string
  updatedAt: string
}

interface CreateItemData {
  title: string
  description?: string
}

interface UpdateItemData {
  title?: string
  description?: string
  isActive?: boolean
}

interface ApiResponse<T> {
  data: T
  message?: string
  success: boolean
}

interface PaginationMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
}

interface PaginatedResponse<T> {
  data: T[]
  meta: PaginationMeta
}

// Store
export const useItemStore = defineStore('items', () => {
  // State
  const items: Ref<Item[]> = ref([])
  const loading: Ref<boolean> = ref(false)
  const error: Ref<string | null> = ref(null)
  const selectedItem: Ref<Item | null> = ref(null)
  const filters: Ref<Record<string, any>> = ref({})
  const pagination: Ref<PaginationMeta | null> = ref(null)

  // Getters
  const activeItems = computed(() => 
    items.value.filter(item => item.isActive)
  )

  const itemById = computed(() => (id: number) => 
    items.value.find(item => item.id === id)
  )

  const filteredItems = computed(() => {
    let filtered = items.value

    if (filters.value.search) {
      const search = filters.value.search.toLowerCase()
      filtered = filtered.filter(item => 
        item.title.toLowerCase().includes(search) ||
        item.description?.toLowerCase().includes(search)
      )
    }

    if (filters.value.isActive !== undefined) {
      filtered = filtered.filter(item => 
        item.isActive === filters.value.isActive
      )
    }

    return filtered
  })

  const hasItems = computed(() => items.value.length > 0)
  const isLoading = computed(() => loading.value)
  const hasError = computed(() => error.value !== null)

  // Actions
  const fetchItems = async (params?: Record<string, any>) => {
    loading.value = true
    error.value = null

    try {
      // Simulate API call - replace with actual API
      const response = await mockApiCall(params)
      
      if (response.success) {
        items.value = response.data.data
        pagination.value = response.data.meta
        error.value = null
      } else {
        throw new Error(response.message || 'Failed to fetch items')
      }
    } catch (err: any) {
      error.value = err.message || 'An error occurred'
      console.error('Failed to fetch items:', err)
    } finally {
      loading.value = false
    }
  }

  const fetchItem = async (id: number) => {
    loading.value = true
    error.value = null

    try {
      // Simulate API call - replace with actual API
      const response = await mockApiCall({ id })
      
      if (response.success) {
        selectedItem.value = response.data
        error.value = null
      } else {
        throw new Error(response.message || 'Failed to fetch item')
      }
    } catch (err: any) {
      error.value = err.message || 'An error occurred'
      console.error('Failed to fetch item:', err)
    } finally {
      loading.value = false
    }
  }

  const createItem = async (data: CreateItemData) => {
    loading.value = true
    error.value = null

    try {
      // Simulate API call - replace with actual API
      const response = await mockApiCall(data, 'POST')
      
      if (response.success) {
        items.value.unshift(response.data)
        error.value = null
        return response.data
      } else {
        throw new Error(response.message || 'Failed to create item')
      }
    } catch (err: any) {
      error.value = err.message || 'An error occurred'
      console.error('Failed to create item:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const updateItem = async (id: number, data: UpdateItemData) => {
    loading.value = true
    error.value = null

    try {
      // Simulate API call - replace with actual API
      const response = await mockApiCall({ id, ...data }, 'PUT')
      
      if (response.success) {
        const index = items.value.findIndex(item => item.id === id)
        if (index !== -1) {
          items.value[index] = { ...items.value[index], ...response.data }
        }
        
        if (selectedItem.value?.id === id) {
          selectedItem.value = { ...selectedItem.value, ...response.data }
        }
        
        error.value = null
        return response.data
      } else {
        throw new Error(response.message || 'Failed to update item')
      }
    } catch (err: any) {
      error.value = err.message || 'An error occurred'
      console.error('Failed to update item:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const deleteItem = async (id: number) => {
    loading.value = true
    error.value = null

    try {
      // Simulate API call - replace with actual API
      const response = await mockApiCall({ id }, 'DELETE')
      
      if (response.success) {
        items.value = items.value.filter(item => item.id !== id)
        
        if (selectedItem.value?.id === id) {
          selectedItem.value = null
        }
        
        error.value = null
        return true
      } else {
        throw new Error(response.message || 'Failed to delete item')
      }
    } catch (err: any) {
      error.value = err.message || 'An error occurred'
      console.error('Failed to delete item:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const setFilters = (newFilters: Record<string, any>) => {
    filters.value = { ...filters.value, ...newFilters }
  }

  const clearFilters = () => {
    filters.value = {}
  }

  const clearError = () => {
    error.value = null
  }

  const reset = () => {
    items.value = []
    loading.value = false
    error.value = null
    selectedItem.value = null
    filters.value = {}
    pagination.value = null
  }

  // Mock API function - replace with actual API calls
  const mockApiCall = async (data: any, method: string = 'GET'): Promise<ApiResponse<any>> => {
    // Simulate network delay
    await new Promise(resolve => setTimeout(resolve, 500))
    
    // Simulate different responses based on method
    switch (method) {
      case 'GET':
        return {
          success: true,
          data: {
            data: [
              {
                id: 1,
                title: 'Sample Item 1',
                description: 'This is a sample item',
                isActive: true,
                createdAt: new Date().toISOString(),
                updatedAt: new Date().toISOString()
              }
            ],
            meta: {
              current_page: 1,
              last_page: 1,
              per_page: 10,
              total: 1
            }
          }
        }
      case 'POST':
        return {
          success: true,
          data: {
            id: Date.now(),
            ...data,
            isActive: true,
            createdAt: new Date().toISOString(),
            updatedAt: new Date().toISOString()
          }
        }
      case 'PUT':
        return {
          success: true,
          data: {
            ...data,
            updatedAt: new Date().toISOString()
          }
        }
      case 'DELETE':
        return {
          success: true,
          data: true
        }
      default:
        throw new Error('Unsupported method')
    }
  }

  return {
    // State
    items,
    loading,
    error,
    selectedItem,
    filters,
    pagination,
    
    // Getters
    activeItems,
    itemById,
    filteredItems,
    hasItems,
    isLoading,
    hasError,
    
    // Actions
    fetchItems,
    fetchItem,
    createItem,
    updateItem,
    deleteItem,
    setFilters,
    clearFilters,
    clearError,
    reset
  }
})
