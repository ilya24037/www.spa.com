<template>
  <FormSection
    title="Акции и предложения"
    hint="Добавьте специальные предложения для привлечения клиентов"
    :error="errors.gift || errors.new_client_discount"
  >
    <div class="space-y-6">
      <!-- Скидка новым клиентам -->
      <DiscountInput
        v-model="localNewClientDiscount"
        :error="errors.new_client_discount"
      />

      <!-- Подарок или бонус -->
      <GiftInput
        v-model="localGift"
        :error="errors.gift"
      />

      <!-- Предварительный просмотр -->
      <PromoPreview
        :discount="localNewClientDiscount"
        :gift="localGift"
      />

      <!-- Советы по акциям -->
      <PromoTips />
    </div>
  </FormSection>
</template>

<script setup>
import { ref, watch } from 'vue'
import FormSection from '@/Components/UI/Forms/FormSection.vue'

// Микрокомпоненты
import DiscountInput from './components/DiscountInput.vue'
import GiftInput from './components/GiftInput.vue'
import PromoPreview from './components/PromoPreview.vue'
import PromoTips from './components/PromoTips.vue'

const props = defineProps({
  newClientDiscount: { type: [String, Number], default: '' },
  gift: { type: String, default: '' },
  errors: { type: Object, default: () => ({}) }
})

const emit = defineEmits([
  'update:newClientDiscount',
  'update:gift'
])

// Локальное состояние
const localNewClientDiscount = ref(String(props.newClientDiscount || ''))
const localGift = ref(props.gift || '')

// Отслеживание изменений пропсов
watch(() => props.newClientDiscount, (newValue) => { 
  localNewClientDiscount.value = String(newValue || '') 
})
watch(() => props.gift, (newValue) => { 
  localGift.value = newValue || '' 
})

// Отправка изменений родителю
watch(localNewClientDiscount, (newValue) => {
  emit('update:newClientDiscount', newValue)
})

watch(localGift, (newValue) => {
  emit('update:gift', newValue)
})
</script>