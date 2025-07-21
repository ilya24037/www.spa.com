<template>
    <div class="form-section">
        <h3 class="form-section-title">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
            </svg>
            Формат работы
        </h3>
        
        <div class="form-row">
            <div class="w-full">
                <label class="block text-sm font-medium text-gray-700 mb-3">
                    Формат работы *
                </label>
                <div class="space-y-3">
                    <label class="flex items-center p-4 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                        <input 
                            v-model="form.work_format"
                            type="radio"
                            value="private"
                            class="w-5 h-5 text-blue-600 border-gray-300 focus:ring-blue-500 focus:ring-2"
                        >
                        <div class="ml-3">
                            <div class="text-sm font-medium text-gray-900">Частный мастер</div>
                            <div class="text-xs text-gray-500">Работаете в одиночку</div>
                        </div>
                    </label>
                    <label class="flex items-center p-4 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                        <input 
                            v-model="form.work_format"
                            type="radio"
                            value="salon"
                            class="w-5 h-5 text-blue-600 border-gray-300 focus:ring-blue-500 focus:ring-2"
                        >
                        <div class="ml-3">
                            <div class="text-sm font-medium text-gray-900">Салон</div>
                            <div class="text-xs text-gray-500">У вас есть отдельное помещение и штат мастеров</div>
                        </div>
                    </label>
                    <label class="flex items-center p-4 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                        <input 
                            v-model="form.work_format"
                            type="radio"
                            value="network"
                            class="w-5 h-5 text-blue-600 border-gray-300 focus:ring-blue-500 focus:ring-2"
                        >
                        <div class="ml-3">
                            <div class="text-sm font-medium text-gray-900">Сеть салонов</div>
                            <div class="text-xs text-gray-500">У вас несколько салонов в разных местах</div>
                        </div>
                    </label>
                </div>
                
                <p class="text-xs text-gray-500 mt-2">
                    Выберите формат вашей работы
                </p>
                
                <div v-if="errors.work_format" class="mt-2 text-sm text-red-600 flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                    {{ errors.work_format }}
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
</script> 