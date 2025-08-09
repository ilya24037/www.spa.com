import { defineStore } from 'pinia'
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
  description?: string
  experience?: number
  is_verified?: boolean
  services?: Service[]
  photos?: string[]
  schedule?: WorkSchedule
  created_at: string
  updated_at: string
  [key: string]: any
}

export interface Service {
  id: number
  name: string
  description?: string
  price: number
  duration?: number
  category_id?: number
  category?: Category
}

export interface Category {
  id: number
  name: string
  slug: string
  description?: string
}

export interface WorkSchedule {
  monday?: DaySchedule
  tuesday?: DaySchedule
  wednesday?: DaySchedule
  thursday?: DaySchedule
  friday?: DaySchedule
  saturday?: DaySchedule
  sunday?: DaySchedule
}

export interface DaySchedule {
  is_working: boolean
  start_time?: string
  end_time?: string
  break_start?: string
  break_end?: string
}

export interface MasterFilters {
  category_id: number | null
  district: string | null
  metro: string | null
  min_price: number | null
  max_price: number | null
  service_type: string | null
  with_photo: boolean
  verified_only: boolean
  sort: 'rating' | 'price_asc' | 'price_desc' | 'reviews' | 'created_at'
}

export interface MastersResponse {
  data: Master[]
  links?: PaginationLinks
  meta?: PaginationMeta
}

export interface PaginationLinks {
  first: string | null
  last: string | null
  prev: string | null
  next: string | null
}

export interface PaginationMeta {
  current_page: number
  from: number
  last_page: number
  per_page: number
  to: number
  total: number
}

export interface FavoriteToggleResponse {
  added: boolean
  message: string
}

interface MasterState {
  masters: Master[]
  currentMaster: Master | null
  filters: MasterFilters
  loading: boolean
  searchQuery: string
  favorites: number[]
  compare: number[]
  error: string | null
}

