<template>
  <div class="clients-section">
    <div class="checkbox-group">
      <BaseCheckbox
        v-for="option in clientOptions"
        :key="option.value"
        :model-value="localClients.includes(option.value)"
        :label="option.label"
        :error="showError && !hasSelection"
        @update:modelValue="toggleClient(option.value, $event)"
      />
    </div>
    <div v-if="showError && !hasSelection" class="text-sm text-red-600 mt-2">
      Выберите, с какими клиентами работаете
    </div>
    
    <!-- Возраст клиента от -->
    <div class="mt-4 w-[170px]">
      <BaseInput
        v-model="localClientAgeFrom"
        type="number"
        name="client_age_from"
        label="Возраст клиента от"
        placeholder="18"
        :min="18"
        :max="120"
        :clearable="true"
        @blur="validateAndCorrectAge"
        class="w-[170px]"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import BaseCheckbox from '@/src/shared/ui/atoms/BaseCheckbox/BaseCheckbox.vue'
import BaseInput from '@/src/shared/ui/atoms/BaseInput/BaseInput.vue'

const props = defineProps({
  clients: { type: Array, default: () => [] },
  clientAgeFrom: { type: [Number, String], default: null },
  errors: { type: Object, default: () => ({}) },
  forceValidation: { type: Boolean, default: false }
})

const emit = defineEmits(['update:clients', 'update:clientAgeFrom', 'clearForceValidation'])
const localClients = ref([...props.clients])
const localClientAgeFrom = ref(props.clientAgeFrom)

// Состояние для валидации
const touched = ref(false)

// Проверка наличия выбора
const hasSelection = computed(() => localClients.value.length > 0)

// Показывать ошибку если поле тронуто, есть ошибка от родителя или принудительная валидация
const showError = computed(() => touched.value || !!props.errors?.clients || props.forceValidation)

// Опции для чекбоксов
const clientOptions = computed(() => [
  { value: 'men', label: 'Мужчины' },
  { value: 'women', label: 'Женщины' },
  { value: 'couples', label: 'Пары' }
])

watch(() => props.clients, (val) => { 
  localClients.value = [...val] 
})

watch(() => props.clientAgeFrom, (val) => {
  localClientAgeFrom.value = val
})

watch(() => localClientAgeFrom.value, (val) => {
  emit('update:clientAgeFrom', val)
})

// Валидация при потере фокуса
const validateAndCorrectAge = () => {
  if (localClientAgeFrom.value !== null && localClientAgeFrom.value !== undefined && localClientAgeFrom.value !== '') {
    let numVal = Number(localClientAgeFrom.value)
    if (numVal < 18) {
      localClientAgeFrom.value = 18
    } else if (numVal > 120) {
      localClientAgeFrom.value = 120
    }
  }
}

const toggleClient = (value, checked) => {
  touched.value = true // Помечаем что поле тронуто
  
  if (checked) {
    if (!localClients.value.includes(value)) {
      localClients.value.push(value)
    }
  } else {
    const index = localClients.value.indexOf(value)
    if (index > -1) {
      localClients.value.splice(index, 1)
    }
  }
  emit('update:clients', localClients.value)
  
  // Сбрасываем флаг принудительной валидации если выбран хотя бы один клиент
  if (props.forceValidation && localClients.value.length > 0) {
    emit('clearForceValidation')
  }
}
</script>

<style scoped>
.clients-section {
  /* Убираем лишние стили, теперь это подсекция */
}

.checkbox-group { 
  display: flex; 
  flex-direction: column; 
  gap: 12px; 
}

.error-message { 
  color: #dc3545; 
  font-size: 0.875rem; 
  margin-top: 0.5rem; 
}
</style>