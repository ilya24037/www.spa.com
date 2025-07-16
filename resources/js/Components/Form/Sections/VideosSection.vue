<template>
    <div class="form-section">
        <h3 class="form-section-title">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
            </svg>
            Видео
        </h3>
        
        <div class="form-row">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Добавить видео
                </label>
                <VideoUploader 
                    :videos="form.videos || []"
                    @update:videos="updateVideos"
                    :max-videos="5"
                    :errors="errors.videos"
                />
                <p class="mt-1 text-sm text-gray-500">
                    Максимум 5 видео. Поддерживаются форматы: MP4, MOV, AVI.
                </p>
                <p v-if="errors.videos" class="mt-1 text-sm text-red-600">
                    {{ errors.videos }}
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import VideoUploader from '@/Components/Form/Upload/VideoUploader.vue'

// Props
const props = defineProps({
    form: {
        type: Object,
        required: true
    },
    errors: {
        type: Object,
        default: () => ({})
    }
})

// Обновление видео
const updateVideos = (videos) => {
    props.form.videos = videos
}
</script> 