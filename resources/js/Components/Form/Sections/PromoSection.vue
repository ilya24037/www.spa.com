<template>
  <div class="promo-section">
    <h2 class="form-group-title">Акции</h2>
    <div class="promo-fields">
      <div class="field-group">
        <BaseInput
          v-model="localDiscount"
          type="text"
          label="Скидка новым клиентам:"
          placeholder="Например: 10% или 500 руб"
          @update:modelValue="emitAll"
        />
      </div>
      
      <div class="field-group">
        <BaseInput
          v-model="localGift"
          type="text"
          label="Подарок:"
          placeholder="Например: бесплатный чай"
          @update:modelValue="emitAll"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import BaseInput from '@/Components/UI/BaseInput.vue'

const props = defineProps({
  newClientDiscount: { type: String, default: '' },
  gift: { type: String, default: '' },
  errors: { type: Object, default: () => ({}) }
})

const emit = defineEmits(['update:newClientDiscount', 'update:gift'])

const localDiscount = ref(props.newClientDiscount)
const localGift = ref(props.gift)

watch(() => props.newClientDiscount, val => { localDiscount.value = val })
watch(() => props.gift, val => { localGift.value = val })

const emitAll = () => {
  emit('update:newClientDiscount', localDiscount.value)
  emit('update:gift', localGift.value)
}
</script>

<style scoped>
.promo-section { 
  background: white; 
  border-radius: 8px; 
  padding: 20px; 
}

.form-group-title { 
  font-size: 18px; 
  font-weight: 600; 
  color: #333; 
  margin-bottom: 16px; 
}

.promo-fields { 
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 16px;
}

.field-group {
  width: 100%;
}
</style> 