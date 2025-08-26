/**
 * Lighthouse Audit Integration для SPA Platform
 * 
 * Интеграция с Chrome DevTools для автоматического аудита
 */

import { logger } from './logger'
import { performanceMonitor } from './performanceMonitor'

export interface LighthouseMetrics {
  performance: number
  accessibility: number
  bestPractices: number
  seo: number
  pwa?: number
  
  // Детальные метрики
  firstContentfulPaint: number
  largestContentfulPaint: number
  firstInputDelay: number
  cumulativeLayoutShift: number
  speedIndex: number
  totalBlockingTime: number
}

export interface AuditRecommendation {
  category: 'performance' | 'accessibility' | 'best-practices' | 'seo'
  title: string
  description: string
  impact: 'high' | 'medium' | 'low'
  savings?: {
    time?: number  // ms
    bytes?: number // bytes
  }
}

class LighthouseAudit {
  private auditResults: LighthouseMetrics[] = []
  private recommendations: AuditRecommendation[] = []

  /**
   * Запуск аудита (симуляция для demo)
   */
  async runAudit(url?: string): Promise<LighthouseMetrics> {
    const targetUrl = url || window.location.href
    
    logger.info(`Running Lighthouse audit for ${targetUrl}`, null, 'LighthouseAudit')
    
    try {
      // В реальном проекте здесь будет интеграция с Lighthouse API
      const metrics = await this.simulateAudit()
      
      this.auditResults.push(metrics)
      this.generateRecommendations(metrics)
      
      logger.info('Lighthouse audit completed', metrics, 'LighthouseAudit')
      
      return metrics
    } catch (error) {
      logger.error('Lighthouse audit failed', error, 'LighthouseAudit')
      throw error
    }
  }

  /**
   * Симуляция аудита на основе реальных метрик
   */
  private async simulateAudit(): Promise<LighthouseMetrics> {
    const webVitals = performanceMonitor.getCoreWebVitals()
    const averages = performanceMonitor.getAverageMetrics()
    
    // Рассчитываем оценки на основе реальных метрик
    const performance = this.calculatePerformanceScore(webVitals, averages)
    const accessibility = this.estimateAccessibilityScore()
    const bestPractices = this.estimateBestPracticesScore()
    const seo = this.estimateSEOScore()
    
    return {
      performance,
      accessibility,
      bestPractices,
      seo,
      pwa: 85, // Базовая PWA поддержка
      
      // Детальные метрики из performanceMonitor
      firstContentfulPaint: webVitals?.FCP?.value || 1500,
      largestContentfulPaint: webVitals?.LCP?.value || 2200,
      firstInputDelay: webVitals?.FID?.value || 80,
      cumulativeLayoutShift: webVitals?.CLS?.value || 0.05,
      speedIndex: averages?.pageLoadTime || 2000,
      totalBlockingTime: 150
    }
  }

  /**
   * Расчет оценки производительности
   */
  private calculatePerformanceScore(webVitals: any, averages: any): number {
    let score = 100
    
    // Снижаем оценку на основе Core Web Vitals
    if (webVitals?.LCP?.rating === 'poor') score -= 25
    else if (webVitals?.LCP?.rating === 'needs-improvement') score -= 10
    
    if (webVitals?.FID?.rating === 'poor') score -= 20
    else if (webVitals?.FID?.rating === 'needs-improvement') score -= 8
    
    if (webVitals?.CLS?.rating === 'poor') score -= 15
    else if (webVitals?.CLS?.rating === 'needs-improvement') score -= 5
    
    // Дополнительные факторы
    if (averages?.pageLoadTime > 3000) score -= 10
    if (averages?.routeChangeTime > 1000) score -= 5
    
    return Math.max(0, Math.min(100, score))
  }

  /**
   * Оценка доступности
   */
  private estimateAccessibilityScore(): number {
    // Базовая оценка с учетом лучших практик проекта
    let score = 90
    
    // Проверяем наличие семантических элементов
    const hasProperHeadings = document.querySelectorAll('h1, h2, h3').length > 0
    const hasAltTexts = document.querySelectorAll('img[alt]').length > 0
    const hasAriaLabels = document.querySelectorAll('[aria-label]').length > 0
    
    if (!hasProperHeadings) score -= 10
    if (!hasAltTexts) score -= 5
    if (!hasAriaLabels) score -= 5
    
    return Math.max(0, score)
  }

