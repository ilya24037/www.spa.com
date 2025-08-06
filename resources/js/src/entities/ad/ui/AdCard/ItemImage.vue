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
    
    <!-- РРЅРґРёРєР°С‚РѕСЂС‹ СЃР»Р°Р№РґРµСЂР° РџРћР” С„РѕС‚Рѕ РєР°Рє РЅР° Ozon -->
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

<script setup lang="ts">
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

// РЎРѕСЃС‚РѕСЏРЅРёРµ РґР»СЏ РїСЂРѕРєСЂСѓС‚РєРё С„РѕС‚Рѕ
const isHovering = ref(false)
const currentPhotoIndex = ref(0)

// РСЃРїРѕР»СЊР·СѓРµРј С‚Сѓ Р¶Рµ Р»РѕРіРёРєСѓ С‡С‚Рѕ Рё РІ PhotoGallery.vue
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
          alt: photo.alt || `Р¤РѕС‚Рѕ ${index + 1}`
        })
      }
    })
  }
  
  // Р•СЃР»Рё РЅРµС‚ С„РѕС‚Рѕ РІ РјР°СЃСЃРёРІРµ, РїСЂРѕРІРµСЂСЏРµРј РѕС‚РґРµР»СЊРЅС‹Рµ РїРѕР»СЏ
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
          alt: props.item.name || 'Р¤РѕС‚Рѕ'
        })
        break
      }
    }
  }
  
  // Fallback РЅР° С‚РµСЃС‚РѕРІРѕРµ РёР·РѕР±СЂР°Р¶РµРЅРёРµ
  if (photos.length === 0) {
    const demoNumber = (props.item.id % 4) + 1
    photos.push({
      url: `/images/masters/demo-${demoNumber}.jpg`,
      alt: 'Р”РµРјРѕ С„РѕС‚Рѕ'
    })
  }
  
  return photos
})

// URL С‚РµРєСѓС‰РµРіРѕ РёР·РѕР±СЂР°Р¶РµРЅРёСЏ
const currentImageUrl = computed(() => {
  return processedPhotos.value[currentPhotoIndex.value]?.url || '/images/masters/demo-1.jpg'
})

// Р¤СѓРЅРєС†РёРё РґР»СЏ РїСЂРѕРєСЂСѓС‚РєРё С„РѕС‚Рѕ
const startImagePreview = () => {
  if (processedPhotos.value.length > 1) {
    isHovering.value = true
  }
}

const stopImagePreview = () => {
  isHovering.value = false
  currentPhotoIndex.value = 0 // Р’РѕР·РІСЂР°С‰Р°РµРјСЃСЏ Рє РїРµСЂРІРѕРјСѓ С„РѕС‚Рѕ
}

const handleMouseMove = (event) => {
  if (!isHovering.value || processedPhotos.value.length <= 1) return
  
  const rect = event.currentTarget.getBoundingClientRect()
  const x = event.clientX - rect.left
  const width = rect.width
  
  // РћРїСЂРµРґРµР»СЏРµРј РёРЅРґРµРєСЃ С„РѕС‚Рѕ РЅР° РѕСЃРЅРѕРІРµ РїРѕР·РёС†РёРё РјС‹С€Рё
  const photoIndex = Math.floor((x / width) * processedPhotos.value.length)
  const clampedIndex = Math.max(0, Math.min(photoIndex, processedPhotos.value.length - 1))
  
  if (clampedIndex !== currentPhotoIndex.value) {
    currentPhotoIndex.value = clampedIndex
  }
}

const handleImageError = (event) => {
  
  // Р•СЃР»Рё СЌС‚Рѕ СѓР¶Рµ fallback РёР·РѕР±СЂР°Р¶РµРЅРёРµ, РїРѕРєР°Р·С‹РІР°РµРј placeholder
  if (event.target.src.includes('demo-')) {
    event.target.src = '/images/default-avatar.svg'
  } else {
    // РџСЂРѕР±СѓРµРј РґСЂСѓРіРѕРµ demo РёР·РѕР±СЂР°Р¶РµРЅРёРµ
    const demoNumber = Math.floor(Math.random() * 4) + 1
    event.target.src = `/images/masters/demo-${demoNumber}.jpg`
  }
}
</script>

