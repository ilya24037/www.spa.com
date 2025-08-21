// Основные компоненты
export { default as MediaSection } from './ui/MediaSection.vue'
export { default as PhotoGallery } from './ui/PhotoGallery/PhotoGallery.vue'
export { default as VideoUpload } from './ui/VideoUpload/VideoUpload.vue'

// Composables
export { usePhotoUpload } from './composables/usePhotoUpload'
export { useVideoUpload } from './composables/useVideoUpload'
export { useFormatDetection } from './composables/useFormatDetection'

// Типы
export type { Photo } from './composables/usePhotoUpload'
export type { Video, VideoMetadata, VideoSource } from './composables/useVideoUpload'
export type { FormatSupport } from './composables/useFormatDetection'