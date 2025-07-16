<template>
    <div class="form-section">
        <h3 class="form-section-title">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
            Ваши клиенты
        </h3>
        
        <div class="form-row">
            <div class="w-full">
                <label class="block text-sm font-medium text-gray-700 mb-3">
                    Кто ваши клиенты? *
                </label>
                <div class="space-y-3">
                    <label class="flex items-center">
                        <input 
                            v-model="form.clients"
                            type="checkbox"
                            value="women"
                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                        >
                        <span class="ml-2 text-sm text-gray-700">Женщины</span>
                    </label>
                    <label class="flex items-center">
                        <input 
                            v-model="form.clients"
                            type="checkbox"
                            value="men"
                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                        >
                        <span class="ml-2 text-sm text-gray-700">Мужчины</span>
                    </label>
                </div>
                <div v-if="errors.clients" class="mt-1 text-sm text-red-600">
                    {{ errors.clients }}
                </div>
            </div>
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

// Вызываем инициализацию сразу
initializeClients()

// Отслеживаем изменения через watchEffect
watchEffect(() => {
    initializeClients()
})
</script> 