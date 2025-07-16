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
                <input 
                    v-model="form.title"
                    type="text"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                    :class="{ 'border-red-500': errors.title }"
                    placeholder="Например: Массаж эротический для мужчин на дому"
                    maxlength="60"
                    required
                >
                <div class="flex justify-between items-center mt-1">
                    <p class="text-xs text-gray-500">
                        Например: «Массаж», «Массаж лечебный» или «Массаж эротический». Не указывайте контакты или цены
                    </p>
                    <span class="text-xs text-gray-400">
                        {{ (form.title || '').length }}/60
                    </span>
                </div>
                <div v-if="errors.title" class="mt-1 text-sm text-red-600">
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
</script> 