/**
 * Performance Monitor для SPA Platform
 * 
 * Мониторинг Core Web Vitals и производительности
 */

import { logger } from './logger'

// Типы метрик
export interface PerformanceMetrics {
  // Core Web Vitals
  LCP?: number // Largest Contentful Paint
  FID?: number // First Input Delay  
  CLS?: number // Cumulative Layout Shift
  
  // Other Web Vitals
  FCP?: number // First Contentful Paint
  TTFB?: number // Time to First Byte
  
  // Custom metrics
  pageLoadTime?: number
  routeChangeTime?: number
  componentMountTime?: number
  
  // Bundle metrics
  bundleSize?: number
  chunksLoaded?: number
  
  timestamp: number
  url: string
  userAgent: string
}

export interface PerformanceThresholds {
  LCP: { good: number, poor: number }
  FID: { good: number, poor: number }
  CLS: { good: number, poor: number }
  FCP: { good: number, poor: number }
  TTFB: { good: number, poor: number }
}

class PerformanceMonitor {
  private metrics: PerformanceMetrics[] = []
  private observers: PerformanceObserver[] = []
  // private startTime = performance.now() // Закомментировано, пока не используется
  
  // Пороговые значения для Core Web Vitals
  private thresholds: PerformanceThresholds = {
    LCP: { good: 2500, poor: 4000 }, // мс
    FID: { good: 100, poor: 300 },   // мс
    CLS: { good: 0.1, poor: 0.25 },  // score
    FCP: { good: 1800, poor: 3000 }, // мс
    TTFB: { good: 800, poor: 1800 }  // мс
  }

  constructor() {
    this.init()
  }

  /**
   * Инициализация мониторинга
   */
  private init() {
    if (typeof window === 'undefined') return

    // Наблюдаем за paint метриками
    this.observePaintMetrics()
    
    // Наблюдаем за layout shift
    this.observeLayoutShift()
    
    // Наблюдаем за first input
    this.observeFirstInput()
    
    // Мониторим загрузку страницы
    this.monitorPageLoad()
    
    // Мониторим навигацию
    this.monitorNavigation()
    
    logger.info('Performance monitoring initialized', null, 'PerformanceMonitor')
  }

  /**
   * Наблюдение за paint метриками (LCP, FCP)
   */
  private observePaintMetrics() {
    if (!('PerformanceObserver' in window)) return

    try {
      const observer = new PerformanceObserver((list) => {
        const entries = list.getEntries()
        
        entries.forEach((entry) => {
          if (entry.entryType === 'largest-contentful-paint') {
            this.recordMetric('LCP', entry.startTime)
          }
          
          if (entry.entryType === 'paint' && entry.name === 'first-contentful-paint') {
            this.recordMetric('FCP', entry.startTime)
          }
        })
      })

      observer.observe({ entryTypes: ['largest-contentful-paint', 'paint'] })
      this.observers.push(observer)
    } catch (error) {
      logger.warn('Failed to observe paint metrics', error, 'PerformanceMonitor')
    }
  }

  /**
   * Наблюдение за layout shift
   */
  private observeLayoutShift() {
    if (!('PerformanceObserver' in window)) return

    try {
      let clsValue = 0
      let sessionValue = 0
      let sessionEntries: PerformanceEntry[] = []

      const observer = new PerformanceObserver((list) => {
        const entries = list.getEntries() as any[]
        
        entries.forEach((entry) => {
          // Только неожиданные сдвиги
          if (!entry.hadRecentInput) {
            const firstSessionEntry = sessionEntries[0]
            const lastSessionEntry = sessionEntries[sessionEntries.length - 1]

            // Если запись не принадлежит существующей сессии, начинаем новую
            if (sessionValue && 
                entry.startTime - lastSessionEntry.startTime > 1000 ||
                entry.startTime - firstSessionEntry.startTime > 5000) {
              sessionValue = 0
              sessionEntries = []
            }

            sessionEntries.push(entry)
            sessionValue += entry.value
            clsValue = Math.max(clsValue, sessionValue)
          }
        })

        this.recordMetric('CLS', clsValue)
      })

      observer.observe({ entryTypes: ['layout-shift'] })
      this.observers.push(observer)
    } catch (error) {
      logger.warn('Failed to observe layout shift', error, 'PerformanceMonitor')
    }
  }

  /**
   * Наблюдение за first input
   */
  private observeFirstInput() {
    if (!('PerformanceObserver' in window)) return

    try {
      const observer = new PerformanceObserver((list) => {
        const entries = list.getEntries()
        
        entries.forEach((entry: any) => {
          if (entry.processingStart && entry.startTime) {
            const fid = entry.processingStart - entry.startTime
            this.recordMetric('FID', fid)
          }
        })
      })

      observer.observe({ entryTypes: ['first-input'] })
      this.observers.push(observer)
    } catch (error) {
      logger.warn('Failed to observe first input', error, 'PerformanceMonitor')
    }
  }

  /**
   * Мониторинг загрузки страницы
   */
  private monitorPageLoad() {
    if (typeof window === 'undefined') return

    window.addEventListener('load', () => {
      // Получаем навигационные метрики
      const navEntry = performance.getEntriesByType('navigation')[0] as PerformanceNavigationTiming
      
      if (navEntry) {
        const ttfb = navEntry.responseStart - navEntry.requestStart
        const pageLoadTime = navEntry.loadEventEnd - navEntry.fetchStart  // ✅ Используем fetchStart вместо navigationStart
        
        this.recordMetric('TTFB', ttfb)
        this.recordMetric('pageLoadTime', pageLoadTime)
      }
    })
  }

