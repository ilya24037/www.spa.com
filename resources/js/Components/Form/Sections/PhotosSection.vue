<template>
    <div class="form-section">
        <h3 class="form-section-title">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            Фотографии
        </h3>
        
        <div class="form-row">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Добавить фотографии
                </label>
                <PhotoUploader 
                    :photos="form.photos || []"
                    @update:photos="updatePhotos"
                    :max-photos="20"
                    :errors="errors.photos"
                />
                <p class="mt-1 text-sm text-gray-500">
                    Максимум 20 фотографий. Первая фотография будет использована как главная.
                </p>
                <p v-if="errors.photos" class="mt-1 text-sm text-red-600">
                    {{ errors.photos }}
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import PhotoUploader from '@/Components/Form/Upload/PhotoUploader.vue'

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

// Обновление фотографий
const updatePhotos = (photos) => {
    props.form.photos = photos
}
</script> 