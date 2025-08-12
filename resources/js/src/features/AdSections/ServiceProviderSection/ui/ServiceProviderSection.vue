<template>
  <div class="service-provider-section">
    <h2 class="form-group-title">Кто оказывает услуги</h2>
    <div class="checkbox-group">
      <BaseCheckbox
        v-for="option in providerOptions"
        :key="option.value"
        :model-value="localProviders.includes(option.value)"
        :label="option.label"
        @update:modelValue="toggleProvider(option.value, $event)"
      />
    </div>
    <div v-if="errors.service_provider" class="error-message">
      {{ errors.service_provider }}
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import BaseCheckbox from '@/src/shared/ui/atoms/BaseCheckbox/BaseCheckbox.vue'

const props = defineProps({
  serviceProvider: { type: Array, default: () => [] },
  errors: { type: Object, default: () => ({}) }
})

const emit = defineEmits(['update:serviceProvider'])

const localProviders = ref([...props.serviceProvider])

watch(() => props.serviceProvider, (val) => {
  localProviders.value = [...val]
})

// Опции для CheckboxGroup
const providerOptions = computed(() => [
  { value: 'women', label: 'Женщина' },
  { value: 'men', label: 'Мужчина' },
  { value: 'couple', label: 'Пара' }
])

const toggleProvider = (value, checked) => {
  if (checked) {
    if (!localProviders.value.includes(value)) {
      localProviders.value.push(value)
    }
  } else {
    const index = localProviders.value.indexOf(value)
    if (index > -1) {
      localProviders.value.splice(index, 1)
    }
  }
  emit('update:serviceProvider', [...localProviders.value])
}
</script>

<style scoped>
.service-provider-section {
  background: white;
  border-radius: 8px;
  padding: 20px;
  margin-bottom: 24px;
}

.form-group-title {
  font-size: 18px;
  font-weight: 600;
  color: #333;
  margin: 0 0 20px 0;
  line-height: 1.3;
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