/**
 * ❤️ FAVORITES SLICE
 * Слайс для работы с избранным (паттерн из Avito)
 * Файл извлечен из: avito-favorite-collections-integration.js
 */

import { createSlice, createAsyncThunk } from '@reduxjs/toolkit'

/**
 * Async thunk для загрузки избранного
 * Паттерн pending/fulfilled/rejected из Avito
 */
export const fetchFavorites = createAsyncThunk(
  'favorites/fetch',
  async (userId, { rejectWithValue }) => {
    try {
      const response = await fetch(`/api/favorites/${userId}`)
      if (!response.ok) throw new Error('Failed to fetch')
      return response.json()
    } catch (error) {
      return rejectWithValue(error.message)
    }
  }
)

/**
 * Слайс избранного с паттернами Avito
 */
const favoritesSlice = createSlice({
  name: 'favorites',
  
  initialState: {
    items: [],
    collections: [],
    loading: false,
    error: null,
    lastUpdated: null
  },
  
  // Синхронные reducers
  reducers: {
    addToFavorites: (state, action) => {
      // Immer позволяет "мутировать" state
      state.items.push(action.payload)
      state.lastUpdated = Date.now()
    },
    
    removeFromFavorites: (state, action) => {
      state.items = state.items.filter(
        item => item.id !== action.payload
      )
      state.lastUpdated = Date.now()
    },
    
    createCollection: (state, action) => {
      state.collections.push({
        id: Date.now(),
        name: action.payload.name,
        items: action.payload.items || [],
        createdAt: Date.now()
      })
    },
    
    clearFavorites: (state) => {
      state.items = []
      state.collections = []
      state.lastUpdated = Date.now()
    }
  },
  
  // Асинхронные reducers (паттерн Avito)
  extraReducers: (builder) => {
    builder
      // Pending state
      .addCase(fetchFavorites.pending, (state) => {
        state.loading = true
        state.error = null
      })
      // Fulfilled state
      .addCase(fetchFavorites.fulfilled, (state, action) => {
        state.loading = false
        state.items = action.payload.items
        state.collections = action.payload.collections
        state.lastUpdated = Date.now()
      })
      // Rejected state
      .addCase(fetchFavorites.rejected, (state, action) => {
        state.loading = false
        state.error = action.payload
      })
  }
})

export const {
  addToFavorites,
  removeFromFavorites,
  createCollection,
  clearFavorites
} = favoritesSlice.actions

export default favoritesSlice.reducer

/**
 * Селекторы для получения данных
 */
export const selectFavorites = state => state.favorites.items
export const selectCollections = state => state.favorites.collections
export const selectIsLoading = state => state.favorites.loading
export const selectIsFavorite = (state, itemId) => 
  state.favorites.items.some(item => item.id === itemId)