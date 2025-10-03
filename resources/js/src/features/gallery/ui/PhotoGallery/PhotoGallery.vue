<!-- resources/js/src/features/gallery/ui/PhotoGallery/PhotoGallery.vue -->
<template>
  <div :class="CONTAINER_CLASSES">
    <!-- Р—Р°РіРѕР»РѕРІРѕРє -->
    <div v-if="title" :class="HEADER_CLASSES">
      <h3 :class="TITLE_CLASSES">
        {{ title }}
      </h3>
      <span v-if="photos.length" :class="COUNT_CLASSES">
        {{ photos.length }} {{ getPhotoWord() }}
      </span>
    </div>

    <!-- Вертикальный layout (Avito/WB style) -->
    <div v-if="layout === 'vertical'" class="flex gap-4">
      <!-- Левая колонка - миниатюры -->
      <div class="hidden lg:flex flex-col gap-2 w-24 max-h-[600px] overflow-y-auto scrollbar-hide">
        <button
          v-for="(photo, index) in photos"
          :key="photo.id || index"
          class="relative w-20 h-20 rounded-lg overflow-hidden transition-all flex-shrink-0"
          :class="[
            currentIndex === index 
              ? 'ring-2 ring-blue-500 scale-105' 
              : 'opacity-70 hover:opacity-100'
          ]"
          @click="setCurrentPhoto(index)"
        >
          <img
            :src="getThumbnailUrl(photo)"
            :alt="`РњРёРЅРёР°С‚СЋСЂР° ${index + 1}`"
            class="w-full h-full object-cover"
          >
        </button>
      </div>

      <!-- Правая колонка - основное фото -->
      <div class="flex-1">
        <div :class="MAIN_PHOTO_CONTAINER_CLASSES">
          <img
            :src="currentPhoto.url"
            :alt="currentPhoto.alt || `Р¤РѕС‚Рѕ ${currentIndex + 1}`"
            :class="MAIN_PHOTO_CLASSES"
            @load="handleImageLoad"
            @error="handleImageError"
          >

          <!-- РЎС‡РµС‚С‡РёРє -->
          <div v-if="photos.length > 1" :class="COUNTER_CLASSES">
            {{ currentIndex + 1 }} / {{ photos.length }}
          </div>

          <!-- РљРЅРѕРїРєР° РїРѕР»РЅРѕСЌРєСЂР°РЅРЅРѕРіРѕ РїСЂРѕСЃРјРѕС‚СЂР° -->
          <button
            :class="FULLSCREEN_BUTTON_CLASSES"
            @click="openFullscreen"
          >
            <svg
              :class="FULLSCREEN_ICON_CLASSES"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"
              />
            </svg>
          </button>
        </div>

        <!-- Миниатюры для мобильных (горизонтально снизу) -->
        <div v-if="photos.length > 1 && showThumbnails" class="lg:hidden flex gap-2 overflow-x-auto mt-4 scrollbar-hide">
          <button
            v-for="(photo, index) in photos"
            :key="photo.id || index"
            :class="getThumbnailClasses(index)"
            @click="setCurrentPhoto(index)"
          >
            <img
              :src="getThumbnailUrl(photo)"
              :alt="`РњРёРЅРёР°С‚СЋСЂР° ${index + 1}`"
              :class="THUMBNAIL_IMAGE_CLASSES"
            >
          </button>
        </div>
      </div>
    </div>

    <!-- Горизонтальный layout (default) -->
    <div v-else>
      <!-- РћСЃРЅРѕРІРЅР°СЏ С„РѕС‚РѕРіСЂР°С„РёСЏ -->
      <div :class="MAIN_PHOTO_CONTAINER_CLASSES">
        <img
          :src="currentPhoto.url"
          :alt="currentPhoto.alt || `Р¤РѕС‚Рѕ ${currentIndex + 1}`"
          :class="MAIN_PHOTO_CLASSES"
          @load="handleImageLoad"
          @error="handleImageError"
        >

        <!-- РќР°РІРёРіР°С†РёСЏ -->
        <button
          v-if="photos.length > 1"
          :class="NAV_BUTTON_LEFT_CLASSES"
          @click="previousPhoto"
        >
          <svg
            :class="NAV_ICON_CLASSES"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M15 19l-7-7 7-7"
            />
          </svg>
        </button>

        <button
          v-if="photos.length > 1"
          :class="NAV_BUTTON_RIGHT_CLASSES"
          @click="nextPhoto"
        >
          <svg
            :class="NAV_ICON_CLASSES"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M9 5l7 7-7 7"
            />
          </svg>
        </button>

        <!-- РЎС‡РµС‚С‡РёРє -->
        <div v-if="photos.length > 1" :class="COUNTER_CLASSES">
          {{ currentIndex + 1 }} / {{ photos.length }}
        </div>

        <!-- РљРЅРѕРїРєР° РїРѕР»РЅРѕСЌРєСЂР°РЅРЅРѕРіРѕ РїСЂРѕСЃРјРѕС‚СЂР° -->
        <button
          :class="FULLSCREEN_BUTTON_CLASSES"
          @click="openFullscreen"
        >
          <svg
            :class="FULLSCREEN_ICON_CLASSES"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"
            />
          </svg>
        </button>
      </div>

      <!-- РњРёРЅРёР°С‚СЋСЂС‹ -->
      <div v-if="photos.length > 1 && showThumbnails" :class="THUMBNAILS_CONTAINER_CLASSES">
        <button
          v-for="(photo, index) in photos"
          :key="photo.id || index"
          :class="getThumbnailClasses(index)"
          @click="setCurrentPhoto(index)"
        >
          <img
            :src="getThumbnailUrl(photo)"
            :alt="`РњРёРЅРёР°С‚СЋСЂР° ${index + 1}`"
            :class="THUMBNAIL_IMAGE_CLASSES"
          >
        </button>
      </div>
    </div>

    <!-- РџРѕР»РЅРѕСЌРєСЂР°РЅРЅС‹Р№ РїСЂРѕСЃРјРѕС‚СЂ -->
    <PhotoViewer />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import PhotoViewer from '../PhotoViewer/PhotoViewer.vue'
