<template>
  <Card v-if="showDistricts" variant="bordered" class="bg-slate-50">
    <div class="space-y-4">
      <div class="flex items-center space-x-2">
        <span class="text-lg">🗺️</span>
        <span class="font-semibold text-gray-800">Районы выезда</span>
      </div>
      
      <FormField
        label="Районы выезда"
        hint="Выберите районы, куда вы готовы выезжать"
        :error="error"
      >
        <!-- Используем готовый CheckboxGroup -->
        <CheckboxGroup 
          v-model="localValue"
          :options="districtOptions"
          direction="column"
          class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2"
        />
      </FormField>
      
      <!-- Быстрый выбор -->
      <div class="pt-4 border-t border-gray-200">
        <p class="text-sm font-medium text-gray-700 mb-3">Быстрый выбор:</p>
        <div class="flex flex-wrap gap-2">
          <button
            type="button"
            @click="selectAllDistricts"
            class="px-3 py-1 text-xs bg-blue-100 text-blue-800 rounded-full hover:bg-blue-200 transition-colors"
          >
            Все районы
          </button>
          <button
            type="button"
            @click="selectCentralDistricts"
            class="px-3 py-1 text-xs bg-green-100 text-green-800 rounded-full hover:bg-green-200 transition-colors"
          >
            Только центр
          </button>
          <button
            type="button"
            @click="clearDistricts"
            class="px-3 py-1 text-xs bg-gray-100 text-gray-800 rounded-full hover:bg-gray-200 transition-colors"
          >
            Очистить
          </button>
        </div>
      </div>
    </div>
  </Card>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import CheckboxGroup from '@/Components/UI/CheckboxGroup.vue'
import FormField from '@/Components/UI/Forms/FormField.vue'
import Card from '@/Components/UI/Cards/Card.vue'

const props = defineProps({
  modelValue: { type: Array, default: () => [] },
  selectedServiceTypes: { type: Array, default: () => [] },
  error: { type: String, default: '' }
})

const emit = defineEmits(['update:modelValue'])

const localValue = ref([...props.modelValue])

watch(() => props.modelValue, (newValue) => {
  localValue.value = Array.isArray(newValue) ? [...newValue] : []
})

watch(localValue, (newValue) => {
  emit('update:modelValue', newValue)
}, { deep: true })

// Показывать только если выбран выезд
const showDistricts = computed(() => {
  return props.selectedServiceTypes.includes('outcall')
})

// Районы Москвы
const districts = [
  'Центральный', 'Северный', 'Северо-Восточный', 'Восточный', 
  'Юго-Восточный', 'Южный', 'Юго-Западный', 'Западный', 
  'Северо-Западный', 'Зеленоград'
]

const centralDistricts = ['Центральный', 'Северный', 'Западный']

const districtOptions = districts.map(district => ({
  value: district,
  label: district
}))

// Методы быстрого выбора
const selectAllDistricts = () => {
  localValue.value = [...districts]
}

const selectCentralDistricts = () => {
  localValue.value = [...centralDistricts]
}

const clearDistricts = () => {
  localValue.value = []
}
</script>