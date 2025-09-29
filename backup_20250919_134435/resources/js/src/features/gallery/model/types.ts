/**
 * TypeScript типы для фичи галереи
 * Типы для фото, состояния галереи и опций
 */

// =================== ОСНОВНЫЕ ТИПЫ ===================

export interface Photo {
  id: string | number
  url: string
  thumbnail?: string
  title?: string
  description?: string
  alt?: string
  filename?: string
  width?: number
  height?: number
  size?: number
  type?: 'photo' | 'video'
  createdAt?: string
  updatedAt?: string
  // Дополнительные метаданные
  exif?: PhotoExif
  tags?: string[]
  author?: string
  location?: PhotoLocation
}

export interface PhotoExif {
  camera?: string
  lens?: string
  iso?: number
  aperture?: string
  shutterSpeed?: string
  focalLength?: string
  flash?: boolean
  orientation?: number
  dateTime?: string
  gps?: {
    latitude: number
    longitude: number
    altitude?: number
  }
}

export interface PhotoLocation {
  name?: string
  address?: string
  city?: string
  country?: string
  coordinates?: {
    lat: number
    lng: number
  }
}

// =================== СОСТОЯНИЕ ГАЛЕРЕИ ===================

export interface GalleryState {
  isOpen: boolean
  photos: Photo[]
  currentIndex: number
  isFullscreen: boolean
  isZoomed: boolean
  zoomLevel: number
  isLoading: boolean
  error: string | null
  options: GalleryOptions
}

// =================== ОПЦИИ ГАЛЕРЕИ ===================

export interface GalleryOptions {
  // Интерфейс
  showThumbnails: boolean
  showControls: boolean
  showCounter: boolean
  showTitle: boolean
  showDescription: boolean
  
  // Поведение
  loop: boolean
  autoplay: boolean
  autoplayInterval: number
  closeOnOutsideClick: boolean
  
  // Управление
  enableKeyboard: boolean
  enableZoom: boolean
  enableFullscreen: boolean
  enableSwipe: boolean
  
  // Зум
  maxZoom: number
  minZoom: number
  zoomStep: number
  
  // Анимации
  transitionDuration: number
  easing: string
  
  // Предзагрузка
  preloadCount: number
  lazyLoad: boolean
  
  // Качество
  thumbnailQuality: 'low' | 'medium' | 'high'
  fullsizeQuality: 'medium' | 'high' | 'original'
}

// =================== СОБЫТИЯ ===================

export interface GalleryEvents {
  'open': [photos: Photo[], startIndex: number]
  'close': []
  'change': [currentIndex: number, photo: Photo]
  'zoom': [zoomLevel: number]
  'fullscreen': [isFullscreen: boolean]
  'error': [error: string]
  'load': [photo: Photo]
  'beforeChange': [fromIndex: number, toIndex: number]
  'afterChange': [currentIndex: number]
}

// =================== КОМПОНЕНТЫ ===================

export interface PhotoViewerProps {
  photos: Photo[]
  initialIndex?: number
  options?: Partial<GalleryOptions>
  visible?: boolean
}

export interface PhotoThumbnailProps {
  photo: Photo
  size?: 'sm' | 'md' | 'lg'
  loading?: 'lazy' | 'eager'
  onClick?: (photo: Photo, index: number) => void
}

export interface PhotoGridProps {
  photos: Photo[]
  columns?: number | 'auto'
  gap?: number
  aspectRatio?: number
  loading?: 'lazy' | 'eager'
  onPhotoClick?: (photo: Photo, index: number) => void
}

export interface PhotoControlsProps {
  hasNext: boolean
  hasPrevious: boolean
  isZoomed: boolean
  isFullscreen: boolean
  canZoom: boolean
  canFullscreen: boolean
  onNext?: () => void
  onPrevious?: () => void
  onZoom?: () => void
  onFullscreen?: () => void
  onClose?: () => void
  onDownload?: () => void
  onShare?: () => void
}

// =================== ЖЕСТЫ ===================

export interface SwipeGesture {
  startX: number
  startY: number
  currentX: number
  currentY: number
  deltaX: number
  deltaY: number
  direction: 'left' | 'right' | 'up' | 'down' | null
  distance: number
  velocity: number
  duration: number
}

export interface PinchGesture {
  scale: number
  startScale: number
  deltaScale: number
  centerX: number
  centerY: number
  distance: number
  velocity: number
}

export interface GestureHandlers {
  onSwipeLeft?: () => void
  onSwipeRight?: () => void
  onSwipeUp?: () => void
  onSwipeDown?: () => void
  onPinchStart?: (gesture: PinchGesture) => void
  onPinchMove?: (gesture: PinchGesture) => void
  onPinchEnd?: (gesture: PinchGesture) => void
  onTap?: (x: number, y: number) => void
  onDoubleTap?: (x: number, y: number) => void
}