import { useGalleryStore } from '@/src/features/gallery/model/gallery.store'

// рџЋЇ РЎС‚РёР»Рё СЃРѕРіР»Р°СЃРЅРѕ РґРёР·Р°Р№РЅ-СЃРёСЃС‚РµРјРµ
const CONTAINER_CLASSES = 'space-y-4'
const HEADER_CLASSES = 'flex items-center justify-between'
const TITLE_CLASSES = 'text-lg font-semibold text-gray-500'
const COUNT_CLASSES = 'text-sm text-gray-500'
const MAIN_PHOTO_CONTAINER_CLASSES = 'relative aspect-[4/3] bg-gray-500 rounded-lg overflow-hidden'
const MAIN_PHOTO_CLASSES = 'w-full h-full object-cover'
const NAV_BUTTON_LEFT_CLASSES = 'absolute left-4 top-1/2 -translate-y-1/2 w-10 h-10 bg-black/50 hover:bg-black/70 text-white rounded-full flex items-center justify-center transition-colors'
const NAV_BUTTON_RIGHT_CLASSES = 'absolute right-4 top-1/2 -translate-y-1/2 w-10 h-10 bg-black/50 hover:bg-black/70 text-white rounded-full flex items-center justify-center transition-colors'
const NAV_ICON_CLASSES = 'w-5 h-5'
const COUNTER_CLASSES = 'absolute top-4 right-4 bg-black/50 text-white px-3 py-1 rounded-full text-sm'
const FULLSCREEN_BUTTON_CLASSES = 'absolute bottom-4 right-4 w-10 h-10 bg-black/50 hover:bg-black/70 text-white rounded-full flex items-center justify-center transition-colors'
const FULLSCREEN_ICON_CLASSES = 'w-5 h-5'
const THUMBNAILS_CONTAINER_CLASSES = 'flex gap-2 overflow-x-auto py-2'
const THUMBNAIL_BASE_CLASSES = 'flex-shrink-0 w-16 h-16 rounded overflow-hidden border-2 transition-colors'
const THUMBNAIL_ACTIVE_CLASSES = 'border-blue-500'
const THUMBNAIL_INACTIVE_CLASSES = 'border-gray-500 hover:border-gray-500'
const THUMBNAIL_IMAGE_CLASSES = 'w-full h-full object-cover'

