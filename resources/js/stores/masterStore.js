import { defineStore } from 'pinia'
import axios from 'axios'

export const useMasterStore = defineStore('master', {
    state: () => ({
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
        compare: []
    }),

    getters: {
        filteredMasters: (state) => {
            return state.masters
        },

        compareCount: (state) => state.compare.length,
        
        isInFavorites: (state) => {
            return (masterId) => state.favorites.includes(masterId)
        },

        isInCompare: (state) => {
            return (masterId) => state.compare.includes(masterId)
        }
    },

    actions: {
        async fetchMasters(params = {}) {
            try {
                this.loading = true
                const response = await axios.get('/api/masters', {
                    params: { ...this.filters, ...params }
                })
                this.masters = response.data.data
                return response.data
            } catch (error) {
                throw error
            } finally {
                this.loading = false
            }
        },

        async fetchMaster(id) {
            try {
                this.loading = true
                const response = await axios.get(`/api/masters/${id}`)
                this.currentMaster = response.data
                return response.data
            } catch (error) {
                throw error
            } finally {
                this.loading = false
            }
        },

        setFilter(key, value) {
            this.filters[key] = value
        },

        resetFilters() {
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
        },

        async toggleFavorite(masterId) {
            try {
                const response = await axios.post('/favorites/toggle', {
                    master_id: masterId
                })
                
                if (response.data.added) {
                    this.favorites.push(masterId)
                } else {
                    const index = this.favorites.indexOf(masterId)
                    if (index > -1) {
                        this.favorites.splice(index, 1)
                    }
                }
                
                return response.data
            } catch (error) {
                throw error
            }
        },

        async loadFavorites() {
            try {
                const response = await axios.get('/api/favorites')
                this.favorites = response.data.map(f => f.master_id)
            } catch (error) {
            }
        },

        addToCompare(masterId) {
            if (!this.isInCompare(masterId) && this.compare.length < 3) {
                this.compare.push(masterId)
                return true
            }
            return false
        },

        removeFromCompare(masterId) {
            const index = this.compare.indexOf(masterId)
            if (index > -1) {
                this.compare.splice(index, 1)
            }
        },

        clearCompare() {
            this.compare = []
        }
    },

    persist: {
        enabled: true,
        strategies: [
            {
                key: 'master-compare',
                storage: localStorage,
                paths: ['compare']
            }
        ]
    }
})