/**
 * Типы для ImageOptimization из архитектуры Ozon
 * Оптимизация изображений, lazy loading, CDN
 */

// Основная конфигурация изображения
export interface ImageConfig {
  src: string
  alt: string
  width?: number
  height?: number
  aspectRatio?: number
  quality?: number
  format?: ImageFormat
  sizes?: ResponsiveSizes
  placeholder?: PlaceholderConfig
  lazyLoading?: LazyLoadingConfig
  fallback?: string
  srcSet?: SrcSetConfig
}

// Форматы изображений
export type ImageFormat = 
  | 'webp'
  | 'avif'
  | 'jpeg'
  | 'jpg'
  | 'png'
  | 'svg'
  | 'auto'

// Responsive размеры
export interface ResponsiveSizes {
  mobile: ImageSize
  tablet: ImageSize
  desktop: ImageSize
  wide?: ImageSize
}

export interface ImageSize {
  width: number
  height?: number
  quality?: number
}

// Конфигурация placeholder
export interface PlaceholderConfig {
  type: PlaceholderType
  blurDataURL?: string
  color?: string
  showSkeleton?: boolean
  showSpinner?: boolean
  customComponent?: any
}

export type PlaceholderType = 
  | 'blur'
  | 'color'
  | 'skeleton'
  | 'spinner'
  | 'none'

// Конфигурация lazy loading
export interface LazyLoadingConfig {
  enabled: boolean
  threshold?: number
  rootMargin?: string
  placeholder?: PlaceholderConfig
  fadeIn?: boolean
  fadeInDuration?: number
  preloadNext?: number
}

// SrcSet конфигурация
export interface SrcSetConfig {
  sources: ImageSource[]
  sizes: string
  defaultSize?: string
}

export interface ImageSource {
  media?: string
  srcSet: string
  type?: string
  sizes?: string
}

// CDN конфигурация
export interface CDNConfig {
  baseUrl: string
  transformations: CDNTransformation[]
  autoOptimize?: boolean
  autoFormat?: boolean
  quality?: number
  progressive?: boolean
}

export interface CDNTransformation {
  type: TransformationType
  params: Record<string, any>
}

export type TransformationType = 
  | 'resize'
  | 'crop'
  | 'quality'
  | 'format'
  | 'blur'
  | 'sharpen'
  | 'brightness'
  | 'contrast'
  | 'saturation'

// Состояние загрузки изображения
export interface ImageState {
  isLoading: boolean
  isLoaded: boolean
  hasError: boolean
  isIntersecting: boolean
  loadStartTime?: number
  loadEndTime?: number
  retryCount: number
  currentSrc?: string
}

// Метрики производительности
export interface ImageMetrics {
  loadTime: number
  fileSize?: number
  format: string
  dimensions: {
    width: number
    height: number
  }
  compressionRatio?: number
  cacheHit?: boolean
}

// Оптимизация изображений
export interface OptimizationConfig {
  enableWebP?: boolean
  enableAVIF?: boolean
  autoFormat?: boolean
  qualityByDevice?: QualityByDevice
  compressionLevel?: number
  progressiveJPEG?: boolean
  stripMetadata?: boolean
}

export interface QualityByDevice {
  mobile: number
  tablet: number
  desktop: number
  retina: number
}

// Кеширование
export interface CacheConfig {
  enabled: boolean
  strategy: CacheStrategy
  maxAge?: number
  maxSize?: number
  persistent?: boolean
}

export type CacheStrategy = 
  | 'memory'
  | 'disk'
  | 'hybrid'
  | 'network'

// Интерфейс для галереи
export interface GalleryConfig {
  images: ImageConfig[]
  thumbnails?: ThumbnailConfig
  lightbox?: LightboxConfig
  navigation?: boolean
  autoplay?: AutoplayConfig
  lazy?: boolean
}

export interface ThumbnailConfig {
  size: ImageSize
  aspectRatio?: number
  showCount?: boolean
  maxVisible?: number
}

export interface LightboxConfig {
  enabled: boolean
  showThumbnails?: boolean
  showCounter?: boolean
  showZoom?: boolean
  allowDownload?: boolean
  keyboard?: boolean
}

export interface AutoplayConfig {
  enabled: boolean
  interval: number
  pauseOnHover?: boolean
  showControls?: boolean
}

// Обработчики событий
export interface ImageEvents {
  onLoad?: (metrics: ImageMetrics) => void
  onError?: (error: ImageError) => void
  onIntersect?: (isIntersecting: boolean) => void
  onRetry?: (attempt: number) => void
}

export interface ImageError {
  type: ErrorType
  message: string
  src: string
  retryable: boolean
}

export type ErrorType = 
  | 'network'
  | 'timeout'
  | 'format'
  | 'size'
  | 'cors'
  | 'unknown'

// Константы по умолчанию
export const DEFAULT_IMAGE_CONFIG: Partial<ImageConfig> = {
  quality: 85,
  format: 'auto',
  placeholder: {
    type: 'skeleton',
    showSkeleton: true,
    color: '#f0f2f5'
  },
  lazyLoading: {
    enabled: true,
    threshold: 0.1,
    rootMargin: '50px',
    fadeIn: true,
    fadeInDuration: 300,
    preloadNext: 2
  }
}

export const DEFAULT_RESPONSIVE_SIZES: ResponsiveSizes = {
  mobile: { width: 375, quality: 75 },
  tablet: { width: 768, quality: 80 },
  desktop: { width: 1200, quality: 85 },
  wide: { width: 1920, quality: 90 }
}

export const DEFAULT_OPTIMIZATION: OptimizationConfig = {
  enableWebP: true,
  enableAVIF: true,
  autoFormat: true,
  qualityByDevice: {
    mobile: 75,
    tablet: 80,
    desktop: 85,
    retina: 90
  },
  compressionLevel: 80,
  progressiveJPEG: true,
  stripMetadata: true
}

// Утилиты для работы с URL
export interface URLUtils {
  addParams: (url: string, params: Record<string, any>) => string
  getOptimizedUrl: (src: string, config: ImageConfig) => string
  generateSrcSet: (src: string, sizes: ImageSize[]) => string
  getBestFormat: (userAgent: string) => ImageFormat
}

// Детектор поддержки форматов
export interface FormatSupport {
  webp: boolean
  avif: boolean
  heic: boolean
  jpeg2000: boolean
}

// Статистика использования
export interface ImageStats {
  totalImages: number
  loadedImages: number
  failedImages: number
  avgLoadTime: number
  totalBytes: number
  savedBytes: number
  cacheHitRate: number
  formatUsage: Record<ImageFormat, number>
}

// Настройки для разных устройств
export interface DeviceSettings {
  isMobile: boolean
  isTablet: boolean
  isRetina: boolean
  connectionSpeed: ConnectionSpeed
  dataMode: DataMode
}

export type ConnectionSpeed = 
  | 'slow-2g'
  | '2g'
  | '3g'
  | '4g'
  | '5g'
  | 'wifi'
  | 'unknown'

export type DataMode = 
  | 'data-saver'
  | 'normal'
  | 'high-quality'

// Фильтры и эффекты
export interface ImageFilters {
  blur?: number
  brightness?: number
  contrast?: number
  saturation?: number
  sepia?: number
  grayscale?: boolean
  invert?: boolean
  hueRotate?: number
}

// Конфигурация watermark
export interface WatermarkConfig {
  enabled: boolean
  text?: string
  image?: string
  position: WatermarkPosition
  opacity: number
  size: number
}

export type WatermarkPosition = 
  | 'top-left'
  | 'top-right'
  | 'bottom-left'
  | 'bottom-right'
  | 'center'