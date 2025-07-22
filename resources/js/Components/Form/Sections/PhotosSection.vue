<template>
    <div class="form-field">
        <PhotoUploader 
            :photos="form.photos || []"
            @update:photos="updatePhotos"
            :max-photos="20"
            :errors="errors.photos"
        />
        
        <p class="field-hint">
            Перетащите фото сюда или нажмите для выбора
        </p>
        <p class="field-hint">
            До 10 фото, формат JPG, PNG
        </p>
        <p class="field-hint">
            Максимум 50 фотографий. Первая фотография будет использоваться как главная
        </p>
        
        <div v-if="errors.photos" class="error-message">
            {{ errors.photos }}
        </div>
    </div>
</template>

<script setup>
import PhotoUploader from '../Upload/PhotoUploader.vue'

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

const updatePhotos = (photos) => {
    props.form.photos = photos
}
</script> 