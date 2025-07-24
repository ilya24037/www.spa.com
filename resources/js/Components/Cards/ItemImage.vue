<template>
  <div class="item-image-section">
    <a :href="itemUrl" class="item-image-link">
      <div class="item-image-wrapper">
        <img 
          :src="getImageUrl(item.avatar || item.main_image)"
          :alt="item.name"
          class="item-image"
          @error="handleImageError"
        >
        <!-- Индикаторы слайдера (белые точки) -->
        <div v-if="item.photos_count > 1" class="slider-indicators">
          <div 
            v-for="n in Math.min(item.photos_count, 4)" 
            :key="n"
            class="slider-dot"
            :class="{ 'active': n === 1 }"
          ></div>
        </div>
      </div>
    </a>
  </div>
</template>

<script setup>
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

const getImageUrl = (imageUrl) => {
  if (!imageUrl) return '/images/default-avatar.svg'
  if (imageUrl.startsWith('http')) return imageUrl
  return imageUrl.startsWith('/') ? imageUrl : `/${imageUrl}`
}

const handleImageError = (event) => {
  event.target.src = '/images/default-avatar.svg'
}
</script>

<style scoped>
.item-image-section {
  @apply relative flex-shrink-0 w-40 h-32;
}

.item-image-link {
  @apply block w-full h-full;
}

.item-image-wrapper {
  @apply relative w-full h-full rounded-lg overflow-hidden;
}

.item-image {
  @apply w-full h-full object-cover;
}

.slider-indicators {
  @apply absolute bottom-2 left-1/2 transform -translate-x-1/2 flex gap-1;
}

.slider-dot {
  @apply w-2 h-2 rounded-full bg-white bg-opacity-60 transition-all;
}

.slider-dot.active {
  @apply bg-white;
}
</style>