<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <h4 class="text-lg font-medium text-gray-900">Дополнительные услуги</h4>
      <ActionButton
        variant="secondary"
        size="small"
        @click="$emit('add-service')"
      >
        + Добавить услугу
      </ActionButton>
    </div>

    <div v-if="services.length === 0" class="text-center py-8 text-gray-500 bg-gray-50 rounded-lg">
      Добавьте дополнительные услуги, которые вы предоставляете
    </div>

    <div v-else class="space-y-4">
      <div
        v-for="(service, index) in services"
        :key="service.id"
        class="relative p-4 border border-gray-200 rounded-lg bg-white"
      >
        <!-- Кнопка удаления -->
        <button
          type="button"
          @click="$emit('remove-service', index)"
          class="absolute top-3 right-3 p-1 text-gray-400 hover:text-red-500 transition-colors"
          title="Удалить услугу"
        >
          <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"/>
            <line x1="6" y1="6" x2="18" y2="18"/>
          </svg>
        </button>

        <div class="space-y-4 pr-8">
          <!-- Название и цена -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <FormField
              label="Название услуги"
              :error="getServiceError(index, 'name')"
            >
              <BaseInput
                :model-value="service.name"
                @update:model-value="updateService(index, 'name', $event)"
                placeholder="Антицеллюлитный массаж"
              />
            </FormField>

            <FormField
              label="Цена"
              :error="getServiceError(index, 'price')"
            >
              <div class="flex gap-2">
                <BaseInput
                  :model-value="service.price"
                  @update:model-value="updateService(index, 'price', $event)"
                  type="number"
                  min="0"
                  step="100"
                  placeholder="4000"
                  suffix="₽"
                  class="flex-1"
                />
                <BaseSelect
                  :model-value="service.price_unit"
                  @update:model-value="updateService(index, 'price_unit', $event)"
                  :options="priceUnitOptions"
                  class="w-32"
                />
              </div>
            </FormField>
          </div>

          <!-- Описание -->
          <FormField
            label="Описание (необязательно)"
            hint="Краткое описание услуги для клиентов"
            :error="getServiceError(index, 'description')"
          >
            <BaseTextarea
              :model-value="service.description"
              @update:model-value="updateService(index, 'description', $event)"
              :rows="2"
              placeholder="Описание техники, длительности, особенностей услуги"
            />
          </FormField>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import FormField from '@/Components/UI/Forms/FormField.vue'
import BaseInput from '@/Components/UI/BaseInput.vue'
import BaseSelect from '@/Components/UI/BaseSelect.vue'
import BaseTextarea from '@/Components/UI/BaseTextarea.vue'
import ActionButton from '@/Components/UI/Buttons/ActionButton.vue'

const props = defineProps({
  services: {
    type: Array,
    default: () => []
  },
  errors: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['add-service', 'remove-service', 'update-service'])

// Опции для единиц измерения цены
const priceUnitOptions = [
  { value: 'час', label: 'за час' },
  { value: 'сеанс', label: 'за сеанс' },
  { value: 'услуга', label: 'за услугу' },
  { value: 'программа', label: 'за программу' }
]

// Методы
const updateService = (index, field, value) => {
  emit('update-service', index, field, value)
}

const getServiceError = (index, field) => {
  const errorKey = `additional_services.${index}.${field}`
  return props.errors[errorKey] || ''
}
</script>