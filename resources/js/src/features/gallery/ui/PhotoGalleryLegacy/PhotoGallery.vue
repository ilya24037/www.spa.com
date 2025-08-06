<template>
  <!-- РЈРЅРёРІРµСЂСЃР°Р»СЊРЅР°СЏ РіР°Р»РµСЂРµСЏ С„РѕС‚РѕРіСЂР°С„РёР№ -->
  <div class="photo-gallery" :class="galleryClasses">
    
    <!-- Р РµР¶РёРј РїРѕР»РЅРѕР№ РіР°Р»РµСЂРµРё (РґР»СЏ РјР°СЃС‚РµСЂРѕРІ/РѕР±СЉСЏРІР»РµРЅРёР№) -->
    <div v-if="mode === 'full'" class="full-gallery bg-white rounded-lg shadow-sm overflow-hidden">
      <!-- Р”РµСЃРєС‚РѕРїРЅР°СЏ РІРµСЂСЃРёСЏ -->
      <div class="hidden md:flex gap-4 p-4">
        <!-- РњРёРЅРёР°С‚СЋСЂС‹ СЃР»РµРІР° -->
        <div v-if="showThumbnails" class="flex flex-col gap-2 w-20">
          <div 
            v-for="(photo, index) in processedPhotos" 
            :key="index"
            @click="selectPhoto(index)"
            :class="[
              'relative w-16 h-16 rounded-lg overflow-hidden cursor-pointer border-2 transition-all',
              currentPhotoIndex === index ? 'border-blue-500' : 'border-gray-200 hover:border-gray-300'
            ]"
          >
            <img 
              :src="photo.thumb || photo.url"
              :alt="`Р¤РѕС‚Рѕ ${index + 1}`"
              class="w-full h-full object-cover"
            >
            <div 
              v-if="currentPhotoIndex === index"
              class="absolute inset-0 bg-blue-500 bg-opacity-10"
            ></div>
          </div>
        </div>
        
        <!-- РћСЃРЅРѕРІРЅРѕРµ РёР·РѕР±СЂР°Р¶РµРЅРёРµ -->
        <div class="flex-1">
          <div class="relative aspect-[4/3] bg-gray-100 rounded-lg overflow-hidden">
            <img 
              :src="currentPhoto.url"
              :alt="currentPhoto.alt || 'Р¤РѕС‚Рѕ'"
              class="w-full h-full object-cover cursor-pointer"
              @click="openLightbox"
            >
            
            <!-- Р‘РµР№РґР¶Рё -->
            <div v-if="showBadges" class="absolute top-4 left-4 flex flex-col gap-2">
              <slot name="badges" />
            </div>
            
            <!-- РљРЅРѕРїРєР° СѓРІРµР»РёС‡РµРЅРёСЏ -->
            <button 
              v-if="showLightboxButton"
              @click="openLightbox"
              class="absolute bottom-4 right-4 bg-black bg-opacity-50 text-white p-2 rounded-full hover:bg-opacity-70 transition-all"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/>
              </svg>
            </button>
            
            <!-- РЎС‡РµС‚С‡РёРє С„РѕС‚Рѕ -->
            <div v-if="showCounter" class="absolute bottom-4 left-4 bg-black bg-opacity-50 text-white px-3 py-1 rounded-full text-sm">
              {{ currentPhotoIndex + 1 }} / {{ processedPhotos.length }}
            </div>
          </div>
        </div>
      </div>
      
      <!-- РњРѕР±РёР»СЊРЅР°СЏ РІРµСЂСЃРёСЏ -->
      <div class="md:hidden">
        <div class="relative aspect-[4/3] bg-gray-100">
          <img 
            :src="currentPhoto.url"
            :alt="currentPhoto.alt || 'Р¤РѕС‚Рѕ'"
            class="w-full h-full object-cover cursor-pointer"
            @click="openLightbox"
          >
        
          <div v-if="showBadges" class="absolute top-4 left-4 flex flex-col gap-2">
            <slot name="badges" />
          </div>
          
          <button 
            v-if="currentPhotoIndex > 0"
            @click="previousPhoto"
            class="absolute left-2 top-1/2 -translate-y-1/2 bg-black bg-opacity-50 text-white p-2 rounded-full"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
          </button>
          
          <button 
            v-if="currentPhotoIndex < processedPhotos.length - 1"
            @click="nextPhoto"
            class="absolute right-2 top-1/2 -translate-y-1/2 bg-black bg-opacity-50 text-white p-2 rounded-full"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
          </button>
          
          <div v-if="showCounter" class="absolute bottom-4 right-4 bg-black bg-opacity-50 text-white px-2 py-1 rounded-full text-sm">
            {{ currentPhotoIndex + 1 }} / {{ processedPhotos.length }}
          </div>
        </div>
        
        <div v-if="showThumbnails" class="flex gap-2 p-4 overflow-x-auto">
          <div 
            v-for="(photo, index) in processedPhotos" 
            :key="index"
            @click="selectPhoto(index)"
            :class="[
              'relative w-16 h-16 flex-shrink-0 rounded-lg overflow-hidden cursor-pointer border-2 transition-all',
              currentPhotoIndex === index ? 'border-blue-500' : 'border-gray-200'
            ]"
          >
            <img 
              :src="photo.thumb || photo.url"
              :alt="`Р¤РѕС‚Рѕ ${index + 1}`"
              class="w-full h-full object-cover"
            >
            <div 
              v-if="currentPhotoIndex === index"
              class="absolute inset-0 bg-blue-500 bg-opacity-10"
            ></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Р РµР¶РёРј СЃРµС‚РєРё (РґР»СЏ РѕР±СЉСЏРІР»РµРЅРёР№) -->
    <div v-else-if="mode === 'grid'" class="grid-gallery">
      <h2 v-if="title" class="text-xl font-semibold mb-4">{{ title }}</h2>
      <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
        <div 
          v-for="(photo, index) in processedPhotos" 
          :key="index"
          class="relative aspect-square bg-gray-100 rounded-lg overflow-hidden"
        >
          <img 
            :src="photo.preview || photo.url" 
            :alt="`Р¤РѕС‚Рѕ ${index + 1}`"
            class="w-full h-full object-cover cursor-pointer hover:scale-105 transition-transform"
            @click="openLightbox(index)"
          />
          <div v-if="index === 0" class="absolute bottom-2 left-2 bg-black bg-opacity-70 text-white text-xs px-2 py-1 rounded">
            РћСЃРЅРѕРІРЅРѕРµ С„РѕС‚Рѕ
          </div>
        </div>
      </div>
    </div>

    <!-- Р РµР¶РёРј РїСЂРµРІСЊСЋ (РґР»СЏ РєР°СЂС‚РѕС‡РµРє) -->
    <div v-else-if="mode === 'preview'" class="preview-gallery">
      <div class="relative w-full h-full">
        <img 
          :src="getImageUrl(firstPhoto)"
          :alt="title || 'Р¤РѕС‚Рѕ'"
          class="w-full h-full object-cover"
          @error="handleImageError"
        >
        <div v-if="processedPhotos.length > 1" class="absolute bottom-2 left-2 flex gap-1">
          <div 
            v-for="n in Math.min(processedPhotos.length, 4)" 
            :key="n"
            class="w-2 h-2 rounded-full bg-white bg-opacity-70"
            :class="{ 'bg-opacity-100': n === 1 }"
          ></div>
        </div>
      </div>
    </div>
    
    <!-- Lightbox -->
    <div 
      v-if="showLightbox && enableLightbox" 
      class="fixed inset-0 bg-black bg-opacity-90 flex items-center justify-center z-50"
      @click="closeLightbox"
    >
      <div class="relative max-w-4xl max-h-full p-4">
        <img 
          :src="currentPhoto.url"
          :alt="currentPhoto.alt"
          class="max-w-full max-h-full object-contain"
          @click.stop
        >
        
        <button 
          @click="closeLightbox"
          class="absolute top-4 right-4 text-white bg-black bg-opacity-50 p-2 rounded-full hover:bg-opacity-70"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
        
        <button 
          v-if="currentPhotoIndex > 0"
          @click.stop="previousPhoto"
          class="absolute left-4 top-1/2 -translate-y-1/2 text-white bg-black bg-opacity-50 p-2 rounded-full hover:bg-opacity-70"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
          </svg>
        </button>
        
        <button 
          v-if="currentPhotoIndex < processedPhotos.length - 1"
          @click.stop="nextPhoto"
          class="absolute right-4 top-1/2 -translate-y-1/2 text-white bg-black bg-opacity-50 p-2 rounded-full hover:bg-opacity-70"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
          </svg>
        </button>
        
        <div class="absolute bottom-4 left-1/2 -translate-x-1/2 text-white bg-black bg-opacity-50 px-3 py-1 rounded-full">
          {{ currentPhotoIndex + 1 }} / {{ processedPhotos.length }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  photos: { type: Array, default: () => [] },
  mode: { type: String, default: 'full' }, // full, grid, preview
  title: { type: String, default: '' },
  showThumbnails: { type: Boolean, default: true },
  showCounter: { type: Boolean, default: true },
  showBadges: { type: Boolean, default: false },
  showLightboxButton: { type: Boolean, default: true },
  enableLightbox: { type: Boolean, default: true },
  containerClass: { type: String, default: '' }
})

