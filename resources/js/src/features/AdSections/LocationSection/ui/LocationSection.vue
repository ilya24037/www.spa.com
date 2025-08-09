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
          :class="{ 'checked': isSelected(option.value) }"
        >
          <svg 
            class="check-icon" 
            v-if="isSelected(option.value)"
            width="12" 
            height="10" 
            viewBox="0 0 10 8" 
            fill="none" 
            xmlns="http://www.w3.org/2000/svg"
          >
            <path 
              d="M1 4.35714L3.4 6.5L9 1.5" 
              stroke="currentColor" 
              stroke-width="2" 
              stroke-linecap="round"
            />
          </svg>
        </div>
        <span class="checkbox-label">{{ option.label }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'

const props = defineProps({
  serviceLocation: { type: Array, default: () => [] },
  errors: { type: Object, default: () => ({}) }
})

const emit = defineEmits(['update:serviceLocation'])

const localLocation = ref([...props.serviceLocation])

watch(() => props.serviceLocation, (val) => {
  localLocation.value = [...val]
})

// Опции для выбора локации
const locationOptions = [
  { value: 'У заказчика дома', label: 'У заказчика дома' },
  { value: 'У себя дома', label: 'У себя дома' },
  { value: 'В офисе', label: 'В офисе' }
]

const isSelected = (value) => {
  return localLocation.value.includes(value)
}

const toggleOption = (value) => {
  const index = localLocation.value.indexOf(value)
  if (index > -1) {
    localLocation.value.splice(index, 1)
  } else {
    localLocation.value.push(value)
  }
  emitLocation()
}

const emitLocation = () => {
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

.custom-checkbox:hover {
  border-color: #8c8c8c;
}

.custom-checkbox.checked {
  background: #007bff;
  border-color: #007bff;
}

.check-icon {
  width: 12px;
  height: 10px;
  color: #fff;
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
