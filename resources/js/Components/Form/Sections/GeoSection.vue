<template>
    <div class="form-section">
        <h3 class="form-section-title">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
            Местоположение
        </h3>
        
        <div class="form-row two-columns">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Город *
                </label>
                <GeoSuggest 
                    v-model="form.city"
                    placeholder="Выберите город"
                    :error="errors.city"
                />
                <p v-if="errors.city" class="mt-1 text-sm text-red-600">
                    {{ errors.city }}
                </p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Район
                </label>
                <input 
                    type="text" 
                    v-model="form.district"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Например, Центральный"
                >
                <p v-if="errors.district" class="mt-1 text-sm text-red-600">
                    {{ errors.district }}
                </p>
            </div>
        </div>
        
        <div class="form-row">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Адрес
                </label>
                <textarea 
                    v-model="form.address"
                    rows="2"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Укажите адрес или ближайшие ориентиры"
                ></textarea>
                <p class="mt-1 text-sm text-gray-500">
                    Точный адрес не публикуется. Клиенты увидят только район.
                </p>
                <p v-if="errors.address" class="mt-1 text-sm text-red-600">
                    {{ errors.address }}
                </p>
            </div>
        </div>
        
        <div class="form-row">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Условия работы
                </label>
                <div class="space-y-2">
                    <label class="flex items-center">
                        <input 
                            type="checkbox" 
                            v-model="form.work_at_client"
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        >
                        <span class="ml-2 text-sm text-gray-700">Выезд к клиенту</span>
                    </label>
                    <label class="flex items-center">
                        <input 
                            type="checkbox" 
                            v-model="form.work_at_salon"
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        >
                        <span class="ml-2 text-sm text-gray-700">Работа в салоне</span>
                    </label>
                    <label class="flex items-center">
                        <input 
                            type="checkbox" 
                            v-model="form.work_at_hotel"
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        >
                        <span class="ml-2 text-sm text-gray-700">Работа в отеле</span>
                    </label>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import GeoSuggest from '@/Components/Form/Geo/GeoSuggest.vue'

// Props
defineProps({
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