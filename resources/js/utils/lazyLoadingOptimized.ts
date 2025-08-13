/**
 * Оптимизированная система ленивой загрузки
 * 
 * Использует bundleOptimizer для максимальной производительности
 */

import { bundleOptimizer, createOptimizedComponent } from '@/src/shared/utils/bundleOptimizer'
import { logger } from '@/src/shared/utils/logger'

// Критические компоненты для предзагрузки
const CRITICAL_COMPONENTS = [
  {
    name: 'AppHeader',
    loader: () => import('@/src/widgets/header/AppHeader.vue')
  },
  {
    name: 'AppFooter', 
    loader: () => import('@/src/shared/ui/organisms/Footer/Footer.vue')
  },
  {
    name: 'Toast',
    loader: () => import('@/src/shared/ui/molecules/Toast/Toast.vue')
  }
]

// Компоненты по маршрутам для prefetch
const ROUTE_COMPONENTS = {
  'home': [
    { name: 'MastersCatalog', loader: () => import('@/src/widgets/masters-catalog/MastersCatalog.vue') },
    { name: 'MasterCard', loader: () => import('@/src/entities/master/ui/MasterCard/MasterCard.vue') }
  ],
  'masters.show': [
    { name: 'MasterProfile', loader: () => import('@/src/widgets/master-profile/MasterProfile.vue') },
    { name: 'BookingWidget', loader: () => import('@/src/entities/booking/ui/BookingWidget/BookingWidget.vue') }
  ],
  'dashboard': [
    { name: 'ProfileDashboard', loader: () => import('@/src/widgets/profile-dashboard/ProfileDashboard.vue') },
    { name: 'AdForm', loader: () => import('@/src/features/ad-creation/ui/AdForm.vue') }
  ]
}

/**
 * Создает оптимизированный ленивый компонент
 */
export function createLazyComponent(
  importFn: () => Promise<any>,
  componentName: string,
  options: {
    preload?: boolean
    prefetch?: boolean
    critical?: boolean
    timeout?: number
  } = {}
) {
  return createOptimizedComponent(importFn, componentName, {
    timeout: options.timeout || 8000,
    preload: options.preload || options.critical,
    prefetch: options.prefetch
  })
}

/**
 * Предзагрузка критических компонентов
 */
export async function preloadCriticalComponents() {
  logger.info('Starting critical components preload', null, 'LazyLoading')
  
  const startTime = performance.now()
  await bundleOptimizer.preloadCritical(CRITICAL_COMPONENTS)
  const endTime = performance.now()
  
  logger.info(`Critical components preloaded in ${(endTime - startTime).toFixed(2)}ms`, null, 'LazyLoading')
}

/**
 * Prefetch компонентов для маршрута
 */
export function preloadRouteComponents(routeName: string) {
  const components = ROUTE_COMPONENTS[routeName as keyof typeof ROUTE_COMPONENTS]
  
  if (components) {
    logger.debug(`Prefetching components for route: ${routeName}`, { count: components.length }, 'LazyLoading')
    bundleOptimizer.prefetchRoute(routeName, components)
  }
}

// Оптимизированные компоненты для экспорта
export const LazyComponents = {
  // Widgets
  AppHeader: createLazyComponent(
    () => import('@/src/widgets/header/AppHeader.vue'),
    'AppHeader',
    { critical: true }
  ),

  MastersCatalog: createLazyComponent(
    () => import('@/src/widgets/masters-catalog/MastersCatalog.vue'),
    'MastersCatalog',
    { prefetch: true }
  ),

  MasterProfile: createLazyComponent(
    () => import('@/src/widgets/master-profile/MasterProfile.vue'),
    'MasterProfile'
  ),

  ProfileDashboard: createLazyComponent(
    () => import('@/src/widgets/profile-dashboard/ProfileDashboard.vue'),
    'ProfileDashboard'
  ),

  // Entities
  MasterCard: createLazyComponent(
    () => import('@/src/entities/master/ui/MasterCard/MasterCard.vue'),
    'MasterCard',
    { prefetch: true }
  ),

  BookingWidget: createLazyComponent(
    () => import('@/src/entities/booking/ui/BookingWidget/BookingWidget.vue'),
    'BookingWidget'
  ),

  AdForm: createLazyComponent(
    () => import('@/src/features/ad-creation/ui/AdForm.vue'),
    'AdForm'
  ),

  // Features
  SearchWidget: createLazyComponent(
    () => import('@/src/features/search/ui/GlobalSearch/GlobalSearch.vue'),
    'SearchWidget'
  ),

  AuthWidget: createLazyComponent(
    () => import('@/src/features/auth/ui/AuthWidget/AuthWidget.vue'),
    'AuthWidget',
    { critical: true }
  ),

  FavoritesCounter: createLazyComponent(
    () => import('@/src/features/favorites/ui/FavoritesCounter/FavoritesCounter.vue'),
    'FavoritesCounter'
  ),

  // Shared UI
  Modal: createLazyComponent(
    () => import('@/src/shared/ui/molecules/Modal/Modal.vue'),
    'Modal'
  ),

  Toast: createLazyComponent(
    () => import('@/src/shared/ui/molecules/Toast/Toast.vue'),
    'Toast',
    { critical: true }
  ),

  ErrorBoundary: createLazyComponent(
    () => import('@/src/shared/ui/molecules/ErrorBoundary/ErrorBoundary.vue'),
    'ErrorBoundary',
    { critical: true }
  ),

  // Форма компоненты (разбитые на чанки)
  MediaForm: createLazyComponent(
    () => import('@/src/shared/ui/molecules/Forms/features/MediaForm/MediaForm.vue'),
    'MediaForm'
  ),

  PhotoUploadArea: createLazyComponent(
    () => import('@/src/shared/ui/molecules/Forms/features/MediaForm/components/PhotoUploadArea.vue'),
    'PhotoUploadArea'
  ),

  VideoUploadArea: createLazyComponent(
    () => import('@/src/shared/ui/molecules/Forms/features/MediaForm/components/VideoUploadArea.vue'),
    'VideoUploadArea'
  ),

  MediaPreview: createLazyComponent(
    () => import('@/src/shared/ui/molecules/Forms/features/MediaForm/components/MediaPreview.vue'),
    'MediaPreview'
  )
}

/**
 * Получить метрики производительности загрузки
 */
export function getPerformanceMetrics() {
  const metrics = bundleOptimizer.getLoadMetrics()
  const recommendations = bundleOptimizer.getOptimizationRecommendations()
  
  return {
    ...metrics,
    recommendations
  }
}

/**
 * Очистить кеш компонентов
 */
export function clearComponentCache() {
  bundleOptimizer.clearCache()
}

/**
 * Инициализация оптимизированной системы загрузки
 */
export async function initializeOptimizedLoading() {
  try {
    // Предзагружаем критические компоненты
    await preloadCriticalComponents()
    
    // Настраиваем prefetch для текущего маршрута
    const currentRoute = window.location.pathname
    if (currentRoute === '/') {
      preloadRouteComponents('home')
    }
    
    logger.info('Optimized loading system initialized', null, 'LazyLoading')
  } catch (error) {
    logger.error('Failed to initialize optimized loading', error, 'LazyLoading')
  }
}

// Экспорт для обратной совместимости
export { bundleOptimizer }
export default LazyComponents
