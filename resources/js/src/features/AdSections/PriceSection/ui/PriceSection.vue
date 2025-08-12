<template>
  <div class="price-section">
    <div class="price-fields">
      <div class="price-input-group">
        <BaseInput
          v-model="localPrice"
          type="number"
          label="Цена"
          placeholder="5000"
          :min="0"
          :step="100"
          suffix="₽"
          :error="errors.price"
          @update:modelValue="emitAll"
        />
        
        <BaseSelect
          v-model="localUnit"
          label="За что"
          :options="priceUnitOptions"
          :error="errors.priceUnit"
          @update:modelValue="emitAll"
        />
      </div>
      
      <div class="price-options">
        <BaseCheckbox
          v-model="localIsStartingPrice"
          label="Цена 'от' (стартовая цена)"
          hint="Отметьте, если указана минимальная цена, которая может увеличиться в зависимости от объема работ"
          @update:modelValue="emitAll"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import BaseInput from '@/src/shared/ui/atoms/BaseInput/BaseInput.vue'
import BaseSelect from '@/src/shared/ui/atoms/BaseSelect/BaseSelect.vue'
import BaseCheckbox from '@/src/shared/ui/atoms/BaseCheckbox/BaseCheckbox.vue'

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

// Опции для единицы стоимости
const priceUnitOptions = computed(() => [
  { value: 'hour', label: 'Час' },
  { value: '90min', label: '1.5 часа' },
  { value: '2hours', label: '2 часа' },
  { value: '3hours', label: '3 часа' },
  { value: 'session', label: 'Сеанс' },
  { value: 'day', label: 'День' },
  { value: 'night', label: 'Ночь' },
  { value: 'service', label: 'Услугу' },
  { value: 'visit', label: 'Выезд' },
  { value: 'complex', label: 'Комплекс' },
  { value: 'person', label: 'Человека' },
  { value: 'object', label: 'Объект' }
])

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
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
  align-items: flex-end;
}

.price-options {
  padding: 16px;
  background: #f5f5f5;
  border-radius: 8px;
}

@media (max-width: 640px) {
  .price-input-group {
    grid-template-columns: 1fr;
  }
}
</style> 