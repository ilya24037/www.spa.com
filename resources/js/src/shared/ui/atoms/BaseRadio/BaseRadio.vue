<!-- Р'Р°Р·РѕРІР°СЏ СЂР°РґРёРѕРєРЅРѕРїРєР° РІ СЃС‚РёР»Рµ РђРІРёС‚Рѕ -->
<template>
  <div 
    class="radio-container" 
    :class="{ 'disabled': disabled }"
    @click="select"
  >
    <!-- Скрытый input для форм -->
    <input 
      :id="radioId"
      type="radio"
      :name="radioName"
      :value="value"
      :checked="isSelected"
      :disabled="disabled"
      :aria-label="!label && !description ? `Radio option ${value}` : undefined"
      :aria-describedby="description ? `${radioId}-description` : undefined"
      :aria-checked="isSelected"
      :required="required"
      class="sr-only"
      @change="select"
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
    
    <label v-if="label || $slots.default" :for="radioId" class="radio-content">
      <div v-if="label" class="radio-label">
        {{ label }}
      </div>
      <div v-if="description" :id="`${radioId}-description`" class="radio-description">
        {{ description }}
      </div>
      <slot />
    </label>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useId } from '@/src/shared/composables/useId'

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
    id: {
        type: String,
        default: ''
    },
    name: {
        type: String,
        default: ''
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
    },
    required: {
        type: Boolean,
        default: false
    }
})

// Events
const emit = defineEmits(['update:modelValue'])

// Generate unique ID if not provided
const radioId = computed(() => props.id || useId('radio'))

// Auto-generate name from label if not provided (для группировки)
const radioName = computed(() => {
    if (props.name) return props.name
    // Radio кнопки требуют name для группировки - генерируем из контекста
    console.warn('[BaseRadio] name обязателен для правильной группировки radio кнопок')
    return 'radio-group-' + Math.random().toString(36).substr(2, 9)
})

// Validate and warn about accessibility in development mode
if (process.env.NODE_ENV !== 'production') {
    if (!props.name) {
        console.warn('[BaseRadio] Атрибут name обязателен для группировки radio кнопок. Автоматически сгенерировано временное значение.')
    }
    if (!props.label && !props.description) {
        console.warn('[BaseRadio] Рекомендуется указать label или description для доступности')
    }
}

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
/* Класс для скрытия элемента (screen reader only) */
.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border-width: 0;
}

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
  border: 2px solid #d1d5db;
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
  border-color: #9ca3af;
}

.custom-radio.checked {
  border-color: #6b7280;
}

.custom-radio.disabled {
  cursor: not-allowed;
  background: #f5f5f5;
}

.radio-dot {
  width: 8px;
  height: 8px;
  background: #6b7280;
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

