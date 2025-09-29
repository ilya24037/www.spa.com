/**
 * Mutex - мьютекс для защиты от race conditions
 * Размер: ~80 строк
 * 
 * Предназначение:
 * - Предотвращение параллельного выполнения критических секций
 * - Гарантия атомарности сложных операций
 * - Защита от race conditions в асинхронном коде
 * - Контроль очереди ожидающих операций
 * 
 * Принципы:
 * - KISS: Простая реализация сложного механизма
 * - Safety First: Предотвращение deadlock'ов
 * - Performance: Минимальные накладные расходы
 * - Reliability: Корректная работа в любых условиях
 */

import { logger } from '@/src/shared/lib/logger'

/**
 * Информация о заблокированной операции
 */
export interface LockInfo {
  /** ID операции */
  operationId: string
  /** Время начала ожидания */
  waitStart: number
  /** Время получения блокировки */
  lockAcquired?: number
  /** Контекст операции */
  context: string
}

/**
 * Конфигурация мьютекса
 */
export interface MutexConfig {
  /** Имя мьютекса для отладки */
  name?: string
  /** Таймаут ожидания блокировки (мс) */
  timeout?: number
  /** Включить детальное логирование */
  debug?: boolean
}

/**
 * Mutex - мьютекс для защиты критических секций от race conditions
 */
export class Mutex {
  /** Имя мьютекса для отладки */
  private readonly name: string
  
  /** Заблокирован ли мьютекс */
  private locked = false
  
  /** Очередь ожидающих операций */
  private waitQueue: Array<{
    resolve: () => void
    reject: (error: Error) => void
    info: LockInfo
  }> = []
  
  /** Информация о текущей заблокированной операции */
  private currentLock: LockInfo | null = null
  
  /** Конфигурация */
  private config: Required<MutexConfig>

  constructor(config: MutexConfig = {}) {
    this.name = config.name || `Mutex-${Math.random().toString(36).substr(2, 9)}`
    this.config = {
      name: this.name,
      timeout: config.timeout || 10000, // 10 секунд по умолчанию
      debug: config.debug || false
    }

    if (this.config.debug) {
      logger.debug(`[${this.name}] Мьютекс создан`)
    }
  }

  /**
   * Получение блокировки
   * 
   * @param context - контекст операции для отладки
   * @returns Promise который разрешается когда блокировка получена
   * 
   * @example
   * ```typescript
   * const mutex = new Mutex({ name: 'PluginInstall' })
   * 
   * await mutex.acquire('install-markers-plugin')
   * try {
   *   // Критическая секция
   *   await installPlugin(plugin)
   * } finally {
   *   mutex.release()
   * }
   * ```
   */
  async acquire(context: string = 'unknown'): Promise<void> {
    const operationId = `${context}-${Date.now()}-${Math.random().toString(36).substr(2, 5)}`
    
    const lockInfo: LockInfo = {
      operationId,
      waitStart: Date.now(),
      context
    }

    if (this.config.debug) {
      logger.debug(`[${this.name}] Запрос блокировки: ${operationId}`)
    }

    // Если мьютекс свободен, захватываем сразу
    if (!this.locked) {
      this.locked = true
      this.currentLock = {
        ...lockInfo,
        lockAcquired: Date.now()
      }
      
      if (this.config.debug) {
        logger.debug(`[${this.name}] Блокировка получена сразу: ${operationId}`)
      }
      
      return
    }

    // Если занят, встаем в очередь
    return new Promise((resolve, reject) => {
      const timeoutId = setTimeout(() => {
        // Удаляем из очереди при таймауте
        const index = this.waitQueue.findIndex(item => item.info.operationId === operationId)
        if (index !== -1) {
          this.waitQueue.splice(index, 1)
        }
        
        const error = new Error(`[${this.name}] Таймаут ожидания блокировки: ${operationId} (${this.config.timeout}мс)`)
        logger.error(error.message)
        reject(error)
      }, this.config.timeout)

      this.waitQueue.push({
        resolve: () => {
          clearTimeout(timeoutId)
          this.currentLock = {
            ...lockInfo,
            lockAcquired: Date.now()
          }
          
          if (this.config.debug) {
            const waitTime = Date.now() - lockInfo.waitStart
            logger.debug(`[${this.name}] Блокировка получена из очереди: ${operationId} (ожидание: ${waitTime}мс)`)
          }
          
          resolve()
        },
        reject: (error: Error) => {
          clearTimeout(timeoutId)
          reject(error)
        },
        info: lockInfo
      })

      if (this.config.debug) {
        logger.debug(`[${this.name}] Добавлено в очередь: ${operationId} (позиция: ${this.waitQueue.length})`)
      }
    })
  }

  /**
   * Освобождение блокировки
   * 
   * @example
   * ```typescript
   * try {
   *   await mutex.acquire('critical-operation')
   *   // критическая секция
   * } finally {
   *   mutex.release() // ОБЯЗАТЕЛЬНО в finally!
   * }
   * ```
   */
  release(): void {
    if (!this.locked) {
      logger.warn(`[${this.name}] Попытка освободить незаблокированный мьютекс`)
      return
    }

    const releasedLock = this.currentLock
    if (this.config.debug && releasedLock) {
      const holdTime = Date.now() - (releasedLock.lockAcquired || releasedLock.waitStart)
      logger.debug(`[${this.name}] Блокировка освобождена: ${releasedLock.operationId} (удержание: ${holdTime}мс)`)
    }

    this.currentLock = null

    // Если есть очередь, передаем блокировку следующему
    if (this.waitQueue.length > 0) {
      const next = this.waitQueue.shift()!
      next.resolve()
      
      if (this.config.debug) {
        logger.debug(`[${this.name}] Блокировка передана: ${next.info.operationId}`)
      }
    } else {
      // Если очереди нет, освобождаем мьютекс
      this.locked = false
      
      if (this.config.debug) {
        logger.debug(`[${this.name}] Мьютекс полностью освобожден`)
      }
    }
  }

  /**
   * Выполнение функции с автоматической блокировкой
   * 
   * @param fn - функция для выполнения в критической секции
   * @param context - контекст для отладки
   * @returns результат выполнения функции
   * 
   * @example
   * ```typescript
   * const result = await mutex.runExclusive(async () => {
   *   // Критическая секция
   *   return await complexOperation()
   * }, 'complex-operation')
   * ```
   */
  async runExclusive<T>(fn: () => Promise<T>, context: string = 'exclusive'): Promise<T> {
    await this.acquire(context)
    try {
      return await fn()
    } finally {
      this.release()
    }
  }

  /**
   * Получение статуса мьютекса для отладки
   */
  getStatus() {
    return {
      name: this.name,
      locked: this.locked,
      queueLength: this.waitQueue.length,
      currentLock: this.currentLock,
      waitQueue: this.waitQueue.map(item => ({
        operationId: item.info.operationId,
        context: item.info.context,
        waitTime: Date.now() - item.info.waitStart
      }))
    }
  }

  /**
   * Принудительная очистка мьютекса (только для экстренных случаев)
   */
  forceReset(): void {
    logger.warn(`[${this.name}] ПРИНУДИТЕЛЬНАЯ ОЧИСТКА мьютекса`)
    
    // Отклоняем все ожидающие операции
    this.waitQueue.forEach(item => {
      item.reject(new Error(`[${this.name}] Мьютекс принудительно очищен`))
    })
    
    this.waitQueue = []
    this.locked = false
    this.currentLock = null
  }
}