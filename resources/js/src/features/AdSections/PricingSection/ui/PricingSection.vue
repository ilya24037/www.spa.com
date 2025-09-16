<template>
  <div class="pricing-section p-5">
    <div class="pricing-container">
      <!-- В апартаментах -->
      <div class="pricing-group">
        <h4 class="pricing-subtitle">АПАРТАМЕНТЫ</h4>
        <div v-if="showPriceError" class="text-sm text-red-600 mb-2">
          Нужно заполнить цену за час в апартаментах и/или на выезд
        </div>
        <div class="pricing-grid">
          <BaseInput
            v-model="localPrices.apartments_express"
            type="number"
            name="apartments_express"
            label="Экспресс (30 мин)"
            placeholder="0"
            :min="0"
            :clearable="true"
            suffix="₽"
            @update:modelValue="updatePrices"
            class="w-[170px]"
          />
          <BaseInput
            v-model="localPrices.apartments_1h"
            type="number"
            name="apartments_1h"
            label="1 час"
            placeholder="0"
            :min="0"
            :required="true"
            :clearable="true"
            suffix="₽"
            @update:modelValue="updatePrices"
            class="w-[170px]"
          />
          <BaseInput
            v-model="localPrices.apartments_2h"
            type="number"
            name="apartments_2h"
            label="2 часа"
            placeholder="0"
            :min="0"
            :clearable="true"
            suffix="₽"
            @update:modelValue="updatePrices"
            class="w-[170px]"
          />
          <BaseInput
            v-model="localPrices.apartments_night"
            type="number"
            name="apartments_night"
            label="Ночь"
            placeholder="0"
            :min="0"
            :clearable="true"
            suffix="₽"
            @update:modelValue="updatePrices"
            class="w-[170px]"
          />
        </div>
      </div>

      <!-- Выезд к клиенту -->
      <div class="pricing-group">
        <h4 
          class="pricing-subtitle cursor-pointer transition-colors duration-200"
          @click="toggleOutcallField"
        >
          ВЫЕЗД
          <svg
            xmlns="http://www.w3.org/2000/svg"
            width="20"
            height="20"
            viewBox="0 0 24 24"
            class="text-gray-500 ml-2 transition-transform duration-200"
            :class="{ 'rotate-180': isOutcallExpanded }"
            fill="currentColor"
          >
            <path d="M6.497 9.385a1.5 1.5 0 0 1 2.118.112L12 13.257l3.385-3.76a1.5 1.5 0 0 1 2.23 2.006l-4.5 5a1.5 1.5 0 0 1-2.23 0l-4.5-5a1.5 1.5 0 0 1 .112-2.118"></path>
          </svg>
        </h4>
        <div v-show="isOutcallExpanded" class="pricing-grid">
          <BaseInput
            v-model="localPrices.outcall_express"
            type="number"
            name="outcall_express"
            label="Экспресс (30 мин)"
            placeholder="0"
            :min="0"
            :clearable="true"
            suffix="₽"
            @update:modelValue="updatePrices"
            class="w-[170px]"
          />
          <BaseInput
            v-model="localPrices.outcall_1h"
            type="number"
            name="outcall_1h"
            label="1 час"
            placeholder="0"
            :min="0"
            :clearable="true"
            suffix="₽"
            @update:modelValue="updatePrices"
            class="w-[170px]"
          />
          <BaseInput
            v-model="localPrices.outcall_2h"
            type="number"
            name="outcall_2h"
            label="2 часа"
            placeholder="0"
            :min="0"
            :clearable="true"
            suffix="₽"
            @update:modelValue="updatePrices"
            class="w-[170px]"
          />
          <BaseInput
            v-model="localPrices.outcall_night"
            type="number"
            name="outcall_night"
            label="Ночь"
            placeholder="0"
            :min="0"
            :clearable="true"
            suffix="₽"
            @update:modelValue="updatePrices"
            class="w-[170px]"
          />
        </div>
      </div>

      <!-- Финалов в час -->
      <div>
        <h4 
          class="pricing-subtitle cursor-pointer transition-colors duration-200"
          @click="toggleFinishesField"
        >
          Финалов в час
          <svg
            xmlns="http://www.w3.org/2000/svg"
            width="20"
            height="20"
            viewBox="0 0 24 24"
            class="text-gray-500 ml-2 transition-transform duration-200"
            :class="{ 'rotate-180': isFinishesExpanded }"
            fill="currentColor"
          >
            <path d="M6.497 9.385a1.5 1.5 0 0 1 2.118.112L12 13.257l3.385-3.76a1.5 1.5 0 0 1 2.23 2.006l-4.5 5a1.5 1.5 0 0 1-2.23 0l-4.5-5a1.5 1.5 0 0 1 .112-2.118"></path>
          </svg>
        </h4>
        <div v-show="isFinishesExpanded" class="pricing-grid">
          <BaseSelect
            v-model="localPrices.finishes_per_hour"
            placeholder="Не выбрано"
            :options="finishesPerHourOptions"
            @update:modelValue="updatePrices"
            class="w-[170px]"
          />
        </div>
      </div>

      <!-- Акции и скидки -->
      <div class="promo-section">
        <h4 
          class="promo-subtitle cursor-pointer transition-colors duration-200"
          @click="togglePromoField"
        >
          Акции и скидки
          <svg
            xmlns="http://www.w3.org/2000/svg"
            width="20"
            height="20"
            viewBox="0 0 24 24"
            class="text-gray-500 ml-2 transition-transform duration-200"
            :class="{ 'rotate-180': isPromoExpanded }"
            fill="currentColor"
          >
            <path d="M6.497 9.385a1.5 1.5 0 0 1 2.118.112L12 13.257l3.385-3.76a1.5 1.5 0 0 1 2.23 2.006l-4.5 5a1.5 1.5 0 0 1-2.23 0l-4.5-5a1.5 1.5 0 0 1 .112-2.118"></path>
          </svg>
        </h4>
        <div v-show="isPromoExpanded" class="pricing-grid promo-grid">
          <BaseInput
            v-model="localPromo.newClientDiscount"
            type="number"
            label="Скидка новым клиентам %"
            placeholder="%"
            :min="1"
            :max="100"
            :clearable="true"
            :error="errors?.newClientDiscount"
            @update:modelValue="updatePromo"
            @blur="validateAndCorrectDiscount"
            class="w-[170px]"
          />
          <div></div> <!-- Пустая колонка 2 -->
          <div class="gift-field-container">
            <BaseInput
              v-model="localPromo.gift"
              type="text"
              label="Подарок"
              placeholder="Что и на каких условиях дарите"
              :clearable="true"
              :error="errors?.gift"
              @update:modelValue="updatePromo"
              class="w-[340px]"
            />
            <div class="field-hint">Можно не заполнять, если у вас нет такой акции</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, reactive, computed } from 'vue'
