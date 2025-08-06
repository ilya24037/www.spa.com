/**
 * Система ленивой загрузки компонентов по образцу Wildberries
 * Оптимизирует время загрузки страниц
 */

import { defineAsyncComponent } from 'vue'

/**
 * Создает ленивый компонент с обработкой ошибок
 */
export function createLazyComponent(importFn, options = {}) {
  const defaultOptions = {
    // Компонент загрузки
    loadingComponent: {
      template: `
        <div class="flex items-center justify-center p-4">
          <div class="spinner spinner--md"></div>
          <span class="ml-2 text--secondary">Загрузка...</span>
        </div>
      `
    },
    
    // Компонент ошибки
    errorComponent: {
      template: `
        <div class="alert alert--error">
          <p>Ошибка загрузки компонента</p>
          <button class="btn btn--secondary btn--sm mt-2" @click="retry">
            Повторить
          </button>
        </div>
      `,
      methods: {
        retry() {
          window.location.reload()
        }
      }
    },
    
    // Задержка показа загрузчика (мс)
    delay: 200,
    
    // Таймаут загрузки (мс)
    timeout: 10000,
    
    // Retry при ошибке
    retryOnError: true,
    
    ...options
  }

  return defineAsyncComponent({
    loader: importFn,
    ...defaultOptions
  })
}

/**
 * Предзагрузка компонентов для критических путей
 */
export function preloadComponents(componentImports) {
  if (typeof window !== 'undefined' && 'requestIdleCallback' in window) {
    window.requestIdleCallback(() => {
      componentImports.forEach(importFn => {
        importFn().catch(() => {
          // Тихо игнорируем ошибки предзагрузки
        })
      })
    })
  }
}

/**
 * Intersection Observer для ленивой загрузки при скролле
 */
export function createIntersectionLazyComponent(importFn, options = {}) {
  return defineAsyncComponent({
    loader: () => {
      return new Promise((resolve) => {
        // Создаем placeholder элемент
        const placeholder = document.createElement('div')
        placeholder.style.minHeight = options.minHeight || '200px'
        
        // Наблюдаем за видимостью
        const observer = new IntersectionObserver(
          (entries) => {
            entries.forEach(entry => {
              if (entry.isIntersecting) {
                observer.disconnect()
                importFn().then(resolve)
              }
            })
          },
          {
            rootMargin: options.rootMargin || '50px'
          }
        )
        
        observer.observe(placeholder)
      })
    },
    
    loadingComponent: {
      template: `<div class="lazy-placeholder" style="min-height: ${options.minHeight || '200px'}"></div>`
    }
  })
}

/**
 * Ленивые компоненты виджетов
 */
export const LazyWidgets = {
  // Каталог мастеров - критический, загружаем быстро
  MastersCatalog: createLazyComponent(
    () => import('@/src/widgets/masters-catalog'),
    { delay: 0 }
  ),
  
  // Календарь бронирования - загружаем при необходимости
  BookingCalendar: createLazyComponent(
    () => import('@/src/widgets/booking-calendar'),
    { delay: 500 }
  ),
  
  // Дашборд профиля - загружаем при скролле
  ProfileDashboard: createIntersectionLazyComponent(
    () => import('@/src/widgets/profile-dashboard'),
    { minHeight: '400px', rootMargin: '100px' }
  ),
  
  // Карта - тяжелый компонент, загружаем только при клике
  GoogleMap: createLazyComponent(
    () => import('@/src/features/map/ui/GoogleMap'),
    { delay: 1000, timeout: 15000 }
  ),
  
  // Чат - загружаем в фоне
  ChatWidget: createLazyComponent(
    () => import('@/src/widgets/chat'),
    { delay: 2000 }
  )
}

/**
 * Ленивые компоненты страниц
 */
export const LazyPages = {
  // Авторизация - быстрая загрузка
  Login: createLazyComponent(
    () => import('@/Pages/Auth/Login.vue'),
    { delay: 0 }
  ),
  
  // Регистрация - быстрая загрузка
  Register: createLazyComponent(
    () => import('@/Pages/Auth/Register.vue'),
    { delay: 0 }
  ),
  
  // Профиль мастера - важная страница
  MasterProfile: createLazyComponent(
    () => import('@/Pages/Masters/Show.vue'),
    { delay: 100 }
  ),
  
  // Создание объявления - ленивая загрузка
  AddItem: createLazyComponent(
    () => import('@/Pages/AddItem.vue'),
    { delay: 300 }
  ),
  
  // Админ-панель - загружаем только для админов
  AdminDashboard: createLazyComponent(
    () => import('@/Pages/Admin/Dashboard.vue'),
    { delay: 500, timeout: 20000 }
  )
}

/**
 * Ленивые UI компоненты (тяжелые)
 */
export const LazyUI = {
  // Богатый текстовый редактор
  RichEditor: createLazyComponent(
    () => import('@/src/shared/ui/molecules/RichEditor'),
    { delay: 1000 }
  ),
  
  // Загрузчик файлов с превью
  FileUploader: createLazyComponent(
    () => import('@/src/shared/ui/molecules/FileUploader'),
    { delay: 500 }
  ),
  
  // Календарь с выбором даты
  DatePicker: createLazyComponent(
    () => import('@/src/shared/ui/molecules/DatePicker'),
    { delay: 300 }
  ),
  
  // Сложные формы
  DynamicForm: createLazyComponent(
    () => import('@/src/shared/ui/organisms/DynamicForm'),
    { delay: 400 }
  )
}

/**
 * Предзагрузка критических компонентов
 * Запускаем после загрузки основного бандла
 */
export function preloadCriticalComponents() {
  // Предзагружаем компоненты для главной страницы
  preloadComponents([
    () => import('@/src/widgets/masters-catalog'),
    () => import('@/src/shared/ui/molecules/SearchBar'),
    () => import('@/src/shared/ui/organisms/Header')
  ])
}

/**
 * Предзагрузка компонентов на основе маршрута
 */
export function preloadRouteComponents(routeName) {
  const routePreloads = {
    'masters.show': [
      () => import('@/src/widgets/booking-calendar'),
      () => import('@/src/features/reviews'),
      () => import('@/src/features/gallery')
    ],
    
    'profile.dashboard': [
      () => import('@/src/widgets/profile-dashboard'),
      () => import('@/src/features/analytics'),
      () => import('@/src/features/notifications')
    ],
    
    'add-item': [
      () => import('@/src/entities/ad/ui/AdForm'),
      () => import('@/src/features/media-upload'),
      () => import('@/src/features/location-picker')
    ]
  }
  
  const components = routePreloads[routeName]
  if (components) {
    preloadComponents(components)
  }
}

/**
 * Мониторинг производительности ленивой загрузки
 */
export function trackLazyLoadingPerformance(componentName, startTime) {
  if (typeof window !== 'undefined' && window.performance) {
    const endTime = performance.now()
    const loadTime = endTime - startTime
    
    // Отправляем метрики (можно интегрировать с аналитикой)
    console.log(`[LazyLoading] ${componentName} loaded in ${loadTime.toFixed(2)}ms`)
    
    // Если компонент загружается слишком долго
    if (loadTime > 2000) {
      console.warn(`[LazyLoading] Slow component: ${componentName} (${loadTime.toFixed(2)}ms)`)
    }
  }
}