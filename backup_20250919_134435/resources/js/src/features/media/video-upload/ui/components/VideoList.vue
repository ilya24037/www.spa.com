<template>
  <div class="video-list">
    <!-- Счетчик видео и подсказка (как у фото) -->
    <div v-if="safeVideos.length > 0" class="flex justify-between items-center mb-2">
      <span class="text-sm text-gray-600">
        Загружено {{ safeVideos.length }} видео
      </span>
      <span v-if="safeVideos.length > 1" class="text-xs text-gray-500">
        Перетащите для изменения порядка
      </span>
    </div>
    
    <!-- Сетка видео как у фотографий -->
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
      <TransitionGroup name="list">
        <div
          v-for="(video, index) in safeVideos"
          :key="video.id"
          class="video-item-wrapper relative"
          :class="{
            'opacity-50': props.draggedIndex === index,
            'ring-2 ring-blue-500': props.dragOverIndex === index
          }"
          :draggable="true"
          @dragstart="$emit('dragstart', index)"
          @dragover.prevent="$emit('dragover', index)"
          @drop.prevent="$emit('drop', index)"
          @dragend="$emit('dragend')"
        >
          <!-- Компонент видео -->
          <VideoItem
            :video="video"
            :index="index"
            @remove="handleRemove(video.id)"
          />

          <!-- Индикатор главного видео -->
          <div v-if="index === 0" class="mt-2 text-center">
            <span class="bg-green-500 text-white text-xs px-2 py-1 rounded font-medium shadow-sm">
              Главное видео
            </span>
          </div>
        </div>
      </TransitionGroup>
    </div>
    
    <!-- Empty state с явной проверкой -->
    <div v-if="safeVideos === null || safeVideos === undefined || safeVideos.length === 0" class="text-center py-4 text-gray-500">
      Видео не загружены
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import VideoItem from './VideoItem.vue'
import type { Video } from '../../model/types'

interface Props {
  videos: Video[]
  draggedIndex?: number | null
  dragOverIndex?: number | null
}

const props = defineProps<Props>()

const emit = defineEmits<{
  remove: [id: string | number]
  dragstart: [index: number]
  dragover: [index: number]
  drop: [index: number]
  dragend: []
}>()

// Computed для защиты от null/undefined (требование CLAUDE.md)
const safeVideos = computed(() => {
  // Явная проверка на null и undefined
  if (props.videos === null || props.videos === undefined) {
    return []
  }
  return props.videos
})

// Обработчик удаления с явной проверкой
const handleRemove = (id: string | number) => {
  // Явная проверка ID на null и undefined
  if (id !== null && id !== undefined) {
    emit('remove', id)
  }
}
</script>

<style scoped>
.list-enter-active,
.list-leave-active {
  transition: all 0.3s ease;
}
.list-enter-from {
  opacity: 0;
  transform: translateX(-30px);
}
.list-leave-to {
  opacity: 0;
  transform: translateX(30px);
}
</style>