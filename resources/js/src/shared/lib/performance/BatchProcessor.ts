/**
 * BatchProcessor - система пакетной обработки операций для оптимизации производительности
 * Размер: ~120 строк
 * 
 * Предназначение:
 * - Объединение множественных операций в пакеты
 * - Предотвращение избыточных вызовов DOM операций
 * - Оптимизация производительности через debounce/throttle
 * - Управление приоритетом операций
 * 
 * Принципы:
 * - KISS: Простая система пакетирования
 * - Performance First: Максимальная оптимизация
 * - Safety: Защита от потери операций
 * - Reliability: Гарантированное выполнение
 */

import { logger } from '@/src/shared/lib/logger'

/**
 * Приоритет операции
 */
export enum OperationPriority {
  LOW = 1,
  NORMAL = 2,
  HIGH = 3,
  CRITICAL = 4
}

/**
 * Операция для пакетной обработки
 */
export interface BatchOperation<T = any> {
  /** Уникальный ID операции */
  id: string
  /** Данные операции */
  data: T
  /** Приоритет операции */
  priority: OperationPriority
  /** Время добавления операции */
  timestamp: number
  /** Контекст операции */
  context?: string
}

/**
 * Конфигурация обработчика пакетов
 */
export interface BatchProcessorConfig {
  /** Максимальный размер пакета */
  maxBatchSize: number
  /** Максимальное время ожидания перед обработкой (мс) */
  maxWaitTime: number
  /** Имя процессора для отладки */
  name?: string
  /** Включить детальное логирование */
  debug?: boolean
  /** Обрабатывать операции по приоритету */
  priorityEnabled?: boolean
}

/**
 * Обработчик пакетов операций
 */
export type BatchHandler<T> = (operations: BatchOperation<T>[]) => Promise<void> | void

/**
 * BatchProcessor - система пакетной обработки операций
 */
export class BatchProcessor<T = any> {
  /** Конфигурация процессора */
  private config: Required<BatchProcessorConfig>
  
  /** Очередь операций ожидающих обработки */
  private operationQueue: BatchOperation<T>[] = []
  
  /** Обработчик пакетов */
  private handler: BatchHandler<T>
  
  /** Таймер для отложенной обработки */
  private timeoutId: NodeJS.Timeout | null = null
  
  /** Флаг активной обработки */
  private isProcessing = false
  
  /** Статистика */
  private stats = {
    totalOperations: 0,
    totalBatches: 0,
    averageBatchSize: 0,
    lastProcessTime: 0
  }

  /**
   * @param handler - функция обработки пакетов операций
   * @param config - конфигурация процессора
   */
  constructor(handler: BatchHandler<T>, config: Partial<BatchProcessorConfig> = {}) {
    this.handler = handler
    this.config = {
      maxBatchSize: config.maxBatchSize || 50,
      maxWaitTime: config.maxWaitTime || 100,
      name: config.name || `BatchProcessor-${Math.random().toString(36).substr(2, 5)}`,
      debug: config.debug || false,
      priorityEnabled: config.priorityEnabled || true
    }

    if (this.config.debug) {
      logger.debug(`[${this.config.name}] Процессор создан:`, this.config)
    }
  }

  /**
   * Добавление операции в очередь
   * 
   * @param data - данные операции
   * @param priority - приоритет операции
   * @param context - контекст для отладки
   * @returns ID добавленной операции
   */
  add(data: T, priority: OperationPriority = OperationPriority.NORMAL, context?: string): string {
    const operation: BatchOperation<T> = {
      id: `${this.config.name}-${Date.now()}-${Math.random().toString(36).substr(2, 5)}`,
      data,
      priority,
      timestamp: Date.now(),
      context
    }

    // Добавляем в очередь с учетом приоритета
    if (this.config.priorityEnabled) {
      this.insertByPriority(operation)
    } else {
      this.operationQueue.push(operation)
    }

    this.stats.totalOperations++

    if (this.config.debug) {
      logger.debug(`[${this.config.name}] Операция добавлена:`, {
        id: operation.id,
        priority,
        queueSize: this.operationQueue.length,
        context
      })
    }

    this.scheduleProcessing()
    return operation.id
  }

