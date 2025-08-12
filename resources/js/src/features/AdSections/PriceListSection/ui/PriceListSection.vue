<template>
  <div class="price-list-section">
    <h2 class="form-group-title">Прайс-лист</h2>
    <p class="section-description">
      Создайте подробный прайс-лист ваших услуг. Клиенты смогут увидеть все цены.
    </p>
    
    <div class="price-list-fields">
      <!-- Основная услуга -->
      <div class="main-service">
        <h4 class="field-title">Основная услуга</h4>
        <div class="service-row">
          <BaseInput
            v-model="localMainServiceName"
            type="text"
            placeholder="Название услуги"
            :error="errors?.main_service_name?.[0]"
          />
          <div class="price-input-group">
            <BaseInput
              v-model="localMainServicePrice"
              type="number"
              placeholder="0"
              :error="errors?.main_service_price?.[0]"
              class="price-input"
            />
            <BaseSelect
              v-model="localMainServicePriceUnit"
              :options="priceUnitOptions"
              class="price-unit"
            />
          </div>
        </div>
      </div>

      <!-- Дополнительные услуги -->
      <div class="additional-services">
        <div class="services-header">
          <h4 class="field-title">Дополнительные услуги</h4>
          <button 
            type="button" 
            class="btn-add-service"
            @click="addService"
          >
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <line x1="12" y1="5" x2="12" y2="19" stroke-width="2"/>
              <line x1="5" y1="12" x2="19" y2="12" stroke-width="2"/>
            </svg>
            Добавить услугу
          </button>
        </div>

        <div v-if="localAdditionalServices.length === 0" class="empty-services">
          <p>Добавьте дополнительные услуги, которые вы предоставляете</p>
        </div>

        <div v-else class="services-list">
          <div 
            v-for="(service, index) in localAdditionalServices" 
            :key="service.id || index"
            class="service-item"
          >
            <div class="service-item-header">
              <span class="service-number">{{ index + 1 }}.</span>
              <button 
                type="button"
                @click="removeService(index)"
                class="btn-remove"
                title="Удалить услугу"
              >
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                  <line x1="18" y1="6" x2="6" y2="18" stroke-width="2"/>
                  <line x1="6" y1="6" x2="18" y2="18" stroke-width="2"/>
                </svg>
              </button>
            </div>
            
            <div class="service-fields">
              <BaseInput
                v-model="service.name"
                type="text"
                placeholder="Название услуги"
                @update:modelValue="updateServices"
              />
              
              <div class="price-input-group">
                <BaseInput
                  v-model="service.price"
                  type="number"
                  placeholder="0"
                  class="price-input"
                  @update:modelValue="updateServices"
                />
                <BaseSelect
                  v-model="service.unit"
                  :options="priceUnitOptions"
                  class="price-unit"
                  @update:modelValue="updateServices"
                />
              </div>
              
              <BaseTextarea
                v-model="service.description"
                placeholder="Описание услуги (необязательно)"
                :rows="2"
                @update:modelValue="updateServices"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import BaseInput from '@/src/shared/ui/atoms/BaseInput/BaseInput.vue'
import BaseSelect from '@/src/shared/ui/atoms/BaseSelect/BaseSelect.vue'
import BaseTextarea from '@/src/shared/ui/atoms/BaseTextarea/BaseTextarea.vue'

// Types
interface ServiceItem {
  id?: number
  name: string
  price: number | string
  unit: string
  description?: string
}

// Props
interface Props {
  mainServiceName?: string
  mainServicePrice?: number | null
  mainServicePriceUnit?: string
  additionalServices?: ServiceItem[]
  errors?: Record<string, string[]>
}

const props = withDefaults(defineProps<Props>(), {
  mainServiceName: '',
  mainServicePrice: null,
  mainServicePriceUnit: 'hour',
  additionalServices: () => [],
  errors: () => ({})
})

// Опции для единиц стоимости
const priceUnitOptions = computed(() => [
  { value: 'hour', label: 'за час' },
  { value: 'session', label: 'за сеанс' },
  { value: 'day', label: 'за день' },
  { value: 'service', label: 'за услугу' }
])

// Emits
const emit = defineEmits<{
  'update:mainServiceName': [value: string]
  'update:mainServicePrice': [value: number | null]
  'update:mainServicePriceUnit': [value: string]
  'update:additionalServices': [value: ServiceItem[]]
}>()

// Local state
const localMainServiceName = ref(props.mainServiceName)
const localMainServicePrice = ref(props.mainServicePrice)
const localMainServicePriceUnit = ref(props.mainServicePriceUnit)
const localAdditionalServices = ref<ServiceItem[]>([...props.additionalServices])

// Watch for prop changes
watch(() => props.mainServiceName, (newVal) => {
  localMainServiceName.value = newVal
})

watch(() => props.mainServicePrice, (newVal) => {
  localMainServicePrice.value = newVal
})

watch(() => props.mainServicePriceUnit, (newVal) => {
  localMainServicePriceUnit.value = newVal
})

watch(() => props.additionalServices, (newVal) => {
  localAdditionalServices.value = [...newVal]
}, { deep: true })

// Watch local changes and emit
watch(localMainServiceName, (newVal) => {
  emit('update:mainServiceName', newVal)
})

watch(localMainServicePrice, (newVal) => {
  emit('update:mainServicePrice', newVal)
})

watch(localMainServicePriceUnit, (newVal) => {
  emit('update:mainServicePriceUnit', newVal)
})

// Methods
const addService = () => {
  const newService: ServiceItem = {
    id: Date.now(),
    name: '',
    price: '',
    unit: 'service',
    description: ''
  }
  
  localAdditionalServices.value.push(newService)
  emit('update:additionalServices', localAdditionalServices.value)
}

const removeService = (index: number) => {
  localAdditionalServices.value.splice(index, 1)
  emit('update:additionalServices', localAdditionalServices.value)
}

const updateServices = () => {
  emit('update:additionalServices', localAdditionalServices.value)
}
</script>

<style scoped>
.price-list-section {
  @apply space-y-4;
}

.form-group-title {
  @apply text-xl font-semibold text-gray-900 mb-2;
}

.section-description {
  @apply text-gray-600 mb-4;
}

.price-list-fields {
  @apply space-y-6;
}

/* Основная услуга */
.main-service {
  @apply bg-gray-50 rounded-lg p-4;
}

.field-title {
  @apply text-sm font-medium text-gray-700 mb-3;
}

.service-row {
  @apply flex gap-3;
}

.form-input {
  @apply flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors;
}

.form-input.error {
  @apply border-red-500;
}

.price-input-group {
  @apply flex gap-2;
}

.price-input {
  @apply w-32;
}

.price-unit {
  @apply w-32;
}

.form-select {
  @apply px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors bg-white;
}

.error-message {
  @apply text-red-500 text-sm mt-1 block;
}

/* Дополнительные услуги */
.additional-services {
  @apply space-y-4;
}

.services-header {
  @apply flex justify-between items-center;
}

.btn-add-service {
  @apply flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors;
}

.empty-services {
  @apply text-center py-8 text-gray-500 bg-gray-50 rounded-lg;
}

.services-list {
  @apply space-y-4;
}

.service-item {
  @apply bg-white border border-gray-200 rounded-lg p-4;
}

.service-item-header {
  @apply flex justify-between items-center mb-3;
}

.service-number {
  @apply font-medium text-gray-700;
}

.btn-remove {
  @apply p-1 text-red-500 hover:bg-red-50 rounded transition-colors;
}

.service-fields {
  @apply space-y-3;
}

.form-textarea {
  @apply w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors resize-none;
}
</style>