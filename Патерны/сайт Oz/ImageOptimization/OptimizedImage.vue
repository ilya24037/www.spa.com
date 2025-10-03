<template>
  <div 
    class="optimized-image"
    :class="imageClasses"
    :style="containerStyles"
  >
    <!-- Placeholder -->
    <Transition name="fade" mode="out-in">
      <div 
        v-if="showPlaceholder"
        class="image-placeholder"
        :class="placeholderClasses"
        :style="placeholderStyles"
      >
        <!-- Скелетон -->
        <div v-if="placeholderType === 'skeleton'" class="skeleton-loader">
          <div class="skeleton-shimmer" />
        </div>
        
        <!-- Спиннер -->
        <div v-else-if="placeholderType === 'spinner'" class="spinner-loader">
          <svg width="40" height="40" viewBox="0 0 40 40" class="spinner">
            <circle
              cx="20"
              cy="20"
              r="18"
              fill="none"
              stroke="currentColor"
              stroke-width="3"
              stroke-dasharray="90"
              stroke-dashoffset="20"
            />
          </svg>
        </div>
        
        <!-- Цветной placeholder -->
        <div 
          v-else-if="placeholderType === 'color'"
          class="color-placeholder"
          :style="{ backgroundColor: placeholderColor }"
        />
        
        <!-- Blur placeholder -->
        <img
          v-else-if="placeholderType === 'blur' && blurDataURL"
          :src="blurDataURL"
          :alt="alt"
          class="blur-placeholder"
          aria-hidden="true"
        />
        
        <!-- Кастомный placeholder -->
        <component
          v-else-if="placeholderType === 'custom' && customPlaceholder"
          :is="customPlaceholder"
          :width="width"
          :height="height"
        />
      </div>
    </Transition>
    
    <!-- Основное изображение -->
    <Transition :name="transitionName">
      <picture v-if="shouldLoad && !imageState.hasError" class="image-picture">
        <!-- AVIF источники -->
        <source
          v-if="supportsAVIF && avifSources.length > 0"
          :srcset="avifSources.join(', ')"
          :sizes="responsiveSizes"
          type="image/avif"
        />
        
        <!-- WebP источники -->
        <source
          v-if="supportsWebP && webpSources.length > 0"
          :srcset="webpSources.join(', ')"
          :sizes="responsiveSizes"
          type="image/webp"
        />
        
        <!-- Основное изображение -->
        <img
          ref="imageRef"
          :src="optimizedSrc"
          :alt="alt"
          :width="width"
          :height="height"
          :loading="nativeLoading"
          :decoding="decoding"
          :fetchpriority="fetchPriority"
          :srcset="fallbackSrcSet"
          :sizes="responsiveSizes"
          class="optimized-img"
          :style="imageStyles"
          @load="handleLoad"
          @error="handleError"
          @progress="handleProgress"
        />
      </picture>
    </Transition>
    
    <!-- Ошибка загрузки -->
    <Transition name="fade">
      <div v-if="imageState.hasError" class="image-error" @click="retry">
        <svg width="48" height="48" viewBox="0 0 24 24" class="error-icon">
          <path 
            fill="currentColor" 
            d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"
          />
        </svg>
        <p class="error-text">Ошибка загрузки</p>
        <button class="error-retry">Повторить</button>
      </div>
    </Transition>
    
    <!-- Прогресс загрузки -->
    <Transition name="fade">
      <div 
        v-if="showProgress && imageState.isLoading"
        class="image-progress"
      >
        <div 
          class="progress-bar"
          :style="{ width: `${loadProgress}%` }"
        />
      </div>
    </Transition>
    
    <!-- Overlay информация -->
    <div v-if="showOverlay" class="image-overlay">
      <slot name="overlay" :state="imageState" :metrics="imageMetrics">
        <div class="overlay-info">
          <span v-if="imageMetrics.fileSize" class="file-size">
            {{ formatFileSize(imageMetrics.fileSize) }}
          </span>
          <span v-if="imageMetrics.loadTime" class="load-time">
            {{ imageMetrics.loadTime }}ms
          </span>
        </div>
      </slot>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import type {
  ImageConfig,
  ImageState,
  ImageMetrics,
  ImageEvents,
  PlaceholderType,
  LazyLoadingConfig
} from './ImageOptimization.types'
import { 
  DEFAULT_IMAGE_CONFIG,
  DEFAULT_RESPONSIVE_SIZES
} from './ImageOptimization.types'

