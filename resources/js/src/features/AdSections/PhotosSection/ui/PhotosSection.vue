<template>
  <div class="photos-section">
    <h2 class="form-group-title">Фотографии</h2>
    <input type="file" multiple accept="image/*" @change="onFilesChange" />
    <div class="photos-preview">
      <img v-for="(photo, idx) in localPhotos" :key="idx" :src="photoUrl(photo)" class="photo-thumb" />
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
const props = defineProps({
  photos: { type: Array, default: () => [] },
  errors: { type: Object, default: () => ({}) }
})
const emit = defineEmits(['update:photos'])
const localPhotos = ref([...props.photos])
watch(() => props.photos, val => { localPhotos.value = [...val] })
const onFilesChange = (e) => {
  const files = Array.from(e.target.files)
  localPhotos.value = files
  emit('update:photos', files)
}
const photoUrl = (file) => {
  if (typeof file === 'string') return file
  if (file instanceof File) return URL.createObjectURL(file)
  return ''
}
</script>

<style scoped>
.photos-section { background: white; border-radius: 8px; padding: 20px; }
.form-group-title { font-size: 18px; font-weight: 600; color: #333; margin-bottom: 16px; }
.photos-preview { display: flex; gap: 8px; margin-top: 12px; }
.photo-thumb { width: 80px; height: 80px; object-fit: cover; border-radius: 6px; border: 1px solid #ddd; }
</style> 