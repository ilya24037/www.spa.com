/**
 * 📦 REDUX TOOLKIT SETUP
 * Паттерн из Avito для управления состоянием
 * Файл извлечен из: avito-favorite-collections-integration.js
 */

import { configureStore } from '@reduxjs/toolkit'

/**
 * Конфигурация store как в Avito
 * Использует Redux Toolkit для упрощения работы
 */
export function setupStore() {
  const store = configureStore({
    reducer: {
      // Здесь подключаются слайсы
      favorites: favoritesReducer,
      user: userReducer,
      filters: filtersReducer
    },
    
    // Middleware из кода Avito
    middleware: (getDefaultMiddleware) =>
      getDefaultMiddleware({
        // Проверка сериализации (как в Avito)
        serializableCheck: {
          ignoredActions: ['favorites/addItem'],
          ignoredPaths: ['items.date']
        },
        // Проверка иммутабельности
        immutableCheck: {
          warnAfter: 128
        }
      }),
    
    // DevTools только в development (паттерн Avito)
    devTools: process.env.NODE_ENV !== 'production'
  })
  
  return store
}

/**
 * Типизация для TypeScript (опционально)
 */
export type RootState = ReturnType<typeof store.getState>
export type AppDispatch = typeof store.dispatch