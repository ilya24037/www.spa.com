<template>
    <div class="form-field">
        <div class="radio-group">
            <BaseRadio
                v-model="form.work_format"
                value="individual"
                label="Индивидуально"
                description="Вы работаете самостоятельно"
            />
            
            <BaseRadio
                v-model="form.work_format"
                value="salon"
                label="Салон"
                description="У вас есть отдельное помещение и штат мастеров"
            />
        </div>
        
        <div v-if="errors.work_format" class="error-message">
            {{ errors.work_format }}
        </div>
    </div>
</template>

<script setup>
import BaseRadio from '../../UI/BaseRadio.vue'

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

<style scoped>
.radio-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.error-message {
    margin-top: 8px;
    color: #ff4d4f;
    font-size: 14px;
    line-height: 1.4;
}
</style> 