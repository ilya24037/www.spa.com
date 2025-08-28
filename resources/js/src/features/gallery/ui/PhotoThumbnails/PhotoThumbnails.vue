<template>
  <div class="grid grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2">
    <button
      v-for="(image, index) in images"
      :key="image.id"
      class="aspect-square rounded-lg overflow-hidden bg-gray-500 hover:opacity-80 transition-opacity"
      :aria-label="`Открыть изображение ${index + 1}`"
      @click="openViewer(index)"
    >
      <img
        :src="image.thumbnail || image.url"
        :alt="image.alt || `Изображение ${index + 1}`"
        class="w-full h-full object-cover"
        loading="lazy"
      >
    </button>
  </div>
</template>

<script setup lang="ts">
import type { Photo } from '../../model/types'
import { useGalleryStore } from '@/src/features/gallery/model/gallery.store'

interface Props {
  images: Photo[]
}

const props = defineProps<Props>()

// Store
const galleryStore = useGalleryStore()

// Methods
const openViewer = (index: number) => {
    galleryStore.openGallery(props.images, index)
}
</script>