// =================== STORE ===================
export const useMasterStore = defineStore<'master', MasterState>('master', {
  state: (): MasterState => ({
    masters: [],
    currentMaster: null,
    filters: {
      category_id: null,
      district: null,
      metro: null,
      min_price: null,
      max_price: null,
      service_type: null,
      with_photo: false,
      verified_only: false,
      sort: 'rating'
    },
    loading: false,
    searchQuery: '',
    favorites: [],
    compare: [],
    error: null
  }),

  getters: {
    /**
     * Отфильтрованные мастера (базовая фильтрация)
     */
    filteredMasters: (state): Master[] => {
      let result = [...state.masters]

      // Поиск по имени
      if (state.searchQuery.trim()) {
        const query = state.searchQuery.toLowerCase()
        result = result.filter(master => 
          master.name.toLowerCase().includes(query) ||
          master.description?.toLowerCase().includes(query) ||
          master.services?.some(service => 
            service.name.toLowerCase().includes(query)
          )
        )
      }

      // Фильтр по цене
      if (state.filters.min_price !== null) {
        result = result.filter(master => 
          (master.price_from || 0) >= state.filters.min_price!
        )
      }

      if (state.filters.max_price !== null) {
        result = result.filter(master => 
          (master.price_to || master.price_from || 0) <= state.filters.max_price!
        )
      }

      // Фильтр только с фото
      if (state.filters.with_photo) {
        result = result.filter(master => 
          master.photos && master.photos.length > 0
        )
      }

      // Фильтр только верифицированные
      if (state.filters.verified_only) {
        result = result.filter(master => master.is_verified)
      }

      return result
    },

    /**
     * Количество мастеров в сравнении
     */
    compareCount: (state): number => state.compare.length,
    
    /**
     * Максимальное количество для сравнения достигнуто
     */
    compareMaxReached: (state): boolean => state.compare.length >= 3,

    /**
     * Проверка находится ли мастер в избранном
     */
    isInFavorites: (state) => {
      return (masterId: number): boolean => state.favorites.includes(masterId)
    },

    /**
     * Проверка находится ли мастер в сравнении
     */
    isInCompare: (state) => {
      return (masterId: number): boolean => state.compare.includes(masterId)
    },

    /**
     * Получить мастера по ID
     */
    getMasterById: (state) => {
      return (masterId: number): Master | undefined => 
        state.masters.find(master => master.id === masterId)
    },

    /**
     * Активные фильтры
     */
    activeFiltersCount: (state): number => {
      let count = 0
      if (state.filters.category_id) count++
      if (state.filters.district) count++
      if (state.filters.metro) count++
      if (state.filters.min_price !== null) count++
      if (state.filters.max_price !== null) count++
      if (state.filters.service_type) count++
      if (state.filters.with_photo) count++
      if (state.filters.verified_only) count++
      if (state.searchQuery.trim()) count++
      return count
    }
  },

  actions: {
    /**
     * Загрузка списка мастеров
     */
    async fetchMasters(params: Record<string, any> = {}): Promise<MastersResponse> {
      try {
        this.loading = true
        this.error = null
        
        const response = await axios.get<MastersResponse>('/api/masters', {
          params: { 
            ...this.filters, 
            search: this.searchQuery,
            ...params 
          }
        })
        
        this.masters = response.data.data || []
        return response.data
      } catch (error) {
        if (error instanceof AxiosError) {
          this.error = error.response?.data?.message || 'Ошибка загрузки мастеров'
        } else {
          this.error = 'Неизвестная ошибка при загрузке мастеров'
        }
        throw error
      } finally {
        this.loading = false
      }
    },

    /**
     * Загрузка конкретного мастера
     */
    async fetchMaster(id: number): Promise<Master> {
      try {
        this.loading = true
        this.error = null
        
        const response = await axios.get<Master>(`/api/masters/${id}`)
        this.currentMaster = response.data
        
        return response.data
      } catch (error) {
        if (error instanceof AxiosError) {
          this.error = error.response?.data?.message || 'Ошибка загрузки мастера'
        } else {
          this.error = 'Неизвестная ошибка при загрузке мастера'
        }
        throw error
      } finally {
        this.loading = false
      }
    },

    /**
     * Установка фильтра
     */
    setFilter<K extends keyof MasterFilters>(key: K, value: MasterFilters[K]): void {
      this.filters[key] = value
    },

    /**
     * Установка нескольких фильтров
     */
    setFilters(filters: Partial<MasterFilters>): void {
      this.filters = { ...this.filters, ...filters }
    },

    /**
     * Сброс всех фильтров
     */
    resetFilters(): void {
      this.filters = {
        category_id: null,
        district: null,
        metro: null,
        min_price: null,
        max_price: null,
        service_type: null,
        with_photo: false,
        verified_only: false,
        sort: 'rating'
      }
      this.searchQuery = ''
    },

    /**
     * Установка поискового запроса
     */
    setSearchQuery(query: string): void {
      this.searchQuery = query
    },

    /**
     * Переключение мастера в избранном
     */
    async toggleFavorite(masterId: number): Promise<FavoriteToggleResponse> {
      try {
        this.error = null
        
        const response = await axios.post<FavoriteToggleResponse>('/favorites/toggle', {
          master_id: masterId
        })
        
        if (response.data.added) {
          if (!this.favorites.includes(masterId)) {
            this.favorites.push(masterId)
          }
        } else {
          const index = this.favorites.indexOf(masterId)
          if (index > -1) {
            this.favorites.splice(index, 1)
          }
        }
        
        return response.data
      } catch (error) {
        if (error instanceof AxiosError) {
          this.error = error.response?.data?.message || 'Ошибка при обновлении избранного'
        }
        throw error
      }
    },

    /**
     * Загрузка избранных мастеров
     */
    async loadFavorites(): Promise<void> {
      try {
        const response = await axios.get<{ data: { master_id: number }[] }>('/api/favorites')
        this.favorites = response.data.data?.map(item => item.master_id) || []
      } catch (error) {
        // Игнорируем ошибки при загрузке избранного
        this.favorites = []
      }
    },

    /**
     * Добавление в сравнение
     */
    addToCompare(masterId: number): boolean {
      if (!this.isInCompare(masterId) && this.compare.length < 3) {
        this.compare.push(masterId)
        return true
      }
      return false
    },

    /**
     * Удаление из сравнения
     */
    removeFromCompare(masterId: number): void {
      const index = this.compare.indexOf(masterId)
      if (index > -1) {
        this.compare.splice(index, 1)
      }
    },

    /**
     * Очистка списка сравнения
     */
    clearCompare(): void {
      this.compare = []
    },

    /**
     * Обновление данных мастера в списке
     */
    updateMasterInList(masterId: number, updates: Partial<Master>): void {
      const index = this.masters.findIndex(master => master.id === masterId)
      if (index > -1) {
        this.masters[index] = { ...this.masters[index], ...updates }
      }
      
      if (this.currentMaster?.id === masterId) {
        this.currentMaster = { ...this.currentMaster, ...updates }
      }
    },

    /**
     * Очистка ошибок
     */
    clearError(): void {
      this.error = null
    },

    /**
     * Очистка текущего мастера
     */
    clearCurrentMaster(): void {
      this.currentMaster = null
    }
  }
})
