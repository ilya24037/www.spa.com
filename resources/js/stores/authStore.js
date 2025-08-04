import { defineStore } from 'pinia'
import axios from 'axios'

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        isAuthenticated: false,
        loading: false,
        error: null
    }),

    getters: {
        isMaster: (state) => state.user?.role === 'master',
        isClient: (state) => state.user?.role === 'client',
        isAdmin: (state) => state.user?.role === 'admin',
        hasProfile: (state) => state.user?.master_profile !== null,
        isVerified: (state) => state.user?.master_profile?.is_verified || false
    },

    actions: {
        async fetchUser() {
            try {
                this.loading = true
                const response = await axios.get('/api/user')
                this.user = response.data
                this.isAuthenticated = true
            } catch (error) {
                this.user = null
                this.isAuthenticated = false
            } finally {
                this.loading = false
            }
        },

        async login(credentials) {
            try {
                this.loading = true
                this.error = null
                
                await axios.get('/sanctum/csrf-cookie')
                await axios.post('/login', credentials)
                
                await this.fetchUser()
                
                return { success: true }
            } catch (error) {
                this.error = error.response?.data?.message || 'Ошибка входа'
                return { success: false, error: this.error }
            } finally {
                this.loading = false
            }
        },

        async register(data) {
            try {
                this.loading = true
                this.error = null
                
                await axios.get('/sanctum/csrf-cookie')
                await axios.post('/register', data)
                
                await this.fetchUser()
                
                return { success: true }
            } catch (error) {
                this.error = error.response?.data?.errors || 'Ошибка регистрации'
                return { success: false, errors: this.error }
            } finally {
                this.loading = false
            }
        },

        async logout() {
            try {
                await axios.post('/logout')
                this.user = null
                this.isAuthenticated = false
                window.location.href = '/'
            } catch (error) {
            }
        },

        setUser(user) {
            this.user = user
            this.isAuthenticated = !!user
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