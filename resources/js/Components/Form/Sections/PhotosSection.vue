<template>
    <div class="form-field">
        <!-- Новый PhotoUploader в стиле Авито -->
        <AvitoPhotoUploader 
            v-model="form.photos"
            :max-files="10"
        />
    </div>
</template>

<script setup>
import AvitoPhotoUploader from '../../Features/PhotoUploader/index.vue'

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
</script> 