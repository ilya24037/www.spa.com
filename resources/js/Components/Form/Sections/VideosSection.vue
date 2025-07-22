<template>
    <div class="form-field">
        <VideoUploader 
            :videos="form.videos || []"
            @update:videos="updateVideos"
            :max-videos="5"
            :errors="errors.videos"
        />
        
        <p class="field-hint">
            Добавьте видео обзор до 50MB
        </p>
        <p class="field-hint">
            Первая фотография будет использоваться как превью
        </p>
        
        <div v-if="errors.videos" class="error-message">
            {{ errors.videos }}
        </div>
    </div>
</template>

<script setup>
import VideoUploader from '../Upload/VideoUploader.vue'

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

const updateVideos = (videos) => {
    props.form.videos = videos
}
</script> 