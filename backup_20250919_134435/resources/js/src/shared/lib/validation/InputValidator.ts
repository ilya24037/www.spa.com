/**
 * InputValidator - система строгой валидации входных параметров
 * Размер: ~200 строк
 * 
 * Предназначение:
 * - Защита от XSS, SQL injection и других атак
 * - Валидация типов данных на runtime
 * - Санитизация входных данных
 * - Предотвращение DoS атак через большие данные
 * 
 * Принципы:
 * - Security First: Безопасность превыше всего
 * - KISS: Простые и надежные валидаторы
 * - Performance: Быстрая валидация без блокировок
 * - Defensive Programming: Ожидаем худшее
 */

import { logger } from '@/src/shared/lib/logger'

/**
 * Результат валидации
 */
export interface ValidationResult<T = any> {
  /** Валидация прошла успешно */
  isValid: boolean
  /** Валидированное и санитизированное значение */
  value?: T
  /** Ошибки валидации */
  errors: string[]
  /** Предупреждения */
  warnings: string[]
  /** Санитизированные значения (что было изменено) */
  sanitized: string[]
}

/**
 * Правила валидации строк
 */
export interface StringValidationRules {
  /** Минимальная длина */
  minLength?: number
  /** Максимальная длина */
  maxLength?: number
  /** Регулярное выражение для проверки */
  pattern?: RegExp
  /** Разрешенные символы (whitelist) */
  allowedChars?: string
  /** Запрещенные символы (blacklist) */
  forbiddenChars?: string
  /** Разрешить HTML теги */
  allowHtml?: boolean
  /** Обрезать пробелы */
  trim?: boolean
  /** Привести к нижнему регистру */
  toLowerCase?: boolean
}

/**
 * Правила валидации чисел
 */
export interface NumberValidationRules {
  /** Минимальное значение */
  min?: number
  /** Максимальное значение */
  max?: number
  /** Разрешить дробные числа */
  allowFloat?: boolean
  /** Разрешить отрицательные числа */
  allowNegative?: boolean
  /** Разрешить ноль */
  allowZero?: boolean
}

/**
 * Правила валидации объектов
 */
export interface ObjectValidationRules {
  /** Максимальная глубина вложенности */
  maxDepth?: number
  /** Максимальное количество ключей */
  maxKeys?: number
  /** Разрешенные ключи */
  allowedKeys?: string[]
  /** Запрещенные ключи */
  forbiddenKeys?: string[]
}

/**
 * InputValidator - центральный класс валидации входных данных
 */
export class InputValidator {
  /** Счетчик валидаций для статистики */
  private static validationCount = 0
  
  /** Максимальное время выполнения валидации (мс) */
  private static readonly MAX_VALIDATION_TIME = 100

