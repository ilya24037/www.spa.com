// resources/js/stores/favorites.js
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

export const useFavoritesStore = defineStore('favorites', () => {
  // Состояние
  const favorites = ref([])
  const loading = ref(false)
  
  // Геттеры
  const favoriteIds = computed(() => favorites.value.map(f => f.id))
  
  const isFavorite = computed(() => {
    return (masterId) => favoriteIds.value.includes(masterId)
  })
  
  const count = computed(() => favorites.value.length)
  
  // Действия
  async function loadFavorites() {
    try {
      loading.value = true
      const response = await axios.get('/api/favorites')
      favorites.value = response.data.data || []
    } catch (error) {
      favorites.value = []
    } finally {
      loading.value = false
    }
  }
  
  async function toggle(master) {
    try {
      const response = await axios.post('/favorites/toggle', {
        master_id: master.id
      })
      
      if (response.data.added) {
        // Добавляем в избранное
        favorites.value.push(master)
      } else {
        // Удаляем из избранного
        const index = favorites.value.findIndex(f => f.id === master.id)
        if (index > -1) {
          favorites.value.splice(index, 1)
        }
      }
      
      return response.data
    } catch (error) {
      throw error
    }
  }
  
  function add(master) {
    if (!isFavorite.value(master.id)) {
      favorites.value.push(master)
    }
  }
  
  function remove(masterId) {
    const index = favorites.value.findIndex(f => f.id === masterId)
    if (index > -1) {
      favorites.value.splice(index, 1)
    }
  }
  
  function clear() {
    favorites.value = []
  }
  
  return {
    // Состояние
    favorites,
    loading,
    
    // Геттеры
    favoriteIds,
    isFavorite,
    count,
    
    // Действия
    loadFavorites,
    toggle,
    add,
    remove,
    clear
  }
})