/**
 * Типы для WidgetSystem - базовая система виджетов Ozon
 * Микро-фронтенд архитектура, версионирование, изоляция
 */

// Основной интерфейс виджета
export interface Widget {
  id: string
  name: string
  version: string
  type: WidgetType
  component: any
  config: WidgetConfig
  dependencies?: WidgetDependency[]
  metadata: WidgetMetadata
  lifecycle: WidgetLifecycle
  permissions?: WidgetPermissions
}

// Типы виджетов
export type WidgetType = 
  | 'product-grid'
  | 'product-card'
  | 'price-block'
  | 'badge-system'
  | 'rating-block'
  | 'favorite-button'
  | 'infinite-scroll'
  | 'tracking-system'
  | 'image-optimization'
  | 'custom'

// Конфигурация виджета
export interface WidgetConfig {
  props?: Record<string, any>
  slots?: WidgetSlot[]
  events?: WidgetEvent[]
  styling?: WidgetStyling
  responsive?: ResponsiveConfig
  accessibility?: AccessibilityConfig
  performance?: PerformanceConfig
}

// Слоты виджета
export interface WidgetSlot {
  name: string
  required: boolean
  description?: string
  fallback?: any
}

// События виджета
export interface WidgetEvent {
  name: string
  payload?: Record<string, any>
  description?: string
}

// Стилизация
export interface WidgetStyling {
  theme?: string
  cssVars?: Record<string, string>
  classes?: string[]
  isolation?: boolean
  shadowDOM?: boolean
}

// Responsive конфигурация
export interface ResponsiveConfig {
  breakpoints: Record<string, number>
  behavior: ResponsiveBehavior
}

export interface ResponsiveBehavior {
  mobile?: Partial<WidgetConfig>
  tablet?: Partial<WidgetConfig>
  desktop?: Partial<WidgetConfig>
}

// Доступность
export interface AccessibilityConfig {
  role?: string
  ariaLabel?: string
  ariaDescribedBy?: string
  tabIndex?: number
  focusable?: boolean
  screenReader?: boolean
}

// Производительность
export interface PerformanceConfig {
  lazy?: boolean
  preload?: boolean
  priority?: 'high' | 'normal' | 'low'
  timeout?: number
  errorBoundary?: boolean
}

// Зависимости
export interface WidgetDependency {
  id: string
  version: string
  required: boolean
  type: 'widget' | 'library' | 'service'
}

// Метаданные
export interface WidgetMetadata {
  title: string
  description: string
  author: string
  category: WidgetCategory
  tags?: string[]
  screenshot?: string
  documentation?: string
  changelog?: string
  created: Date
  updated: Date
}

export type WidgetCategory = 
  | 'display'
  | 'input'
  | 'navigation'
  | 'layout'
  | 'media'
  | 'data'
  | 'utility'

// Жизненный цикл
export interface WidgetLifecycle {
  onMount?: () => void | Promise<void>
  onUnmount?: () => void | Promise<void>
  onUpdate?: (props: any) => void | Promise<void>
  onError?: (error: Error) => void
  onActivate?: () => void
  onDeactivate?: () => void
}

// Разрешения
export interface WidgetPermissions {
  api?: string[]
  storage?: boolean
  cookies?: boolean
  location?: boolean
  camera?: boolean
  microphone?: boolean
}

// Реестр виджетов
export interface WidgetRegistry {
  widgets: Map<string, Widget>
  versions: Map<string, string[]>
  dependencies: Map<string, WidgetDependency[]>
  cache: Map<string, any>
}

// Контейнер виджетов
export interface WidgetContainer {
  id: string
  widgets: WidgetInstance[]
  layout: LayoutConfig
  isolation: IsolationConfig
  communication: CommunicationConfig
}

// Экземпляр виджета
export interface WidgetInstance {
  id: string
  widgetId: string
  version: string
  config: WidgetConfig
  state: WidgetState
  runtime: WidgetRuntime
}

// Состояние виджета
export interface WidgetState {
  status: WidgetStatus
  error?: Error
  data?: any
  isActive: boolean
  lastUpdate: Date
}

export type WidgetStatus = 
  | 'loading'
  | 'ready'
  | 'error'
  | 'inactive'
  | 'destroyed'

// Runtime виджета
export interface WidgetRuntime {
  element?: HTMLElement
  component?: any
  eventListeners: Map<string, Function[]>
  cleanup?: () => void
}

// Конфигурация layout
export interface LayoutConfig {
  type: LayoutType
  responsive: boolean
  overflow: OverflowBehavior
  spacing: SpacingConfig
}

export type LayoutType = 
  | 'grid'
  | 'flex'
  | 'absolute'
  | 'stack'
  | 'mosaic'

export type OverflowBehavior = 
  | 'visible'
  | 'hidden'
  | 'scroll'
  | 'auto'

export interface SpacingConfig {
  gap: number
  padding: number
  margin: number
}

// Изоляция
export interface IsolationConfig {
  css: boolean
  js: boolean
  events: boolean
  dom: boolean
  storage: boolean
}

// Коммуникация
export interface CommunicationConfig {
  eventBus: boolean
  postMessage: boolean
  sharedState: boolean
  api: boolean
}

