import { defineStore } from 'pinia'
import axios, { AxiosError } from 'axios'

// =================== TYPES ===================
export interface User {
  id: number
  name: string
  email: string
  email_verified_at?: string
  role: 'client' | 'master' | 'admin'
  master_profile?: MasterProfile | null
  avatar?: string
  phone?: string
  created_at: string
  updated_at: string
}

export interface MasterProfile {
  id: number
  user_id: number
  title?: string
  description?: string
  experience?: number
  is_verified: boolean
  rating?: number
  reviews_count?: number
  price_from?: number
  price_to?: number
  location?: string
  avatar?: string
  photos?: string[]
  services?: Service[]
  created_at: string
  updated_at: string
}

export interface Service {
  id: number
  name: string
  description?: string
  price: number
  duration?: number
  category_id?: number
}

export interface LoginCredentials {
  email: string
  password: string
  remember?: boolean
}

export interface RegisterData {
  name: string
  email: string
  password: string
  password_confirmation: string
  role?: 'client' | 'master'
  phone?: string
}

export interface AuthResponse {
  success: boolean
  error?: string
  errors?: Record<string, string[]>
}

interface AuthState {
  user: User | null
  isAuthenticated: boolean
  loading: boolean
  error: string | null
}

// =================== STORE ===================
export const useAuthStore = defineStore('auth', {
  state: (): AuthState => ({
    user: null,
    isAuthenticated: false,
    loading: false,
    error: null
  }),

  getters: {
    /**
     * Проверяет является ли пользователь мастером
     */
    isMaster: (state): boolean => state.user?.role === 'master',
    
    /**
     * Проверяет является ли пользователь клиентом
     */
    isClient: (state): boolean => state.user?.role === 'client',
    
    /**
     * Проверяет является ли пользователь администратором
     */
    isAdmin: (state): boolean => state.user?.role === 'admin',
    
    /**
     * Проверяет есть ли у пользователя профиль мастера
     */
    hasProfile: (state): boolean => state.user?.master_profile !== null && state.user?.master_profile !== undefined,
    
    /**
     * Проверяет верифицирован ли профиль мастера
     */
    isVerified: (state): boolean => state.user?.master_profile?.is_verified || false,
    
    /**
     * Получает аватар пользователя
     */
    userAvatar: (state): string | null => {
      return state.user?.master_profile?.avatar || state.user?.avatar || null
    },
    
    /**
     * Получает рейтинг мастера
     */
    masterRating: (state): number => {
      return state.user?.master_profile?.rating || 0
    }
  },

  actions: {
    /**
     * Получение данных текущего пользователя
     */
    async fetchUser(): Promise<void> {
      try {
        this.loading = true
        this.error = null
        
        const response = await axios.get<User>('/api/user')
        this.user = response.data
        this.isAuthenticated = true
      } catch (error) {
        this.user = null
        this.isAuthenticated = false
        
        if (error instanceof AxiosError && error.response?.status !== 401) {
          this.error = 'Ошибка загрузки данных пользователя'
        }
      } finally {
        this.loading = false
      }
    },

    /**
     * Авторизация пользователя
     */
    async login(credentials: LoginCredentials): Promise<AuthResponse> {
      try {
        this.loading = true
        this.error = null
        
        // Получаем CSRF токен
        await axios.get('/sanctum/csrf-cookie')
        
        // Выполняем авторизацию
        await axios.post('/login', credentials)
        
        // Загружаем данные пользователя
        await this.fetchUser()
        
        return { success: true }
      } catch (error) {
        if (error instanceof AxiosError) {
          this.error = error.response?.data?.message || 'Ошибка входа'
          return { 
            success: false, 
            error: this.error 
          }
        }
        
        this.error = 'Неизвестная ошибка'
        return { success: false, error: this.error }
      } finally {
        this.loading = false
      }
    },

    /**
     * Регистрация нового пользователя
     */
    async register(data: RegisterData): Promise<AuthResponse> {
      try {
        this.loading = true
        this.error = null
        
        // Получаем CSRF токен
        await axios.get('/sanctum/csrf-cookie')
        
        // Выполняем регистрацию
        await axios.post('/register', data)
        
        // Загружаем данные пользователя
        await this.fetchUser()
        
        return { success: true }
      } catch (error) {
        if (error instanceof AxiosError) {
          const errorData = error.response?.data
          this.error = errorData?.message || 'Ошибка регистрации'
          
          return { 
            success: false, 
            error: this.error,
            errors: errorData?.errors
          }
        }
        
        this.error = 'Неизвестная ошибка'
        return { success: false, error: this.error }
      } finally {
        this.loading = false
      }
    },

    /**
     * Выход из системы
     */
    async logout(): Promise<void> {
      try {
        await axios.post('/logout')
      } catch (error) {
        // Игнорируем ошибки при выходе
      } finally {
        this.user = null
        this.isAuthenticated = false
        this.error = null
        
        // Перенаправляем на главную страницу
        window.location.href = '/'
      }
    },

    /**
     * Установка пользователя (например, после SSR)
     */
    setUser(user: User | null): void {
      this.user = user
      this.isAuthenticated = !!user
    },

    /**
     * Очистка ошибок
     */
    clearError(): void {
      this.error = null
    },

    /**
     * Обновление профиля пользователя
     */
    updateUser(userData: Partial<User>): void {
      if (this.user) {
        this.user = { ...this.user, ...userData }
      }
    },

    /**
     * Обновление профиля мастера
     */
    updateMasterProfile(profileData: Partial<MasterProfile>): void {
      if (this.user?.master_profile) {
        this.user.master_profile = { ...this.user.master_profile, ...profileData }
      }
    }
  },

  persist: {
    enabled: true,
    strategies: [
      {
        key: 'auth',
        storage: localStorage,
        paths: ['isAuthenticated']
      }
    ]
  }
})
