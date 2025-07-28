<template>
    <div class="form-field">
        <label class="field-label">Название объявления</label>
        <div class="input-wrapper">
            <input
                v-model="localTitle"
                @input="emitTitle"
                type="text"
                name="title"
                id="title"
                data-field="title"
                class="avito-input"
                :class="{ 'border-red-500 focus:ring-red-500 focus:border-red-500': errors.title }"
                placeholder=""
                maxlength="60"
                required
            >
            <button 
                v-if="localTitle" 
                type="button" 
                @click="clearTitle" 
                class="clear-button"
            >
                <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9.00001 10.8428L16.15 17.9928L17.5642 16.5786L10.4142 9.42857L17.5643 2.27851L16.1501 0.864296L9.00001 8.01436L1.84994 0.864296L0.43573 2.27851L7.58579 9.42857L0.435787 16.5786L1.85 17.9928L9.00001 10.8428Z" fill="currentColor"></path>
                </svg>
            </button>
        </div>
        
        <p class="field-hint">
            Например, «Маникюр, педикюр и наращивание ногтей» или «Ремонт квартир под ключ»
        </p>
        
        <div v-if="errors.title" class="error-message">
            {{ errors.title }}
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
    title: { type: String, default: '' },
    errors: { type: Object, default: () => ({}) }
})

const emit = defineEmits(['update:title'])

const localTitle = ref(props.title)

watch(() => props.title, (val) => {
    localTitle.value = val || ''
})

const emitTitle = () => {
    emit('update:title', localTitle.value)
}

const clearTitle = () => {
    localTitle.value = ''
    emitTitle()
}
</script>

<style scoped>
.input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.avito-input {
    width: 100%;
    padding: 12px 40px 12px 16px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    line-height: 1.5;
    color: #1a1a1a;
    background: #f5f5f5;
    transition: all 0.2s ease;
    font-family: inherit;
}

.avito-input:focus {
    outline: none;
    background: #fff;
    box-shadow: 0 0 0 2px rgba(24, 144, 255, 0.2);
}

.clear-button {
    position: absolute;
    right: 12px;
    width: 18px;
    height: 18px;
    border: none;
    background: none;
    cursor: pointer;
    color: #8c8c8c;
    transition: color 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1;
}

.clear-button:hover {
    color: #1a1a1a;
}

.field-hint {
    margin-top: 8px;
    font-size: 14px;
    color: #8c8c8c;
    line-height: 1.4;
}

.error-message {
    margin-top: 8px;
    color: #ff4d4f;
    font-size: 14px;
    line-height: 1.4;
}
</style> 