/**
 * Композабл для оптимизированных обновлений
 * Timing-и основаны на исследовании маркетплейсов (Ozon, Avito, Wildberries)
 */

export function useOptimizedUpdates() {
  
  // === СОБСТВЕННЫЕ РЕАЛИЗАЦИИ (БЕЗ ВНЕШНИХ ЗАВИСИМОСТЕЙ) ===
  
  /**
   * Простая и эффективная реализация debounce
   */
  function debounce(func, wait, options = {}) {
    let timeout
    let result
    const { leading = false, trailing = true } = options
    
    const debounced = function(...args) {
      const later = () => {
        timeout = null
        if (trailing) result = func.apply(this, args)
      }
      
      const callNow = leading && !timeout
      clearTimeout(timeout)
      timeout = setTimeout(later, wait)
      if (callNow) result = func.apply(this, args)
      
      return result
    }
    
    debounced.cancel = function() {
      clearTimeout(timeout)
      timeout = null
    }
    
    debounced.flush = function() {
      if (timeout) {
        clearTimeout(timeout)
        timeout = null
        return func.apply(this, arguments)
      }
    }
    
    return debounced
  }
  
  /**
   * Простая и эффективная реализация throttle
   */
  function throttle(func, wait, options = {}) {
    let timeout
    let previous = 0
    const { leading = true, trailing = true } = options
    
    const throttled = function(...args) {
      const now = Date.now()
      
      if (!previous && !leading) previous = now
      const remaining = wait - (now - previous)
      
      if (remaining <= 0 || remaining > wait) {
        if (timeout) {
          clearTimeout(timeout)
          timeout = null
        }
        previous = now
        func.apply(this, args)
      } else if (!timeout && trailing) {
        timeout = setTimeout(() => {
          previous = leading ? Date.now() : 0
          timeout = null
          func.apply(this, args)
        }, remaining)
      }
    }
    
    throttled.cancel = function() {
      clearTimeout(timeout)
      timeout = null
      previous = 0
    }
    
    return throttled
  }
  // === ОПТИМАЛЬНЫЕ ЗАДЕРЖКИ ===
  
  // Основаны на исследовании UX маркетплейсов
  const DEBOUNCE_DELAY = 300  // Для обычных обновлений (ввод текста)
  const API_DELAY = 500       // Для API вызовов (сохранение данных) 
  const SCROLL_DELAY = 16     // 60fps для скролла и анимаций
  const SEARCH_DELAY = 400    // Для поиска (чуть больше чем обычный ввод)
  const RESIZE_DELAY = 100    // Для ресайза окна
  
  // === ФАБРИКИ ФУНКЦИЙ ===
  
  /**
   * Создание debounced функции с настраиваемой задержкой
   * @param {Function} fn - Функция для debounce
   * @param {number} delay - Задержка в миллисекундах
   * @param {Object} options - Дополнительные опции
   * @returns {Function} Debounced функция
   */
  const createDebouncedUpdate = (fn, delay = DEBOUNCE_DELAY, options = {}) => {
    const defaultOptions = {
      leading: false,   // Не вызывать сразу
      trailing: true    // Вызывать в конце
    }
    
    return debounce(fn, delay, { ...defaultOptions, ...options })
  }
  
  /**
   * Создание throttled функции с настраиваемой задержкой
   * @param {Function} fn - Функция для throttle
   * @param {number} delay - Задержка в миллисекундах
   * @param {Object} options - Дополнительные опции
   * @returns {Function} Throttled функция
   */
  const createThrottledUpdate = (fn, delay = SCROLL_DELAY, options = {}) => {
    const defaultOptions = {
      leading: true,    // Вызывать сразу
      trailing: true    // И в конце тоже
    }
    
    return throttle(fn, delay, { ...defaultOptions, ...options })
  }
  
  // === СПЕЦИАЛИЗИРОВАННЫЕ ВЕРСИИ ===
  
  /**
   * Для API вызовов - длинная задержка для экономии запросов
   */
  const debouncedApiCall = (fn) => createDebouncedUpdate(fn, API_DELAY)
  
  /**
   * Для скролла и анимаций - высокая частота обновлений
   */
  const throttledScroll = (fn) => createThrottledUpdate(fn, SCROLL_DELAY)
  
  /**
   * Для поиска - умеренная задержка для баланса UX/производительность
   */
  const debouncedSearch = (fn) => createDebouncedUpdate(fn, SEARCH_DELAY)
  
  /**
   * Для ввода текста - стандартная задержка
   */
  const debouncedInput = (fn) => createDebouncedUpdate(fn, DEBOUNCE_DELAY)
  
  /**
   * Для ресайза окна - быстрая реакция
   */
  const throttledResize = (fn) => createThrottledUpdate(fn, RESIZE_DELAY)
  
  /**
   * Для валидации формы - мгновенно при начале ввода, задержка при окончании
   */
  const debouncedValidation = (fn) => createDebouncedUpdate(fn, DEBOUNCE_DELAY, {
    leading: true,  // Показать ошибку сразу
    trailing: true  // И проверить в конце
  })
  
  // === УТИЛИТЫ ===
  
  /**
   * Отмена всех pending вызовов debounced/throttled функции
   * @param {Function} optimizedFn - Функция с методом cancel
   */
  const cancelOptimizedFunction = (optimizedFn) => {
    if (optimizedFn && typeof optimizedFn.cancel === 'function') {
      optimizedFn.cancel()
    }
  }
  
  /**
   * Принудительный вызов pending функции
   * @param {Function} optimizedFn - Функция с методом flush
   */
  const flushOptimizedFunction = (optimizedFn) => {
    if (optimizedFn && typeof optimizedFn.flush === 'function') {
      optimizedFn.flush()
    }
  }
  
  /**
   * Создание функции с автоматической отменой при размонтировании компонента
   * @param {Function} fn - Исходная функция
   * @param {number} delay - Задержка
   * @param {Object} lifecycle - Объект с onUnmounted для Vue
   * @returns {Function} Optimized функция с автоотменой
   */
  const createAutoCleanupFunction = (fn, delay, lifecycle = null) => {
    const optimizedFn = createDebouncedUpdate(fn, delay)
    
    // Автоматическая очистка при размонтировании (для Vue)
    if (lifecycle && lifecycle.onUnmounted) {
      lifecycle.onUnmounted(() => {
        cancelOptimizedFunction(optimizedFn)
      })
    }
    
    return optimizedFn
  }
  
  // === ПАТТЕРНЫ МАРКЕТПЛЕЙСОВ ===
  
  /**
   * Паттерн "Мгновенный UI + Отложенный API" (как у Ozon)
   * UI обновляется мгновенно, API вызов с задержкой
   */
  const createOptimisticUpdate = (uiUpdateFn, apiCallFn) => {
    const debouncedApi = debouncedApiCall(apiCallFn)
    
    return (...args) => {
      // Мгновенное обновление UI
      uiUpdateFn(...args)
      // Отложенный API вызов
      debouncedApi(...args)
    }
  }
  
  /**
   * Паттерн "Группировка обновлений" (как у Wildberries)
   * Несколько изменений объединяются в один API вызов
   */
  const createBatchedUpdate = (batchProcessor, delay = API_DELAY) => {
    const pendingUpdates = new Set()
    
    const processBatch = createDebouncedUpdate(() => {
      if (pendingUpdates.size > 0) {
        const updates = Array.from(pendingUpdates)
        pendingUpdates.clear()
        batchProcessor(updates)
      }
    }, delay)
    
    return (updateData) => {
      pendingUpdates.add(updateData)
      processBatch()
    }
  }
  
  /**
   * Паттерн "Умная частота" (адаптивная задержка)
   * Частота обновлений зависит от активности пользователя
   */
  const createAdaptiveUpdate = (fn, baseDelay = DEBOUNCE_DELAY) => {
    let activityLevel = 0
    let lastActivity = Date.now()
    
    const updateActivityLevel = () => {
      const now = Date.now()
      const timeSinceLastActivity = now - lastActivity
      
      if (timeSinceLastActivity < 1000) {
        activityLevel = Math.min(activityLevel + 1, 5) // Увеличиваем активность
      } else {
        activityLevel = Math.max(activityLevel - 1, 0) // Снижаем активность
      }
      
      lastActivity = now
    }
    
    return (...args) => {
      updateActivityLevel()
      
      // Чем выше активность, тем больше задержка
      const adaptiveDelay = baseDelay + (activityLevel * 100)
      const adaptiveFn = createDebouncedUpdate(fn, adaptiveDelay)
      
      adaptiveFn(...args)
    }
  }
  
  return {
    // Константы
    DEBOUNCE_DELAY,
    API_DELAY,
    SCROLL_DELAY,
    SEARCH_DELAY,
    RESIZE_DELAY,
    
    // Базовые фабрики
    createDebouncedUpdate,
    createThrottledUpdate,
    
    // Специализированные функции
    debouncedApiCall,
    throttledScroll,
    debouncedSearch,
    debouncedInput,
    throttledResize,
    debouncedValidation,
    
    // Утилиты
    cancelOptimizedFunction,
    flushOptimizedFunction,
    createAutoCleanupFunction,
    
    // Продвинутые паттерны
    createOptimisticUpdate,
    createBatchedUpdate,
    createAdaptiveUpdate
  }
}