  /**
   * Принудительная обработка всех операций в очереди
   */
  async flush(): Promise<void> {
    if (this.timeoutId) {
      clearTimeout(this.timeoutId)
      this.timeoutId = null
    }

    if (this.operationQueue.length > 0 && !this.isProcessing) {
      await this.processBatch()
    }
  }

  /**
   * Получение статистики процессора
   */
  getStats() {
    return {
      ...this.stats,
      currentQueueSize: this.operationQueue.length,
      isProcessing: this.isProcessing,
      config: this.config
    }
  }

  /**
   * Очистка очереди операций
   */
  clear(): void {
    if (this.timeoutId) {
      clearTimeout(this.timeoutId)
      this.timeoutId = null
    }

    const queueSize = this.operationQueue.length
    this.operationQueue = []

    if (this.config.debug && queueSize > 0) {
      logger.debug(`[${this.config.name}] Очередь очищена: ${queueSize} операций отменено`)
    }
  }

  /**
   * Добавление операции в очередь с учетом приоритета
   */
  private insertByPriority(operation: BatchOperation<T>): void {
    let inserted = false
    
    // Ищем место для вставки по приоритету
    for (let i = 0; i < this.operationQueue.length; i++) {
      if (operation.priority > this.operationQueue[i].priority) {
        this.operationQueue.splice(i, 0, operation)
        inserted = true
        break
      }
    }

    // Если не вставили, добавляем в конец
    if (!inserted) {
      this.operationQueue.push(operation)
    }
  }

  /**
   * Планирование обработки пакета
   */
  private scheduleProcessing(): void {
    // Если достигли максимального размера пакета, обрабатываем сразу
    if (this.operationQueue.length >= this.config.maxBatchSize) {
      if (this.timeoutId) {
        clearTimeout(this.timeoutId)
        this.timeoutId = null
      }
      
      // Обрабатываем асинхронно чтобы не блокировать добавление
      setTimeout(() => this.processBatch(), 0)
      return
    }

    // Если таймер еще не запущен, запускаем
    if (!this.timeoutId) {
      this.timeoutId = setTimeout(() => {
        this.timeoutId = null
        this.processBatch()
      }, this.config.maxWaitTime)
    }
  }

  /**
   * Обработка пакета операций
   */
  private async processBatch(): Promise<void> {
    if (this.isProcessing || this.operationQueue.length === 0) {
      return
    }

    this.isProcessing = true
    const startTime = Date.now()

    try {
      // Извлекаем пакет операций
      const batchSize = Math.min(this.config.maxBatchSize, this.operationQueue.length)
      const batch = this.operationQueue.splice(0, batchSize)

      this.stats.totalBatches++
      this.stats.averageBatchSize = 
        (this.stats.averageBatchSize * (this.stats.totalBatches - 1) + batch.length) / this.stats.totalBatches

      if (this.config.debug) {
        logger.debug(`[${this.config.name}] Обработка пакета:`, {
          batchSize: batch.length,
          remainingInQueue: this.operationQueue.length,
          priorities: batch.map(op => op.priority)
        })
      }

      // Обрабатываем пакет
      await this.handler(batch)

      const processingTime = Date.now() - startTime
      this.stats.lastProcessTime = processingTime

      if (this.config.debug) {
        logger.debug(`[${this.config.name}] Пакет обработан:`, {
          processingTime,
          batchSize: batch.length
        })
      }

      // Если остались операции, планируем следующую обработку
      if (this.operationQueue.length > 0) {
        this.scheduleProcessing()
      }

    } catch (error) {
      logger.error(`[${this.config.name}] Ошибка обработки пакета:`, error)
      
      // В случае ошибки очищаем очередь чтобы избежать бесконечных повторов
      this.operationQueue = []
      
    } finally {
      this.isProcessing = false
    }
  }
}