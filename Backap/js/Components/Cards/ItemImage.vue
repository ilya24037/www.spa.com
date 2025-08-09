<template>
  <div class="item-image-section">
    <div class="item-image-container">
      <div 
        class="item-image-wrapper ozon-style"
        @mouseenter="startImagePreview"
        @mouseleave="stopImagePreview"
        @mousemove="handleMouseMove"
      >
        <img 
          :src="currentImageUrl"
          :alt="item.name"
          class="item-image"
          @error="handleImageError"
        >
      </div>
    </div>
    
    <!-- Индикаторы слайдера ПОД фото как на Ozon -->
    <div v-if="processedPhotos.length > 1" class="slider-indicators ozon-indicators">
      <div 
        v-for="n in Math.min(processedPhotos.length, 6)" 
        :key="n"
        class="slider-dot ozon-dot"
        :class="{ 'active': n === currentPhotoIndex + 1 }"
      ></div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'

const props = defineProps({
  item: {
    type: Object,
    required: true
  },
  itemUrl: {
    type: String,
    required: true
  }
})

// Состояние для прокрутки фото
const isHovering = ref(false)
const currentPhotoIndex = ref(0)

// Используем ту же логику что и в PhotoGallery.vue
const processedPhotos = computed(() => {
  const photos = []
  
  if (props.item.photos && props.item.photos.length) {
    props.item.photos.forEach((photo, index) => {
      let photoUrl = null
      
      if (typeof photo === 'string') {
        photoUrl = photo
      } else if (photo && typeof photo === 'object') {
        photoUrl = photo.preview || photo.url || photo.src || photo.path
      }
      
      if (photoUrl && photoUrl !== 'undefined' && photoUrl !== 'null') {
        photos.push({
          url: photoUrl,
          alt: photo.alt || `Фото ${index + 1}`
        })
      }
    })
  }
  
  // Если нет фото в массиве, проверяем отдельные поля
  if (photos.length === 0) {
    const possibleImages = [
      props.item.avatar,
      props.item.main_image,
      props.item.image,
      props.item.photo
    ]
    
    for (const img of possibleImages) {
      if (img && img !== 'null' && img !== 'undefined' && img !== '') {
        photos.push({
          url: img,
          alt: props.item.name || 'Фото'
        })
        break
      }
    }
  }
  
  // Fallback на тестовое изображение
  if (photos.length === 0) {
    const demoNumber = (props.item.id % 4) + 1
    photos.push({
      url: `/images/masters/demo-${demoNumber}.jpg`,
      alt: 'Демо фото'
    })
  }
  
  return photos
})

// URL текущего изображения
const currentImageUrl = computed(() => {
  return processedPhotos.value[currentPhotoIndex.value]?.url || '/images/masters/demo-1.jpg'
})

// Функции для прокрутки фото
const startImagePreview = () => {
  if (processedPhotos.value.length > 1) {
    isHovering.value = true
  }
}

const stopImagePreview = () => {
  isHovering.value = false
  currentPhotoIndex.value = 0 // Возвращаемся к первому фото
}

const handleMouseMove = (event) => {
  if (!isHovering.value || processedPhotos.value.length <= 1) return
  
  const rect = event.currentTarget.getBoundingClientRect()
  const x = event.clientX - rect.left
  const width = rect.width
  
  // Определяем индекс фото на основе позиции мыши
  const photoIndex = Math.floor((x / width) * processedPhotos.value.length)
  const clampedIndex = Math.max(0, Math.min(photoIndex, processedPhotos.value.length - 1))
  
  if (clampedIndex !== currentPhotoIndex.value) {
    currentPhotoIndex.value = clampedIndex
  }
}

const handleImageError = (event) => {
  // Если это уже fallback изображение, показываем placeholder
  if (event.target.src.includes('demo-')) {
    event.target.src = '/images/default-avatar.svg'
  } else {
    // Пробуем другое demo изображение
    const demoNumber = Math.floor(Math.random() * 4) + 1
    event.target.src = `/images/masters/demo-${demoNumber}.jpg`
  }
}
</script>

<style scoped>
/* Основные размеры в стиле Ozon */
.item-image-section {
  @apply relative flex-shrink-0;
  width: 160px;  /* Увеличиваем ширину */
  height: 240px; /* Увеличиваем высоту для места под индикаторами */
  background: transparent; /* Убираем любой фон */
}

.item-image-container {
  @apply block w-full;
  height: 213px; /* Высота самого изображения */
}

.item-image-wrapper {
  @apply relative w-full h-full overflow-hidden;
  @apply cursor-pointer transition-all duration-300;
}

/* Стиль Ozon - более скругленные углы */
.item-image-wrapper.ozon-style {
  border-radius: 16px; /* Более скругленные углы как на Ozon */
  @apply shadow-sm hover:shadow-md;
}

.item-image {
  @apply w-full h-full object-cover transition-all duration-200;
}

/* Индикаторы ПОД фото в стиле Ozon */
.slider-indicators.ozon-indicators {
  @apply flex gap-1 justify-center mt-2;
  width: 100%;
  background: transparent; /* Убираем любой фон */
}

.slider-dot.ozon-dot {
  width: 6px;
  height: 6px;
  @apply rounded-full transition-all duration-200;
  background-color: rgba(59, 130, 246, 0.4); /* Синий цвет как на Ozon */
}

.slider-dot.ozon-dot.active {
  background-color: #3B82F6; /* Яркий синий для активной точки */
  transform: scale(1.2);
}

/* Эффекты при наведении */
.item-image-wrapper:hover {
  @apply shadow-lg;
}

/* Плавное появление индикаторов */
.slider-indicators {
  @apply transition-opacity duration-200;
  opacity: 0.8;
}

.item-image-section:hover .slider-indicators {
  opacity: 1;
}

/* Responsive для мобильных */
@media (max-width: 768px) {
  .item-image-section {
    width: 140px;
    height: 214px; /* 187px изображение + 27px для индикаторов */
  }
  
  .item-image-container {
    height: 187px; /* Пропорции 3:4 для мобильных */
  }
}
</style>