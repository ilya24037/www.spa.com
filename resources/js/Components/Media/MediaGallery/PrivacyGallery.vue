<template>
  <div class="privacy-gallery">
    <!-- Главное фото с размытием -->
    <div class="main-photo">
      <img 
        :src="currentPhoto?.url"
        :alt="masterName"
        :class="blurClass"
        @click="toggleBlur"
        class="w-full h-80 object-cover rounded-lg cursor-pointer"
      >
      
      <!-- Индикатор размытия -->
      <div v-if="isBlurred" class="blur-indicator">
        <EyeIcon class="w-6 h-6" />
        <span>Нажмите для просмотра</span>
      </div>
    </div>

    <!-- Миниатюры с размытием -->
    <div class="thumbnails">
      <div 
        v-for="(photo, index) in photos" 
        :key="photo.id"
        @click="selectPhoto(index)"
        class="thumbnail-item"
      >
        <img 
          :src="photo.thumb_url"
          :class="[blurClass, { 'active': currentIndex === index }]"
          class="w-20 h-20 object-cover rounded-lg"
        >
      </div>
    </div>

    <!-- Настройки приватности -->
    <div class="privacy-controls">
      <button @click="toggleAutoBlur" class="privacy-btn">
        <ShieldCheckIcon class="w-4 h-4" />
        {{ autoBlur ? 'Авторазмытие ВКЛ' : 'Авторазмытие ВЫКЛ' }}
      </button>
      
      <button @click="applyWatermark" class="privacy-btn">
        <CameraIcon class="w-4 h-4" />
        Добавить водяной знак
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { EyeIcon, ShieldCheckIcon, CameraIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  photos: Array,
  masterName: String,
  autoBlur: {
    type: Boolean,
    default: true
  }
})

const currentIndex = ref(0)
const isBlurred = ref(props.autoBlur)

const currentPhoto = computed(() => 
  props.photos[currentIndex.value] || null
)

const blurClass = computed(() => 
  isBlurred.value ? 'blur-md' : ''
)

const selectPhoto = (index) => {
  currentIndex.value = index
  if (props.autoBlur) {
    isBlurred.value = true
  }
}

const toggleBlur = () => {
  isBlurred.value = !isBlurred.value
}

const toggleAutoBlur = () => {
  // Логика переключения авторазмытия
}

const applyWatermark = () => {
  // Логика добавления водяного знака
}
</script>

<style scoped>
.blur-indicator {
  @apply absolute inset-0 flex flex-col items-center justify-center bg-black bg-opacity-30 text-white;
}

.privacy-controls {
  @apply flex gap-2 mt-4;
}

.privacy-btn {
  @apply flex items-center gap-2 px-3 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700;
}

.thumbnail-item.active img {
  @apply ring-2 ring-blue-500;
}
</style> 