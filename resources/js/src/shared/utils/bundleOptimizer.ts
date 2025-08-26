/**
 * Bundle Optimizer для SPA Platform
 * 
 * Оптимизирует размер bundle через:
 * - Tree shaking
 * - Code splitting 
 * - Dynamic imports
 * - Lazy loading
 */

import { defineAsyncComponent, type AsyncComponentLoader } from 'vue'
import { logger } from './logger'

// Типы для оптимизации
export interface BundleMetrics {
  componentName: string
  loadTime: number
  size?: number
  chunkName?: string
}

export interface OptimizationConfig {
  enablePreloading: boolean
  enablePrefetching: boolean
  chunkSizeLimit: number
  componentCacheTime: number
}

class BundleOptimizer {
  private loadedChunks = new Set<string>()
  private componentCache = new Map<string, any>()
  private loadMetrics: BundleMetrics[] = []
  
  private config: OptimizationConfig = {
    enablePreloading: true,
    enablePrefetching: true,
    chunkSizeLimit: 50 * 1024, // 50KB
    componentCacheTime: 5 * 60 * 1000 // 5 minutes
  }

  /**
   * Создает оптимизированный async компонент
   */
  createAsyncComponent(
    loader: AsyncComponentLoader,
    componentName: string,
    options: {
      preload?: boolean
      prefetch?: boolean
      retry?: boolean
      timeout?: number
    } = {}
  ) {
    const startTime = performance.now()
    
    return defineAsyncComponent({
      loader: async () => {
        try {
          // Проверяем кеш
          if (this.componentCache.has(componentName)) {
            const cached = this.componentCache.get(componentName)
            if (Date.now() - cached.timestamp < this.config.componentCacheTime) {
              return cached.component
            }
          }

          // Загружаем компонент
          const component = await loader()
          const loadTime = performance.now() - startTime

          // Кешируем компонент
          this.componentCache.set(componentName, {
            component,
            timestamp: Date.now()
          })

          // Записываем метрики
          this.recordMetrics({
            componentName,
            loadTime,
            chunkName: this.getChunkName(componentName)
          })

          return component
        } catch (error) {
          logger.error(`Failed to load component ${componentName}`, error, 'BundleOptimizer')
          throw error
        }
      },

      loadingComponent: {
        template: `
          <div class="flex items-center justify-center p-4">
            <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
            <span class="ml-2 text-sm text-gray-600">Загрузка...</span>
          </div>
        `
      },

      errorComponent: {
        template: `
          <div class="border border-red-200 rounded-lg p-4 bg-red-50">
            <div class="flex items-center">
              <svg class="w-5 h-5 text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
              </svg>
              <span class="text-sm text-red-700">Ошибка загрузки компонента</span>
            </div>
            <button 
              @click="$parent.$forceUpdate()" 
              class="mt-2 text-xs text-red-600 hover:text-red-800 underline"
            >
              Повторить
            </button>
          </div>
        `
      },

      delay: 200,
      timeout: options.timeout || 10000
    })
  }

  /**
   * Предзагрузка критических компонентов
   */
  async preloadCritical(components: Array<{ name: string, loader: AsyncComponentLoader }>) {
    if (!this.config.enablePreloading) return

    const preloadPromises = components.map(async ({ name, loader }) => {
      try {
        await loader()
        this.markChunkAsLoaded(this.getChunkName(name))
      } catch (error) {
        logger.warn(`Failed to preload critical component ${name}`, error, 'BundleOptimizer')
      }
    })

    await Promise.allSettled(preloadPromises)
  }

  /**
   * Prefetch компонентов для следующих страниц
   */
  prefetchRoute(routeName: string, components: Array<{ name: string, loader: AsyncComponentLoader }>) {
    if (!this.config.enablePrefetching) return

    // Prefetch только при idle состоянии
    if ('requestIdleCallback' in window) {
      requestIdleCallback(() => {
        this.doPrefetch(routeName, components)
      })
    } else {
      // Fallback для браузеров без requestIdleCallback
      setTimeout(() => {
        this.doPrefetch(routeName, components)
      }, 2000)
    }
  }

