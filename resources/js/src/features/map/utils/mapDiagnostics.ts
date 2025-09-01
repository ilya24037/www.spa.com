/**
 * Утилиты диагностики карты
 * Помогают выявить проблемы с инициализацией
 */

export interface DiagnosticsResult {
  step: string
  status: 'ok' | 'error' | 'warning'
  message: string
  details?: any
}

export class MapDiagnostics {
  private results: DiagnosticsResult[] = []
  
  // Очистить результаты
  clear() {
    this.results = []
    console.log('[MapDiagnostics] 🧹 Результаты очищены')
  }
  
  // Добавить результат
  private addResult(step: string, status: 'ok' | 'error' | 'warning', message: string, details?: any) {
    this.results.push({ step, status, message, details })
    
    const icon = status === 'ok' ? '✅' : status === 'warning' ? '⚠️' : '❌'
    console.log(`[MapDiagnostics] ${icon} ${step}: ${message}`)
    if (details) {
      console.log(`[MapDiagnostics] 📋 Детали:`, details)
    }
  }
  
  // Проверить доступность Yandex Maps API
  checkApiAvailability(apiKey: string) {
    if (!apiKey || apiKey.trim() === '') {
      this.addResult('API Key', 'warning', 'API ключ пустой', { apiKey })
      return
    }
    
    if (window.ymaps) {
      if (window.ymaps.ready) {
        this.addResult('API Availability', 'ok', 'Yandex Maps API загружен и готов')
      } else {
        this.addResult('API Availability', 'warning', 'Yandex Maps API загружен, но не готов')
      }
    } else {
      this.addResult('API Availability', 'error', 'Yandex Maps API не загружен')
    }
  }
  
  // Проверить DOM элементы
  checkDomElements(mapId: string) {
    const container = document.getElementById(mapId)
    if (container) {
      this.addResult('DOM Container', 'ok', `Контейнер найден: ${mapId}`, {
        id: mapId,
        offsetWidth: container.offsetWidth,
        offsetHeight: container.offsetHeight,
        style: container.style.cssText
      })
    } else {
      this.addResult('DOM Container', 'error', `Контейнер не найден: ${mapId}`)
    }
  }
  
  // Проверить конфигурацию карты
  checkMapConfig(config: any) {
    if (!config) {
      this.addResult('Map Config', 'error', 'Конфигурация карты отсутствует')
      return
    }
    
    if (!config.center || !Array.isArray(config.center) || config.center.length !== 2) {
      this.addResult('Map Config', 'error', 'Неверный формат центра карты', { center: config.center })
      return
    }
    
    if (typeof config.zoom !== 'number' || config.zoom < 1 || config.zoom > 23) {
      this.addResult('Map Config', 'warning', 'Подозрительное значение zoom', { zoom: config.zoom })
    } else {
      this.addResult('Map Config', 'ok', 'Конфигурация карты корректна', config)
    }
  }
  
  // Проверить объект карты
  checkMapInstance(map: any) {
    if (!map) {
      this.addResult('Map Instance', 'error', 'Объект карты не создан')
      return
    }
    
    try {
      const center = map.getCenter()
      const zoom = map.getZoom()
      const bounds = map.getBounds()
      
      this.addResult('Map Instance', 'ok', 'Карта успешно создана и функционирует', {
        center,
        zoom,
        bounds
      })
    } catch (error) {
      this.addResult('Map Instance', 'error', 'Карта создана, но не функционирует', { error: error.message })
    }
  }
  
  // Проверить загруженные скрипты
  checkLoadedScripts() {
    const scripts = Array.from(document.querySelectorAll('script[src*="api-maps.yandex.ru"]'))
    
    if (scripts.length === 0) {
      this.addResult('Scripts', 'error', 'Скрипты Yandex Maps не найдены в DOM')
    } else if (scripts.length === 1) {
      const script = scripts[0] as HTMLScriptElement
      this.addResult('Scripts', 'ok', 'Скрипт Yandex Maps загружен', {
        src: script.src,
        loaded: script.readyState === 'complete'
      })
    } else {
      this.addResult('Scripts', 'warning', `Найдено ${scripts.length} скриптов Yandex Maps (возможно дублирование)`)
    }
  }
  
  // Полная диагностика
  fullDiagnostics(params: {
    mapId: string
    apiKey: string
    config?: any
    mapInstance?: any
  }) {
    console.log('[MapDiagnostics] 🚀 Начинаем полную диагностику')
    
    this.clear()
    
    this.checkLoadedScripts()
    this.checkApiAvailability(params.apiKey)
    this.checkDomElements(params.mapId)
    
    if (params.config) {
      this.checkMapConfig(params.config)
    }
    
    if (params.mapInstance) {
      this.checkMapInstance(params.mapInstance)
    }
    
    this.printSummary()
    return this.results
  }
  
  // Печать краткого отчёта
  printSummary() {
    const errors = this.results.filter(r => r.status === 'error').length
    const warnings = this.results.filter(r => r.status === 'warning').length
    const success = this.results.filter(r => r.status === 'ok').length
    
    console.log('[MapDiagnostics] 📊 ИТОГИ ДИАГНОСТИКИ:')
    console.log(`[MapDiagnostics] ✅ Успешно: ${success}`)
    console.log(`[MapDiagnostics] ⚠️ Предупреждения: ${warnings}`)
    console.log(`[MapDiagnostics] ❌ Ошибки: ${errors}`)
    
    if (errors > 0) {
      console.log('[MapDiagnostics] 🚨 Обнаружены критические ошибки!')
      const errorMessages = this.results.filter(r => r.status === 'error').map(r => `  • ${r.step}: ${r.message}`)
      console.log(errorMessages.join('\n'))
    }
    
    return { errors, warnings, success }
  }
  
  // Получить результаты
  getResults() {
    return this.results
  }
}

// Создать глобальную утилиту диагностики
export const mapDiagnostics = new MapDiagnostics()

// Добавить в window для отладки
if (typeof window !== 'undefined') {
  (window as any).__mapDiagnostics = mapDiagnostics
}