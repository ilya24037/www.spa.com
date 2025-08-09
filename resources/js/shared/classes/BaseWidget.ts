/**
 * Базовый класс для изолированных виджетов по принципу Ozon
 * 
 * Обеспечивает:
 * - Изоляцию состояния
 * - Собственный API слой  
 * - Error handling
 * - Performance мониторинг
 * - Ленивую загрузку
 */

import { defineAsyncComponent, type AsyncComponentLoader } from 'vue'
import type { Component } from '@vue/runtime-core'
import { logger } from '@/src/shared/utils/logger'

export interface WidgetConfig {
  name: string
  loadingComponent?: Component
  errorComponent?: Component
  delay?: number
  timeout?: number
  retryCount?: number
}

export interface WidgetPerformance {
  loadTime: number
  renderTime: number
  memoryUsage: number
  errorCount: number
}

export abstract class BaseWidget {
  protected config: WidgetConfig
  protected performance: WidgetPerformance
  
  constructor(config: WidgetConfig) {
    this.config = {
      delay: 200,
      timeout: 3000,
      retryCount: 3,
      ...config
    }
    
    this.performance = {
      loadTime: 0,
      renderTime: 0,
      memoryUsage: 0,
      errorCount: 0
    }
  }

  /**
   * Создает ленивый компонент с мониторингом производительности
   */
  public createLazyComponent(loader: AsyncComponentLoader): Component {
    const startTime = performance.now()
    
    return defineAsyncComponent({
      loader: async () => {
        try {
          const component = await loader()
          this.performance.loadTime = performance.now() - startTime
          this.trackPerformance()
          return component
        } catch (error) {
          this.performance.errorCount++
          this.handleError(error as Error)
          throw error
        }
      },
      
      loadingComponent: this.config.loadingComponent || this.getDefaultLoadingComponent(),
      errorComponent: this.config.errorComponent || this.getDefaultErrorComponent(),
      delay: this.config.delay,
      timeout: this.config.timeout,
    })
  }

  /**
   * Отслеживает производительность виджета
   */
  protected trackPerformance(): void {
    if (typeof window !== 'undefined' && window.performance) {
      const memory = (performance as any).memory
      if (memory) {
        this.performance.memoryUsage = memory.usedJSHeapSize
      }
      
      // Отправляем метрики в систему мониторинга
      this.reportPerformance()
    }
  }

  /**
   * Обрабатывает ошибки виджета изолированно
   */
  protected handleError(error: Error): void {
    logger.error(`[Widget ${this.config.name}] Error:`, error)
    
    // Не пробрасываем ошибку наружу - изоляция
    // Можно отправить в систему логирования
    if (typeof window !== 'undefined') {
      window.dispatchEvent(new CustomEvent('widget-error', {
        detail: {
          widget: this.config.name,
          error: error.message,
          performance: this.performance
        }
      }))
    }
  }

  /**
   * Отправляет метрики производительности
   */
  protected reportPerformance(): void {
    // Проверяем режим разработки через import.meta.env для Vite
    if (import.meta.env.DEV) {
      // Removed console.log in production
    }
    
    // В production можно отправлять в analytics
  }

  /**
   * Компонент загрузки по умолчанию
   */
  protected getDefaultLoadingComponent(): Component {
    return {
      template: `
        <div class="animate-pulse bg-gray-200 rounded-lg p-4">
          <div class="h-4 bg-gray-300 rounded w-1/2 mb-2"></div>
          <div class="h-4 bg-gray-300 rounded w-3/4"></div>
        </div>
      `
    }
  }

  /**
   * Компонент ошибки по умолчанию
   */
  protected getDefaultErrorComponent(): Component {
    return {
      template: `
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
          <div class="text-red-800 text-sm">
            Виджет временно недоступен
          </div>
          <button 
            @click="$emit('retry')"
            class="mt-2 text-xs text-red-600 hover:text-red-800 underline"
          >
            Попробовать еще раз
          </button>
        </div>
      `
    }
  }

  /**
   * Очистка ресурсов виджета
   */
  public cleanup(): void {
    // Переопределяется в наследниках для специфичной очистки
  }
}

/**
 * Фабрика для создания изолированных виджетов
 */
export class WidgetFactory {
  private static widgets = new Map<string, BaseWidget>()

  public static register(name: string, widget: BaseWidget): void {
    this.widgets.set(name, widget)
  }

  public static get(name: string): BaseWidget | undefined {
    return this.widgets.get(name)
  }

  public static cleanup(): void {
    this.widgets.forEach(widget => widget.cleanup())
    this.widgets.clear()
  }
}

/**
 * Типы для изолированных виджетов
 */
export interface WidgetProps {
  [key: string]: any
}

export interface WidgetEmits {
  [key: string]: (...args: any[]) => void
}

export interface IsolatedWidgetConfig extends WidgetConfig {
  props?: WidgetProps
  emits?: WidgetEmits
}