const currentPhotoIndex = ref(0)
const showLightbox = ref(false)

const processedPhotos = computed(() => {
  const photos = []
  
  if (props.photos && props.photos.length) {
    props.photos.forEach((photo, index) => {
      let photoUrl = null
      let photoThumb = null
      
      if (typeof photo === 'string') {
        photoUrl = photo
        photoThumb = photo
      } else if (photo && typeof photo === 'object') {
        photoUrl = photo.preview || photo.url || photo.src || photo.path
        photoThumb = photo.thumb || photo.preview || photo.url || photo.src || photo.path
      }
      
      if (photoUrl && photoUrl !== 'undefined') {
        photos.push({
          url: photoUrl,
          thumb: photoThumb || photoUrl,
          preview: photo.preview || photoUrl,
          alt: photo.alt || `Р¤РѕС‚Рѕ ${index + 1}`
        })
      }
    })
  }
  
  if (photos.length === 0) {
    photos.push({
      url: '/images/masters/demo-1.jpg',
      thumb: '/images/masters/demo-1.jpg',
      preview: '/images/masters/demo-1.jpg',
      alt: 'Р¤РѕС‚Рѕ РЅРµРґРѕСЃС‚СѓРїРЅРѕ'
    })
  }
  
  return photos
})

const currentPhoto = computed(() => 
  processedPhotos.value[currentPhotoIndex.value] || processedPhotos.value[0]
)