interface Props extends ImageConfig, ImageEvents {
  debug?: boolean
  showProgress?: boolean
  showOverlay?: boolean
  nativeLoading?: 'lazy' | 'eager' | 'auto'
  decoding?: 'sync' | 'async' | 'auto'
  fetchPriority?: 'high' | 'low' | 'auto'
  customPlaceholder?: any
}

const props = withDefaults(defineProps<Props>(), {
  ...DEFAULT_IMAGE_CONFIG,
  debug: false,
  showProgress: false,
  showOverlay: false,
  nativeLoading: 'auto',
  decoding: 'async',
  fetchPriority: 'auto'
})

const emit = defineEmits<{
  'load': [metrics: ImageMetrics]
  'error': [error: Error]
  'intersect': [isIntersecting: boolean]
  'retry': [attempt: number]
}>()

// Refs
const imageRef = ref<HTMLImageElement>()
const intersectionObserver = ref<IntersectionObserver>()

// Состояние
const imageState = ref<ImageState>({
  isLoading: false,
  isLoaded: false,
  hasError: false,
  isIntersecting: false,
  retryCount: 0
})

const imageMetrics = ref<ImageMetrics>({
  loadTime: 0,
  format: 'unknown',
  dimensions: { width: 0, height: 0 }
})

const loadProgress = ref(0)
const shouldLoad = ref(!props.lazyLoading?.enabled)

// Детектор поддержки форматов
const supportsWebP = ref(false)
const supportsAVIF = ref(false)

// Computed
const imageClasses = computed(() => [
  'optimized-image',
  {
    'optimized-image--loading': imageState.value.isLoading,
    'optimized-image--loaded': imageState.value.isLoaded,
    'optimized-image--error': imageState.value.hasError,
    'optimized-image--debug': props.debug
  }
])

const placeholderType = computed((): PlaceholderType => {
  return props.placeholder?.type || 'skeleton'
})

const showPlaceholder = computed(() => {
  return !imageState.value.isLoaded && !imageState.value.hasError && placeholderType.value !== 'none'
})

const placeholderClasses = computed(() => [
  'image-placeholder',
  `placeholder--${placeholderType.value}`
])

const placeholderColor = computed(() => {
  return props.placeholder?.color || '#f0f2f5'
})

const blurDataURL = computed(() => {
  return props.placeholder?.blurDataURL
})

const containerStyles = computed(() => {
  const styles: any = {}
  
  if (props.width && props.height) {
    styles.aspectRatio = `${props.width} / ${props.height}`
  } else if (props.aspectRatio) {
    styles.aspectRatio = props.aspectRatio
  }
  
  if (props.width) {
    styles.width = typeof props.width === 'number' ? `${props.width}px` : props.width
  }
  
  if (props.height) {
    styles.height = typeof props.height === 'number' ? `${props.height}px` : props.height
  }
  
  return styles
})

const placeholderStyles = computed(() => {
  const styles: any = {}
  
  if (placeholderType.value === 'color') {
    styles.backgroundColor = placeholderColor.value
  }
  
  return styles
})

const imageStyles = computed(() => {
  const styles: any = {}
  
  if (props.lazyLoading?.fadeIn && imageState.value.isLoaded) {
    styles.transition = `opacity ${props.lazyLoading.fadeInDuration || 300}ms ease`
  }
  
  return styles
})

