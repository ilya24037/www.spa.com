<template>
  <div class="videos-section">
    <h2 class="form-group-title">Видео</h2>
    <label for="video-upload" class="sr-only">Загрузить видео</label>
    <input type="file"
           id="video-upload"
           name="videos[]"
           multiple
           accept="video/*"
           @change="onFilesChange" />
    <div class="videos-preview">
      <video v-for="(video, idx) in localVideos"
             :key="idx"
             :src="videoUrl(video)"
             class="video-thumb"
             controls />
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
const props = defineProps({
  videos: { type: Array, default: () => [] },
  errors: { type: Object, default: () => ({}) }
})
const emit = defineEmits(['update:videos'])
const localVideos = ref([...props.videos])
watch(() => props.videos, val => { localVideos.value = [...val] })
onMounted(() => { if (props.videos) localVideos.value = [...props.videos] })
const onFilesChange = (e) => {
  const files = Array.from(e.target.files)
  localVideos.value = files
  emit('update:videos', files)
}
const videoUrl = (file) => {
  if (typeof file === 'string') return file
  if (file instanceof File) return URL.createObjectURL(file)
  return ''
}
</script>

<style scoped>
.videos-section { background: white; border-radius: 8px; padding: 20px; }
.form-group-title { font-size: 18px; font-weight: 600; color: #333; margin-bottom: 16px; }
.videos-preview { display: flex; gap: 8px; margin-top: 12px; }
.video-thumb { width: 120px; height: 80px; object-fit: cover; border-radius: 6px; border: 1px solid #ddd; }
</style> 