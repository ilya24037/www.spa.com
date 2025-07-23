<template>
    <div class="form-field">
        <!-- Новый VideoUploader в стиле Авито -->
        <AvitoVideoUploader 
            v-model="form.video"
            :max-file-size="50 * 1024 * 1024"
        />
    </div>
</template>

<script setup>
import AvitoVideoUploader from '../../Features/PhotoUploader/VideoUploader.vue'

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