  /**
   * Оценка лучших практик
   */
  private estimateBestPracticesScore(): number {
    let score = 95
    
    // Проверяем безопасность
    const isHTTPS = window.location.protocol === 'https:'
    const hasCSP = document.querySelector('meta[http-equiv="Content-Security-Policy"]')
    
    if (!isHTTPS) score -= 20
    if (!hasCSP) score -= 5
    
    return Math.max(0, score)
  }

  /**
   * Оценка SEO
   */
  private estimateSEOScore(): number {
    let score = 90
    
    // Проверяем мета-теги
    const hasTitle = document.title.length > 0
    const hasDescription = document.querySelector('meta[name="description"]')
    const hasViewport = document.querySelector('meta[name="viewport"]')
    const hasCanonical = document.querySelector('link[rel="canonical"]')
    
    if (!hasTitle) score -= 15
    if (!hasDescription) score -= 10
    if (!hasViewport) score -= 10
    if (!hasCanonical) score -= 5
    
    return Math.max(0, score)
  }

  /**
   * Генерация рекомендаций
   */
  private generateRecommendations(metrics: LighthouseMetrics) {
    this.recommendations = []
    
    // Рекомендации по производительности
    if (metrics.performance < 80) {
      this.recommendations.push({
        category: 'performance',
        title: 'Оптимизация изображений',
        description: 'Используйте современные форматы изображений (WebP, AVIF) и оптимизируйте размеры',
        impact: 'high',
        savings: { time: 500, bytes: 200000 }
      })
      
      this.recommendations.push({
        category: 'performance',
        title: 'Минификация JavaScript',
        description: 'Уменьшите размер JS файлов путем удаления неиспользуемого кода',
        impact: 'medium',
        savings: { time: 200, bytes: 50000 }
      })
    }
    
    if (metrics.largestContentfulPaint > 2500) {
      this.recommendations.push({
        category: 'performance',
        title: 'Улучшение LCP',
        description: 'Оптимизируйте загрузку самого большого контентного элемента',
        impact: 'high',
        savings: { time: 800 }
      })
    }
    
    // Рекомендации по доступности
    if (metrics.accessibility < 90) {
      this.recommendations.push({
        category: 'accessibility',
        title: 'Улучшение контрастности',
        description: 'Увеличьте контрастность текста для лучшей читаемости',
        impact: 'medium'
      })
      
      this.recommendations.push({
        category: 'accessibility',
        title: 'ARIA атрибуты',
        description: 'Добавьте ARIA метки для интерактивных элементов',
        impact: 'medium'
      })
    }
    
    // Рекомендации по лучшим практикам
    if (metrics.bestPractices < 90) {
      this.recommendations.push({
        category: 'best-practices',
        title: 'Обновление зависимостей',
        description: 'Обновите библиотеки до последних версий для устранения уязвимостей',
        impact: 'high'
      })
    }
    
    // Рекомендации по SEO
    if (metrics.seo < 90) {
      this.recommendations.push({
        category: 'seo',
        title: 'Мета-описания',
        description: 'Добавьте уникальные мета-описания для всех страниц',
        impact: 'medium'
      })
    }
  }

  /**
   * Получить результаты аудита
   */
  getAuditResults(): LighthouseMetrics[] {
    return [...this.auditResults]
  }

  /**
   * Получить рекомендации
   */
  getRecommendations(): AuditRecommendation[] {
    return [...this.recommendations]
  }

  /**
   * Получить последний результат
   */
  getLatestResults(): LighthouseMetrics | null {
    return this.auditResults[this.auditResults.length - 1] || null
  }

  /**
   * Сравнить с предыдущим аудитом
   */
  compareWithPrevious(): Record<string, number> | null {
    if (this.auditResults.length < 2) return null
    
    const current = this.auditResults[this.auditResults.length - 1]
    const previous = this.auditResults[this.auditResults.length - 2]
    
    return {
      performance: current.performance - previous.performance,
      accessibility: current.accessibility - previous.accessibility,
      bestPractices: current.bestPractices - previous.bestPractices,
      seo: current.seo - previous.seo
    }
  }

  /**
   * Экспорт результатов
   */
  exportResults(): string {
    const data = {
      auditResults: this.auditResults,
      recommendations: this.recommendations,
      timestamp: new Date().toISOString(),
      url: window.location.href
    }
    
    return JSON.stringify(data, null, 2)
  }

  /**
   * Очистка результатов
   */
  clear() {
    this.auditResults = []
    this.recommendations = []
  }
}

// Экспортируем singleton
export const lighthouseAudit = new LighthouseAudit()

export default lighthouseAudit