import BaseInput from '@/src/shared/ui/atoms/BaseInput/BaseInput.vue'
import BaseRadio from '@/src/shared/ui/atoms/BaseRadio/BaseRadio.vue'
import BaseSelect from '@/src/shared/ui/atoms/BaseSelect/BaseSelect.vue'

// Состояние для сворачивания поля "Финалов в час"
const isFinishesExpanded = ref(false)

// Функция переключения поля "Финалов в час"
const toggleFinishesField = () => {
  isFinishesExpanded.value = !isFinishesExpanded.value
}

// Состояние для сворачивания секции "ВЫЕЗД"
const isOutcallExpanded = ref(false)

// Функция переключения секции "ВЫЕЗД"
const toggleOutcallField = () => {
  isOutcallExpanded.value = !isOutcallExpanded.value
}

// Состояние для сворачивания секции "Акции и скидки"
const isPromoExpanded = ref(false)

// Функция переключения секции "Акции и скидки"
const togglePromoField = () => {
  isPromoExpanded.value = !isPromoExpanded.value
}

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
      outcall_night: null,
      finishes_per_hour: ''
    })
  },
  startingPrice: {
    type: String,
    default: null
  },
  errors: {
    type: Object,
    default: () => ({})
  },
  forceValidation: {
    type: Object,
    default: () => ({})
  },
  // Добавляем props для акций
  newClientDiscount: {
    type: String,
    default: ''
  },
  gift: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['update:prices', 'update:startingPrice', 'clearForceValidation', 'update:newClientDiscount', 'update:gift'])

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

// Опции для выпадающего списка "Финалов в час"
const finishesPerHourOptions = computed(() => [
  { value: '', label: 'Не выбрано' },
  { value: '1', label: '1' },
  { value: '2', label: 'до 2-х' },
  { value: '3', label: 'до 3-х' },
  { value: '4', label: 'до 4-х' },
  { value: '5', label: 'до 5-ти' },
  { value: 'unlimited', label: 'безгранично' }
])

// Локальное состояние цен
const localPrices = reactive({
  apartments_express: props.prices?.apartments_express ?? null,
  apartments_1h: props.prices?.apartments_1h ?? null,
  apartments_2h: props.prices?.apartments_2h ?? null,
  apartments_night: props.prices?.apartments_night ?? null,
  outcall_express: props.prices?.outcall_express ?? null,
  outcall_1h: props.prices?.outcall_1h ?? null,
  outcall_2h: props.prices?.outcall_2h ?? null,
  outcall_night: props.prices?.outcall_night ?? null,
  finishes_per_hour: props.prices?.finishes_per_hour ?? ''
})

// Локальное состояние акций
const localPromo = reactive({
  newClientDiscount: props.newClientDiscount ?? '',
  gift: props.gift ?? ''
})



// Следим за изменениями props
watch(() => props.prices, (newPrices) => {
  if (newPrices) {
    // Сохраняем текущее значение finishes_per_hour если оно есть
    const currentFinishesPerHour = localPrices.finishes_per_hour
    
    Object.keys(newPrices).forEach(key => {
      if (newPrices[key] !== undefined) {
        localPrices[key] = newPrices[key]
      }
    })
    
    // Восстанавливаем finishes_per_hour если его не было в newPrices
    if (newPrices.finishes_per_hour === undefined && currentFinishesPerHour) {
      localPrices.finishes_per_hour = currentFinishesPerHour
    }
  }
}, { deep: true, immediate: true })

