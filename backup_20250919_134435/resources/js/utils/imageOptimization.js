/**
 * Оптимизация изображений по стандартам Wildberries
 * Адаптивные изображения, WebP, ленивая загрузка
 */

/**
 * Генерирует адаптивные URL изображений
 */
export function generateResponsiveImageUrls(baseUrl, sizes = [320, 640, 768, 1024, 1280, 1920]) {
    if (!baseUrl) return {}
  
    const urls = {}
    const extension = baseUrl.split('.').pop()
    const baseWithoutExt = baseUrl.replace(`.${extension}`, '')
  
    sizes.forEach(size => {
        urls[size] = `${baseWithoutExt}_${size}w.webp`
        urls[`${size}_fallback`] = `${baseWithoutExt}_${size}w.${extension}`
    })
  
    return urls
}

/**
 * Создает srcset для адаптивных изображений
 */
export function createSrcSet(imageUrls, format = 'webp') {
    if (!imageUrls || typeof imageUrls !== 'object') return ''
  
    const entries = Object.entries(imageUrls)
        .filter(([key]) => {
            if (format === 'webp') return !key.includes('fallback')
            return key.includes('fallback')
        })
        .map(([key, url]) => {
            const width = key.replace('_fallback', '')
            return `${url} ${width}w`
        })
  
    return entries.join(', ')
}

/**
 * Компонент адаптивного изображения
 */
export const OptimizedImage = {
    name: 'OptimizedImage',
  
    props: {
        src: {
            type: String,
            required: true
        },
        alt: {
            type: String,
            required: true
        },
        sizes: {
            type: String,
            default: '(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw'
        },
        lazy: {
            type: Boolean,
            default: true
        },
        placeholder: {
            type: String,
            default: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzIwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PC9zdmc+'
        },
        aspectRatio: {
            type: String,
            default: 'auto'
        },
        objectFit: {
            type: String,
            default: 'cover'
        }
    },
  
    data() {
        return {
            isLoaded: false,
            hasError: false,
            isIntersecting: false,
            observer: null
        }
    },
  
    computed: {
        imageUrls() {
            return generateResponsiveImageUrls(this.src)
        },
    
        webpSrcSet() {
            return createSrcSet(this.imageUrls, 'webp')
        },
    
        fallbackSrcSet() {
            return createSrcSet(this.imageUrls, 'fallback')
        },
    
        shouldLoadImage() {
            return !this.lazy || this.isIntersecting
        }
    },
  
    mounted() {
        if (this.lazy) {
            this.setupIntersectionObserver()
        } else {
            this.isIntersecting = true
        }
    },
  
    beforeUnmount() {
        if (this.observer) {
            this.observer.disconnect()
        }
    },
  
    methods: {
        setupIntersectionObserver() {
            if ('IntersectionObserver' in window) {
                this.observer = new IntersectionObserver(
                    (entries) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting) {
                                this.isIntersecting = true
                                this.observer.disconnect()
                            }
                        })
                    },
                    {
                        rootMargin: '50px 0px',
                        threshold: 0.1
                    }
                )
        
                this.observer.observe(this.$refs.imageContainer)
            } else {
                // Fallback для старых браузеров
                this.isIntersecting = true
            }
        },
    
        onImageLoad() {
            this.isLoaded = true
            this.$emit('load')
        },
    
        onImageError() {
            this.hasError = true
            this.$emit('error')
        },
    
        retry() {
            this.hasError = false
            this.isLoaded = false
        }
    },
  
    template: `
    <div 
      ref="imageContainer"
      class="optimized-image"
      :style="{
        aspectRatio: aspectRatio,
        background: !isLoaded ? 'var(--bg-muted)' : 'transparent'
      }"
    >
      <!-- Placeholder пока изображение не загрузилось -->
      <img
        v-if="!shouldLoadImage || (!isLoaded && !hasError)"
        :src="placeholder"
        :alt="alt"
        class="optimized-image__placeholder"
        :style="{ objectFit }"
      />
      
      <!-- Основное изображение -->
      <picture v-if="shouldLoadImage && !hasError">
        <!-- WebP формат для современных браузеров -->
        <source
          v-if="webpSrcSet"
          :srcset="webpSrcSet"
          :sizes="sizes"
          type="image/webp"
        />
        
        <!-- Fallback для старых браузеров -->
        <img
          :src="src"
          :srcset="fallbackSrcSet"
          :sizes="sizes"
          :alt="alt"
          class="optimized-image__img"
          :class="{
            'optimized-image__img--loaded': isLoaded,
            'optimized-image__img--loading': !isLoaded
          }"
          :style="{ objectFit }"
          @load="onImageLoad"
          @error="onImageError"
          loading="lazy"
        />
      </picture>
      
      <!-- Компонент ошибки -->
      <div 
        v-if="hasError" 
        class="optimized-image__error"
        @click="retry"
      >
        <div class="text--muted">
          <svg class="w-8 h-8 mx-auto mb-2" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd" />
          </svg>
          <p class="text-sm">Ошибка загрузки</p>
          <button class="btn btn--secondary btn--sm mt-1">Повторить</button>
        </div>
      </div>
      
      <!-- Загрузчик -->
      <div 
        v-if="shouldLoadImage && !isLoaded && !hasError"
        class="optimized-image__loader"
      >
        <div class="spinner spinner--sm"></div>
      </div>
    </div>
  `
}

