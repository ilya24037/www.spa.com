<template>
    <div class="form-section">
        <h3 class="form-section-title">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
            </svg>
            Стоимость основной услуги
        </h3>
        
        <p class="text-sm text-gray-600 mb-4">
            Заказчик увидит эту цену рядом с названием объявления
        </p>
        
        <div class="form-row">
            <div class="w-full">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Стоимость основной услуги *
                </label>
                <div class="flex items-center space-x-3">
                    <div class="relative">
                        <input 
                            v-model="form.price"
                            type="number"
                            min="0"
                            step="100"
                            class="w-32 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                            :class="{ 'border-red-500 focus:ring-red-500 focus:border-red-500': errors.price }"
                            placeholder="0"
                            required
                        >
                        <span class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500">₽</span>
                    </div>
                    <div class="relative">
                        <select 
                            v-model="form.price_unit"
                            class="px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors appearance-none pr-10"
                            :class="{ 'border-red-500 focus:ring-red-500 focus:border-red-500': errors.price_unit }"
                            required
                        >
                            <option value="">за услугу</option>
                            <option value="hour">за час</option>
                            <option value="session">за сеанс</option>
                            <option value="day">за день</option>
                            <option value="visit">за визит</option>
                        </select>
                        <!-- Кастомная стрелка -->
                        <div class="absolute right-3 top-1/2 transform -translate-y-1/2 pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                    </div>
                </div>
                
                <div class="mt-3">
                    <label class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors cursor-pointer">
                        <input 
                            v-model="form.is_starting_price"
                            type="checkbox"
                            class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500 focus:ring-2"
                        >
                        <span class="ml-3 text-sm text-gray-700 font-medium">Это начальная цена</span>
                    </label>
                </div>
                
                <p class="text-xs text-gray-500 mt-2">
                    Укажите стоимость вашей основной услуги
                </p>
                
                <div v-if="errors.price" class="mt-2 text-sm text-red-600 flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                    {{ errors.price }}
                </div>
                <div v-if="errors.price_unit" class="mt-2 text-sm text-red-600 flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                    {{ errors.price_unit }}
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

// Инициализируем значения по умолчанию
const initializePriceFields = () => {
    if (!props.form.price_unit) {
        props.form.price_unit = 'session'
    }
    if (!props.form.is_starting_price) {
        props.form.is_starting_price = false
    }
}

// Вызываем инициализацию сразу
initializePriceFields()

// Отслеживаем изменения через watchEffect
watchEffect(() => {
    initializePriceFields()
})
</script>

<style scoped>
.price-group {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 16px;
  align-items: end;
}

@media (max-width: 768px) {
  .price-group {
    grid-template-columns: 1fr;
  }
}
</style> 