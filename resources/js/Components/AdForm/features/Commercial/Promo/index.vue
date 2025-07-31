<template>
  <FormSection
    title="Акции и предложения"
    hint="Добавьте специальные предложения для привлечения клиентов"
    :error="errors.gift || errors.new_client_discount"
  >
    <div class="space-y-6">
      <!-- Скидка новым клиентам -->
      <DiscountInput
        :model-value="newClientDiscount"
        @update:model-value="updateNewClientDiscount"
        :error="errors.new_client_discount"
      />

      <!-- Подарок или бонус -->
      <GiftInput
        :model-value="gift"
        @update:model-value="updateGift"
        :error="errors.gift"
      />

      <!-- Предварительный просмотр -->
      <PromoPreview
        :discount="newClientDiscount"
        :gift="gift"
      />

      <!-- Советы по акциям -->
      <PromoTips />
    </div>
  </FormSection>
</template>

<script setup>
import { computed } from 'vue'
import FormSection from '@/Components/UI/Forms/FormSection.vue'
import { useAdFormStore } from '../../../stores/adFormStore'

// Микрокомпоненты
import DiscountInput from './components/DiscountInput.vue'
import GiftInput from './components/GiftInput.vue'
import PromoPreview from './components/PromoPreview.vue'
import PromoTips from './components/PromoTips.vue'

// AVITO-STYLE: Используем централизованный store
const store = useAdFormStore()

const props = defineProps({
  errors: { type: Object, default: () => ({}) }
})

// Читаем данные ТОЛЬКО из store (как на Avito)
const newClientDiscount = computed(() => store.formData.new_client_discount || '')
const gift = computed(() => store.formData.gift || '')

// Методы обновляют ТОЛЬКО store (как на Avito/Ozon)
const updateNewClientDiscount = (value) => {
  console.log('updateNewClientDiscount called:', value)
  store.updateField('new_client_discount', value)
}

const updateGift = (value) => {
  console.log('updateGift called:', value)
  store.updateField('gift', value)
}
</script>