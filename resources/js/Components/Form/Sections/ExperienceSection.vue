<template>
    <div class="form-section">
        <h3 class="form-section-title">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
            </svg>
            Опыт работы
        </h3>
        
        <div class="form-row">
            <div class="w-full">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Опыт работы *
                </label>
                <div class="relative">
                    <select 
                        v-model="form.experience"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors appearance-none pr-12"
                        :class="{ 'border-red-500 focus:ring-red-500 focus:border-red-500': errors.experience }"
                        required
                    >
                        <option value="">Выберите опыт работы</option>
                        <option value="no_experience">Нет опыта</option>
                        <option value="less_than_1">Менее 1 года</option>
                        <option value="1_3">1-3 года</option>
                        <option value="3_6">3-6 лет</option>
                        <option value="more_than_6">Более 6 лет</option>
                    </select>
                    <!-- Кастомная стрелка -->
                    <div class="absolute right-3 top-1/2 transform -translate-y-1/2 pointer-events-none">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </div>
                </div>
                
                <p class="text-xs text-gray-500 mt-2">
                    Укажите ваш опыт работы в данной сфере
                </p>
                
                <div v-if="errors.experience" class="mt-2 text-sm text-red-600 flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                    {{ errors.experience }}
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