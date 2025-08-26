/**
 * Экспорты модели фичи gallery
 * Централизованный доступ к store и типам
 */

// Store
export { useGalleryStore } from './gallery.store'

// Типы
export type {
  Photo,
  PhotoExif,
  PhotoLocation,
  GalleryState,
  GalleryOptions,
  GalleryEvents,
  PhotoViewerProps,
  PhotoThumbnailProps,
  PhotoGridProps,
  PhotoControlsProps,
  SwipeGesture,
  PinchGesture,
  GestureHandlers,
  PhotoUploadRequest,
  PhotoUploadResponse,
  PhotoListRequest,
  PhotoListResponse,
  Album,
  AlbumListRequest,
  AlbumListResponse,
  PhotoFilter,
  PhotoSearchOptions,
  PhotoSearchResult,
  ExportOptions,
  ImportOptions,
  PhotoSize,
  PhotoSizeConfig,
  PhotoTransform
} from './types'

// Константы
export {
  PHOTO_MIME_TYPES,
  VIDEO_MIME_TYPES,
  MAX_FILE_SIZE,
  MAX_DIMENSION,
  THUMBNAIL_SIZE,
  PREVIEW_SIZE,
  DEFAULT_GALLERY_OPTIONS
} from './types'