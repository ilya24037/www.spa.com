<!-- Сетка фотографий с drag&drop (десктоп) и кнопками (мобильные) -->
<template>
  <div class="photo-grid-container">
    <!-- Счетчик фото -->
    <div class="flex justify-between items-center mb-2">
      <span class="text-sm text-gray-600">
        Загружено {{ photos.length }} фото
      </span>
      <span v-if="!isMobile && photos.length > 1" class="text-xs text-gray-500">
        Перетащите для изменения порядка
      </span>
    </div>

    <!-- Сетка фотографий -->
    <div class="grid grid-cols-3 gap-3 sm:grid-cols-4 md:grid-cols-5">
      <div
        v-for="(photo, index) in photos"
        :key="photo.id || index"
        class="photo-item-wrapper relative"
        :class="{
          'opacity-50': draggedIndex === index,
          'ring-2 ring-blue-500': dragOverIndex === index
        }"
        :draggable="!isMobile"
        @dragstart="!isMobile && handleDragStart(index, $event)"
        @dragover.prevent="!isMobile && handleDragOver(index, $event)"
        @drop.prevent="!isMobile && handleDragDrop(index, $event)"
        @dragend="!isMobile && handleDragEnd($event)"
      >

        <!-- Компонент фото -->
        <PhotoItem
          :photo="photo"
          :index="index"
          :is-main="index === 0"
          @rotate="$emit('rotate', index)"
          @remove="$emit('remove', index)"
        />

        <!-- Индикатор главного фото -->
        <div v-if="index === 0" class="mt-1 text-center">
          <span class="bg-green-500 text-white text-xs px-2 py-1 rounded font-medium shadow-sm">
            Главное фото
          </span>
        </div>

        <!-- Кнопки для мобильных -->
        <div v-if="isMobile && photos.length > 1" class="mobile-controls absolute bottom-2 left-2 right-2 flex justify-between gap-1">
          <button
            v-if="index > 0"
            @click="movePhoto(index, index - 1)"
            class="bg-white/90 rounded p-1 shadow hover:bg-white"
          >
            <svg class="w-4 h-4"
                 fill="none"
                 stroke="currentColor"
                 viewBox="0 0 24 24">
              <path stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M15 19l-7-7 7-7" />
            </svg>
          </button>
          
          <button
            v-if="index > 0"
            @click="makeMain(index)"
            class="bg-white/90 rounded p-1 shadow hover:bg-white flex-1"
          >
            <svg class="w-4 h-4 mx-auto" fill="currentColor" viewBox="0 0 20 20">
              <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
            </svg>
          </button>

          <button
            v-if="index < photos.length - 1"
            @click="movePhoto(index, index + 1)"
            class="bg-white/90 rounded p-1 shadow hover:bg-white"
          >
            <svg class="w-4 h-4"
                 fill="none"
                 stroke="currentColor"
                 viewBox="0 0 24 24">
              <path stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M9 5l7 7-7 7" />
            </svg>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import PhotoItem from './PhotoItem.vue'
import type { Photo } from '../../model/types'

interface Props {
  photos: Photo[]
  draggedIndex: number | null
  dragOverIndex: number | null
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'update:photos': [photos: Photo[]]
  'rotate': [index: number]
  'remove': [index: number]
  'dragstart': [index: number]
  'dragover': [index: number]
  'drop': [index: number]
  'dragend': []
}>()

// Определение мобильного устройства
const isMobile = ref(false)

const checkMobile = () => {
  isMobile.value = window.innerWidth < 768
}

onMounted(() => {
  checkMobile()
  window.addEventListener('resize', checkMobile)
})

onUnmounted(() => {
  window.removeEventListener('resize', checkMobile)
})

// Обработчики drag&drop
const handleDragStart = (index: number, event: DragEvent) => {
  // Не блокируем dragstart - он нужен для перетаскивания фото
  emit('dragstart', index)
}

const handleDragOver = (index: number, event: DragEvent) => {
  // Только preventDefault для разрешения drop
  event.preventDefault()
  emit('dragover', index)
}

const handleDragDrop = (index: number, event: DragEvent) => {
  // Только preventDefault для разрешения drop
  event.preventDefault()
  emit('drop', index)
}

const handleDragEnd = (event: DragEvent) => {
  // Не блокируем dragend
  emit('dragend')
}

// Функции для мобильной версии
const movePhoto = (fromIndex: number, toIndex: number) => {
  if (toIndex < 0 || toIndex >= props.photos.length) return
  
  const newPhotos = [...props.photos]
  const [movedPhoto] = newPhotos.splice(fromIndex, 1)
  newPhotos.splice(toIndex, 0, movedPhoto)
  
  // ❌ УБИРАЕМ этот эмит - он создает дублирование!
  // emit('update:photos', newPhotos)
  
  // ✅ Вместо этого эмитим событие drag&drop для единообразия
  emit('drop', toIndex)
}

const makeMain = (index: number) => {
  if (index === 0) return
  // ✅ Используем movePhoto, который уже эмитит 'drop'
  movePhoto(index, 0)
}
</script>

<style scoped>
.photo-item-wrapper {
  transition: transform 0.2s, opacity 0.2s;
  cursor: move;
}

.photo-item-wrapper:hover {
  transform: scale(1.02);
}

.mobile-controls button {
  transition: all 0.2s;
}

.mobile-controls button:active {
  transform: scale(0.95);
}

@media (max-width: 767px) {
  .photo-item-wrapper {
    cursor: default;
  }
}
</style>