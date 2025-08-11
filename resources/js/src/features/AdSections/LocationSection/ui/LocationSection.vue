<template>
  <div class="location-section">
    <h2 class="form-group-title">Где вы оказываете услуги</h2>
    <div class="checkbox-group">
      <div 
        v-for="option in locationOptions" 
        :key="option.value"
        class="checkbox-item"
        @click="toggleOption(option.value)"
      >
        <div 
          class="custom-checkbox"
          :class="{ 'checked': localLocation.includes(option.value) }"
        >
          <svg v-if="localLocation.includes(option.value)" class="checkmark" viewBox="0 0 24 24">
            <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" fill="currentColor"/>
          </svg>
        </div>
        <span class="checkbox-label">{{ option.label }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'

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
const locationOptions = [
  { value: 'У заказчика дома', label: 'У заказчика дома' },
  { value: 'У себя дома', label: 'У себя дома' },
  { value: 'В офисе', label: 'В офисе' }
]

const toggleOption = (value) => {
  const index = localLocation.value.indexOf(value)
  if (index > -1) {
    localLocation.value.splice(index, 1)
  } else {
    localLocation.value.push(value)
  }
  emit('update:serviceLocation', [...localLocation.value])
}
</script>

<style scoped>
.location-section {
  margin-bottom: 24px;
}

.form-group-title {
  font-size: 20px;
  font-weight: 500;
  color: #000000;
  margin: 0 0 20px 0;
  line-height: 1.3;
}

.checkbox-group {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.checkbox-item {
  display: flex;
  align-items: center;
  cursor: pointer;
  gap: 12px;
  padding: 8px 0;
  user-select: none;
}

.checkbox-item:hover .custom-checkbox {
  border-color: #8c8c8c;
}

.custom-checkbox {
  width: 20px;
  height: 20px;
  border: 2px solid #d9d9d9;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
  background: #fff;
  flex-shrink: 0;
  cursor: pointer;
}

.custom-checkbox.checked {
  border-color: #007bff;
  background: #007bff;
}

.checkmark {
  width: 14px;
  height: 14px;
  color: white;
}

.checkbox-label {
  font-size: 16px;
  color: #1a1a1a;
  font-weight: 400;
  line-height: 1.4;
  cursor: pointer;
  user-select: none;
}
</style>
