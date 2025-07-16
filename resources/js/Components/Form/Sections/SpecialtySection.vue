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
                <select 
                    v-model="form.specialty"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                    :class="{ 'border-red-500': errors.specialty }"
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
                <div v-if="errors.specialty" class="mt-1 text-sm text-red-600">
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