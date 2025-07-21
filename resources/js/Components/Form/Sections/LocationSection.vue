<template>
    <div class="form-section">
        <h3 class="form-section-title">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
            Местоположение
        </h3>
        
        <div class="form-row">
            <div class="w-full">
                <label class="block text-sm font-medium text-gray-700 mb-3">
                    Где вы оказываете услуги? *
                </label>
                <div class="space-y-3">
                    <label class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors cursor-pointer">
                        <input 
                            v-model="form.service_location"
                            type="checkbox"
                            value="home"
                            class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500 focus:ring-2"
                        >
                        <span class="ml-3 text-sm text-gray-700 font-medium">У заказчика дома</span>
                    </label>
                    <label class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors cursor-pointer">
                        <input 
                            v-model="form.service_location"
                            type="checkbox"
                            value="salon"
                            class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500 focus:ring-2"
                        >
                        <span class="ml-3 text-sm text-gray-700 font-medium">У себя дома</span>
                    </label>
                    <label class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors cursor-pointer">
                        <input 
                            v-model="form.service_location"
                            type="checkbox"
                            value="office"
                            class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500 focus:ring-2"
                        >
                        <span class="ml-3 text-sm text-gray-700 font-medium">В салоне</span>
                    </label>
                    <label class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors cursor-pointer">
                        <input 
                            v-model="form.service_location"
                            type="checkbox"
                            value="coworking"
                            class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500 focus:ring-2"
                        >
                        <span class="ml-3 text-sm text-gray-700 font-medium">В коворкинге</span>
                    </label>
                    <label class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors cursor-pointer">
                        <input 
                            v-model="form.service_location"
                            type="checkbox"
                            value="clinic"
                            class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500 focus:ring-2"
                        >
                        <span class="ml-3 text-sm text-gray-700 font-medium">В клинике</span>
                    </label>
                </div>
                
                <p class="text-xs text-gray-500 mt-2">
                    Выберите места, где вы оказываете услуги
                </p>
                
                <div v-if="errors.service_location" class="mt-2 text-sm text-red-600 flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
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