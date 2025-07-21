<template>
    <div class="form-section">
        <h3 class="form-section-title">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
            </svg>
            Специальность или сфера
        </h3>
        
        <div class="form-row">
            <div class="w-full">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Специальность *
                </label>
                <div class="relative">
                    <select 
                        v-model="form.specialty"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors appearance-none pr-12"
                        :class="{ 'border-red-500 focus:ring-red-500 focus:border-red-500': errors.specialty }"
                        required
                    >
                        <option value="">Выберите специальность</option>
                        <option value="massage">Массаж</option>
                        <option value="therapeutic">Лечебный массаж</option>
                        <option value="relaxing">Расслабляющий массаж</option>
                        <option value="sports">Спортивный массаж</option>
                        <option value="anti-cellulite">Антицеллюлитный массаж</option>
                        <option value="lymphatic">Лимфодренажный массаж</option>
                        <option value="honey">Медовый массаж</option>
                        <option value="stone">Стоун-массаж</option>
                        <option value="thai">Тайский массаж</option>
                        <option value="erotic">Эротический массаж</option>
                    </select>
                    <!-- Кастомная стрелка -->
                    <div class="absolute right-3 top-1/2 transform -translate-y-1/2 pointer-events-none">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </div>
                </div>
                
                <p class="text-xs text-gray-500 mt-2">
                    Выберите основную специальность, по которой вы оказываете услуги
                </p>
                
                <div v-if="errors.specialty" class="mt-2 text-sm text-red-600 flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                    {{ errors.specialty }}
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