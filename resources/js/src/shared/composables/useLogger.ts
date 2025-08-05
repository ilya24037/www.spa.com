/**
 * Composable для использования logger во Vue компонентах
 * Автоматически добавляет контекст компонента
 */

import { getCurrentInstance } from 'vue'
import { logger, type LogContext } from '../lib/logger'

export function useLogger(moduleContext?: string) {
  const instance = getCurrentInstance()
  
  // Получаем имя компонента для контекста
  const componentName = instance?.type.__name || instance?.type.name || 'Unknown'
  const module = moduleContext || componentName

  // Создаем logger с контекстом компонента
  const contextLogger = logger.createChild({ module })

  return {
    debug: (message: string, context?: LogContext, ...args: any[]) => {
      contextLogger.debug(message, { ...context, module }, ...args)
    },
    
    info: (message: string, context?: LogContext, ...args: any[]) => {
      contextLogger.info(message, { ...context, module }, ...args)
    },
    
    warn: (message: string, context?: LogContext, ...args: any[]) => {
      contextLogger.warn(message, { ...context, module }, ...args)
    },
    
    error: (message: string, error?: Error | any, context?: LogContext) => {
      contextLogger.error(message, error, { ...context, module })
    },
    
    fatal: (message: string, error?: Error | any, context?: LogContext) => {
      contextLogger.fatal(message, error, { ...context, module })
    }
  }
}

/**
 * Пример использования:
 * 
 * <script setup>
 * import { useLogger } from '@/shared/composables/useLogger'
 * 
 * const logger = useLogger('BookingModal')
 * 
 * const handleSubmit = async () => {
 *   try {
 *     logger.info('Начало отправки формы бронирования')
 *     const result = await submitBooking()
 *     logger.info('Бронирование успешно создано', { bookingId: result.id })
 *   } catch (error) {
 *     logger.error('Ошибка при создании бронирования', error, { 
 *       userId: currentUser.value?.id 
 *     })
 *   }
 * }
 * </script>
 */