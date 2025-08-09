<!-- resources/js/Components/Masters/MasterGallery/GalleryImage.vue -->
<template>
  <div class="gallery-image relative">
    <!-- Контейнер изображения с соотношением сторон -->
    <div class="aspect-[3/4] relative overflow-hidden bg-gray-100">
      <img 
        :src="src"
        :alt="alt"
        class="w-full h-full object-cover cursor-pointer transition-transform duration-300 hover:scale-105"
        @click="$emit('click')"
        @error="$emit('error', $event)"
      >
      
      <!-- Бейджи -->
      <div class="absolute top-4 left-4 space-y-2">
        <TransitionGroup name="badge">
          <span 
            v-if="isPremium" 
            key="premium"
            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gradient-to-r from-amber-400 to-amber-500 text-white shadow-lg backdrop-blur-sm"
          >
            ⭐ ПРЕМИУМ
          </span>
          <span 
            v-if="isVerified" 
            key="verified"
            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-500 text-white shadow-lg backdrop-blur-sm"
          >
            ✓ Проверен
          </span>
        </TransitionGroup>
      </div>
      
      <!-- Иконка увеличения при наведении -->
      <div class="absolute inset-0 flex items-center justify-center opacity-0 hover:opacity-100 transition-opacity duration-300 bg-black/20">
        <div class="bg-white/90 backdrop-blur-sm p-3 rounded-full">
          <svg class="w-8 h-8 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" />
          </svg>
        </div>
      </div>
    </div>
    
    <!-- Водяной знак для премиум -->
    <div v-if="isPremium" class="absolute bottom-4 right-4">
      <div class="bg-black/30 backdrop-blur-sm text-white px-2 py-1 rounded text-xs font-medium">
        Premium Photo
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  src: {
    type: String,
    required: true
  },
  alt: {
    type: String,
    default: ''
  },
  isPremium: {
    type: Boolean,
    default: false
  },
  isVerified: {
    type: Boolean,
    default: false
  }
})

defineEmits(['click', 'error'])
</script>

<style scoped>
/* Анимация для бейджей */
.badge-enter-active,
.badge-leave-active {
  transition: all 0.3s ease;
}

.badge-enter-from {
  opacity: 0;
  transform: translateX(-20px);
}

.badge-leave-to {
  opacity: 0;
  transform: translateX(20px);
}

/* Соотношение сторон для разных экранов */
@media (min-width: 1024px) {
  .aspect-\[3\/4\] {
    aspect-ratio: 3 / 4;
  }
}

@media (max-width: 1023px) {
  .aspect-\[3\/4\] {
    aspect-ratio: 4 / 3;
  }
}
</style>