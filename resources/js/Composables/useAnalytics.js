import { ref } from 'vue'

const analyticsEnabled = ref(true)
const debug = ref(import.meta.env.DEV)

export function useAnalytics() {
  /**
   * Отслеживает событие
   * @param {string} eventName - название события
   * @param {Object} properties - дополнительные данные
   */
  function track(eventName, properties = {}) {
    if (!analyticsEnabled.value) return

    const eventData = {
      event: eventName,
      timestamp: Date.now(),
      url: window.location.href,
      user_agent: navigator.userAgent,
      ...properties
    }

    // В debug режиме выводим в консоль
    if (debug.value) {
      console.log('📊 Analytics Event:', eventName, eventData)
    }

    // Отправляем в Google Analytics (если подключен)
    if (window.gtag) {
      window.gtag('event', eventName, {
        custom_parameter: JSON.stringify(properties),
        event_category: 'user_interaction',
        event_label: eventName
      })
    }

    // Отправляем в Яндекс.Метрику (если подключена)
    if (window.ym) {
      window.ym(window.yaCounterId, 'reachGoal', eventName, properties)
    }

    // Отправляем на свой сервер
    sendToServer(eventData)
  }

  /**
   * Отслеживает конверсионное событие
   * @param {string} eventName - название события
   * @param {Object} properties - дополнительные данные
   */
  function trackConversion(eventName, properties = {}) {
    track(eventName, {
      ...properties,
      conversion: true,
      value: properties.value || 0
    })
  }

  /**
   * Отслеживает ошибку
   * @param {string|Error} error - ошибка
   * @param {Object} context - контекст ошибки
   */
  function trackError(error, context = {}) {
    const errorData = {
      error_message: error.message || error,
      error_stack: error.stack,
      ...context
    }

    track('error_occurred', errorData)
  }

  /**
   * Отслеживает время на странице
   * @param {string} pageName - название страницы
   * @param {number} timeSpent - время в секундах
   */
  function trackPageTime(pageName, timeSpent) {
    track('page_time', {
      page_name: pageName,
      time_spent_seconds: timeSpent
    })
  }

  /**
   * Отправляет данные на сервер
   * @param {Object} data - данные для отправки
   */
  async function sendToServer(data) {
    try {
      await fetch('/api/analytics', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(data)
      })
    } catch (error) {
      if (debug.value) {
        console.warn('Ошибка отправки аналитики:', error)
      }
    }
  }

  /**
   * Включает/выключает аналитику
   * @param {boolean} enabled - состояние
   */
  function setEnabled(enabled) {
    analyticsEnabled.value = enabled
  }

  return {
    track,
    trackConversion,
    trackError,
    trackPageTime,
    setEnabled,
    analyticsEnabled: analyticsEnabled.value
  }
}