<style scoped>
/* РћСЃРЅРѕРІРЅС‹Рµ СЂР°Р·РјРµСЂС‹ РІ СЃС‚РёР»Рµ Ozon */
.item-image-section {
  @apply relative flex-shrink-0;
  width: 160px;  /* РЈРІРµР»РёС‡РёРІР°РµРј С€РёСЂРёРЅСѓ */
  height: 240px; /* РЈРІРµР»РёС‡РёРІР°РµРј РІС‹СЃРѕС‚Сѓ РґР»СЏ РјРµСЃС‚Р° РїРѕРґ РёРЅРґРёРєР°С‚РѕСЂР°РјРё */
  background: transparent; /* РЈР±РёСЂР°РµРј Р»СЋР±РѕР№ С„РѕРЅ */
}

.item-image-container {
  @apply block w-full;
  height: 213px; /* Р’С‹СЃРѕС‚Р° СЃР°РјРѕРіРѕ РёР·РѕР±СЂР°Р¶РµРЅРёСЏ */
}

.item-image-wrapper {
  @apply relative w-full h-full overflow-hidden;
  @apply cursor-pointer transition-all duration-300;
}

/* РЎС‚РёР»СЊ Ozon - Р±РѕР»РµРµ СЃРєСЂСѓРіР»РµРЅРЅС‹Рµ СѓРіР»С‹ */
.item-image-wrapper.ozon-style {
  border-radius: 16px; /* Р‘РѕР»РµРµ СЃРєСЂСѓРіР»РµРЅРЅС‹Рµ СѓРіР»С‹ РєР°Рє РЅР° Ozon */
  @apply shadow-sm hover:shadow-md;
}

.item-image {
  @apply w-full h-full object-cover transition-all duration-200;
}

/* РРЅРґРёРєР°С‚РѕСЂС‹ РџРћР” С„РѕС‚Рѕ РІ СЃС‚РёР»Рµ Ozon */
.slider-indicators.ozon-indicators {
  @apply flex gap-1 justify-center mt-2;
  width: 100%;
  background: transparent; /* РЈР±РёСЂР°РµРј Р»СЋР±РѕР№ С„РѕРЅ */
}

.slider-dot.ozon-dot {
  width: 6px;
  height: 6px;
  @apply rounded-full transition-all duration-200;
  background-color: rgba(59, 130, 246, 0.4); /* РЎРёРЅРёР№ С†РІРµС‚ РєР°Рє РЅР° Ozon */
}

.slider-dot.ozon-dot.active {
  background-color: #3B82F6; /* РЇСЂРєРёР№ СЃРёРЅРёР№ РґР»СЏ Р°РєС‚РёРІРЅРѕР№ С‚РѕС‡РєРё */
  transform: scale(1.2);
}

/* Р­С„С„РµРєС‚С‹ РїСЂРё РЅР°РІРµРґРµРЅРёРё */
.item-image-wrapper:hover {
  @apply shadow-lg;
}

/* РџР»Р°РІРЅРѕРµ РїРѕСЏРІР»РµРЅРёРµ РёРЅРґРёРєР°С‚РѕСЂРѕРІ */
.slider-indicators {
  @apply transition-opacity duration-200;
  opacity: 0.8;
}

.item-image-section:hover .slider-indicators {
  opacity: 1;
}

/* Responsive РґР»СЏ РјРѕР±РёР»СЊРЅС‹С… */
@media (max-width: 768px) {
  .item-image-section {
    width: 140px;
    height: 214px; /* 187px РёР·РѕР±СЂР°Р¶РµРЅРёРµ + 27px РґР»СЏ РёРЅРґРёРєР°С‚РѕСЂРѕРІ */
  }
  
  .item-image-container {
    height: 187px; /* РџСЂРѕРїРѕСЂС†РёРё 3:4 РґР»СЏ РјРѕР±РёР»СЊРЅС‹С… */
  }
}
</style>

