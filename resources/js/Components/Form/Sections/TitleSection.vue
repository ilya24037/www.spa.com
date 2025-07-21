<template>
    <div class="form-section">
        <h3 class="form-section-title">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2h4a1 1 0 011 1v1a1 1 0 01-1 1H3a1 1 0 01-1-1V5a1 1 0 011-1h4z"></path>
            </svg>
            Название объявления
        </h3>
        
        <div class="form-row">
            <div class="w-full">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Название объявления *
                </label>
                <div class="relative">
                    <input 
                        v-model="form.title"
                        type="text"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors pr-12"
                        :class="{ 'border-red-500 focus:ring-red-500 focus:border-red-500': errors.title }"
                        placeholder="Например: Массаж эротический для мужчин на дому"
                        maxlength="60"
                        required
                    >
                    <!-- Кнопка очистки -->
                    <button 
                        v-if="form.title"
                        @click="clearTitle"
                        type="button"
                        class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <div class="flex justify-between items-center mt-2">
                    <p class="text-xs text-gray-500">
                        Например: «Массаж», «Массаж лечебный» или «Массаж эротический». Не указывайте контакты или цены
                    </p>
                    <span class="text-xs text-gray-400">
                        {{ (form.title || '').length }}/60
                    </span>
                </div>
                
                <div v-if="errors.title" class="mt-2 text-sm text-red-600 flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                    {{ errors.title }}
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

const clearTitle = () => {
    props.form.title = ''
}
</script> 