const transitionName = computed(() => {
  return props.lazyLoading?.fadeIn ? 'fade-in' : 'none'
})

// Оптимизированный URL
const optimizedSrc = computed(() => {
  return getOptimizedUrl(props.src, {
    width: props.width,
    height: props.height,
    quality: props.quality,
    format: getBestFormat()
  })
})

// AVIF источники
const avifSources = computed(() => {
  if (!props.sizes || !supportsAVIF.value) return []
  
  return Object.entries(props.sizes).map(([breakpoint, size]) => {
    const url = getOptimizedUrl(props.src, {
      ...size,
      format: 'avif'
    })
    return `${url} ${size.width}w`
  })
})

// WebP источники
const webpSources = computed(() => {
  if (!props.sizes || !supportsWebP.value) return []
  
  return Object.entries(props.sizes).map(([breakpoint, size]) => {
    const url = getOptimizedUrl(props.src, {
      ...size,
      format: 'webp'
    })
    return `${url} ${size.width}w`
  })
})

// Fallback srcset
const fallbackSrcSet = computed(() => {
  if (!props.sizes) return ''
  
  return Object.entries(props.sizes).map(([breakpoint, size]) => {
    const url = getOptimizedUrl(props.src, {
      ...size,
      format: 'jpeg'
    })
    return `${url} ${size.width}w`
  }).join(', ')
})

// Responsive sizes
const responsiveSizes = computed(() => {
  if (!props.sizes) return ''
  
  const sizesArray = []
  
  if (props.sizes.mobile) {
    sizesArray.push(`(max-width: 767px) ${props.sizes.mobile.width}px`)
  }
  
  if (props.sizes.tablet) {
    sizesArray.push(`(max-width: 1023px) ${props.sizes.tablet.width}px`)
  }
  
  if (props.sizes.desktop) {
    sizesArray.push(`${props.sizes.desktop.width}px`)
  }
  
  return sizesArray.join(', ')
})

// Методы
const detectFormatSupport = async () => {
  try {
    // Проверка WebP
    const webpData = 'data:image/webp;base64,UklGRiIAAABXRUJQVlA4IBYAAAAwAQCdASoBAAEADsD+JaQAA3AAAAAA'
    const webpImage = new Image()
    webpImage.src = webpData
    supportsWebP.value = await new Promise(resolve => {
      webpImage.onload = () => resolve(true)
      webpImage.onerror = () => resolve(false)
    })
    
    // Проверка AVIF
    const avifData = 'data:image/avif;base64,AAAAIGZ0eXBhdmlmAAAAAGF2aWZtaWYxbWlhZk1BMUIAAADybWV0YQAAAAAAAAAoaGRscgAAAAAAAAAAcGljdAAAAAAAAAAAAAAAAGxpYmF2aWYAAAAADnBpdG0AAAAAAAEAAAAeaWxvYwAAAABEAAABAAEAAAABAAABGgAAAB0AAAAoaWluZgAAAAAAAQAAABppbmZlAgAAAAABAABhdjAxQ29sb3IAAAAAamlwcnAAAABLaXBjbwAAABRpc3BlAAAAAAAAAAIAAAACAAAAEHBpeGkAAAAAAwgICAAAAAxhdjFDgQ0MAAAAABNjb2xybmNseAACAAIAAYAAAAAXaXBtYQAAAAAAAAABAAEEAQKDBAAAACVtZGF0EgAKCBgABogQEAwgMg8f8D///8WfhwB8+ErK42A='
    const avifImage = new Image()
    avifImage.src = avifData
    supportsAVIF.value = await new Promise(resolve => {
      avifImage.onload = () => resolve(true)
      avifImage.onerror = () => resolve(false)
    })
  } catch (error) {
    console.warn('Failed to detect image format support:', error)
  }
}