// =================== API ТИПЫ ===================

export interface PhotoUploadRequest {
  file: File
  title?: string
  description?: string
  tags?: string[]
  albumId?: string | number
}

export interface PhotoUploadResponse {
  success: boolean
  photo?: Photo
  error?: string
  uploadId?: string
}

export interface PhotoListRequest {
  page?: number
  limit?: number
  albumId?: string | number
  tags?: string[]
  search?: string
  sortBy?: 'date' | 'title' | 'size'
  sortOrder?: 'asc' | 'desc'
}

export interface PhotoListResponse {
  success: boolean
  photos: Photo[]
  total: number
  page: number
  pages: number
  limit: number
}

// =================== АЛЬБОМЫ ===================

export interface Album {
  id: string | number
  title: string
  description?: string
  cover?: Photo
  photoCount: number
  isPublic: boolean
  createdAt: string
  updatedAt: string
  author?: {
    id: string | number
    name: string
    avatar?: string
  }
}

export interface AlbumListRequest {
  page?: number
  limit?: number
  search?: string
  isPublic?: boolean
  sortBy?: 'date' | 'title' | 'photoCount'
  sortOrder?: 'asc' | 'desc'
}

export interface AlbumListResponse {
  success: boolean
  albums: Album[]
  total: number
  page: number
  pages: number
  limit: number
}

// =================== ФИЛЬТРАЦИЯ И ПОИСК ===================

export interface PhotoFilter {
  tags?: string[]
  dateFrom?: string
  dateTo?: string
  type?: 'photo' | 'video'
  minWidth?: number
  minHeight?: number
  maxSize?: number
  author?: string
  location?: string
  hasGps?: boolean
}

export interface PhotoSearchOptions {
  query: string
  filters?: PhotoFilter
  suggestions?: boolean
  highlight?: boolean
}

export interface PhotoSearchResult {
  photos: Photo[]
  total: number
  suggestions?: string[]
  facets?: {
    tags: Array<{ tag: string, count: number }>
    authors: Array<{ author: string, count: number }>
    locations: Array<{ location: string, count: number }>
    types: Array<{ type: string, count: number }>
  }
}

// =================== ЭКСПОРТ И ИМПОРТ ===================

export interface ExportOptions {
  format: 'zip' | 'tar'
  quality: 'thumbnail' | 'medium' | 'high' | 'original'
  includeMetadata: boolean
  includeAlbumStructure: boolean
  maxSize?: number
}

export interface ImportOptions {
  extractMetadata: boolean
  generateThumbnails: boolean
  preserveStructure: boolean
  duplicateHandling: 'skip' | 'replace' | 'rename'
}

// =================== УТИЛИТЫ ===================

export type PhotoSize = 'thumbnail' | 'small' | 'medium' | 'large' | 'original'

export interface PhotoSizeConfig {
  width: number
  height?: number
  quality: number
  format?: 'jpg' | 'webp' | 'avif'
}

export interface PhotoTransform {
  resize?: { width?: number, height?: number }
  crop?: { x: number, y: number, width: number, height: number }
  rotate?: number
  flip?: 'horizontal' | 'vertical'
  quality?: number
  format?: 'jpg' | 'webp' | 'avif' | 'png'
}

// =================== КОНСТАНТЫ ===================

export const PHOTO_MIME_TYPES = [
  'image/jpeg',
  'image/png',
  'image/gif',
  'image/webp',
  'image/avif',
  'image/svg+xml'
] as const

export const VIDEO_MIME_TYPES = [
  'video/mp4',
  'video/webm',
  'video/ogg',
  'video/quicktime'
] as const

export const MAX_FILE_SIZE = 50 * 1024 * 1024 // 50MB
export const MAX_DIMENSION = 8000 // 8000px
export const THUMBNAIL_SIZE = 200
export const PREVIEW_SIZE = 800

export const DEFAULT_GALLERY_OPTIONS: GalleryOptions = {
  showThumbnails: true,
  showControls: true,
  showCounter: true,
  showTitle: true,
  showDescription: false,
  loop: true,
  autoplay: false,
  autoplayInterval: 3000,
  closeOnOutsideClick: true,
  enableKeyboard: true,
  enableZoom: true,
  enableFullscreen: true,
  enableSwipe: true,
  maxZoom: 3,
  minZoom: 1,
  zoomStep: 0.5,
  transitionDuration: 300,
  easing: 'ease-out',
  preloadCount: 2,
  lazyLoad: true,
  thumbnailQuality: 'medium',
  fullsizeQuality: 'high'
}