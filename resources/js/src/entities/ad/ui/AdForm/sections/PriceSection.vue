<template>
  <div class="price-section">
    <h2 class="form-group-title">Стоимость основной услуги</h2>
    <div class="price-fields">
      <label>Цена:
        <input type="text" v-model="localPrice" @input="emitAll" />
      </label>
      <label>Единица:
        <select v-model="localUnit" @change="emitAll">
          <option value="hour">за час</option>
          <option value="session">за сеанс</option>
        </select>
      </label>
      <label>
        <input type="checkbox" v-model="localIsStartingPrice" @change="emitAll" />
        Это стартовая цена
      </label>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
const props = defineProps({
  price: { type: String, default: '' },
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
  emit('update:price', localPrice.value)
  emit('update:priceUnit', localUnit.value)
  emit('update:isStartingPrice', localIsStartingPrice.value)
}
</script>

<style scoped>
.price-section { background: white; border-radius: 8px; padding: 20px; }
.form-group-title { font-size: 18px; font-weight: 600; color: #333; margin-bottom: 16px; }
.price-fields { display: flex; gap: 16px; align-items: center; }
</style> 