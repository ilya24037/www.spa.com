<template>
  <div v-if="photos.length > 1" :class="CONTAINER_CLASSES">
    <button
      v-for="(photo, index) in photos"
      :key="photo.id || index"
      @click="$emit(`"select`", index)"
      :class="getThumbnailClasses(index)"
    >
      <img
        :src="getThumbnailUrl(photo)"
        :alt="`Миниатюра ${index + 1}`"
        :class="IMAGE_CLASSES"
      >
    </button>
  </div>
</template>

<script setup>
import { computed } from "vue"

const CONTAINER_CLASSES = "flex gap-2 overflow-x-auto py-2"
const THUMBNAIL_BASE_CLASSES = "flex-shrink-0 w-16 h-16 rounded overflow-hidden border-2 transition-colors cursor-pointer"
const THUMBNAIL_ACTIVE_CLASSES = "border-blue-500" 
const THUMBNAIL_INACTIVE_CLASSES = "border-gray-200 hover:border-gray-300"
const IMAGE_CLASSES = "w-full h-full object-cover"

const props = defineProps({
  photos: { type: Array, required: true },
  currentIndex: { type: Number, default: 0 }
})

defineEmits(["select"])

const getThumbnailUrl = (photo) => {
  return photo.thumbnail || photo.small || photo.url
}

const getThumbnailClasses = (index) => {
  return [
    THUMBNAIL_BASE_CLASSES,
    index === props.currentIndex ? THUMBNAIL_ACTIVE_CLASSES : THUMBNAIL_INACTIVE_CLASSES
  ].join(" ")
}
</script>