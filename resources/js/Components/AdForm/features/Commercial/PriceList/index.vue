<template>
  <FormSection
    title="Прайс-лист"
    hint="Создайте подробный прайс-лист ваших услуг. Клиенты смогут увидеть все цены."
    :errors="errors"
    :error-keys="['main_service_name', 'main_service_price', 'additional_services']"
  >
    <div class="space-y-6">
      <!-- Основная услуга -->
      <MainServicePrice
        :service-name="mainServiceName"
        :service-price="mainServicePrice"
        :price-unit="mainServicePriceUnit"
        :errors="errors"
        @update:service-name="updateField('main_service_name', $event)"
        @update:service-price="updateField('main_service_price', $event)"
        @update:price-unit="updateField('main_service_price_unit', $event)"
      />

      <!-- Дополнительные услуги -->
      <AdditionalServicesList
        :services="additionalServices"
        :errors="errors"
        @add-service="addService"
        @remove-service="removeService"
        @update-service="updateService"
      />

      <!-- Советы по ценообразованию -->
      <PriceListTips />
    </div>
  </FormSection>
</template>

<script setup>
import { computed } from 'vue'
import FormSection from '@/Components/UI/Forms/FormSection.vue'
import { useAdFormStore } from '../../../stores/adFormStore'

// Подкомпоненты
import MainServicePrice from './components/MainServicePrice.vue'
import AdditionalServicesList from './components/AdditionalServicesList.vue'
import PriceListTips from './components/PriceListTips.vue'

// AVITO-STYLE: Используем централизованный store
const store = useAdFormStore()

const props = defineProps({
  errors: {
    type: Object,
    default: () => ({})
  }
})

// Читаем данные ТОЛЬКО из store (как на Avito)
const mainServiceName = computed(() => store.formData.main_service_name || '')
const mainServicePrice = computed(() => store.formData.main_service_price || '')
const mainServicePriceUnit = computed(() => store.formData.main_service_price_unit || 'час')
const additionalServices = computed(() => store.formData.additional_services || [])

// Методы обновляют ТОЛЬКО store (как на Avito/Ozon)
const updateField = (field, value) => {
  console.log('updateField called:', field, value)
  store.updateField(field, value)
}

const addService = () => {
  const newService = {
    id: Date.now() + Math.random(),
    name: '',
    price: '',
    price_unit: 'час',
    description: ''
  }
  
  const currentServices = [...additionalServices.value]
  currentServices.push(newService)
  
  console.log('addService called, new services:', currentServices)
  store.updateField('additional_services', currentServices)
}

const removeService = (index) => {
  const currentServices = [...additionalServices.value]
  currentServices.splice(index, 1)
  
  console.log('removeService called, index:', index, 'new services:', currentServices)
  store.updateField('additional_services', currentServices)
}

const updateService = (index, field, value) => {
  const currentServices = [...additionalServices.value]
  currentServices[index] = { ...currentServices[index], [field]: value }
  
  console.log('updateService called:', index, field, value)
  store.updateField('additional_services', currentServices)
}
</script>