// Обновляем родительский компонент
const updatePrices = () => {
  emit('update:prices', { ...localPrices })
}

// Обновляем акции
const updatePromo = () => {
  emit('update:newClientDiscount', localPromo.newClientDiscount)
  emit('update:gift', localPromo.gift)
}

// Следим за заполнением поля "1 час в апартаментах" для сброса валидации
watch(() => localPrices.apartments_1h, (newValue) => {
  if (props.forceValidation?.apartments_1h && newValue && Number(newValue) > 0) {
    emit('clearForceValidation', 'apartments_1h')
  }
})

// Следим за заполнением поля "1 час выезд к клиенту" для сброса валидации
watch(() => localPrices.outcall_1h, (newValue) => {
  if (props.forceValidation?.outcall_1h && newValue && Number(newValue) > 0) {
    emit('clearForceValidation', 'outcall_1h')
  }
})

// Следим за изменениями props акций
watch(() => props.newClientDiscount, (newValue) => {
  localPromo.newClientDiscount = newValue ?? ''
})

watch(() => props.gift, (newValue) => {
  localPromo.gift = newValue ?? ''
})

// Локальное состояние для начальной цены
const localStartingPrice = ref(props.startingPrice)

// Проверка заполненности хотя бы одного поля "1 час"
const hasRequiredHourPrice = computed(() => {
  const apartmentPrice = localPrices.apartments_1h && Number(localPrices.apartments_1h) > 0
  const outcallPrice = localPrices.outcall_1h && Number(localPrices.outcall_1h) > 0
  return apartmentPrice || outcallPrice
})

// Показывать общую ошибку если есть принудительная валидация и не заполнены цены
const showPriceError = computed(() => {
  return (props.forceValidation?.apartments_1h || props.forceValidation?.outcall_1h) && !hasRequiredHourPrice.value
})

// Следим за изменениями startingPrice
watch(() => props.startingPrice, (newValue) => {
  localStartingPrice.value = newValue
}, { immediate: true })

// Валидация скидки при потере фокуса
const validateAndCorrectDiscount = () => {
  if (localPromo.newClientDiscount !== null && localPromo.newClientDiscount !== undefined && localPromo.newClientDiscount !== '') {
    let numVal = Number(localPromo.newClientDiscount)
    if (numVal < 1) {
      localPromo.newClientDiscount = 1
    } else if (numVal > 100) {
      localPromo.newClientDiscount = 100
    }
  }
}

// Обновляем начальную цену
const updateStartingPrice = (value) => {
  localStartingPrice.value = value
  emit('update:startingPrice', value)
}
</script>

<style scoped>
.pricing-section {
  display: flex;
  flex-direction: column;
}

.pricing-container {
  display: flex;
  flex-direction: column;
  gap: 42px;
}

.pricing-group {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.pricing-subtitle {
  font-size: 18px;
  font-weight: 600;
  color: #1f2937;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 4px;
  font-family: var(--font-family-primary);
}

.pricing-grid {
  display: grid;
  grid-template-columns: repeat(4, 170px);
  gap: 12px;
  align-items: start;
}

/* Выравнивание полей в секции АПАРТАМЕНТЫ - все поля начинаются с одной линии */
.pricing-group:first-child .pricing-grid {
  align-items: stretch; /* растягиваем все элементы на полную высоту */
}

/* Фиксированная высота для всех labels чтобы выровнять inputs */
.pricing-group:first-child .pricing-grid :deep(.label-field) {
  height: 28px; /* фиксированная высота для всех labels */
  display: flex;
  align-items: center;
}

/* Стили для секции акций */
.promo-section {
}

.promo-subtitle {
  font-size: 18px;
  font-weight: 600;
  color: #1f2937;
  margin: 0 0 12px 0;
  display: flex;
  align-items: center;
  gap: 4px;
  font-family: var(--font-family-primary);
}

/* Принудительно одна строка для заголовка "Скидка новым клиентам" */
.promo-section :deep(label) {
  white-space: nowrap;
}

/* Стили для поля подарка с подсказкой */
.gift-field-container {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.field-hint {
  font-size: 12px;
  color: #666;
  margin-top: 4px;
  line-height: 1.3;
  white-space: nowrap;
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

/* Стили для начальной цены */
.starting-price-section {
  margin-top: 4px;
}

.starting-price-title {
  font-size: 14px;
  font-weight: 600;
  color: #1a1a1a;
  margin: 0 0 8px 0;
}

.radio-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  align-items: center;
}

.radio-item {
  display: flex;
  justify-content: center;
}

/* Мобильная адаптация */
@media (max-width: 640px) {
  .pricing-grid {
    grid-template-columns: repeat(2, 170px);
  }
  
  .radio-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
  }
  
  .radio-group {
    flex-direction: column;
    gap: 12px;
  }
  
  .checkbox-list {
    grid-template-columns: 1fr;
  }
}

.promo-grid {
  grid-template-columns: 170px 170px 340px;
}

</style>