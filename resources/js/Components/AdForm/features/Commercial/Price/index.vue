<template>
  <FormSection
    title="Цена за час"
    hint="Укажите стоимость ваших услуг. Цена влияет на позицию в поиске"
    required
    :error="errors.price_per_hour"
  >
    <div class="space-y-6">
      <!-- Основная цена - ПРЯМОЕ взаимодействие как в работающих компонентах -->
      <FormField
        label="Цена за час"
        hint="Основная стоимость услуги (влияет на позицию в поиске)"
        :error="errors.price_per_hour"
        required
      >
        <div class="space-y-4">
          <!-- Поле ввода -->
          <BaseInput
            :model-value="pricePerHour"
            type="number"
            placeholder="3000"
            suffix="₽/час"
            min="500"
            max="50000"
            step="500"
            class="w-48"
            @update:model-value="(value) => updateField('price_per_hour', value)"
          />
          
          <!-- Быстрые цены -->
          <div class="space-y-2">
            <p class="text-sm font-medium text-gray-700">Популярные цены:</p>
            <div class="flex flex-wrap gap-2">
              <button
                v-for="price in quickPrices"
                :key="price"
                type="button"
                @click="setQuickPrice(price)"
                :class="[
                  'px-3 py-2 text-sm border rounded-lg transition-all duration-200',
                  pricePerHour == price
                    ? 'bg-blue-500 border-blue-500 text-white'
                    : 'bg-gray-50 border-gray-200 text-gray-700 hover:bg-gray-100 hover:border-gray-300'
                ]"
              >
                {{ formatPrice(price) }}
              </button>
            </div>
          </div>
        </div>
      </FormField>

      <!-- Цена за выезд -->
      <FormField
        label="Цена за выезд"
        hint="Дополнительная плата за выезд к клиенту"
        :error="errors.outcall_price"
      >
        <BaseInput
          :model-value="outcallPrice"
          type="number"
          placeholder="500"
          suffix="₽"
          min="0"
          max="10000"
          step="100"
          class="w-48"
          @update:model-value="(value) => updateField('outcall_price', value)"
        />
      </FormField>

      <!-- Минимальное время -->
      <FormField
        label="Минимальное время"
        hint="Минимальная продолжительность сеанса"
        :error="errors.min_duration"
      >
        <BaseSelect
          :model-value="minDuration"
          :options="durationOptions"
          placeholder="Выберите время"
          class="w-48"
          @update:model-value="(value) => updateField('min_duration', value)"
        />
      </FormField>

      <!-- Советы по ценообразованию -->
      <PricingTips />
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

// Микрокомпоненты
import PricingTips from './components/PricingTips.vue'

// AVITO-STYLE: Используем централизованный store - ПРЯМОЕ взаимодействие как в работающих компонентах
const store = useAdFormStore()

const props = defineProps({
  errors: { type: Object, default: () => ({}) }
})

// Читаем данные ТОЛЬКО из store (как на Avito) - ТОЧНО КАК В РАБОТАЮЩИХ
const pricePerHour = computed(() => store.formData.price_per_hour || '')
const outcallPrice = computed(() => store.formData.outcall_price || '')
const minDuration = computed(() => store.formData.min_duration || '')

// Методы обновляют ТОЛЬКО store (как на Avito/Ozon) - ТОЧНО КАК В РАБОТАЮЩИХ
const updateField = (field, value) => {
  console.log('updateField called:', field, value)
  store.updateField(field, value)
}

// Быстрые цены
const quickPrices = [2000, 2500, 3000, 3500, 4000, 5000, 6000]

// Опции продолжительности
const durationOptions = [
  { value: '30', label: '30 минут' },
  { value: '60', label: '1 час' },
  { value: '90', label: '1.5 часа' },
  { value: '120', label: '2 часа' },
  { value: '180', label: '3 часа' }
]

// Методы
const setQuickPrice = (price) => {
  console.log('setQuickPrice called:', price)
  store.updateField('price_per_hour', String(price))
}

const formatPrice = (price) => {
  return new Intl.NumberFormat('ru-RU').format(price) + ' ₽'
}
</script>