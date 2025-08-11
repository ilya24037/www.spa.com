<template>
  <div class="price-section">
    <div class="price-fields">
      <div class="price-input-group">
        <div class="price-field">
          <label>Цена:</label>
          <div class="price-input-wrapper">
            <input 
              type="number" 
              v-model="localPrice" 
              @input="emitAll" 
              placeholder="5000"
              min="0"
              step="100"
              class="price-input"
            />
            <span class="currency">₽</span>
          </div>
        </div>
        
        <div class="price-field">
          <label>За что:</label>
          <select v-model="localUnit" @change="emitAll" class="price-select">
            <option value="hour">Час</option>
            <option value="90min">1.5 часа</option>
            <option value="2hours">2 часа</option>
            <option value="3hours">3 часа</option>
            <option value="session">Сеанс</option>
            <option value="day">День</option>
            <option value="night">Ночь</option>
            <option value="service">Услугу</option>
            <option value="visit">Выезд</option>
            <option value="complex">Комплекс</option>
            <option value="person">Человека</option>
            <option value="object">Объект</option>
          </select>
        </div>
      </div>
      
      <div class="price-options">
        <label class="checkbox-label">
          <input 
            type="checkbox" 
            v-model="localIsStartingPrice" 
            @change="emitAll"
            class="checkbox-input"
          />
          <span class="checkbox-text">Цена "от" (стартовая цена)</span>
        </label>
        <p class="price-hint">
          Отметьте, если указана минимальная цена, которая может увеличиться в зависимости от объема работ
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
const props = defineProps({
  price: { type: [String, Number], default: '' },
  priceUnit: { type: String, default: 'hour' },
  isStartingPrice: { type: Boolean, default: false },
  errors: { type: Object, default: () => ({}) }
})
const emit = defineEmits(['update:price', 'update:priceUnit', 'update:isStartingPrice'])
const localPrice = ref(props.price)
const localUnit = ref(props.priceUnit)
const localIsStartingPrice = ref(props.isStartingPrice)
watch(() => props.price, val => { localPrice.value = val })
watch(() => props.priceUnit, val => { localUnit.value = val })
watch(() => props.isStartingPrice, val => { localIsStartingPrice.value = val })
const emitAll = () => {
  // Преобразуем цену в число при эмите
  const priceValue = localPrice.value ? Number(localPrice.value) : null
  emit('update:price', priceValue)
  emit('update:priceUnit', localUnit.value)
  emit('update:isStartingPrice', localIsStartingPrice.value)
}
</script>

<style scoped>
.price-section { 
  padding: 20px 0; 
}

.price-fields {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.price-input-group {
  display: flex;
  gap: 20px;
  align-items: flex-end;
}

.price-field {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.price-field label {
  font-size: 14px;
  font-weight: 500;
  color: #333;
}

.price-input-wrapper {
  position: relative;
  display: inline-block;
}

.price-input {
  padding: 10px 40px 10px 12px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 16px;
  font-weight: 500;
  width: 150px;
  background: #fff;
}

.price-input:focus {
  outline: none;
  border-color: #007bff;
  box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.1);
}

/* Убираем стрелки у number input */
.price-input::-webkit-outer-spin-button,
.price-input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

.price-input[type=number] {
  -moz-appearance: textfield;
}

.currency {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 16px;
  font-weight: 500;
  color: #666;
}

.price-select {
  padding: 10px 12px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
  background: #fff;
  min-width: 150px;
  cursor: pointer;
}

.price-select:focus {
  outline: none;
  border-color: #007bff;
  box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.1);
}

.price-options {
  padding: 16px;
  background: #f5f5f5;
  border-radius: 8px;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
}

.checkbox-input {
  width: 18px;
  height: 18px;
  cursor: pointer;
}

.checkbox-text {
  font-size: 14px;
  font-weight: 500;
  color: #333;
}

.price-hint {
  margin-top: 8px;
  margin-bottom: 0;
  font-size: 13px;
  color: #666;
  line-height: 1.4;
}

@media (max-width: 640px) {
  .price-input-group {
    flex-direction: column;
    align-items: stretch;
  }
  
  .price-input,
  .price-select {
    width: 100%;
  }
}
</style> 