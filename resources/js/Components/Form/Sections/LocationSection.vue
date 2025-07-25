<template>
    <div class="form-field">
        <div class="checkbox-group">
            <div class="checkbox-item" @click="toggleLocation('home')">
                <div 
                    class="custom-checkbox"
                    :class="{ 'checked': form.service_location.includes('home') }"
                >
                    <svg class="check-icon" width="100%" height="100%" viewBox="0 0 10 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M1 4.35714L3.4 6.5L9 1.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path>
                    </svg>
                </div>
                <input 
                    v-model="form.service_location"
                    type="checkbox"
                    value="home"
                    style="display: none;"
                >
                <span class="checkbox-label">У заказчика дома</span>
            </div>
            
            <div class="checkbox-item" @click="toggleLocation('salon')">
                <div 
                    class="custom-checkbox"
                    :class="{ 'checked': form.service_location.includes('salon') }"
                >
                    <svg class="check-icon" width="100%" height="100%" viewBox="0 0 10 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M1 4.35714L3.4 6.5L9 1.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path>
                    </svg>
                </div>
                <input 
                    v-model="form.service_location"
                    type="checkbox"
                    value="salon"
                    style="display: none;"
                >
                <span class="checkbox-label">У себя дома</span>
            </div>
            
            <div class="checkbox-item" @click="toggleLocation('office')">
                <div 
                    class="custom-checkbox"
                    :class="{ 'checked': form.service_location.includes('office') }"
                >
                    <svg class="check-icon" width="100%" height="100%" viewBox="0 0 10 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M1 4.35714L3.4 6.5L9 1.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path>
                    </svg>
                </div>
                <input 
                    v-model="form.service_location"
                    type="checkbox"
                    value="office"
                    style="display: none;"
                >
                <span class="checkbox-label">В офисе</span>
            </div>
        </div>
        
        <div v-if="errors.service_location" class="error-message">
            {{ errors.service_location }}
        </div>
    </div>
</template>

<script setup>
import { watchEffect } from 'vue'

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

// Инициализируем массив если его нет
const initializeServiceLocation = () => {
    if (!Array.isArray(props.form.service_location)) {
        props.form.service_location = []
    }
}

// Функция переключения места оказания услуги
const toggleLocation = (location) => {
    if (!props.form.service_location.includes(location)) {
        props.form.service_location.push(location)
    } else {
        const index = props.form.service_location.indexOf(location)
        props.form.service_location.splice(index, 1)
    }
}

// Вызываем инициализацию сразу
initializeServiceLocation()

// Отслеживаем изменения через watchEffect
watchEffect(() => {
    initializeServiceLocation()
})
</script>

<style scoped>
.checkbox-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.checkbox-item {
    display: flex;
    align-items: center;
    cursor: pointer;
    gap: 12px;
    padding: 8px 0;
    user-select: none;
}

.custom-checkbox {
    width: 20px;
    height: 20px;
    border: 2px solid #d9d9d9;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    background: #fff;
    flex-shrink: 0;
    cursor: pointer;
}

.custom-checkbox:hover {
    border-color: #8c8c8c;
}

.custom-checkbox.checked {
    background: #007bff;
    border-color: #007bff;
}

.check-icon {
    width: 12px;
    height: 10px;
    color: #fff;
    opacity: 0;
    transition: opacity 0.2s ease;
}

.custom-checkbox.checked .check-icon {
    opacity: 1;
}

.checkbox-label {
    font-size: 16px;
    color: #1a1a1a;
    font-weight: 400;
    line-height: 1.4;
    cursor: pointer;
    user-select: none;
}

.error-message {
    margin-top: 8px;
    color: #ff4d4f;
    font-size: 14px;
    line-height: 1.4;
}
</style> 