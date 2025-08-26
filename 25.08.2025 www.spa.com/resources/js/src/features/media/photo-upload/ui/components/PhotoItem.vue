<!-- Компонент отдельной фотографии -->
<template>
  <div class="photo-item relative group" :class="{ 'ring-2 ring-blue-500': isMain }">
    <div class="aspect-square rounded-lg overflow-hidden bg-gray-100 flex items-center justify-center">
      <img 
        :src="photoUrl" 
        :alt="`Фото ${index + 1}`"
        class="w-full h-full object-contain"
        :style="{ transform: `rotate(${photo.rotation || 0}deg)` }"
        loading="lazy"
        decoding="async"
        :aria-label="`Фото номер ${index + 1}${isMain ? ', основное фото' : ''}`"
      />
      
      <!-- Контролы -->
      <div class="absolute top-2 right-2 flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
        <button 
          type="button"
          @click="$emit('rotate')" 
          class="p-1.5 bg-white rounded shadow hover:bg-gray-100"
          title="Повернуть"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
        </button>
        
        <button 
          type="button"
          @click="$emit('remove')" 
          class="p-1.5 bg-white rounded shadow hover:bg-red-50"
          title="Удалить"
        >
          <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
          </svg>
        </button>
      </div>
      

    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { Photo } from '../../model/types'

interface Props {
  photo: Photo
  index: number
  isMain?: boolean
}

const props = defineProps<Props>()

const emit = defineEmits<{
  rotate: []
  remove: []
}>()

const photoUrl = computed(() => {
  if (props.photo.preview) return props.photo.preview
  if (props.photo.url) return props.photo.url
  if (props.photo.file) return URL.createObjectURL(props.photo.file)
  return ''
})
</script>