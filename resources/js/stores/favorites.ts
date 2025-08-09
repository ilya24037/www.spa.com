import { defineStore } from 'pinia'
import { ref, computed, type Ref } from 'vue'
import axios, { AxiosError } from 'axios'

// =================== TYPES ===================
export interface Master {
  id: number
  name: string
  avatar?: string
  rating?: number
  reviews_count?: number
  price_from?: number
  price_to?: number
  location?: string
  services?: Service[]
  is_verified?: boolean
  [key: string]: any
}

export interface Service {
  id: number
  name: string
  price: number
  duration?: number
  category?: string
}

export interface FavoriteToggleResponse {
  added: boolean
  message: string
  master_id: number
}

export interface FavoritesResponse {
  data: Master[]
  meta?: {
    total: number
    current_page: number
    last_page: number
  }
}

// =================== STORE ===================
export const useFavoritesStore = defineStore('favorites', () => {
  // =================== STATE ===================
  const favorites: Ref<Master[]> = ref([])
  const loading: Ref<boolean> = ref(false)
  const error: Ref<string | null> = ref(null)

  // =================== GETTERS ===================
  /**
   * Получить массив ID избранных мастеров
   */
  const favoriteIds = computed((): number[] => 
    favorites.value.map(master => master.id)
  )

  /**
   * Функция проверки находится ли мастер в избранном
   */
  const isFavorite = computed(() => {
    return (masterId: number): boolean => 
      favoriteIds.value.includes(masterId)
  })

  /**
   * Количество избранных мастеров
   */
  const count = computed((): number => favorites.value.length)

  /**
   * Есть ли избранные мастера
   */
  const hasFavorites = computed((): boolean => favorites.value.length > 0)

  /**
   * Получить мастера по ID
   */
  const getMasterById = computed(() => {
    return (masterId: number): Master | undefined =>
      favorites.value.find(master => master.id === masterId)
  })

  // =================== ACTIONS ===================
  /**
   * Загрузка избранных мастеров с сервера
   */
  async function loadFavorites(): Promise<void> {
    try {
      loading.value = true
      error.value = null
      
      const response = await axios.get<FavoritesResponse>('/api/favorites')
      favorites.value = response.data.data || []
    } catch (err) {
      if (err instanceof AxiosError) {
        error.value = err.response?.data?.message || 'Ошибка загрузки избранного'
      } else {
        error.value = 'Неизвестная ошибка при загрузке избранного'
      }
      favorites.value = []
    } finally {
      loading.value = false
    }
  }

  /**
   * Переключение мастера в избранном (добавить/удалить)
   */
  async function toggle(master: Master): Promise<FavoriteToggleResponse> {
    try {
      error.value = null
      
      const response = await axios.post<FavoriteToggleResponse>('/favorites/toggle', {
        master_id: master.id
      })

      if (response.data.added) {
        // Добавляем в избранное
        if (!isFavorite.value(master.id)) {
          favorites.value.push(master)
        }
      } else {
        // Удаляем из избранного
        removeFromLocal(master.id)
      }

      return response.data
    } catch (err) {
      if (err instanceof AxiosError) {
        error.value = err.response?.data?.message || 'Ошибка при обновлении избранного'
        throw new Error(error.value)
      }
      throw err
    }
  }

  /**
   * Добавление мастера в избранное (только локально)
   */
  function addToFavorites(master: Master): void {
    if (!isFavorite.value(master.id)) {
      favorites.value.push(master)
    }
  }

  /**
   * Добавление мастера в избранное с API вызовом
   */
  async function addToFavoritesAsync(master: Master): Promise<boolean> {
    try {
      if (isFavorite.value(master.id)) {
        return true // Уже в избранном
      }

      error.value = null
      
      const response = await axios.post<FavoriteToggleResponse>('/favorites/add', {
        master_id: master.id
      })

      if (response.data.added) {
        addToFavorites(master)
        return true
      }
      
      return false
    } catch (err) {
      if (err instanceof AxiosError) {
        error.value = err.response?.data?.message || 'Ошибка при добавлении в избранное'
      }
      return false
    }
  }

  /**
   * Удаление мастера из избранного (только локально)
   */
  function removeFromFavorites(masterId: number): void {
    removeFromLocal(masterId)
  }

  /**
   * Удаление мастера из избранного с API вызовом
   */
  async function removeFromFavoritesAsync(masterId: number): Promise<boolean> {
    try {
      error.value = null
      
      await axios.delete(`/favorites/${masterId}`)
      removeFromLocal(masterId)
      
      return true
    } catch (err) {
      if (err instanceof AxiosError) {
        error.value = err.response?.data?.message || 'Ошибка при удалении из избранного'
      }
      return false
    }
  }

  /**
   * Удаление из локального состояния
   */
  function removeFromLocal(masterId: number): void {
    const index = favorites.value.findIndex(master => master.id === masterId)
    if (index > -1) {
      favorites.value.splice(index, 1)
    }
  }

  /**
   * Очистка всех избранных
   */
  async function clearFavorites(): Promise<void> {
    try {
      error.value = null
      loading.value = true
      
      await axios.delete('/favorites/clear')
      favorites.value = []
    } catch (err) {
      if (err instanceof AxiosError) {
        error.value = err.response?.data?.message || 'Ошибка при очистке избранного'
      }
    } finally {
      loading.value = false
    }
  }

  /**
   * Локальная очистка (без API)
   */
  function clearFavoritesLocal(): void {
    favorites.value = []
  }

  /**
   * Очистка ошибок
   */
  function clearError(): void {
    error.value = null
  }

  /**
   * Обновление данных мастера в избранном
   */
  function updateMasterData(masterId: number, updates: Partial<Master>): void {
    const index = favorites.value.findIndex(master => master.id === masterId)
    if (index > -1) {
      favorites.value[index] = { ...favorites.value[index], ...updates }
    }
  }

  // =================== RETURN ===================
  return {
    // State
    favorites,
    loading,
    error,

    // Getters
    favoriteIds,
    isFavorite,
    count,
    hasFavorites,
    getMasterById,

    // Actions
    loadFavorites,
    toggle,
    addToFavorites,
    addToFavoritesAsync,
    removeFromFavorites,
    removeFromFavoritesAsync,
    clearFavorites,
    clearFavoritesLocal,
    clearError,
    updateMasterData
  }
})