const getBestFormat = () => {
  if (props.format && props.format !== 'auto') {
    return props.format
  }
  
  if (supportsAVIF.value) return 'avif'
  if (supportsWebP.value) return 'webp'
  return 'jpeg'
}

const getOptimizedUrl = (src: string, options: any) => {
  // В реальном приложении здесь была бы интеграция с CDN
  const params = new URLSearchParams()
  
  if (options.width) params.set('w', options.width.toString())
  if (options.height) params.set('h', options.height.toString())
  if (options.quality) params.set('q', options.quality.toString())
  if (options.format) params.set('f', options.format)
  
  const separator = src.includes('?') ? '&' : '?'
  return `${src}${separator}${params.toString()}`
}

const setupIntersectionObserver = () => {
  if (!props.lazyLoading?.enabled || shouldLoad.value) return
  
  const options = {
    threshold: props.lazyLoading.threshold || 0.1,
    rootMargin: props.lazyLoading.rootMargin || '50px'
  }
  
  intersectionObserver.value = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      imageState.value.isIntersecting = entry.isIntersecting
      
      if (entry.isIntersecting && !shouldLoad.value) {
        shouldLoad.value = true
        emit('intersect', true)
      }
    })
  }, options)
  
  if (imageRef.value?.parentElement) {
    intersectionObserver.value.observe(imageRef.value.parentElement)
  }
}

const handleLoad = (event: Event) => {
  const img = event.target as HTMLImageElement
  const endTime = performance.now()
  const loadTime = imageState.value.loadStartTime 
    ? endTime - imageState.value.loadStartTime 
    : 0
  
  imageState.value.isLoaded = true
  imageState.value.isLoading = false
  imageState.value.loadEndTime = endTime
  
  // Сбор метрик
  imageMetrics.value = {
    loadTime,
    format: getBestFormat(),
    dimensions: {
      width: img.naturalWidth,
      height: img.naturalHeight
    },
    fileSize: getImageFileSize(img.src)
  }
  
  emit('load', imageMetrics.value)
  props.onLoad?.(imageMetrics.value)
}

const handleError = (event: Event) => {
  imageState.value.hasError = true
  imageState.value.isLoading = false
  
  const error = new Error(`Failed to load image: ${props.src}`)
  emit('error', error)
  props.onError?.({
    type: 'network',
    message: error.message,
    src: props.src,
    retryable: true
  })
}

const handleProgress = (event: ProgressEvent) => {
  if (event.lengthComputable) {
    loadProgress.value = (event.loaded / event.total) * 100
  }
}

const retry = () => {
  if (imageState.value.retryCount >= 3) return
  
  imageState.value.retryCount++
  imageState.value.hasError = false
  imageState.value.isLoading = true
  imageState.value.loadStartTime = performance.now()
  
  emit('retry', imageState.value.retryCount)
  props.onRetry?.(imageState.value.retryCount)
  
  // Пересоздаем изображение
  nextTick(() => {
    if (imageRef.value) {
      imageRef.value.src = optimizedSrc.value
    }
  })
}

const getImageFileSize = (url: string): number | undefined => {
  // В реальном приложении размер файла можно получить из заголовков
  return undefined
}

