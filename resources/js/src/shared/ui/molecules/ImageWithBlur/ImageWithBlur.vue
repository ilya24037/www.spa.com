<!-- 
  ImageWithBlur - Изображение с blur-placeholder
  Показывает размытую версию изображения во время загрузки основного
-->
<template>
  <div 
    class="image-with-blur"
    :class="containerClass"
  >
    <!-- Blur placeholder (низкое качество) -->
    <img
      v-if="placeholder && !isLoaded"
      :src="placeholder"
      :alt="alt"
      class="image-with-blur__placeholder"
      :class="imageClass"
      aria-hidden="true"
    >
    
    <!-- Основное изображение -->
    <img
      :src="src"
      :alt="alt"
      :loading="loading"
      class="image-with-blur__main"
      :class="[
        imageClass,
        {
          'image-with-blur__main--loaded': isLoaded,
          'image-with-blur__main--loading': !isLoaded
        }
      ]"
      @load="handleLoad"
      @error="handleError"
    >
    
    <!-- Skeleton loader как fallback -->
    <div
      v-if="!placeholder && !isLoaded && !hasError"
      class="image-with-blur__skeleton"
      :class="imageClass"
    />
    
    <!-- Error state -->
    <div
      v-if="hasError"
      class="image-with-blur__error"
      :class="imageClass"
    >
      <svg 
        class="w-12 h-12 text-gray-300"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="1"
          d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
        />
      </svg>
      <span class="text-xs text-gray-500 mt-2">{{ errorText }}</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'

interface Props {
  // Основное изображение
  src: string
  // Placeholder (base64 или маленькое изображение)
  placeholder?: string
  // Alt текст
  alt?: string
  // Lazy loading
  loading?: 'lazy' | 'eager'
  // Классы для контейнера
  containerClass?: string
  // Классы для изображения
  imageClass?: string
  // Текст ошибки
  errorText?: string
  // Fallback изображение при ошибке
  fallbackSrc?: string
}

const props = withDefaults(defineProps<Props>(), {
  alt: '',
  loading: 'lazy',
  containerClass: '',
  imageClass: '',
  errorText: 'Изображение недоступно',
  fallbackSrc: '/images/no-photo.svg'
})

const emit = defineEmits<{
  'load': []
  'error': [event: Event]
}>()

// Состояние
const isLoaded = ref(false)
const hasError = ref(false)

// Методы
const handleLoad = () => {
  isLoaded.value = true
  hasError.value = false
  emit('load')
}

const handleError = (event: Event) => {
  hasError.value = true
  isLoaded.value = false
  
  // Пробуем fallback изображение
  if (props.fallbackSrc && event.target) {
    const img = event.target as HTMLImageElement
    if (img.src !== props.fallbackSrc) {
      img.src = props.fallbackSrc
      hasError.value = false // Сбрасываем ошибку, пробуем fallback
    }
  }
  
  emit('error', event)
}

// Computed
const shouldShowPlaceholder = computed(() => {
  return props.placeholder && !isLoaded.value && !hasError.value
})
</script>

<style scoped>
.image-with-blur {
  position: relative;
  overflow: hidden;
  background-color: #f3f4f6;
}

/* Placeholder с blur эффектом */
.image-with-blur__placeholder {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  filter: blur(20px);
  transform: scale(1.1); /* Убираем белые края от blur */
  opacity: 0.8;
}

/* Основное изображение */
.image-with-blur__main {
  position: relative;
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: opacity 0.3s ease-in-out;
}

.image-with-blur__main--loading {
  opacity: 0;
}

.image-with-blur__main--loaded {
  opacity: 1;
}

/* Skeleton loader */
.image-with-blur__skeleton {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: linear-gradient(
    90deg,
    #f3f4f6 0%,
    #e5e7eb 50%,
    #f3f4f6 100%
  );
  background-size: 200% 100%;
  animation: skeleton-loading 1.5s ease-in-out infinite;
}

@keyframes skeleton-loading {
  0% {
    background-position: 200% 0;
  }
  100% {
    background-position: -200% 0;
  }
}

/* Error state */
.image-with-blur__error {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background-color: #f9fafb;
  color: #9ca3af;
}

/* Поддержка aspect-ratio */
.image-with-blur[style*="aspect-ratio"] {
  width: 100%;
  height: auto;
}

/* Отключаем drag для изображений */
.image-with-blur img {
  user-select: none;
  -webkit-user-drag: none;
}

/* Поддержка object-position */
.image-with-blur img[style*="object-position"] {
  object-position: inherit;
}
</style>