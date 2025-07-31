<template>
  <FormSection
    title="Расценки"
    hint="Укажите стоимость ваших услуг"
    required
    :errors="errors"
    :error-keys="['price_per_hour', 'outcall_price']"
  >
    <div class="space-y-6">
      <!-- Экспресс 30 мин -->
      <FormField
        label="Экспресс 30 мин"
        :error="errors.express_price"
      >
        <BaseInput
          :model-value="expressPrice"
          @update:model-value="updatePrice('express_price', $event)"
          type="number"
          min="0"
          step="100"
          placeholder="Цена за 30 минут"
          suffix="₽"
        />
      </FormField>

      <!-- Основные тарифы -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- За час (апартаменты) -->
        <FormField
          label="За час (апартаменты)"
          :error="errors.price_per_hour"
        >
          <BaseInput
            :model-value="pricePerHour"
            @update:model-value="updatePrice('price_per_hour', $event)"
            type="number"
            min="0"
            step="100"
            placeholder="Цена за час"
            suffix="₽"
          />
        </FormField>

        <!-- За час (выезд) -->
        <FormField
          label="За час (выезд)"
          :error="errors.outcall_price"
        >
          <BaseInput
            :model-value="outcallPrice"
            @update:model-value="updatePrice('outcall_price', $event)"
            type="number"
            min="0"
            step="100"
            placeholder="Цена за выезд"
            suffix="₽"
          />
        </FormField>

        <!-- За два часа -->
        <FormField
          label="За два часа"
          :error="errors.price_two_hours"
        >
          <BaseInput
            :model-value="priceTwoHours"
            @update:model-value="updatePrice('price_two_hours', $event)"
            type="number"
            min="0"
            step="100"
            placeholder="Цена за 2 часа"
            suffix="₽"
          />
        </FormField>

        <!-- За ночь -->
        <FormField
          label="За ночь"
          :error="errors.price_night"
        >
          <BaseInput
            :model-value="priceNight"
            @update:model-value="updatePrice('price_night', $event)"
            type="number"
            min="0"
            step="100"
            placeholder="Цена за ночь"
            suffix="₽"
          />
        </FormField>
      </div>

      <!-- Контакты в час -->
      <FormField
        label="Контактов в час"
        hint="Сколько клиентов вы можете принять за час"
        :error="errors.contacts_per_hour"
      >
        <BaseSelect
          :model-value="contactsPerHour"
          @update:model-value="updatePrice('contacts_per_hour', $event)"
          :options="contactOptions"
          placeholder="Выберите количество"
        />
      </FormField>
    </div>
  </FormSection>
</template>

<script setup>
import { computed } from 'vue'
import FormSection from '@/Components/UI/Forms/FormSection.vue'
import FormField from '@/Components/UI/Forms/FormField.vue'
import BaseInput from '@/Components/UI/BaseInput.vue'
import BaseSelect from '@/Components/UI/BaseSelect.vue'
import { useAdFormStore } from '../../../stores/adFormStore'

// AVITO-STYLE: Используем централизованный store
const store = useAdFormStore()

const props = defineProps({
  errors: {
    type: Object,
    default: () => ({})
  }
})

// Читаем данные ТОЛЬКО из store (как на Avito)
const expressPrice = computed(() => store.formData.express_price || '')
const pricePerHour = computed(() => store.formData.price_per_hour || '')
const outcallPrice = computed(() => store.formData.outcall_price || '')
const priceTwoHours = computed(() => store.formData.price_two_hours || '')
const priceNight = computed(() => store.formData.price_night || '')
const contactsPerHour = computed(() => store.formData.contacts_per_hour || '')

// Опции для контактов в час
const contactOptions = [
  { value: '1', label: '1 клиент' },
  { value: '2', label: '2 клиента' },
  { value: '3', label: '3 клиента' },
  { value: '4', label: '4 клиента' },
  { value: '5', label: '5+ клиентов' }
]

// Методы обновляют ТОЛЬКО store (как на Avito/Ozon)
const updatePrice = (field, value) => {
  console.log('updatePrice called:', field, value)
  store.updateField(field, value)
}
</script>