  /**
   * Валидация строки с санитизацией
   * 
   * @param value - значение для валидации
   * @param rules - правила валидации
   * @param fieldName - имя поля для ошибок
   * @returns результат валидации
   */
  static validateString(
    value: unknown,
    rules: StringValidationRules = {},
    fieldName: string = 'field'
  ): ValidationResult<string> {
    const startTime = Date.now()
    const result: ValidationResult<string> = {
      isValid: false,
      errors: [],
      warnings: [],
      sanitized: []
    }

    try {
      this.validationCount++

      // Проверка типа
      if (typeof value !== 'string') {
        if (value == null) {
          result.errors.push(`${fieldName}: значение не может быть null или undefined`)
          return result
        }
        
        // Приведение к строке с предупреждением
        value = String(value)
        result.warnings.push(`${fieldName}: значение приведено к строке`)
        result.sanitized.push('type-cast-to-string')
      }

      let stringValue = value as string

      // Базовая защита от DoS атак
      if (stringValue.length > 10000) {
        result.errors.push(`${fieldName}: превышена максимальная длина (10000 символов)`)
        return result
      }

      // Обрезаем пробелы если требуется
      if (rules.trim !== false) { // по умолчанию true
        const originalLength = stringValue.length
        stringValue = stringValue.trim()
        if (originalLength !== stringValue.length) {
          result.sanitized.push('trimmed-whitespace')
        }
      }

      // Приводим к нижнему регистру если требуется
      if (rules.toLowerCase) {
        if (stringValue !== stringValue.toLowerCase()) {
          stringValue = stringValue.toLowerCase()
          result.sanitized.push('converted-to-lowercase')
        }
      }

      // Проверка минимальной длины
      if (rules.minLength && stringValue.length < rules.minLength) {
        result.errors.push(`${fieldName}: минимальная длина ${rules.minLength} символов`)
      }

      // Проверка максимальной длины
      if (rules.maxLength && stringValue.length > rules.maxLength) {
        result.errors.push(`${fieldName}: максимальная длина ${rules.maxLength} символов`)
      }

      // Проверка паттерна
      if (rules.pattern && !rules.pattern.test(stringValue)) {
        result.errors.push(`${fieldName}: не соответствует требуемому формату`)
      }

      // КРИТИЧЕСКАЯ ЗАЩИТА: Проверка на XSS
      if (!rules.allowHtml && this.containsHtml(stringValue)) {
        result.errors.push(`${fieldName}: HTML теги запрещены`)
      }

      // Проверка разрешенных символов
      if (rules.allowedChars) {
        const allowedPattern = new RegExp(`^[${this.escapeRegex(rules.allowedChars)}]*$`)
        if (!allowedPattern.test(stringValue)) {
          result.errors.push(`${fieldName}: содержит недопустимые символы`)
        }
      }

      // Проверка запрещенных символов
      if (rules.forbiddenChars) {
        const forbiddenPattern = new RegExp(`[${this.escapeRegex(rules.forbiddenChars)}]`)
        if (forbiddenPattern.test(stringValue)) {
          result.errors.push(`${fieldName}: содержит запрещенные символы`)
        }
      }

      // КРИТИЧЕСКАЯ ЗАЩИТА: SQL injection patterns
      if (this.containsSqlInjection(stringValue)) {
        result.errors.push(`${fieldName}: обнаружены подозрительные SQL конструкции`)
      }

      result.isValid = result.errors.length === 0
      result.value = stringValue

    } catch (error) {
      result.errors.push(`${fieldName}: ошибка валидации - ${error.message}`)
      logger.error('[InputValidator] Ошибка валидации строки:', error)
    }

    // Проверка времени выполнения
    const executionTime = Date.now() - startTime
    if (executionTime > this.MAX_VALIDATION_TIME) {
      result.warnings.push(`${fieldName}: медленная валидация (${executionTime}мс)`)
      logger.warn(`[InputValidator] Медленная валидация: ${executionTime}мс для поля ${fieldName}`)
    }

    return result
  }

  /**
   * Валидация числа
   * 
   * @param value - значение для валидации
   * @param rules - правила валидации
   * @param fieldName - имя поля для ошибок
   * @returns результат валидации
   */
  static validateNumber(
    value: unknown,
    rules: NumberValidationRules = {},
    fieldName: string = 'field'
  ): ValidationResult<number> {
    const result: ValidationResult<number> = {
      isValid: false,
      errors: [],
      warnings: [],
      sanitized: []
    }

    try {
      this.validationCount++

      // Проверка типа
      let numValue: number
      if (typeof value === 'number') {
        numValue = value
      } else if (typeof value === 'string') {
        // Попытка приведения строки к числу
        numValue = Number(value)
        if (isNaN(numValue)) {
          result.errors.push(`${fieldName}: не является числом`)
          return result
        }
        result.warnings.push(`${fieldName}: строка приведена к числу`)
        result.sanitized.push('string-to-number')
      } else {
        result.errors.push(`${fieldName}: должно быть числом`)
        return result
      }

      // Проверка на NaN и Infinity
      if (!isFinite(numValue)) {
        result.errors.push(`${fieldName}: должно быть конечным числом`)
        return result
      }

      // Проверка дробных чисел
      if (!rules.allowFloat && !Number.isInteger(numValue)) {
        result.errors.push(`${fieldName}: дробные числа не разрешены`)
      }

      // Проверка отрицательных чисел
      if (!rules.allowNegative && numValue < 0) {
        result.errors.push(`${fieldName}: отрицательные числа не разрешены`)
      }

      // Проверка нуля
      if (!rules.allowZero && numValue === 0) {
        result.errors.push(`${fieldName}: ноль не разрешен`)
      }

      // Проверка минимального значения
      if (rules.min !== undefined && numValue < rules.min) {
        result.errors.push(`${fieldName}: минимальное значение ${rules.min}`)
      }

      // Проверка максимального значения
      if (rules.max !== undefined && numValue > rules.max) {
        result.errors.push(`${fieldName}: максимальное значение ${rules.max}`)
      }

      result.isValid = result.errors.length === 0
      result.value = numValue

    } catch (error) {
      result.errors.push(`${fieldName}: ошибка валидации числа - ${error.message}`)
      logger.error('[InputValidator] Ошибка валидации числа:', error)
    }

    return result
  }

