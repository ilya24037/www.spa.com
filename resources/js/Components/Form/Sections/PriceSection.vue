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
                <div class="flex items-center space-x-2">
                    <input 
                        v-model="form.price"
                        type="number"
                        min="0"
                        step="100"
                        class="w-32 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        :class="{ 'border-red-500': errors.price }"
                        placeholder="0"
                        required
                    >
                    <span class="text-sm text-gray-700">₽</span>
                    <select 
                        v-model="form.price_unit"
                        class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        :class="{ 'border-red-500': errors.price_unit }"
                        required
                    >
                        <option value="">за услугу</option>
                        <option value="hour">за час</option>
                        <option value="session">за сеанс</option>
                        <option value="day">за день</option>
                        <option value="visit">за визит</option>
                    </select>
                </div>
                <div class="mt-1">
                    <label class="flex items-center">
                        <input 
                            v-model="form.is_starting_price"
                            type="checkbox"
                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                        >
                        <span class="ml-2 text-sm text-gray-700">это начальная цена</span>
                    </label>
                </div>
                <div v-if="errors.price" class="mt-1 text-sm text-red-600">
                    {{ errors.price }}
                </div>
                <div v-if="errors.price_unit" class="mt-1 text-sm text-red-600">
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