  /**
   * Мониторинг навигации (SPA)
   */
  private monitorNavigation() {
    let routeStartTime = performance.now()

    // Для Inertia.js
    if (typeof window !== 'undefined' && (window as any).Inertia) {
      (window as any).Inertia.on('start', () => {
        routeStartTime = performance.now()
      })

      ;(window as any).Inertia.on('finish', () => {
        const routeChangeTime = performance.now() - routeStartTime
        this.recordMetric('routeChangeTime', routeChangeTime)
      })
    }
  }

  /**
   * Запись метрики
   */
  private recordMetric(type: keyof PerformanceMetrics, value: number) {
    const metric: Partial<PerformanceMetrics> = {
      [type]: value,
      timestamp: Date.now(),
      url: window.location.href,
      userAgent: navigator.userAgent
    }

    // Добавляем к существующим метрикам или создаем новую запись
    const fullMetric: PerformanceMetrics = {
      ...metric,
      timestamp: metric.timestamp || Date.now(),
      url: metric.url || window.location.href,
      userAgent: metric.userAgent || navigator.userAgent
    }
    
    const existingIndex = this.metrics.findIndex(m => 
      Math.abs(m.timestamp - fullMetric.timestamp) < 1000
    )

    if (existingIndex >= 0) {
      this.metrics[existingIndex] = { ...this.metrics[existingIndex], ...fullMetric }
    } else {
      this.metrics.push(fullMetric)
    }

    // Логируем важные метрики
    if (['LCP', 'FID', 'CLS'].includes(type)) {
      const threshold = this.thresholds[type as keyof PerformanceThresholds]
      const rating = this.getRating(value, threshold)
      
      logger.info(`Core Web Vital ${type}: ${value.toFixed(2)} (${rating})`, { value, rating }, 'PerformanceMonitor')
    }

    // Ограничиваем размер массива
    if (this.metrics.length > 50) {
      this.metrics = this.metrics.slice(-25)
    }
  }

  /**
   * Получить рейтинг метрики
   */
  private getRating(value: number, threshold: { good: number, poor: number }): 'good' | 'needs-improvement' | 'poor' {
    if (value <= threshold.good) return 'good'
    if (value <= threshold.poor) return 'needs-improvement'
    return 'poor'
  }

  /**
   * Получить все метрики
   */
  getMetrics(): PerformanceMetrics[] {
    return [...this.metrics]
  }

  /**
   * Получить последние Core Web Vitals
   */
  getCoreWebVitals() {
    const latest = this.metrics[this.metrics.length - 1]
    if (!latest) return null

    return {
      LCP: {
        value: latest.LCP,
        rating: latest.LCP ? this.getRating(latest.LCP, this.thresholds.LCP) : undefined
      },
      FID: {
        value: latest.FID,
        rating: latest.FID ? this.getRating(latest.FID, this.thresholds.FID) : undefined
      },
      CLS: {
        value: latest.CLS,
        rating: latest.CLS ? this.getRating(latest.CLS, this.thresholds.CLS) : undefined
      },
      FCP: {
        value: latest.FCP,
        rating: latest.FCP ? this.getRating(latest.FCP, this.thresholds.FCP) : undefined
      },
      TTFB: {
        value: latest.TTFB,
        rating: latest.TTFB ? this.getRating(latest.TTFB, this.thresholds.TTFB) : undefined
      }
    }
  }

  /**
   * Получить средние значения
   */
  getAverageMetrics() {
    if (this.metrics.length === 0) return null

    const totals = this.metrics.reduce((acc, metric) => {
      Object.keys(metric).forEach(key => {
        if (typeof metric[key as keyof PerformanceMetrics] === 'number') {
          acc[key] = (acc[key] || 0) + (metric[key as keyof PerformanceMetrics] as number)
        }
      })
      return acc
    }, {} as Record<string, number>)

    const averages = Object.keys(totals).reduce((acc, key) => {
      acc[key] = totals[key] / this.metrics.length
      return acc
    }, {} as Record<string, number>)

    return averages
  }

  /**
   * Отправить метрики на сервер
   */
  async sendMetrics() {
    if (this.metrics.length === 0) return

    try {
      const vitals = this.getCoreWebVitals()
      const averages = this.getAverageMetrics()

      await fetch('/api/performance/metrics', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
          vitals,
          averages,
          url: window.location.href,
          timestamp: Date.now()
        })
      })

      logger.debug('Performance metrics sent to server', null, 'PerformanceMonitor')
    } catch (error) {
      logger.warn('Failed to send performance metrics', error, 'PerformanceMonitor')
    }
  }

  /**
   * Измерить время выполнения функции
   */
  async measureAsync<T>(name: string, fn: () => Promise<T>): Promise<T> {
    const startTime = performance.now()
    
    try {
      const result = await fn()
      const endTime = performance.now()
      const duration = endTime - startTime
      
      logger.debug(`${name} completed in ${duration.toFixed(2)}ms`, { duration }, 'PerformanceMonitor')
      
      return result
    } catch (error) {
      const endTime = performance.now()
      const duration = endTime - startTime
      
      logger.error(`${name} failed after ${duration.toFixed(2)}ms`, error, 'PerformanceMonitor')
      throw error
    }
  }

  /**
   * Очистка ресурсов
   */
  destroy() {
    this.observers.forEach(observer => observer.disconnect())
    this.observers = []
    this.metrics = []
  }
}

// Экспортируем singleton
export const performanceMonitor = new PerformanceMonitor()

// Отправляем метрики при выгрузке страницы
if (typeof window !== 'undefined') {
  window.addEventListener('beforeunload', () => {
    performanceMonitor.sendMetrics()
  })
}

export default performanceMonitor
