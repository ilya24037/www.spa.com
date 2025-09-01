/**
 * 📦 WEBPACK CODE SPLITTING
 * Паттерны разделения кода из Avito
 * Файл извлечен из: avito-favorite-collections-integration.js
 */

/**
 * Паттерн webpack chunk из Avito
 * Создание отдельных чанков для оптимизации загрузки
 */

// 1. Базовая инициализация чанков (из кода Avito)
self["webpackChunkavito_desktop_site"] = self["webpackChunkavito_desktop_site"] || []

/**
 * Динамический импорт модулей (паттерн Avito)
 */
export const dynamicImports = {
  // Ленивая загрузка компонента избранного
  loadFavoritesModule: () => import(
    /* webpackChunkName: "favorites" */
    /* webpackPrefetch: true */
    './modules/favorites'
  ),
  
  // Загрузка коллекций только при необходимости
  loadCollectionsModule: () => import(
    /* webpackChunkName: "collections" */
    /* webpackMode: "lazy" */
    './modules/collections'
  ),
  
  // Загрузка тяжелых библиотек
  loadHeavyLibrary: () => import(
    /* webpackChunkName: "vendor-heavy" */
    /* webpackPreload: true */
    'heavy-library'
  )
}

/**
 * Webpack конфигурация для code splitting (адаптация Avito)
 */
export const webpackOptimization = {
  splitChunks: {
    chunks: 'all',
    cacheGroups: {
      // Vendor чанк (как в Avito)
      vendor: {
        test: /[\\/]node_modules[\\/]/,
        name: 'vendor',
        priority: 10,
        reuseExistingChunk: true
      },
      
      // Общие компоненты
      common: {
        minChunks: 2,
        priority: 5,
        reuseExistingChunk: true,
        name: 'common'
      },
      
      // Redux и state management
      redux: {
        test: /[\\/]node_modules[\\/](redux|@reduxjs|immer)/,
        name: 'redux-vendor',
        priority: 15
      },
      
      // UI компоненты
      ui: {
        test: /[\\/]src[\\/]ui[\\/]/,
        name: 'ui-components',
        priority: 8
      }
    }
  },
  
  // Создание runtime chunk (паттерн Avito)
  runtimeChunk: {
    name: 'runtime'
  },
  
  // Минимальный размер чанка
  minSize: 20000,
  
  // Максимальный размер чанка
  maxSize: 244000
}

/**
 * Функция для регистрации чанков (из Avito)
 */
export function registerChunk(chunkId, modules) {
  self["webpackChunkavito_desktop_site"].push([
    [chunkId],
    modules
  ])
}

/**
 * Паттерн для условной загрузки модулей
 */
export class ConditionalLoader {
  constructor() {
    this.loadedModules = new Set()
    this.loadingPromises = new Map()
  }
  
  async loadModule(moduleName, loader) {
    // Если модуль уже загружен
    if (this.loadedModules.has(moduleName)) {
      return true
    }
    
    // Если модуль в процессе загрузки
    if (this.loadingPromises.has(moduleName)) {
      return this.loadingPromises.get(moduleName)
    }
    
    // Начинаем загрузку
    const loadPromise = loader()
      .then(module => {
        this.loadedModules.add(moduleName)
        this.loadingPromises.delete(moduleName)
        return module
      })
      .catch(error => {
        this.loadingPromises.delete(moduleName)
        throw error
      })
    
    this.loadingPromises.set(moduleName, loadPromise)
    return loadPromise
  }
}

/**
 * Пример использования в компоненте
 */
export const useLazyComponent = () => {
  const loader = new ConditionalLoader()
  
  const loadFavorites = async () => {
    const module = await loader.loadModule(
      'favorites',
      dynamicImports.loadFavoritesModule
    )
    return module.default
  }
  
  return { loadFavorites }
}