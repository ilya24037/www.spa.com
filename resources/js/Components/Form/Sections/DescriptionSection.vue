<template>
    <div class="form-field">
        <textarea 
            v-model="form.description"
            rows="6"
            class="avito-textarea"
            :class="{ 'border-red-500 focus:ring-red-500 focus:border-red-500': errors.description }"
            placeholder="Расскажите о своих услугах подробнее. Опишите ваш опыт, используемые техники, материалы и оборудование..."
            required
        ></textarea>
        
        <div class="flex justify-between items-center mt-2">
            <p class="field-hint">
                Минимум 50 символов. Расскажите о своих услугах, опыте и преимуществах
            </p>
            <span class="text-xs text-gray-400">
                {{ (form.description || '').length }}/1000
            </span>
        </div>
        
        <div v-if="errors.description" class="error-message">
            {{ errors.description }}
        </div>
    </div>
</template>

<script setup>
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
.avito-textarea {
    width: 100%;
    padding: 16px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    line-height: 1.5;
    color: #1a1a1a;
    background: #f5f5f5;
    transition: all 0.2s ease;
    font-family: inherit;
    resize: none;
}

.avito-textarea:focus {
    outline: none;
    background: #fff;
    box-shadow: 0 0 0 2px rgba(24, 144, 255, 0.2);
}
</style> 