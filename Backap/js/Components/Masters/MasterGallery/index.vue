<!-- resources/js/Components/Masters/MasterGallery/index.vue -->
<template>
  <div class="master-gallery">
    <!-- Главное фото -->
    <div class="main-photo-container">
      <img 
        :src="currentPhoto?.url || '/images/no-photo.jpg'"
        :alt="`${masterName} - фото ${currentIndex + 1}`"
        class="main-photo"
        @click="openLightbox"
      >
      
      <!-- Навигация по фото -->
      <button 
        v-if="hasPrevious"
        @click="previousPhoto"
        class="nav-btn nav-btn-left"
      >
        <ChevronLeftIcon class="w-6 h-6" />
      </button>
      
      <button 
        v-if="hasNext"
        @click="nextPhoto"
        class="nav-btn nav-btn-right"
      >
        <ChevronRightIcon class="w-6 h-6" />
      </button>
      
      <!-- Счетчик фото -->
      <div class="photo-counter">
        {{ currentIndex + 1 }} из {{ photos.length }}
      </div>
    </div>

    <!-- Миниатюры -->
    <div class="thumbnails-container">
      <div class="thumbnails">
        <button
          v-for="(photo, index) in photos"
          :key="photo.id"
          @click="selectPhoto(index)"
          :class="[
            'thumbnail-btn',
            { 'active': currentIndex === index }
          ]"
        >
          <img 
            :src="photo.thumb_url || photo.url"
            :alt="`${masterName} - фото ${index + 1}`"
            class="thumbnail-img"
          >
        </button>
      </div>
    </div>

    <!-- Полноэкранный просмотр -->
    <GalleryLightbox 
      v-if="showLightbox"
      :photos="photos"
      :current-index="currentIndex"
      :master-name="masterName"
      @close="closeLightbox"
      @next="nextPhoto"
      @previous="previousPhoto"
    />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/24/outline'
import GalleryLightbox from './GalleryLightbox.vue'

const props = defineProps({
  photos: {
    type: Array,
    default: () => []
  },
  masterName: {
    type: String,
    required: true
  }
})

const currentIndex = ref(0)
const showLightbox = ref(false)

const currentPhoto = computed(() => 
  props.photos[currentIndex.value] || null
)

const hasPrevious = computed(() => currentIndex.value > 0)
const hasNext = computed(() => currentIndex.value < props.photos.length - 1)

const selectPhoto = (index) => {
  currentIndex.value = index
}

const nextPhoto = () => {
  if (hasNext.value) {
    currentIndex.value++
  }
}

const previousPhoto = () => {
  if (hasPrevious.value) {
    currentIndex.value--
  }
}

const openLightbox = () => {
  if (props.photos.length > 0) {
    showLightbox.value = true
  }
}

const closeLightbox = () => {
  showLightbox.value = false
}
</script>

<style scoped>
.master-gallery {
  @apply bg-white rounded-lg overflow-hidden shadow-sm;
}

.main-photo-container {
  @apply relative aspect-[4/3] bg-gray-100;
}

.main-photo {
  @apply w-full h-full object-cover cursor-pointer;
}

.nav-btn {
  @apply absolute top-1/2 -translate-y-1/2 bg-black bg-opacity-50 text-white p-3 rounded-full hover:bg-opacity-70 transition-all;
}

.nav-btn-left {
  @apply left-4;
}

.nav-btn-right {
  @apply right-4;
}

.photo-counter {
  @apply absolute bottom-4 right-4 bg-black bg-opacity-50 text-white px-3 py-1 rounded-full text-sm;
}

.thumbnails-container {
  @apply p-4 bg-gray-50;
}

.thumbnails {
  @apply flex gap-2 overflow-x-auto;
}

.thumbnail-btn {
  @apply flex-shrink-0 w-20 h-20 rounded-lg overflow-hidden border-2 border-transparent hover:border-blue-500 transition-colors;
}

.thumbnail-btn.active {
  @apply border-blue-500;
}

.thumbnail-img {
  @apply w-full h-full object-cover;
}
</style>