<template>
  <div class="pricing-section">
    <!-- В апартаментах -->
    <div class="pricing-block">
      <h3 class="pricing-block-title">В апартаментах</h3>
      <div class="pricing-grid">
        <div class="price-item">
          <label class="price-label">Экспресс (30 мин)</label>
          <BaseInput
            v-model="localPrices.apartments_express"
            type="number"
            placeholder="0"
            :min="0"
            @update:modelValue="updatePrices"
          >
            <template #suffix>₽</template>
          </BaseInput>
        </div>
        <div class="price-item">
          <label class="price-label">1 час</label>
          <BaseInput
            v-model="localPrices.apartments_1h"
            type="number"
            placeholder="0"
            :min="0"
            @update:modelValue="updatePrices"
          >
            <template #suffix>₽</template>
          </BaseInput>
        </div>
        <div class="price-item">
          <label class="price-label">2 часа</label>
          <BaseInput
            v-model="localPrices.apartments_2h"
            type="number"
            placeholder="0"
            :min="0"
            @update:modelValue="updatePrices"
          >
            <template #suffix>₽</template>
          </BaseInput>
        </div>
        <div class="price-item">
          <label class="price-label">Ночь</label>
          <BaseInput
            v-model="localPrices.apartments_night"
            type="number"
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
          <label class="price-label">Экспресс (30 мин)</label>
          <BaseInput
            v-model="localPrices.outcall_express"
            type="number"
            placeholder="0"
            :min="0"
            @update:modelValue="updatePrices"
          >
            <template #suffix>₽</template>
          </BaseInput>
        </div>
        <div class="price-item">
          <label class="price-label">1 час</label>
          <BaseInput
            v-model="localPrices.outcall_1h"
            type="number"
            placeholder="0"
            :min="0"
            @update:modelValue="updatePrices"
          >
            <template #suffix>₽</template>
          </BaseInput>
        </div>
        <div class="price-item">
          <label class="price-label">2 часа</label>
          <BaseInput
            v-model="localPrices.outcall_2h"
            type="number"
            placeholder="0"
            :min="0"
            @update:modelValue="updatePrices"
          >
            <template #suffix>₽</template>
          </BaseInput>
        </div>
        <div class="price-item">
          <label class="price-label">Ночь</label>
          <BaseInput
            v-model="localPrices.outcall_night"
            type="number"
            placeholder="0"
            :min="0"
            @update:modelValue="updatePrices"
          >
            <template #suffix>₽</template>
          </BaseInput>
        </div>
      </div>
      
      <!-- Места выезда -->
      <div class="outcall-locations" v-if="hasOutcallPrices">
        <div class="locations-header" @click="isOutcallExpanded = !isOutcallExpanded">
          <label class="locations-label">Куда выезжаете:</label>
          <svg 
            class="expand-icon" 
            :class="{ 'rotate-180': isOutcallExpanded }"
            width="20" 
            height="20" 
            viewBox="0 0 20 20"
            fill="none"
          >
            <path 
              d="M5 7.5L10 12.5L15 7.5" 
              stroke="currentColor" 
              stroke-width="2" 
              stroke-linecap="round" 
              stroke-linejoin="round"
            />
          </svg>
        </div>
        <Transition name="collapse">
          <div v-show="isOutcallExpanded" class="locations-content">
            <div class="checkbox-list">
              <BaseCheckbox
                v-model="localPrices.outcall_apartment"
                label="На квартиру"
                @update:modelValue="updatePrices"
              />
              <BaseCheckbox
                v-model="localPrices.outcall_hotel"
                label="В гостиницу"
                @update:modelValue="updatePrices"
              />
              <BaseCheckbox
                v-model="localPrices.outcall_house"
                label="В загородный дом"
                @update:modelValue="updatePrices"
              />
              <BaseCheckbox
                v-model="localPrices.outcall_sauna"
                label="В сауну"
                @update:modelValue="updatePrices"
              />
              <BaseCheckbox
                v-model="localPrices.outcall_office"
                label="В офис"
                @update:modelValue="updatePrices"
              />
            </div>
            
            <!-- Такси -->
            <div class="taxi-options">
              <label class="taxi-label">Такси:</label>
              <div class="radio-group">
                <BaseRadio
                  v-model="localPrices.taxi_included"
                  :value="false"
                  label="Оплачивается отдельно"
                  name="taxi"
                  @update:modelValue="updatePrices"
                />
                <BaseRadio
                  v-model="localPrices.taxi_included"
                  :value="true"
                  label="Включено в стоимость"
                  name="taxi"
                  @update:modelValue="updatePrices"
                />
              </div>
            </div>
          </div>
        </Transition>
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
import BaseCheckbox from '@/src/shared/ui/atoms/BaseCheckbox/BaseCheckbox.vue'
import BaseRadio from '@/src/shared/ui/atoms/BaseRadio/BaseRadio.vue'

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
      taxi_included: false,
      outcall_apartment: true,
      outcall_hotel: false,
      outcall_house: false,
      outcall_sauna: false,
      outcall_office: false
    })
  },
  errors: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['update:prices'])

// Состояние для разворачивания секции выезда
const isOutcallExpanded = ref(false)

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
  taxi_included: props.prices?.taxi_included ?? false,
  // Места выезда
  outcall_apartment: props.prices?.outcall_apartment ?? true,
  outcall_hotel: props.prices?.outcall_hotel ?? false,
  outcall_house: props.prices?.outcall_house ?? false,
  outcall_sauna: props.prices?.outcall_sauna ?? false,
  outcall_office: props.prices?.outcall_office ?? false
})

// Проверяем, указаны ли цены на выезд
const hasOutcallPrices = computed(() => {
  return localPrices.outcall_express || localPrices.outcall_1h || localPrices.outcall_2h || localPrices.outcall_night
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