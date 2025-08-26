<template>
  <div class="pricing-section">
    <!-- В апартаментах -->
    <div class="pricing-block">
      <h3 class="pricing-block-title">В апартаментах</h3>
      <div class="pricing-grid">
        <div class="price-item">
          <BaseInput
            v-model="localPrices.apartments_express"
            type="number"
            name="apartments_express"
            label="Экспресс (30 мин)"
            placeholder="0"
            :min="0"
            @update:modelValue="updatePrices"
          >
            <template #suffix>₽</template>
          </BaseInput>
        </div>
        <div class="price-item">
          <BaseInput
            v-model="localPrices.apartments_1h"
            type="number"
            name="apartments_1h"
            label="1 час"
            placeholder="0"
            :min="0"
            :required="true"
            @update:modelValue="updatePrices"
          >
            <template #suffix>₽</template>
          </BaseInput>
        </div>
        <div class="price-item">
          <BaseInput
            v-model="localPrices.apartments_2h"
            type="number"
            name="apartments_2h"
            label="2 часа"
            placeholder="0"
            :min="0"
            @update:modelValue="updatePrices"
          >
            <template #suffix>₽</template>
          </BaseInput>
        </div>
        <div class="price-item">
          <BaseInput
            v-model="localPrices.apartments_night"
            type="number"
            name="apartments_night"
            label="Ночь"
            placeholder="0"
            :min="0"
            @update:modelValue="updatePrices"
          >
            <template #suffix>₽</template>
          </BaseInput>
        </div>
      </div>
    </div>

    <!-- Выезд к клиенту -->
    <div class="pricing-block">
      <h3 class="pricing-block-title">Выезд к клиенту</h3>
      <div class="pricing-grid">
        <div class="price-item">
          <BaseInput
            v-model="localPrices.outcall_express"
            type="number"
            name="outcall_express"
            label="Экспресс (30 мин)"
            placeholder="0"
            :min="0"
            @update:modelValue="updatePrices"
          >
            <template #suffix>₽</template>
          </BaseInput>
        </div>
        <div class="price-item">
          <BaseInput
            v-model="localPrices.outcall_1h"
            type="number"
            name="outcall_1h"
            label="1 час"
            placeholder="0"
            :min="0"
            @update:modelValue="updatePrices"
          >
            <template #suffix>₽</template>
          </BaseInput>
        </div>
        <div class="price-item">
          <BaseInput
            v-model="localPrices.outcall_2h"
            type="number"
            name="outcall_2h"
            label="2 часа"
            placeholder="0"
            :min="0"
            @update:modelValue="updatePrices"
          >
            <template #suffix>₽</template>
          </BaseInput>
        </div>
        <div class="price-item">
          <BaseInput
            v-model="localPrices.outcall_night"
            type="number"
            name="outcall_night"
            label="Ночь"
            placeholder="0"
            :min="0"
            @update:modelValue="updatePrices"
          >
            <template #suffix>₽</template>
          </BaseInput>
        </div>
      </div>

    </div>

    <!-- Ошибки валидации -->
    <div v-if="errors.prices" class="error-message">
      {{ errors.prices }}
    </div>
  </div>
</template>

<script setup>
import { ref, watch, reactive, computed } from 'vue'
import BaseInput from '@/src/shared/ui/atoms/BaseInput/BaseInput.vue'

const props = defineProps({
  prices: {
    type: Object,
    default: () => ({
      apartments_express: null,
      apartments_1h: null,
      apartments_2h: null,
      apartments_night: null,
      outcall_express: null,
      outcall_1h: null,
      outcall_2h: null,
      outcall_night: null
    })
  },
  errors: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['update:prices'])



// Хелпер для преобразования значения в булевое
const toBoolean = (value) => {
  // Явная проверка на null и undefined (требование CLAUDE.md)
  if (value === null || value === undefined) {
    return false
  }
  // Преобразуем строки "1", "true" и числа в булевые значения
  if (value === "1" || value === 1 || value === true || value === "true") {
    return true
  }
  if (value === "0" || value === 0 || value === false || value === "false") {
    return false
  }
  return Boolean(value)
}

// Локальное состояние цен
const localPrices = reactive({
  apartments_express: props.prices?.apartments_express ?? null,
  apartments_1h: props.prices?.apartments_1h ?? null,
  apartments_2h: props.prices?.apartments_2h ?? null,
  apartments_night: props.prices?.apartments_night ?? null,
  outcall_express: props.prices?.outcall_express ?? null,
  outcall_1h: props.prices?.outcall_1h ?? null,
  outcall_2h: props.prices?.outcall_2h ?? null,
  outcall_night: props.prices?.outcall_night ?? null
})



// Следим за изменениями props
watch(() => props.prices, (newPrices) => {
  if (newPrices) {
    Object.keys(newPrices).forEach(key => {
      if (newPrices[key] !== undefined) {
        localPrices[key] = newPrices[key]
      }
    })
  }
}, { deep: true, immediate: true })

// Обновляем родительский компонент
const updatePrices = () => {
  emit('update:prices', { ...localPrices })
}
</script>

<style scoped>
.pricing-section {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.pricing-block {
  background: #f8f9fa;
  border-radius: 8px;
  padding: 20px;
}

.pricing-block-title {
  font-size: 16px;
  font-weight: 600;
  color: #1a1a1a;
  margin: 0 0 16px 0;
}

.pricing-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 16px;
}

.price-item {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.price-label {
  font-size: 14px;
  color: #666;
  font-weight: 500;
}

.taxi-options {
  padding-top: 20px;
  border-top: 1px solid #e5e7eb;
}

.taxi-label {
  font-size: 14px;
  font-weight: 600;
  color: #1a1a1a;
  margin-bottom: 12px;
  display: block;
}

.radio-group {
  display: flex;
  gap: 24px;
}

.radio-option {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  font-size: 14px;
  color: #1a1a1a;
}

.radio-option input[type="radio"] {
  width: 18px;
  height: 18px;
  cursor: pointer;
}

/* Места выезда */
.outcall-locations {
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid #e5e7eb;
}

.locations-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  cursor: pointer;
  margin-bottom: 12px;
  user-select: none;
}

.locations-header:hover .locations-label {
  color: #2196f3;
}

.locations-label {
  font-size: 14px;
  font-weight: 600;
  color: #1a1a1a;
  margin: 0;
  transition: color 0.2s ease;
}

.expand-icon {
  transition: transform 0.3s ease;
  color: #666;
}

.expand-icon.rotate-180 {
  transform: rotate(180deg);
}

.locations-content {
  padding-top: 8px;
}

.checkbox-list {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
  margin-bottom: 20px;
}

/* Анимация сворачивания/разворачивания */
.collapse-enter-active,
.collapse-leave-active {
  transition: all 0.3s ease;
  overflow: hidden;
}

.collapse-enter-from,
.collapse-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

.error-message {
  color: #dc3545;
  font-size: 14px;
  margin-top: 8px;
}

/* Мобильная адаптация */
@media (max-width: 640px) {
  .pricing-grid {
    grid-template-columns: 1fr 1fr;
  }
  
  .radio-group {
    flex-direction: column;
    gap: 12px;
  }
  
  .checkbox-list {
    grid-template-columns: 1fr;
  }
}
</style>