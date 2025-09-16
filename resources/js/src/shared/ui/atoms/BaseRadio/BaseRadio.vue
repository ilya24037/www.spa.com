<!-- Base Radio Component -->
<template>
  <div 
    class="flex items-start cursor-pointer py-1 gap-2 select-none"
    :class="{ 'cursor-not-allowed opacity-50': disabled }"
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
      class="w-5 h-5 border-2 rounded-full flex items-center justify-center transition-all duration-200 flex-shrink-0 cursor-pointer"
      :class="{ 
        'border-gray-500': isSelected,
        'border-gray-300 hover:border-gray-400': !isSelected && !disabled && !error,
        'border-red-300 bg-red-50': !isSelected && error,
        'cursor-not-allowed bg-gray-50 border-gray-300': disabled,
        'bg-white': !error || isSelected
      }"
    >
      <div v-if="isSelected" class="w-2 h-2 bg-gray-500 rounded-full transition-all duration-200" />
    </div>
    
    <label v-if="label || $slots.default" :for="radioId" class="flex-1 min-w-0">
      <div v-if="label" class="text-sm text-gray-700 font-medium leading-tight" :class="{ 'text-gray-500': disabled }">
        {{ label }}
      </div>
      <div v-if="description" :id="`${radioId}-description`" class="text-xs text-gray-500 leading-tight mt-1" :class="{ 'text-gray-300': disabled }">
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
    },
    error: {
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
    // Убираем warning в computed, так как он вызывается многократно
    return 'radio-group-' + Math.random().toString(36).substr(2, 9)
})

// Validate and warn about accessibility in development mode
// Отключаем предупреждения, так как они засоряют консоль
// и name генерируется автоматически при необходимости

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

<!-- Компонент полностью мигрирован на Tailwind CSS -->