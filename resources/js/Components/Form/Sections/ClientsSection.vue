<template>
    <div class="form-field">
        <div class="checkbox-group">
            <div class="checkbox-item" @click="toggleClient('women')">
                <div 
                    class="custom-checkbox"
                    :class="{ 'checked': form.clients.includes('women') }"
                >
                    <svg class="check-icon" width="100%" height="100%" viewBox="0 0 10 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M1 4.35714L3.4 6.5L9 1.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path>
                    </svg>
                </div>
                <input 
                    v-model="form.clients"
                    type="checkbox"
                    value="women"
                    style="display: none;"
                >
                <span class="checkbox-label">Женщины</span>
            </div>
            
            <div class="checkbox-item" @click="toggleClient('men')">
                <div 
                    class="custom-checkbox"
                    :class="{ 'checked': form.clients.includes('men') }"
                >
                    <svg class="check-icon" width="100%" height="100%" viewBox="0 0 10 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M1 4.35714L3.4 6.5L9 1.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path>
                    </svg>
                </div>
                <input 
                    v-model="form.clients"
                    type="checkbox"
                    value="men"
                    style="display: none;"
                >
                <span class="checkbox-label">Мужчины</span>
            </div>
        </div>
        
        <p class="field-hint">
            Выберите пол клиентов, которым вы оказываете услуги
        </p>
        
        <div v-if="errors.clients" class="error-message">
            {{ errors.clients }}
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
const initializeClients = () => {
    if (!props.form.clients) {
        props.form.clients = []
    }
}

// Функция переключения клиента
const toggleClient = (client) => {
    if (!props.form.clients.includes(client)) {
        props.form.clients.push(client)
    } else {
        const index = props.form.clients.indexOf(client)
        props.form.clients.splice(index, 1)
    }
}

// Вызываем инициализацию сразу
initializeClients()

// Отслеживаем изменения через watchEffect
watchEffect(() => {
    initializeClients()
})
</script> 