const firstPhoto = computed(() => 
  processedPhotos.value[0]?.url || processedPhotos.value[0]
)

const galleryClasses = computed(() => [
  props.containerClass,
  `gallery-mode-${props.mode}`
])

const selectPhoto = (index) => {
  currentPhotoIndex.value = index
}

const nextPhoto = () => {
  if (currentPhotoIndex.value < processedPhotos.value.length - 1) {
    currentPhotoIndex.value++
  }
}

const previousPhoto = () => {
  if (currentPhotoIndex.value > 0) {
    currentPhotoIndex.value--
  }
}

const openLightbox = (index = null) => {
  if (!props.enableLightbox) return
  if (index !== null) {
    currentPhotoIndex.value = index
  }
  showLightbox.value = true
}

const closeLightbox = () => {
  showLightbox.value = false
}

const getImageUrl = (photo) => {
  if (!photo) return '/images/masters/demo-1.jpg'
  
  if (typeof photo === 'string') {
    if (photo === 'undefined' || !photo) return '/images/masters/demo-1.jpg'
    return photo
  }
  
  if (typeof photo === 'object') {
    return photo.url || photo.preview || photo.src || '/images/masters/demo-1.jpg'
  }
  
  return '/images/masters/demo-1.jpg'
}

const handleImageError = (event) => {
  event.target.src = '/images/masters/demo-1.jpg'
  event.target.onerror = null
}

const handleKeydown = (event) => {
  if (!showLightbox.value) return
  
  switch (event.key) {
    case 'Escape':
      closeLightbox()
      break
    case 'ArrowLeft':
      previousPhoto()
      break
    case 'ArrowRight':
      nextPhoto()
      break
  }
}

onMounted(() => {
  document.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown)
})
</script>

<style scoped>
.transition-all {
  transition-property: all;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 300ms;
}
</style>