const formatFileSize = (bytes: number): string => {
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`
  return `${(bytes / 1024 / 1024).toFixed(1)} MB`
}

// Watchers
watch(shouldLoad, (newValue) => {
  if (newValue && !imageState.value.isLoaded && !imageState.value.hasError) {
    imageState.value.isLoading = true
    imageState.value.loadStartTime = performance.now()
  }
})

// Lifecycle
onMounted(async () => {
  await detectFormatSupport()
  setupIntersectionObserver()
})

onUnmounted(() => {
  if (intersectionObserver.value) {
    intersectionObserver.value.disconnect()
  }
})
</script>

<style scoped>
/* Основной контейнер */
.optimized-image {
  position: relative;
  overflow: hidden;
  background: var(--placeholder-color, #f0f2f5);
}

/* Picture элемент */
.image-picture {
  width: 100%;
  height: 100%;
  display: block;
}

/* Изображение */
.optimized-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  border: none;
}

/* Placeholder */
.image-placeholder {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--placeholder-color, #f0f2f5);
}

/* Скелетон */
.skeleton-loader {
  position: relative;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, #f0f2f5 25%, #e1e3e6 50%, #f0f2f5 75%);
  background-size: 200% 100%;
  animation: skeleton-shimmer 1.5s infinite;
}

.skeleton-shimmer {
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(
    90deg,
    transparent 0%,
    rgba(255, 255, 255, 0.4) 50%,
    transparent 100%
  );
  animation: shimmer 2s infinite;
}

@keyframes skeleton-shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

@keyframes shimmer {
  0% { left: -100%; }
  100% { left: 100%; }
}

/* Спиннер */
.spinner-loader {
  color: #70757a;
}

.spinner {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Цветной placeholder */
.color-placeholder {
  width: 100%;
  height: 100%;
}

/* Blur placeholder */
.blur-placeholder {
  width: 100%;
  height: 100%;
  object-fit: cover;
  filter: blur(8px);
  transform: scale(1.1);
}

/* Ошибка */
.image-error {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: #f8f9fa;
  color: #70757a;
  cursor: pointer;
  transition: all 0.2s ease;
}

.image-error:hover {
  background: #f0f2f5;
}

.error-icon {
  color: #ff4444;
  margin-bottom: 8px;
}

.error-text {
  margin: 0 0 12px;
  font-size: 14px;
  text-align: center;
}

.error-retry {
  padding: 6px 12px;
  background: #005bff;
  color: white;
  border: none;
  border-radius: 4px;
  font-size: 12px;
  cursor: pointer;
  transition: background 0.2s ease;
}

.error-retry:hover {
  background: #0046cc;
}

/* Прогресс */
.image-progress {
  position: absolute;
  bottom: 0;
  left: 0;
  width: 100%;
  height: 3px;
  background: rgba(0, 0, 0, 0.1);
}

.progress-bar {
  height: 100%;
  background: #005bff;
  transition: width 0.3s ease;
}

/* Overlay */
.image-overlay {
  position: absolute;
  top: 8px;
  right: 8px;
  padding: 4px 8px;
  background: rgba(0, 0, 0, 0.7);
  color: white;
  border-radius: 4px;
  font-size: 12px;
  pointer-events: none;
}

.overlay-info {
  display: flex;
  gap: 8px;
}

/* Debug режим */
.optimized-image--debug {
  border: 2px dashed #ffa500;
}

.optimized-image--debug::after {
  content: 'DEBUG';
  position: absolute;
  top: 4px;
  left: 4px;
  padding: 2px 6px;
  background: #ffa500;
  color: white;
  font-size: 10px;
  font-weight: bold;
  border-radius: 2px;
  pointer-events: none;
}

/* Анимации */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.fade-in-enter-active {
  transition: opacity 0.3s ease;
}

.fade-in-enter-from {
  opacity: 0;
}

/* Состояния */
.optimized-image--loading .optimized-img {
  opacity: 0;
}

.optimized-image--loaded .optimized-img {
  opacity: 1;
}

/* Адаптивность */
@media (max-width: 768px) {
  .image-overlay {
    font-size: 11px;
    top: 4px;
    right: 4px;
    padding: 2px 6px;
  }
}

/* Режим высокой контрастности */
@media (prefers-contrast: high) {
  .optimized-image {
    border: 1px solid #000;
  }
}

/* Уменьшение движения */
@media (prefers-reduced-motion: reduce) {
  .skeleton-shimmer,
  .spinner,
  .shimmer {
    animation: none;
  }
  
  .optimized-img,
  .image-error {
    transition: none;
  }
}
</style>