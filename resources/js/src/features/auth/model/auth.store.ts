import { defineStore } from 'pinia'
import { ref, computed, type Ref } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'

// TypeScript интерфейсы
export interface User {
  id: number
  name: string
  email: string
  avatar?: string
  phone?: string
  role: 'user' | 'master' | 'admin'
  balance: number
  isOnline?: boolean
  emailVerifiedAt?: string | null
  createdAt: string
  updatedAt: string
  settings?: UserSettings
  stats?: UserStats
}

export interface UserSettings {
  notifications: boolean
  emailNotifications: boolean
  smsNotifications: boolean
  theme: 'light' | 'dark' | 'auto'
  language: string
}

export interface UserStats {
  totalBookings: number
  totalSpent: number
  favoriteCount: number
  reviewCount: number
  avgRating: number
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
  passwordConfirmation: string
  phone?: string
  acceptTerms: boolean
}

export interface AuthError {
  message: string
  errors?: Record<string, string[]>
}

export const useAuthStore = defineStore('auth', () => {
  // State
  const user: Ref<User | null> = ref(null)
  const isAuthenticated = ref(false)
  const isLoading = ref(false)
  const error: Ref<AuthError | null> = ref(null)
  const loginModalOpen = ref(false)
  const registerModalOpen = ref(false)
  
  // Getters
  const userName = computed(() => user.value?.name || '')
  const userEmail = computed(() => user.value?.email || '')
  const userAvatar = computed(() => user.value?.avatar || '')
  const userBalance = computed(() => user.value?.balance || 0)
  const userRole = computed(() => user.value?.role || 'user')
  const isEmailVerified = computed(() => !!user.value?.emailVerifiedAt)
  const isMaster = computed(() => user.value?.role === 'master')
  const isAdmin = computed(() => user.value?.role === 'admin')
  
  // Actions
  const login = async (credentials: LoginCredentials): Promise<void> => {
    isLoading.value = true
    error.value = null
    
    try {
      const response = await axios.post('/api/auth/login', credentials)
      
      if (response.data.user) {
        user.value = response.data.user
        isAuthenticated.value = true
        
        // Сохранить токен если есть
        if (response.data.token) {
          localStorage.setItem('auth_token', response.data.token)
          axios.defaults.headers.common['Authorization'] = `Bearer ${response.data.token}`
        }
        
        // Закрыть модалку
        loginModalOpen.value = false
        
        // Редирект если нужен
        if (response.data.redirectTo) {
          router.visit(response.data.redirectTo)
        }
      }
    } catch (err: any) {
      error.value = {
        message: err.response?.data?.message || 'Ошибка входа',
        errors: err.response?.data?.errors
      }
      throw err
    } finally {
      isLoading.value = false
    }
  }
  
  const register = async (data: RegisterData): Promise<void> => {
    isLoading.value = true
    error.value = null
    
    try {
      const response = await axios.post('/api/auth/register', data)
      
      if (response.data.user) {
        user.value = response.data.user
        isAuthenticated.value = true
        
        // Сохранить токен если есть
        if (response.data.token) {
          localStorage.setItem('auth_token', response.data.token)
          axios.defaults.headers.common['Authorization'] = `Bearer ${response.data.token}`
        }
        
        // Закрыть модалку
        registerModalOpen.value = false
        
        // Редирект на страницу подтверждения или главную
        const redirectTo = response.data.redirectTo || '/dashboard'
        router.visit(redirectTo)
      }
    } catch (err: any) {
      error.value = {
        message: err.response?.data?.message || 'Ошибка регистрации',
        errors: err.response?.data?.errors
      }
      throw err
    } finally {
      isLoading.value = false
    }
  }
  
  const logout = async (): Promise<void> => {
    isLoading.value = true
    
    try {
      await axios.post('/api/auth/logout')
    } catch (err) {
      console.warn('Logout request failed:', err)
    }
    
    // Очистить состояние
    user.value = null
    isAuthenticated.value = false
    error.value = null
    
    // Очистить токен
    localStorage.removeItem('auth_token')
    delete axios.defaults.headers.common['Authorization']
    
    // Редирект на главную
    router.visit('/')
    
    isLoading.value = false
  }
  
  const fetchUser = async (): Promise<void> => {
    const token = localStorage.getItem('auth_token')
    if (!token) return
    
    try {
      axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
      const response = await axios.get('/api/auth/user')
      
      if (response.data.user) {
        user.value = response.data.user
        isAuthenticated.value = true
      }
    } catch (err) {
      // Токен недействителен
      localStorage.removeItem('auth_token')
      delete axios.defaults.headers.common['Authorization']
      console.warn('Failed to fetch user:', err)
    }
  }
  
  const updateUser = async (userData: Partial<User>): Promise<void> => {
    if (!user.value) return
    
    isLoading.value = true
    error.value = null
    
    try {
      const response = await axios.put('/api/auth/user', userData)
      
      if (response.data.user) {
        user.value = response.data.user
      }
    } catch (err: any) {
      error.value = {
        message: err.response?.data?.message || 'Ошибка обновления профиля',
        errors: err.response?.data?.errors
      }
      throw err
    } finally {
      isLoading.value = false
    }
  }
  
  const updateBalance = (newBalance: number): void => {
    if (user.value) {
      user.value.balance = newBalance
    }
  }
  
  const resendEmailVerification = async (): Promise<void> => {
    isLoading.value = true
    error.value = null
    
    try {
      await axios.post('/api/auth/resend-verification')
    } catch (err: any) {
      error.value = {
        message: err.response?.data?.message || 'Ошибка отправки письма'
      }
      throw err
    } finally {
      isLoading.value = false
    }
  }
  
  const resetPassword = async (email: string): Promise<void> => {
    isLoading.value = true
    error.value = null
    
    try {
      await axios.post('/api/auth/forgot-password', { email })
    } catch (err: any) {
      error.value = {
        message: err.response?.data?.message || 'Ошибка сброса пароля'
      }
      throw err
    } finally {
      isLoading.value = false
    }
  }
  
  // Modal management
  const openLoginModal = (): void => {
    loginModalOpen.value = true
    registerModalOpen.value = false
    error.value = null
  }
  
  const closeLoginModal = (): void => {
    loginModalOpen.value = false
    error.value = null
  }
  
  const openRegisterModal = (): void => {
    registerModalOpen.value = true
    loginModalOpen.value = false
    error.value = null
  }
  
  const closeRegisterModal = (): void => {
    registerModalOpen.value = false
    error.value = null
  }
  
  const switchToRegister = (): void => {
    loginModalOpen.value = false
    registerModalOpen.value = true
    error.value = null
  }
  
  const switchToLogin = (): void => {
    registerModalOpen.value = false
    loginModalOpen.value = true
    error.value = null
  }
  
  const clearError = (): void => {
    error.value = null
  }
  
  // Инициализация при создании store
  fetchUser()
  
  return {
    // State
    user,
    isAuthenticated,
    isLoading,
    error,
    loginModalOpen,
    registerModalOpen,
    
    // Getters
    userName,
    userEmail,
    userAvatar,
    userBalance,
    userRole,
    isEmailVerified,
    isMaster,
    isAdmin,
    
    // Actions
    login,
    register,
    logout,
    fetchUser,
    updateUser,
    updateBalance,
    resendEmailVerification,
    resetPassword,
    
    // Modal management
    openLoginModal,
    closeLoginModal,
    openRegisterModal,
    closeRegisterModal,
    switchToRegister,
    switchToLogin,
    clearError
  }
})