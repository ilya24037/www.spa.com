/**
 * Universal Logger для SPA Platform
 * 
 * Обеспечивает централизованное логирование
 */

export type LogLevel = 'debug' | 'info' | 'warn' | 'error'

export interface LogEntry {
  level: LogLevel
  message: string
  data?: unknown
  timestamp: string
  component?: string
  userId?: number
  url?: string
}

class Logger {
  private isDevelopment: boolean
  private logs: LogEntry[] = []
  private maxLogs = 100

  constructor() {
    this.isDevelopment = import.meta.env.DEV
  }

  /**
   * Debug логирование (только в development)
   */
  debug(message: string, data?: unknown, component?: string): void {
    if (this.isDevelopment) {
    }
    this.addLog('debug', message, data, component)
  }

  /**
   * Информационное логирование
   */
  info(message: string, data?: unknown, component?: string): void {
    if (this.isDevelopment) {
      console.info(`[INFO] ${component ? `[${component}] ` : ''}${message}`, data)
    }
    this.addLog('info', message, data, component)
  }

  /**
   * Предупреждения
   */
  warn(message: string, data?: unknown, component?: string): void {
    if (this.isDevelopment) {
      console.warn(`[WARN] ${component ? `[${component}] ` : ''}${message}`, data)
    }
    this.addLog('warn', message, data, component)
  }

  /**
   * Ошибки (всегда логируются)
   */
  error(message: string, error?: unknown, component?: string): void {
    // В production ошибки логируются только в наш лог
    if (this.isDevelopment) {
      console.error(`[ERROR] ${component ? `[${component}] ` : ''}${message}`, error)
    }
    
    this.addLog('error', message, error, component)
    
    // В production отправляем критические ошибки на сервер
    if (!this.isDevelopment) {
      this.sendErrorToServer(message, error, component)
    }
  }

  /**
   * Добавление записи в лог
   */
  private addLog(level: LogLevel, message: string, data?: unknown, component?: string): void {
    const entry: LogEntry = {
      level,
      message,
      data: this.serializeData(data),
      timestamp: new Date().toISOString(),
      component,
      url: window.location.href
    }

    this.logs.push(entry)

    // Ограничиваем размер логов
    if (this.logs.length > this.maxLogs) {
      this.logs = this.logs.slice(-this.maxLogs)
    }
  }

  /**
   * Сериализация данных для безопасного хранения
   */
  private serializeData(data: unknown): unknown {
    if (!data) return null
    
    try {
      // Удаляем циклические ссылки и функции
      return JSON.parse(JSON.stringify(data, (_key, value) => {
        if (typeof value === 'function') return '[Function]'
        if (value instanceof Error) return value.message
        return value
      }))
    } catch {
      return '[Unserializable]'
    }
  }

  /**
   * Отправка ошибки на сервер (production)
   */
  private async sendErrorToServer(message: string, error?: any, component?: string): Promise<void> {
    try {
      await fetch('/api/logs/error', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
          message,
          error: error instanceof Error ? error.message : String(error),
          stack: error instanceof Error ? error.stack : undefined,
          component,
          url: window.location.href,
          userAgent: navigator.userAgent,
          timestamp: new Date().toISOString()
        })
      })
    } catch {
      // Если не удалось отправить на сервер, сохраняем в localStorage
      this.saveErrorLocally(message, error, component)
    }
  }

  /**
   * Сохранение ошибки локально как fallback
   */
  private saveErrorLocally(message: string, error?: unknown, component?: string): void {
    try {
      const errors = JSON.parse(localStorage.getItem('spa_errors') || '[]')
      errors.push({
        message,
        error: error instanceof Error ? error.message : String(error),
        component,
        timestamp: new Date().toISOString()
      })
      
      // Ограничиваем количество сохраненных ошибок
      if (errors.length > 10) {
        errors.splice(0, errors.length - 10)
      }
      
      localStorage.setItem('spa_errors', JSON.stringify(errors))
    } catch {
      // Если localStorage недоступен, игнорируем
    }
  }

  /**
   * Получение всех логов
   */
  getLogs(): LogEntry[] {
    return [...this.logs]
  }

  /**
   * Получение логов по уровню
   */
  getLogsByLevel(level: LogLevel): LogEntry[] {
    return this.logs.filter(log => log.level === level)
  }

  /**
   * Очистка логов
   */
  clearLogs(): void {
    this.logs = []
  }

  /**
   * Экспорт логов для отладки
   */
  exportLogs(): string {
    return JSON.stringify(this.logs, null, 2)
  }

  /**
   * Performance логирование
   */
  time(label: string): void {
    if (this.isDevelopment) {
      console.time(label)
    }
  }

  timeEnd(label: string): void {
    if (this.isDevelopment) {
      console.timeEnd(label)
    }
  }

  /**
   * Группировка логов
   */
  group(label: string): void {
    if (this.isDevelopment) {
      console.group(label)
    }
  }

  groupEnd(): void {
    if (this.isDevelopment) {
      console.groupEnd()
    }
  }

  /**
   * Логирование состояния компонента
   */
  component(name: string, state?: any): void {
    this.debug(`Component ${name} state`, state, name)
  }

  /**
   * Логирование API вызовов
   */
  api(method: string, url: string, data?: any): void {
    this.debug(`API ${method} ${url}`, data, 'API')
  }

  /**
   * Логирование пользовательских действий
   */
  userAction(action: string, data?: any): void {
    this.info(`User action: ${action}`, data, 'UserAction')
  }
}

// Экспортируем singleton
export const logger = new Logger()

// Удобные алиасы для быстрого использования
export const log = {
  debug: (message: string, data?: any, component?: string) => logger.debug(message, data, component),
  info: (message: string, data?: any, component?: string) => logger.info(message, data, component),
  warn: (message: string, data?: any, component?: string) => logger.warn(message, data, component),
  error: (message: string, error?: any, component?: string) => logger.error(message, error, component),
  component: (name: string, state?: any) => logger.component(name, state),
  api: (method: string, url: string, data?: any) => logger.api(method, url, data),
  userAction: (action: string, data?: any) => logger.userAction(action, data)
}

// Экспорт для обратной совместимости
export default logger
