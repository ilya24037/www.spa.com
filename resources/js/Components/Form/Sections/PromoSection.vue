<template>
    <div class="form-section">
        <h3 class="form-section-title">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"></path>
            </svg>
            Акции
        </h3>
        
        <p class="text-sm text-gray-600 mb-4">
            Клиенты увидят информацию о скидках и подарках в объявлении
        </p>
        
        <div class="form-row">
            <div class="w-full">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Скидка новым клиентам
                </label>
                <div class="flex items-center space-x-2">
                    <input 
                        v-model="form.new_client_discount"
                        type="number"
                        min="0"
                        max="100"
                        class="w-20 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="0"
                    >
                    <span class="text-sm text-gray-700">%</span>
                </div>
                <p class="text-xs text-gray-500 mt-1">
                    Можно не заполнять, если скидки нет
                </p>
            </div>
        </div>
        
        <div class="form-row">
            <div class="w-full">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Подарок
                </label>
                <input 
                    v-model="form.gift"
                    type="text"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="Что вы можете подарить? Например: дарим чай"
                    maxlength="100"
                >
                <div class="flex justify-between items-center mt-1">
                    <p class="text-xs text-gray-500">
                        Можно не заполнять, если подарка нет
                    </p>
                    <span class="text-xs text-gray-400">
                        {{ (form.gift || '').length }}/100
                    </span>
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

<style scoped>
.section-description {
  color: #666;
  font-size: 14px;
  margin-bottom: 20px;
  line-height: 1.5;
}

.promo-fields {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.special-offers {
  margin-top: 8px;
  padding: 16px;
  background: #f8f9fa;
  border-radius: 8px;
  border: 1px solid #e9ecef;
}
</style> 