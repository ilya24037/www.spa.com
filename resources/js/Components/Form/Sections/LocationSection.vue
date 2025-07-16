<template>
    <div class="form-section">
        <h3 class="form-section-title">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
            Где вы оказываете услуги
        </h3>
        
        <div class="form-row">
            <div class="w-full">
                <label class="block text-sm font-medium text-gray-700 mb-3">
                    Где вы оказываете услуги? *
                </label>
                <div class="space-y-3">
                    <label class="flex items-center">
                        <input 
                            v-model="form.service_location"
                            type="checkbox"
                            value="home"
                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                        >
                        <span class="ml-2 text-sm text-gray-700">У заказчика дома</span>
                    </label>
                    <label class="flex items-center">
                        <input 
                            v-model="form.service_location"
                            type="checkbox"
                            value="salon"
                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                        >
                        <span class="ml-2 text-sm text-gray-700">У себя дома</span>
                    </label>
                    <label class="flex items-center">
                        <input 
                            v-model="form.service_location"
                            type="checkbox"
                            value="office"
                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                        >
                        <span class="ml-2 text-sm text-gray-700">В салоне</span>
                    </label>
                    <label class="flex items-center">
                        <input 
                            v-model="form.service_location"
                            type="checkbox"
                            value="coworking"
                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                        >
                        <span class="ml-2 text-sm text-gray-700">В коворкинге</span>
                    </label>
                    <label class="flex items-center">
                        <input 
                            v-model="form.service_location"
                            type="checkbox"
                            value="clinic"
                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                        >
                        <span class="ml-2 text-sm text-gray-700">В клинике</span>
                    </label>
                </div>
                <div v-if="errors.service_location" class="mt-1 text-sm text-red-600">
                    {{ errors.service_location }}
                </div>
            </div>
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

// Инициализируем массив если его нет
if (!props.form.service_location) {
    props.form.service_location = []
}
</script> 