  /**
   * Валидация объекта
   * 
   * @param value - значение для валидации
   * @param rules - правила валидации
   * @param fieldName - имя поля для ошибок
   * @returns результат валидации
   */
  static validateObject(
    value: unknown,
    rules: ObjectValidationRules = {},
    fieldName: string = 'field'
  ): ValidationResult<Record<string, any>> {
    const result: ValidationResult<Record<string, any>> = {
      isValid: false,
      errors: [],
      warnings: [],
      sanitized: []
    }

    try {
      this.validationCount++

      // Проверка типа
      if (typeof value !== 'object' || value === null || Array.isArray(value)) {
        result.errors.push(`${fieldName}: должно быть объектом`)
        return result
      }

      const objValue = value as Record<string, any>

      // Проверка глубины вложенности
      if (rules.maxDepth) {
        const depth = this.getObjectDepth(objValue)
        if (depth > rules.maxDepth) {
          result.errors.push(`${fieldName}: превышена максимальная глубина вложенности (${rules.maxDepth})`)
        }
      }

      // Проверка количества ключей
      const keys = Object.keys(objValue)
      if (rules.maxKeys && keys.length > rules.maxKeys) {
        result.errors.push(`${fieldName}: превышено максимальное количество ключей (${rules.maxKeys})`)
      }

      // Проверка разрешенных ключей
      if (rules.allowedKeys) {
        const invalidKeys = keys.filter(key => !rules.allowedKeys!.includes(key))
        if (invalidKeys.length > 0) {
          result.errors.push(`${fieldName}: недопустимые ключи: ${invalidKeys.join(', ')}`)
        }
      }

      // Проверка запрещенных ключей
      if (rules.forbiddenKeys) {
        const forbiddenFound = keys.filter(key => rules.forbiddenKeys!.includes(key))
        if (forbiddenFound.length > 0) {
          result.errors.push(`${fieldName}: запрещенные ключи: ${forbiddenFound.join(', ')}`)
        }
      }

      result.isValid = result.errors.length === 0
      result.value = objValue

    } catch (error) {
      result.errors.push(`${fieldName}: ошибка валидации объекта - ${error.message}`)
      logger.error('[InputValidator] Ошибка валидации объекта:', error)
    }

    return result
  }

  /**
   * Получение статистики валидации
   */
  static getStats() {
    return {
      totalValidations: this.validationCount,
      maxValidationTime: this.MAX_VALIDATION_TIME
    }
  }

  /**
   * КРИТИЧЕСКАЯ ЗАЩИТА: Проверка на HTML теги
   */
  private static containsHtml(str: string): boolean {
    const htmlPattern = /<[^>]*>/g
    return htmlPattern.test(str)
  }

  /**
   * КРИТИЧЕСКАЯ ЗАЩИТА: Проверка на SQL injection
   */
  private static containsSqlInjection(str: string): boolean {
    const sqlPatterns = [
      /(\b(SELECT|INSERT|UPDATE|DELETE|DROP|CREATE|ALTER|EXEC|UNION)\b)/i,
      /(\b(OR|AND)\s+\d+\s*=\s*\d+)/i,
      /(;|\|\||&&)/,
      /('|(\\')|('')|(%27)|(%22))/,
      /(\/\*|\*\/|--)/
    ]

    return sqlPatterns.some(pattern => pattern.test(str))
  }

  /**
   * Экранирование специальных символов для regex
   */
  private static escapeRegex(str: string): string {
    return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
  }

  /**
   * Получение глубины вложенности объекта
   */
  private static getObjectDepth(obj: any, depth = 1): number {
    if (typeof obj !== 'object' || obj === null) {
      return depth
    }

    const depths = Object.values(obj).map(value => 
      this.getObjectDepth(value, depth + 1)
    )

    return Math.max(depth, ...depths)
  }
}