<!-- Р‘Р°Р·РѕРІР°СЏ СЂР°РґРёРѕРєРЅРѕРїРєР° РІ СЃС‚РёР»Рµ РђРІРёС‚Рѕ -->
<template>
  <div 
    class="radio-container" 
    :class="{ 'disabled': disabled }"
    @click="select"
  >
    <div 
      class="custom-radio"
      :class="{ 
        'checked': isSelected,
        'disabled': disabled 
      }"
    >
      <div v-if="isSelected" class="radio-dot" />
    </div>
    
    <div v-if="label || $slots.default" class="radio-content">
      <div v-if="label" class="radio-label">
        {{ label }}
      </div>
      <div v-if="description" class="radio-description">
        {{ description }}
      </div>
      <slot />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

// Props
const props = defineProps({
    modelValue: {
        type: [String, Number, Boolean],
        default: null
    },
    value: {
        type: [String, Number, Boolean],
        required: true
    },
    label: {
        type: String,
        default: ''
    },
    description: {
        type: String,
        default: ''
    },
    disabled: {
        type: Boolean,
        default: false
    }
})

// Events
const emit = defineEmits(['update:modelValue'])

// Computed
const isSelected = computed(() => {
    return props.modelValue === props.value
})

// Methods
const select = () => {
    if (props.disabled) return
    emit('update:modelValue', props.value)
}
</script>

<style scoped>
.radio-container {
  display: flex;
  align-items: flex-start;
  cursor: pointer;
  padding: 12px 0;
  gap: 12px;
  user-select: none;
}

.radio-container.disabled {
  cursor: not-allowed;
  opacity: 0.5;
}

.custom-radio {
  width: 20px;
  height: 20px;
  border: 2px solid #d9d9d9;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
  background: #fff;
  flex-shrink: 0;
  cursor: pointer;
}

.custom-radio:hover:not(.disabled) {
  border-color: #8c8c8c;
}

.custom-radio.checked {
  border-color: #000;
}

.custom-radio.disabled {
  cursor: not-allowed;
  background: #f5f5f5;
}

.radio-dot {
  width: 8px;
  height: 8px;
  background: #000;
  border-radius: 50%;
  transition: all 0.2s ease;
}

.radio-content {
  flex: 1;
  min-width: 0;
}

.radio-label {
  font-size: 16px;
  color: #1a1a1a;
  font-weight: 400;
  line-height: 1.4;
  margin-bottom: 4px;
}

.radio-description {
  font-size: 14px;
  color: #8c8c8c;
  line-height: 1.4;
}

.radio-container.disabled .radio-label {
  color: #8c8c8c;
}

.radio-container.disabled .radio-description {
  color: #bfbfbf;
}
</style> 