/**
 * Компонент галереи с ленивой загрузкой
 */
export const LazyImageGallery = {
    name: 'LazyImageGallery',
  
    props: {
        images: {
            type: Array,
            required: true
        },
        columns: {
            type: Number,
            default: 3
        },
        gap: {
            type: String,
            default: 'var(--spacing-4)'
        }
    },
  
    computed: {
        gridStyle() {
            return {
                display: 'grid',
                gridTemplateColumns: `repeat(${this.columns}, 1fr)`,
                gap: this.gap
            }
        }
    },
  
    template: `
    <div class="lazy-gallery" :style="gridStyle">
      <OptimizedImage
        v-for="(image, index) in images"
        :key="index"
        :src="image.src"
        :alt="image.alt || 'Изображение'"
        :lazy="true"
        aspect-ratio="1"
        @load="$emit('imageLoad', { index, image })"
        @error="$emit('imageError', { index, image })"
      />
    </div>
  `,
  
    components: {
        OptimizedImage
    }
}

/**
 * Утилиты для работы с изображениями
 */
export const ImageUtils = {
    /**
   * Предзагрузка критических изображений
   */
    preloadImages(imageUrls) {
        if (!Array.isArray(imageUrls)) return
    
        imageUrls.forEach(url => {
            const link = document.createElement('link')
            link.rel = 'preload'
            link.as = 'image'
            link.href = url
            document.head.appendChild(link)
        })
    },
  
    /**
   * Проверка поддержки WebP
   */
    supportsWebP() {
        return new Promise((resolve) => {
            const webP = new Image()
            webP.onload = () => resolve(true)
            webP.onerror = () => resolve(false)
            webP.src = 'data:image/webp;base64,UklGRjoAAABXRUJQVlA4IC4AAACyAgCdASoCAAIALmk0mk0iIiIiIgBoSygABc6WWgAA/veff/0PP8bA//LwYAAA'
        })
    },
  
    /**
   * Оптимизация изображений на клиенте
   */
    async compressImage(file, maxWidth = 1920, quality = 0.8) {
        return new Promise((resolve) => {
            const canvas = document.createElement('canvas')
            const ctx = canvas.getContext('2d')
            const img = new Image()
      
            img.onload = () => {
                // Вычисляем новые размеры
                let { width, height } = img
                if (width > maxWidth) {
                    height = (height * maxWidth) / width
                    width = maxWidth
                }
        
                canvas.width = width
                canvas.height = height
        
                // Рисуем изображение
                ctx.drawImage(img, 0, 0, width, height)
        
                // Конвертируем в blob
                canvas.toBlob(resolve, 'image/jpeg', quality)
            }
      
            img.src = URL.createObjectURL(file)
        })
    },
  
    /**
   * Генерация placeholder для изображений
   */
    generatePlaceholder(width = 320, height = 200, color = '#dddddd') {
        const svg = `
      <svg width="${width}" height="${height}" xmlns="http://www.w3.org/2000/svg">
        <rect width="100%" height="100%" fill="${color}"/>
        <text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" 
              fill="#999" font-family="Arial, sans-serif" font-size="14">
          ${width}×${height}
        </text>
      </svg>
    `
    
        return `data:image/svg+xml;base64,${btoa(svg)}`
    }
}

/**
 * Vue плагин для глобальной регистрации компонентов
 */
export const ImageOptimizationPlugin = {
    install(app) {
        app.component('OptimizedImage', OptimizedImage)
        app.component('LazyImageGallery', LazyImageGallery)
    
        // Глобальные свойства
        app.config.globalProperties.$imageUtils = ImageUtils
    }
}