/**
 * Универсальный logger для приложения
 * Обеспечивает единообразное логирование с поддержкой различных уровней и окружений
 */

export type LogLevel = 'debug' | 'info' | 'warn' | 'error' | 'fatal';

export interface LoggerConfig {
  enabled: boolean;
  level: LogLevel;
  prefix?: string;
  sendToServer?: boolean;
  serverEndpoint?: string;
  includeTimestamp?: boolean;
  includeStackTrace?: boolean;
}

export interface LogContext {
  module?: string;
  userId?: number | string;
  action?: string;
  metadata?: Record<string, any>;
}

class Logger {
  private config: LoggerConfig;
  private levelPriority: Record<LogLevel, number> = {
    debug: 0,
    info: 1,
    warn: 2,
    error: 3,
    fatal: 4
  };

  constructor(config: Partial<LoggerConfig> = {}) {
    this.config = {
      enabled: import.meta.env.PROD, // В production логирование включено
      level: import.meta.env.DEV ? 'debug' : 'warn', // В dev режиме больше логов
      prefix: '[SPA]',
      sendToServer: import.meta.env.PROD,
      serverEndpoint: '/api/logs',
      includeTimestamp: true,
      includeStackTrace: true,
      ...config
    };
  }

  /**
   * Проверка, нужно ли логировать сообщение данного уровня
   */
  private shouldLog(level: LogLevel): boolean {
    if (!this.config.enabled) return false;
    return this.levelPriority[level] >= this.levelPriority[this.config.level];
  }

  /**
   * Форматирование сообщения для вывода
   */
  private formatMessage(level: LogLevel, message: string, context?: LogContext): string {
    const parts: string[] = [];

    if (this.config.prefix) {
      parts.push(this.config.prefix);
    }

    if (this.config.includeTimestamp) {
      parts.push(`[${new Date().toISOString()}]`);
    }

    parts.push(`[${level.toUpperCase()}]`);

    if (context?.module) {
      parts.push(`[${context.module}]`);
    }

    parts.push(message);

    return parts.join(' ');
  }

  /**
   * Получение стека вызовов
   */
  private getStackTrace(): string {
    const error = new Error();
    const stack = error.stack || '';
    // Убираем первые строки стека, относящиеся к самому logger
    return stack.split('\n').slice(3).join('\n');
  }

  /**
   * Отправка логов на сервер
   */
  private async sendToServer(
    level: LogLevel,
    message: string,
    context?: LogContext,
    error?: any
  ): Promise<void> {
    if (!this.config.sendToServer || !this.config.serverEndpoint) return;

    try {
      const payload = {
        level,
        message,
        context,
        error: error ? {
          message: error.message,
          stack: error.stack,
          name: error.name
        } : undefined,
        timestamp: new Date().toISOString(),
        userAgent: navigator.userAgent,
        url: window.location.href
      };

      // Используем sendBeacon для надежной отправки
      const blob = new Blob([JSON.stringify(payload)], { type: 'application/json' });
      navigator.sendBeacon(this.config.serverEndpoint, blob);
    } catch (err) {
      // Не логируем ошибки отправки, чтобы избежать рекурсии
    }
  }

  /**
   * Базовый метод логирования
   */
  private log(
    level: LogLevel,
    message: string,
    context?: LogContext,
    ...args: any[]
  ): void {
    if (!this.shouldLog(level)) return;

    const formattedMessage = this.formatMessage(level, message, context);

    // Определяем метод консоли для вывода
    const consoleMethod = level === 'debug' ? 'log' : level;
    
    // Выводим в консоль в dev режиме
    if (import.meta.env.DEV) {
      console[consoleMethod](formattedMessage, ...args);
      
      if (this.config.includeStackTrace && (level === 'error' || level === 'fatal')) {
        console[consoleMethod]('Stack trace:', this.getStackTrace());
      }
    }

    // Отправляем на сервер в production
    if (level === 'error' || level === 'fatal') {
      this.sendToServer(level, message, context, args[0]);
    }
  }

  /**
   * Debug уровень - подробная информация для отладки
   */
  debug(message: string, context?: LogContext, ...args: any[]): void {
    this.log('debug', message, context, ...args);
  }

  /**
   * Info уровень - общая информация о работе приложения
   */
  info(message: string, context?: LogContext, ...args: any[]): void {
    this.log('info', message, context, ...args);
  }

  /**
   * Warn уровень - предупреждения о потенциальных проблемах
   */
  warn(message: string, context?: LogContext, ...args: any[]): void {
    this.log('warn', message, context, ...args);
  }

  /**
   * Error уровень - ошибки, которые не прерывают работу
   */
  error(message: string, error?: Error | any, context?: LogContext): void {
    this.log('error', message, context, error);
  }

  /**
   * Fatal уровень - критические ошибки, прерывающие работу
   */
  fatal(message: string, error?: Error | any, context?: LogContext): void {
    this.log('fatal', message, context, error);
  }

  /**
   * Создание дочернего logger с контекстом
   */
  createChild(context: LogContext): Logger {
    return new Logger({
      ...this.config,
      prefix: context.module ? `${this.config.prefix} [${context.module}]` : this.config.prefix
    });
  }

  /**
   * Обновление конфигурации logger
   */
  updateConfig(config: Partial<LoggerConfig>): void {
    this.config = { ...this.config, ...config };
  }
}

// Создаем глобальный экземпляр logger
export const logger = new Logger();

// Экспортируем класс для создания кастомных logger'ов
export { Logger };

// Удобные алиасы для быстрого доступа
export const logDebug = logger.debug.bind(logger);
export const logInfo = logger.info.bind(logger);
export const logWarn = logger.warn.bind(logger);
export const logError = logger.error.bind(logger);
export const logFatal = logger.fatal.bind(logger);