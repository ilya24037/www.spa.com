<template>
  <div class="lazy-gallery">
    <!-- Основное изображение -->
    <div class="relative aspect-[4/3] bg-gray-200 rounded-lg overflow-hidden">
      <img 
        v-if="currentImage"
        :src="currentImage.url"
        :alt="currentImage.alt"
        class="w-full h-full object-cover transition-opacity duration-300"
        :class="{ 'opacity-0': imageLoading }"
        @load="imageLoading = false"
        @error="handleImageError"
      />
      
      <!-- Placeholder во время загрузки -->
      <div v-if="imageLoading" class="absolute inset-0 flex items-center justify-center bg-gray-100">
        <div class="w-12 h-12 border-4 border-gray-300 border-t-indigo-600 rounded-full animate-spin"></div>
      </div>
      
      <!-- Навигация -->
      <div v-if="images.length > 1" class="absolute inset-0 flex items-center justify-between px-4">
        <button
          v-show="currentIndex > 0"
          @click="previousImage"
          class="p-2 bg-black/50 hover:bg-black/70 text-white rounded-full transition-colors"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
        </button>
        
        <button
          v-show="currentIndex < images.length - 1"
          @click="nextImage"
          class="p-2 bg-black/50 hover:bg-black/70 text-white rounded-full transition-colors"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </button>
      </div>
      
      <!-- Индикатор -->
      <div v-if="images.length > 1" class="absolute bottom-4 right-4 bg-black/50 text-white px-3 py-1 rounded-full text-sm">
        {{ currentIndex + 1 }} / {{ images.length }}
      </div>
    </div>
    
    <!-- Миниатюры -->
    <div v-if="images.length > 1" class="mt-4 grid grid-cols-4 gap-2">
      <button
        v-for="(image, index) in images"
        :key="index"
        @click="setCurrentImage(index)"
        class="aspect-square bg-gray-200 rounded-lg overflow-hidden"
        :class="{ 'ring-2 ring-indigo-500': index === currentIndex }"
      >
        <img 
          v-if="shouldLoadThumbnail(index)"
          :src="image.thumb || image.url"
          :alt="image.alt"
          class="w-full h-full object-cover"
          loading="lazy"
        />
        <div v-else class="w-full h-full bg-gray-100 flex items-center justify-center">
          <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
        </div>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'

const props = defineProps({
  images: {
    type: Array,
    required: true
  }
})

const currentIndex = ref(0)
const imageLoading = ref(true)
const loadedThumbnails = ref(new Set())

const currentImage = computed(() => props.images[currentIndex.value] || null)

const shouldLoadThumbnail = (index) => {
  // Загружаем миниатюры только рядом с текущим изображением
  const distance = Math.abs(index - currentIndex.value)
  return distance <= 2 || loadedThumbnails.value.has(index)
}

const setCurrentImage = (index) => {
  if (index >= 0 && index < props.images.length) {
    currentIndex.value = index
    imageLoading.value = true
    loadedThumbnails.value.add(index)
  }
}

const previousImage = () => {
  if (currentIndex.value > 0) {
    setCurrentImage(currentIndex.value - 1)
  }
}

const nextImage = () => {
  if (currentIndex.value < props.images.length - 1) {
    setCurrentImage(currentIndex.value + 1)
  }
}

const handleImageError = () => {
  console.warn('Ошибка загрузки изображения:', currentImage.value?.url)
  imageLoading.value = false
}

// Предзагрузка следующего изображения
watch(currentIndex, (newIndex) => {
  if (newIndex < props.images.length - 1) {
    const nextImg = new Image()
    nextImg.src = props.images[newIndex + 1].url
  }
})

// Инициализация
onMounted(() => {
  if (props.images.length > 0) {
    loadedThumbnails.value.add(0)
    loadedThumbnails.value.add(1)
  }
})
</script>