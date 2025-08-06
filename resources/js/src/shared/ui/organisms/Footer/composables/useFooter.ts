import { ref, computed, type Ref } from 'vue'
import type { FooterConfig } from '../model/footer.config'
import { defaultFooterConfig } from '../model/footer.config'

// Интерфейс для composable
interface UseFooterReturn {
  config: Ref<FooterConfig>
  isAccessibilityEnabled: Ref<boolean>
  updateConfig: (newConfig: Partial<FooterConfig>) => void
  toggleAccessibility: () => void
  handleAccessibilityToggle: () => void
}

// Глобальное состояние для Footer (singleton)
const globalFooterConfig = ref<FooterConfig>(defaultFooterConfig)
const globalAccessibilityEnabled = ref<boolean>(false)

/**
 * Composable для управления Footer компонентом
 * Предоставляет централизованную конфигурацию и методы управления
 */
export function useFooter(initialConfig?: Partial<FooterConfig>): UseFooterReturn {
  // Если передана начальная конфигурация, мерджим с дефолтной
  if (initialConfig) {
    globalFooterConfig.value = {
      ...defaultFooterConfig,
      ...initialConfig,
      // Мерджим вложенные объекты
      companyInfo: {
        ...defaultFooterConfig.companyInfo,
        ...initialConfig.companyInfo
      },
      accessibility: {
        ...defaultFooterConfig.accessibility,
        ...initialConfig.accessibility
      }
    }
  }

  // Computed свойство для проверки включенной доступности
  const isAccessibilityEnabled = computed(() => {
    return globalFooterConfig.value.accessibility.enabled && globalAccessibilityEnabled.value
  })

  // Метод для обновления конфигурации
  const updateConfig = (newConfig: Partial<FooterConfig>) => {
    globalFooterConfig.value = {
      ...globalFooterConfig.value,
      ...newConfig
    }
  }

  // Метод для переключения режима доступности
  const toggleAccessibility = () => {
    globalAccessibilityEnabled.value = !globalAccessibilityEnabled.value
    
    // Применяем изменения к DOM
    if (globalAccessibilityEnabled.value) {
      enableAccessibilityMode()
    } else {
      disableAccessibilityMode()
    }
    
    // Сохраняем в localStorage
    try {
      localStorage.setItem('accessibility-mode', globalAccessibilityEnabled.value.toString())
    } catch (error) {
      console.warn('Не удалось сохранить настройки доступности:', error)
    }
  }

  // Обработчик для кнопки доступности
  const handleAccessibilityToggle = () => {
    toggleAccessibility()
    
    // Вызываем callback если он определен
    if (globalFooterConfig.value.accessibility.callback) {
      globalFooterConfig.value.accessibility.callback()
    }

    // Показываем уведомление пользователю
    showAccessibilityNotification()
  }

  return {
    config: globalFooterConfig,
    isAccessibilityEnabled,
    updateConfig,
    toggleAccessibility,
    handleAccessibilityToggle
  }
}

/**
 * Включает режим для слабовидящих
 */
function enableAccessibilityMode(): void {
  const body = document.body
  
  // Добавляем специальный класс
  body.classList.add('accessibility-mode')
  
  // Увеличиваем размер шрифта
  body.style.setProperty('--base-font-size', '18px')
  body.style.setProperty('--line-height', '1.8')
  
  // Увеличиваем контрастность
  body.style.setProperty('--text-contrast', 'high')
  
  // Убираем анимации для чувствительных к движению пользователей
  const style = document.createElement('style')
  style.textContent = `
    .accessibility-mode *,
    .accessibility-mode *::before,
    .accessibility-mode *::after {
      animation-duration: 0.01ms !important;
      animation-iteration-count: 1 !important;
      transition-duration: 0.01ms !important;
    }
  `
  document.head.appendChild(style)
}

/**
 * Отключает режим для слабовидящих
 */
function disableAccessibilityMode(): void {
  const body = document.body
  
  // Удаляем класс
  body.classList.remove('accessibility-mode')
  
  // Сбрасываем CSS переменные
  body.style.removeProperty('--base-font-size')
  body.style.removeProperty('--line-height')
  body.style.removeProperty('--text-contrast')
  
  // Удаляем стили анимаций
  const styles = document.head.querySelectorAll('style')
  styles.forEach(style => {
    if (style.textContent?.includes('accessibility-mode')) {
      style.remove()
    }
  })
}

/**
 * Показывает уведомление о переключении режима доступности
 */
function showAccessibilityNotification(): void {
  const message = globalAccessibilityEnabled.value
    ? 'Режим для слабовидящих включен'
    : 'Режим для слабовидящих отключен'
  
  // Создаем уведомление
  const notification = document.createElement('div')
  notification.className = 'accessibility-notification'
  notification.setAttribute('role', 'alert')
  notification.setAttribute('aria-live', 'assertive')
  notification.textContent = message
  
  // Стили для уведомления
  Object.assign(notification.style, {
    position: 'fixed',
    top: '20px',
    right: '20px',
    background: '#1e40af',
    color: 'white',
    padding: '12px 20px',
    borderRadius: '8px',
    zIndex: '9999',
    fontSize: '14px',
    fontWeight: '500',
    boxShadow: '0 4px 12px rgba(0, 0, 0, 0.15)',
    transform: 'translateY(-20px)',
    opacity: '0',
    transition: 'all 0.3s ease'
  })
  
  document.body.appendChild(notification)
  
  // Показываем уведомление
  requestAnimationFrame(() => {
    notification.style.transform = 'translateY(0)'
    notification.style.opacity = '1'
  })
  
  // Убираем уведомление через 3 секунды
  setTimeout(() => {
    notification.style.transform = 'translateY(-20px)'
    notification.style.opacity = '0'
    setTimeout(() => {
      if (notification.parentNode) {
        notification.parentNode.removeChild(notification)
      }
    }, 300)
  }, 3000)
}

// Инициализируем настройки доступности при загрузке
if (typeof window !== 'undefined') {
  try {
    const saved = localStorage.getItem('accessibility-mode')
    if (saved === 'true') {
      globalAccessibilityEnabled.value = true
      // Применяем настройки после загрузки DOM
      document.addEventListener('DOMContentLoaded', enableAccessibilityMode)
    }
  } catch (error) {
    console.warn('Не удалось загрузить настройки доступности:', error)
  }
}