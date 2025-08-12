<template>
  <div class="location-section">
    <h2 class="form-group-title">Где вы оказываете услуги</h2>
    <div class="checkbox-group">
      <BaseCheckbox
        v-for="option in locationOptions"
        :key="option.value"
        :model-value="localLocation.includes(option.value)"
        :label="option.label"
        @update:modelValue="toggleOption(option.value, $event)"
      />
    </div>
    <div v-if="errors.service_location" class="error-message">
      {{ errors.service_location }}
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import BaseCheckbox from '@/src/shared/ui/atoms/BaseCheckbox/BaseCheckbox.vue'

const props = defineProps({
  serviceLocation: { type: Array, default: () => [] },
  errors: { type: Object, default: () => ({}) }
})

const emit = defineEmits(['update:serviceLocation'])

const localLocation = ref([...props.serviceLocation])

watch(() => props.serviceLocation, (val) => {
  localLocation.value = [...val]
}, { deep: true })

// Опции для выбора локации
const locationOptions = computed(() => [
  { value: 'У заказчика дома', label: 'У заказчика дома' },
  { value: 'У себя дома', label: 'У себя дома' },
  { value: 'В офисе', label: 'В офисе' }
])

const toggleOption = (value, checked) => {
  if (checked) {
    if (!localLocation.value.includes(value)) {
      localLocation.value.push(value)
    }
  } else {
    const index = localLocation.value.indexOf(value)
    if (index > -1) {
      localLocation.value.splice(index, 1)
    }
  }
  emit('update:serviceLocation', [...localLocation.value])
}
</script>

<style scoped>
.location-section {
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