<template>
  <div class="promo-section">
    <h2 class="form-group-title">Акции</h2>
    <div class="promo-fields">
      <BaseInput
        v-model="localDiscount"
        type="text"
        label="Скидка новым клиентам"
        placeholder="Например: 10% скидка на первое посещение"
        :error="errors.newClientDiscount"
        @update:modelValue="emitAll"
      />
      <BaseInput
        v-model="localGift"
        type="text"
        label="Подарок"
        placeholder="Например: Бесплатный массаж при заказе от 2 часов"
        :error="errors.gift"
        @update:modelValue="emitAll"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import BaseInput from '@/src/shared/ui/atoms/BaseInput/BaseInput.vue'
const props = defineProps({
  newClientDiscount: { type: String, default: '' },
  'new-client-discount': { type: String, default: '' },
  gift: { type: String, default: '' },
  errors: { type: Object, default: () => ({}) }
})

const emit = defineEmits(['update:newClientDiscount', 'update:gift', 'update:new-client-discount'])
// Используем kebab-case версию если она есть, иначе camelCase
const localDiscount = ref(props['new-client-discount'] || props.newClientDiscount || '')
const localGift = ref(props.gift || '')
watch(() => props['new-client-discount'] || props.newClientDiscount, val => { localDiscount.value = val || '' })
watch(() => props.gift, val => { localGift.value = val || '' })
const emitAll = () => {
  emit('update:newClientDiscount', localDiscount.value)
  emit('update:new-client-discount', localDiscount.value) // Для v-model с дефисом
  emit('update:gift', localGift.value)
}
</script>

<style scoped>
.promo-section { background: white; border-radius: 8px; padding: 20px; }
.form-group-title { font-size: 18px; font-weight: 600; color: #333; margin-bottom: 16px; }
.promo-fields { 
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
}
</style> 