const props = defineProps({
    photos: {
        type: Array,
        required: true
    },
    title: {
        type: String,
        default: ''
    },
    layout: {
        type: String,
        default: 'horizontal',
        validator: (value) => ['horizontal', 'vertical'].includes(value)
    },
    showThumbnails: {
        type: Boolean,
        default: true
    },
    initialIndex: {
        type: Number,
        default: 0
    }
})

const emit = defineEmits(['photo-change'])

// Store
const galleryStore = useGalleryStore()

// РЎРѕСЃС‚РѕСЏРЅРёРµ
const currentIndex = ref(props.initialIndex)

// Р’С‹С‡РёСЃР»СЏРµРјС‹Рµ СЃРІРѕР№СЃС‚РІР°
const currentPhoto = computed(() => 
    props.photos[currentIndex.value] || { url: '', alt: '' }
)

// РњРµС‚РѕРґС‹
const getThumbnailUrl = (photo) => {
    return photo.thumbnail || photo.small || photo.url
}

const getThumbnailClasses = (index) => {
    return [
        THUMBNAIL_BASE_CLASSES,
        index === currentIndex.value ? THUMBNAIL_ACTIVE_CLASSES : THUMBNAIL_INACTIVE_CLASSES
    ].join(' ')
}

const setCurrentPhoto = (index) => {
    if (index >= 0 && index < props.photos.length) {
        currentIndex.value = index
        emit('photo-change', index)
    }
}

const nextPhoto = () => {
    setCurrentPhoto((currentIndex.value + 1) % props.photos.length)
}

const previousPhoto = () => {
    setCurrentPhoto(currentIndex.value === 0 ? props.photos.length - 1 : currentIndex.value - 1)
}

const openFullscreen = () => {
    galleryStore.openGallery(props.photos, currentIndex.value)
}

const handleImageLoad = () => {
    // РћР±СЂР°Р±РѕС‚РєР° СѓСЃРїРµС€РЅРѕР№ Р·Р°РіСЂСѓР·РєРё
}

const handleImageError = () => {
    // РћР±СЂР°Р±РѕС‚РєР° РѕС€РёР±РєРё Р·Р°РіСЂСѓР·РєРё
}

const getPhotoWord = () => {
    const count = props.photos.length
    const lastDigit = count % 10
    const lastTwoDigits = count % 100
  
    if (lastTwoDigits >= 11 && lastTwoDigits <= 14) return 'С„РѕС‚РѕРіСЂР°С„РёР№'
    if (lastDigit === 1) return 'С„РѕС‚РѕРіСЂР°С„РёСЏ'
    if (lastDigit >= 2 && lastDigit <= 4) return 'С„РѕС‚РѕРіСЂР°С„РёРё'
    return 'С„РѕС‚РѕРіСЂР°С„РёР№'
}
</script>

<style scoped>
/* РЎРєСЂС‹С‚РёРµ СЃРєСЂРѕР»Р»Р±Р°СЂР° РґР»СЏ РІРµСЂС‚РёРєР°Р»СЊРЅРѕР№ РіР°Р»РµСЂРµРё */
.scrollbar-hide {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

.scrollbar-hide::-webkit-scrollbar {
  display: none;
}
</style>
