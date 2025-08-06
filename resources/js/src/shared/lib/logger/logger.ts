/**
 * РЈРЅРёРІРµСЂСЃР°Р»СЊРЅС‹Р№ logger РґР»СЏ РїСЂРёР»РѕР¶РµРЅРёСЏ
 * РћР±РµСЃРїРµС‡РёРІР°РµС‚ РµРґРёРЅРѕРѕР±СЂР°Р·РЅРѕРµ Р»РѕРіРёСЂРѕРІР°РЅРёРµ СЃ РїРѕРґРґРµСЂР¶РєРѕР№ СЂР°Р·Р»РёС‡РЅС‹С… СѓСЂРѕРІРЅРµР№ Рё РѕРєСЂСѓР¶РµРЅРёР№
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
      enabled: import.meta.env.PROD, // Р’ production Р»РѕРіРёСЂРѕРІР°РЅРёРµ РІРєР»СЋС‡РµРЅРѕ
      level: import.meta.env.DEV ? 'debug' : 'warn', // Р’ dev СЂРµР¶РёРјРµ Р±РѕР»СЊС€Рµ Р»РѕРіРѕРІ
      prefix: '[SPA]',
      sendToServer: import.meta.env.PROD,
      serverEndpoint: '/api/logs',
      includeTimestamp: true,
      includeStackTrace: true,
      ...config
    };
  }

  /**
   * РџСЂРѕРІРµСЂРєР°, РЅСѓР¶РЅРѕ Р»Рё Р»РѕРіРёСЂРѕРІР°С‚СЊ СЃРѕРѕР±С‰РµРЅРёРµ РґР°РЅРЅРѕРіРѕ СѓСЂРѕРІРЅСЏ
   */
  private shouldLog(level: LogLevel): boolean {
    if (!this.config.enabled) return false;
    return this.levelPriority[level] >= this.levelPriority[this.config.level];
  }

  /**
   * Р¤РѕСЂРјР°С‚РёСЂРѕРІР°РЅРёРµ СЃРѕРѕР±С‰РµРЅРёСЏ РґР»СЏ РІС‹РІРѕРґР°
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
   * РџРѕР»СѓС‡РµРЅРёРµ СЃС‚РµРєР° РІС‹Р·РѕРІРѕРІ
   */
  private getStackTrace(): string {
    const error = new Error();
    const stack = error.stack || '';
    // РЈР±РёСЂР°РµРј РїРµСЂРІС‹Рµ СЃС‚СЂРѕРєРё СЃС‚РµРєР°, РѕС‚РЅРѕСЃСЏС‰РёРµСЃСЏ Рє СЃР°РјРѕРјСѓ logger
    return stack.split('\n').slice(3).join('\n');
  }

  /**
   * РћС‚РїСЂР°РІРєР° Р»РѕРіРѕРІ РЅР° СЃРµСЂРІРµСЂ
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

      // РСЃРїРѕР»СЊР·СѓРµРј sendBeacon РґР»СЏ РЅР°РґРµР¶РЅРѕР№ РѕС‚РїСЂР°РІРєРё
      const blob = new Blob([JSON.stringify(payload)], { type: 'application/json' });
      navigator.sendBeacon(this.config.serverEndpoint, blob);
    } catch (err) {
      // РќРµ Р»РѕРіРёСЂСѓРµРј РѕС€РёР±РєРё РѕС‚РїСЂР°РІРєРё, С‡С‚РѕР±С‹ РёР·Р±РµР¶Р°С‚СЊ СЂРµРєСѓСЂСЃРёРё
    }
  }

  /**
   * Р‘Р°Р·РѕРІС‹Р№ РјРµС‚РѕРґ Р»РѕРіРёСЂРѕРІР°РЅРёСЏ
   */
  private log(
    level: LogLevel,
    message: string,
    context?: LogContext,
    ...args: any[]
  ): void {
    if (!this.shouldLog(level)) return;

    const formattedMessage = this.formatMessage(level, message, context);

    // РћРїСЂРµРґРµР»СЏРµРј РјРµС‚РѕРґ РєРѕРЅСЃРѕР»Рё РґР»СЏ РІС‹РІРѕРґР°
    const consoleMethod = level === 'debug' ? 'log' : level;
    
    // Р’С‹РІРѕРґРёРј РІ РєРѕРЅСЃРѕР»СЊ РІ dev СЂРµР¶РёРјРµ
    if (import.meta.env.DEV) {
      (console as any)[consoleMethod](formattedMessage, ...args);
      
      if (this.config.includeStackTrace && (level === 'error' || level === 'fatal')) {
        (console as any)[consoleMethod]('Stack trace:', this.getStackTrace());
      }
    }

    // РћС‚РїСЂР°РІР»СЏРµРј РЅР° СЃРµСЂРІРµСЂ РІ production
    if (level === 'error' || level === 'fatal') {
      this.sendToServer(level, message, context, args[0]);
    }
  }

  /**
   * Debug СѓСЂРѕРІРµРЅСЊ - РїРѕРґСЂРѕР±РЅР°СЏ РёРЅС„РѕСЂРјР°С†РёСЏ РґР»СЏ РѕС‚Р»Р°РґРєРё
   */
  debug(message: string, context?: LogContext, ...args: any[]): void {
    this.log('debug', message, context, ...args);
  }

  /**
   * Info СѓСЂРѕРІРµРЅСЊ - РѕР±С‰Р°СЏ РёРЅС„РѕСЂРјР°С†РёСЏ Рѕ СЂР°Р±РѕС‚Рµ РїСЂРёР»РѕР¶РµРЅРёСЏ
   */
  info(message: string, context?: LogContext, ...args: any[]): void {
    this.log('info', message, context, ...args);
  }

  /**
   * Warn СѓСЂРѕРІРµРЅСЊ - РїСЂРµРґСѓРїСЂРµР¶РґРµРЅРёСЏ Рѕ РїРѕС‚РµРЅС†РёР°Р»СЊРЅС‹С… РїСЂРѕР±Р»РµРјР°С…
   */
  warn(message: string, context?: LogContext, ...args: any[]): void {
    this.log('warn', message, context, ...args);
  }

  /**
   * Error СѓСЂРѕРІРµРЅСЊ - РѕС€РёР±РєРё, РєРѕС‚РѕСЂС‹Рµ РЅРµ РїСЂРµСЂС‹РІР°СЋС‚ СЂР°Р±РѕС‚Сѓ
   */
  error(message: string, error?: Error | any, context?: LogContext): void {
    this.log('error', message, context, error);
  }

  /**
   * Fatal СѓСЂРѕРІРµРЅСЊ - РєСЂРёС‚РёС‡РµСЃРєРёРµ РѕС€РёР±РєРё, РїСЂРµСЂС‹РІР°СЋС‰РёРµ СЂР°Р±РѕС‚Сѓ
   */
  fatal(message: string, error?: Error | any, context?: LogContext): void {
    this.log('fatal', message, context, error);
  }

  /**
   * РЎРѕР·РґР°РЅРёРµ РґРѕС‡РµСЂРЅРµРіРѕ logger СЃ РєРѕРЅС‚РµРєСЃС‚РѕРј
   */
  createChild(context: LogContext): Logger {
    return new Logger({
      ...this.config,
      prefix: context.module ? `${this.config.prefix} [${context.module}]` : this.config.prefix
    });
  }

  /**
   * РћР±РЅРѕРІР»РµРЅРёРµ РєРѕРЅС„РёРіСѓСЂР°С†РёРё logger
   */
  updateConfig(config: Partial<LoggerConfig>): void {
    this.config = { ...this.config, ...config };
  }
}

// РЎРѕР·РґР°РµРј РіР»РѕР±Р°Р»СЊРЅС‹Р№ СЌРєР·РµРјРїР»СЏСЂ logger
export const logger = new Logger();

// Р­РєСЃРїРѕСЂС‚РёСЂСѓРµРј РєР»Р°СЃСЃ РґР»СЏ СЃРѕР·РґР°РЅРёСЏ РєР°СЃС‚РѕРјРЅС‹С… logger'РѕРІ
export { Logger };

// РЈРґРѕР±РЅС‹Рµ Р°Р»РёР°СЃС‹ РґР»СЏ Р±С‹СЃС‚СЂРѕРіРѕ РґРѕСЃС‚СѓРїР°
export const logDebug = logger.debug.bind(logger);
export const logInfo = logger.info.bind(logger);
export const logWarn = logger.warn.bind(logger);
export const logError = logger.error.bind(logger);
export const logFatal = logger.fatal.bind(logger);