  private async doPrefetch(routeName: string, components: Array<{ name: string, loader: AsyncComponentLoader }>) {

    for (const { name, loader } of components) {
      try {
        const chunkName = this.getChunkName(name)
        if (!this.loadedChunks.has(chunkName)) {
          await loader()
          this.markChunkAsLoaded(chunkName)
        }
      } catch (error) {
        // Игнорируем ошибки prefetch
      }
    }
  }

  /**
   * Оптимизация импортов библиотек
   */
  optimizeLibraryImports() {
    // Явная карта разрешённых импортов lodash
    const allowed: Record<string, () => Promise<any>> = {
      debounce: () => import('lodash/debounce'),
      throttle: () => import('lodash/throttle'),
      uniq: () => import('lodash/uniq'),
      uniqBy: () => import('lodash/uniqBy'),
      cloneDeep: () => import('lodash/cloneDeep'),
      isEqual: () => import('lodash/isEqual'),
      pick: () => import('lodash/pick'),
      omit: () => import('lodash/omit'),
      merge: () => import('lodash/merge'),
      get: () => import('lodash/get'),
      set: () => import('lodash/set'),
      flatten: () => import('lodash/flatten'),
      flattenDeep: () => import('lodash/flattenDeep'),
    }

    return new Proxy({}, {
      get(_target, prop) {
        if (typeof prop !== 'string') return undefined
        const loader = allowed[prop]
        if (loader) return loader()
        // Fallback: импорт основного lodash (тяжелее) — используем только при необходимости
        return import('lodash').then(mod => (mod as any)[prop])
      },
    })
  }

  /**
   * Анализ метрик загрузки
   */
  getLoadMetrics() {
    const totalComponents = this.loadMetrics.length
    const averageLoadTime = this.loadMetrics.reduce((sum, m) => sum + m.loadTime, 0) / totalComponents
    const slowComponents = this.loadMetrics.filter(m => m.loadTime > 1000)

    return {
      totalComponents,
      averageLoadTime: Math.round(averageLoadTime),
      slowComponents: slowComponents.length,
      loadedChunks: this.loadedChunks.size,
      cacheHitRate: this.calculateCacheHitRate()
    }
  }

  /**
   * Очистка кеша компонентов
   */
  clearCache() {
    this.componentCache.clear()
  }

  /**
   * Получить рекомендации по оптимизации
   */
  getOptimizationRecommendations() {
    const metrics = this.getLoadMetrics()
    const recommendations: string[] = []

    if (metrics.averageLoadTime > 500) {
      recommendations.push('Средняя скорость загрузки компонентов высокая - рассмотрите дополнительное разбиение на чанки')
    }

    if (metrics.slowComponents > 0) {
      recommendations.push(`${metrics.slowComponents} компонентов загружаются медленно - оптимизируйте их размер`)
    }

    if (metrics.cacheHitRate < 0.7) {
      recommendations.push('Низкий hit rate кеша - увеличьте время кеширования компонентов')
    }

    return recommendations
  }

  private recordMetrics(metrics: BundleMetrics) {
    this.loadMetrics.push(metrics)
    
    // Ограничиваем размер массива метрик
    if (this.loadMetrics.length > 100) {
      this.loadMetrics = this.loadMetrics.slice(-50)
    }
  }

  private getChunkName(componentName: string): string {
    // Генерируем имя чанка на основе имени компонента
    return componentName.toLowerCase().replace(/[^a-z0-9]/g, '-')
  }

  private markChunkAsLoaded(chunkName: string) {
    this.loadedChunks.add(chunkName)
  }

  private calculateCacheHitRate(): number {
    // Простая метрика - количество кешированных компонентов / общее количество
    return this.componentCache.size / Math.max(this.loadMetrics.length, 1)
  }
}

// Экспортируем singleton
export const bundleOptimizer = new BundleOptimizer()

// Удобные алиасы
export const createOptimizedComponent = (
  loader: AsyncComponentLoader,
  componentName: string,
  options?: any
) => bundleOptimizer.createAsyncComponent(loader, componentName, options)

export const preloadCritical = (components: any[]) => bundleOptimizer.preloadCritical(components)
export const prefetchRoute = (routeName: string, components: any[]) => bundleOptimizer.prefetchRoute(routeName, components)

export default bundleOptimizer
