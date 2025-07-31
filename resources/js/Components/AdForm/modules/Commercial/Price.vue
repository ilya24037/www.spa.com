<template>
  <FormSection
    title="Цена за час"
    hint="Укажите стоимость ваших услуг. Цена влияет на позицию в поиске"
    required
    :error="errors.price_per_hour"
  >
    <div class="price-input-container">
      <!-- Основная цена -->
      <FormField
        label="Цена за час"
        hint="Основная стоимость услуги"
        :error="errors.price_per_hour"
      >
        <div class="price-input-group">
          <input
            v-model="localPricePerHour"
            @input="updatePricePerHour"
            type="number"
            min="500"
            max="50000"
            step="500"
            placeholder="3000"
            class="price-input"
          />
          <span class="price-suffix">₽/час</span>
        </div>
      </FormField>

      <!-- Цена за выезд -->
      <FormField
        label="Цена за выезд"
        hint="Дополнительная плата за выезд к клиенту"
        :error="errors.outcall_price"
      >
        <div class="price-input-group">
          <input
            v-model="localOutcallPrice"
            @input="updateOutcallPrice"
            type="number"
            min="0"
            max="10000"
            step="100"
            placeholder="500"
            class="price-input"
          />
          <span class="price-suffix">₽</span>
        </div>
      </FormField>

      <!-- Минимальное время -->
      <FormField
        label="Минимальное время"
        hint="Минимальная продолжительность сеанса"
        :error="errors.min_duration"
      >
        <select v-model="localMinDuration" @change="updateMinDuration" class="duration-select">
          <option value="">Выберите время</option>
          <option value="30">30 минут</option>
          <option value="60">1 час</option>
          <option value="90">1.5 часа</option>
          <option value="120">2 часа</option>
          <option value="180">3 часа</option>
        </select>
      </FormField>

      <!-- Скидка новым клиентам -->
      <FormField
        label="Скидка новым клиентам"
        hint="Процент скидки для привлечения новых клиентов"
        :error="errors.new_client_discount"
      >
        <div class="discount-input-group">
          <input
            v-model="localNewClientDiscount"
            @input="updateNewClientDiscount"
            type="number"
            min="0"
            max="50"
            step="5"
            placeholder="10"
            class="price-input"
          />
          <span class="price-suffix">%</span>
        </div>
      </FormField>
    </div>

    <!-- Быстрые цены -->
    <div class="quick-prices">
      <p class="quick-prices-label">Быстрый выбор:</p>
      <div class="quick-price-buttons">
        <button
          v-for="price in quickPrices"
          :key="price"
          type="button"
          @click="setQuickPrice(price)"
          class="quick-price-btn"
        >
          {{ price }} ₽
        </button>
      </div>
    </div>

    <!-- Совет по ценообразованию -->
    <div class="pricing-tip">
      <div class="tip-icon">💡</div>
      <div class="tip-content">
        <p class="tip-title">Совет по ценообразованию</p>
        <p class="tip-text">
          Средняя цена в вашем районе: <strong>3000-5000 ₽/час</strong>. 
          Конкурентная цена поможет получить больше заказов.
        </p>
      </div>
    </div>
  </FormSection>
</template>

<script setup>
import { ref, watch } from 'vue'
import FormSection from '@/Components/UI/Forms/FormSection.vue'
import FormField from '@/Components/UI/Forms/FormField.vue'

const props = defineProps({
  pricePerHour: { type: [String, Number], default: '' },
  outcallPrice: { type: [String, Number], default: '' },
  minDuration: { type: [String, Number], default: '' },
  newClientDiscount: { type: [String, Number], default: '' },
  errors: { type: Object, default: () => ({}) }
})

const emit = defineEmits([
  'update:pricePerHour',
  'update:outcallPrice', 
  'update:minDuration',
  'update:newClientDiscount'
])

// Локальное состояние (конвертируем числа в строки)
const localPricePerHour = ref(String(props.pricePerHour || ''))
const localOutcallPrice = ref(String(props.outcallPrice || ''))
const localMinDuration = ref(String(props.minDuration || ''))
const localNewClientDiscount = ref(String(props.newClientDiscount || ''))

// Отслеживание изменений пропсов
watch(() => props.pricePerHour, (newValue) => { 
  localPricePerHour.value = String(newValue || '') 
})
watch(() => props.outcallPrice, (newValue) => { 
  localOutcallPrice.value = String(newValue || '') 
})
watch(() => props.minDuration, (newValue) => { 
  localMinDuration.value = String(newValue || '') 
})
watch(() => props.newClientDiscount, (newValue) => { 
  localNewClientDiscount.value = String(newValue || '') 
})

// Методы обновления
const updatePricePerHour = () => emit('update:pricePerHour', localPricePerHour.value)
const updateOutcallPrice = () => emit('update:outcallPrice', localOutcallPrice.value)
const updateMinDuration = () => emit('update:minDuration', localMinDuration.value)
const updateNewClientDiscount = () => emit('update:newClientDiscount', localNewClientDiscount.value)

// Быстрые цены
const quickPrices = [2000, 2500, 3000, 3500, 4000, 5000, 6000]

const setQuickPrice = (price) => {
  localPricePerHour.value = String(price)
  updatePricePerHour()
}
</script>

<style scoped>
.price-input-container {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
}

.price-input-group,
.discount-input-group {
  position: relative;
  display: flex;
  align-items: center;
}

.price-input {
  flex: 1;
  padding: 0.75rem 1rem;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  font-size: 1rem;
  transition: border-color 0.2s;
}

.price-input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.price-suffix {
  position: absolute;
  right: 1rem;
  color: #6b7280;
  font-weight: 500;
  pointer-events: none;
}

.duration-select {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  font-size: 1rem;
  background: white;
  transition: border-color 0.2s;
}

.duration-select:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.quick-prices {
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid #e5e7eb;
}

.quick-prices-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
  margin-bottom: 0.75rem;
}

.quick-price-buttons {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.quick-price-btn {
  padding: 0.5rem 1rem;
  background: #f3f4f6;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  color: #374151;
  cursor: pointer;
  transition: all 0.2s;
}

.quick-price-btn:hover {
  background: #e5e7eb;
  border-color: #9ca3af;
}

.quick-price-btn:active {
  background: #3b82f6;
  border-color: #3b82f6;
  color: white;
}

.pricing-tip {
  display: flex;
  gap: 0.75rem;
  margin-top: 1.5rem;
  padding: 1rem;
  background: #fef3c7;
  border: 1px solid #fbbf24;
  border-radius: 0.5rem;
}

.tip-icon {
  font-size: 1.25rem;
  flex-shrink: 0;
}

.tip-content {
  flex: 1;
}

.tip-title {
  font-weight: 600;
  color: #92400e;
  margin-bottom: 0.25rem;
}

.tip-text {
  font-size: 0.875rem;
  color: #92400e;
  margin: 0;
}

@media (max-width: 768px) {
  .price-input-container {
    grid-template-columns: 1fr;
    gap: 1rem;
  }
  
  .quick-price-buttons {
    justify-content: center;
  }
}
</style>