// Менеджер виджетов
export interface WidgetManager {
  registry: WidgetRegistry
  containers: Map<string, WidgetContainer>
  eventBus: WidgetEventBus
  loader: WidgetLoader
  validator: WidgetValidator
}

// Event Bus
export interface WidgetEventBus {
  subscribe: (event: string, callback: Function) => void
  unsubscribe: (event: string, callback: Function) => void
  emit: (event: string, data: any) => void
  once: (event: string, callback: Function) => void
}

// Загрузчик виджетов
export interface WidgetLoader {
  load: (id: string, version?: string) => Promise<Widget>
  loadRemote: (url: string) => Promise<Widget>
  preload: (ids: string[]) => Promise<Widget[]>
  cache: (widget: Widget) => void
  invalidate: (id: string) => void
}

// Валидатор
export interface WidgetValidator {
  validate: (widget: Widget) => ValidationResult
  validateConfig: (config: WidgetConfig) => ValidationResult
  validateDependencies: (deps: WidgetDependency[]) => ValidationResult
}

export interface ValidationResult {
  valid: boolean
  errors: ValidationError[]
  warnings: ValidationWarning[]
}

export interface ValidationError {
  field: string
  message: string
  code: string
}

export interface ValidationWarning {
  field: string
  message: string
  suggestion?: string
}

// Конфигурация системы
export interface WidgetSystemConfig {
  enableHotReload?: boolean
  enableDebug?: boolean
  enableAnalytics?: boolean
  defaultIsolation?: IsolationConfig
  defaultLayout?: LayoutConfig
  cacheStrategy?: CacheStrategy
  loadTimeout?: number
  errorRecovery?: ErrorRecoveryConfig
}

export type CacheStrategy = 
  | 'memory'
  | 'localStorage'
  | 'sessionStorage'
  | 'indexedDB'
  | 'none'

export interface ErrorRecoveryConfig {
  retryAttempts: number
  retryDelay: number
  fallbackWidget?: string
  reportErrors: boolean
}

// Аналитика виджетов
export interface WidgetAnalytics {
  usage: Map<string, UsageStats>
  performance: Map<string, PerformanceStats>
  errors: Map<string, ErrorStats>
}

export interface UsageStats {
  loads: number
  renders: number
  interactions: number
  timeSpent: number
  lastUsed: Date
}

export interface PerformanceStats {
  loadTime: number
  renderTime: number
  memoryUsage: number
  bundleSize: number
  cacheHits: number
}

export interface ErrorStats {
  count: number
  types: Record<string, number>
  lastError: Error
  resolution: string
}

// Маркетплейс виджетов
export interface WidgetMarketplace {
  search: (query: string) => Promise<Widget[]>
  install: (id: string, version?: string) => Promise<boolean>
  uninstall: (id: string) => Promise<boolean>
  update: (id: string) => Promise<boolean>
  getInstalled: () => Widget[]
  getAvailable: () => Promise<Widget[]>
}

// Билдер виджетов
export interface WidgetBuilder {
  create: (config: WidgetBuilderConfig) => Widget
  compile: (source: string) => Promise<Widget>
  bundle: (widgets: Widget[]) => Promise<Bundle>
  optimize: (widget: Widget) => Promise<Widget>
}

export interface WidgetBuilderConfig {
  name: string
  type: WidgetType
  template: string
  script: string
  style: string
  config: WidgetConfig
  metadata: Partial<WidgetMetadata>
}

export interface Bundle {
  id: string
  widgets: Widget[]
  size: number
  dependencies: string[]
  manifest: BundleManifest
}

export interface BundleManifest {
  version: string
  entry: string
  assets: string[]
  externals: string[]
  metadata: Record<string, any>
}

// Константы
export const WIDGET_SYSTEM_EVENTS = {
  WIDGET_LOADED: 'widget:loaded',
  WIDGET_MOUNTED: 'widget:mounted',
  WIDGET_UNMOUNTED: 'widget:unmounted',
  WIDGET_ERROR: 'widget:error',
  WIDGET_UPDATED: 'widget:updated',
  CONTAINER_CREATED: 'container:created',
  CONTAINER_DESTROYED: 'container:destroyed'
} as const

export const DEFAULT_WIDGET_CONFIG: WidgetSystemConfig = {
  enableHotReload: false,
  enableDebug: false,
  enableAnalytics: true,
  defaultIsolation: {
    css: true,
    js: false,
    events: false,
    dom: false,
    storage: false
  },
  defaultLayout: {
    type: 'flex',
    responsive: true,
    overflow: 'auto',
    spacing: {
      gap: 16,
      padding: 16,
      margin: 0
    }
  },
  cacheStrategy: 'memory',
  loadTimeout: 10000,
  errorRecovery: {
    retryAttempts: 3,
    retryDelay: 1000,
    reportErrors: true
  }
}

// Типы для hot reload
export interface HotReloadConfig {
  enabled: boolean
  endpoint: string
  reconnectInterval: number
  maxRetries: number
}

// Типы для A/B тестирования виджетов
export interface WidgetABTest {
  id: string
  name: string
  variants: WidgetVariant[]
  traffic: number
  metrics: string[]
  status: 'draft' | 'running' | 'completed'
}

export interface WidgetVariant {
  id: string
  name: string
  widget: Widget
  traffic: number
  performance: PerformanceStats
}