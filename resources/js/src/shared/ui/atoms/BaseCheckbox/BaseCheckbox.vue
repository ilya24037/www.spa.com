<!-- Base Checkbox Component -->
<template>
  <div 
    class="flex items-center cursor-pointer gap-2 py-1 select-none min-h-[26px]" 
    :class="{ 'cursor-not-allowed opacity-50': disabled }" 
    @click.stop="toggle"
  >
    <!-- Скрытый input для форм -->
    <input 
      :id="checkboxId"
      type="checkbox"
      :name="checkboxName"
      :checked="modelValue"
      :disabled="disabled"
      :aria-label="!label && $slots.label ? 'Checkbox' : undefined"
      :aria-checked="modelValue"
      class="sr-only"
    >
    <div 
      class="w-[18px] h-[18px] rounded flex items-center justify-center transition-all duration-200 flex-shrink-0 cursor-pointer"
      :class="{ 
        'bg-blue-500': modelValue && !error,
        'bg-blue-500 border-2 border-red-300': modelValue && error,
        'bg-gray-200': !modelValue && !error,
        'bg-red-50 border-2 border-red-300': !modelValue && error,
        'hover:bg-gray-300': !disabled && !modelValue && !error,
        'cursor-not-allowed opacity-50': disabled 
      }"
    >
      <svg 
        class="w-3 h-2.5 text-white transition-opacity duration-200 pointer-events-none" 
        :class="{ 
          'opacity-0': !modelValue, 
          'opacity-100': modelValue 
        }"
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
    
    <label 
      v-if="label || $slots.label" 
      :for="checkboxId"
      class="text-sm text-gray-700 font-normal leading-relaxed cursor-pointer select-none"
      :class="{ 'text-gray-500 cursor-not-allowed': disabled }"
    >
      <slot name="label">
        {{ label }}
      </slot>
    </label>
    
    <slot />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useId } from '@/src/shared/composables/useId'

// TypeScript РёРЅС‚РµСЂС„РµР№СЃС‹
interface BaseCheckboxProps {
  modelValue?: boolean
  id?: string
  name?: string
  label?: string
  disabled?: boolean
  error?: boolean
}

const props = withDefaults(defineProps<BaseCheckboxProps>(), {
    modelValue: false,
    name: '',
    label: '',
    disabled: false,
    error: false
});

// Generate unique ID if not provided
const checkboxId = computed(() => props.id || useId('checkbox'))

// Auto-generate name from label if not provided
const checkboxName = computed(() => {
    if (props.name) return props.name
    if (props.label) {
        // Преобразуем label в snake_case для name
        return props.label.toLowerCase()
            .replace(/[^\w\s]/g, '')
            .replace(/\s+/g, '_')
    }
    return checkboxId.value
})

// Предупреждение в dev режиме
if (process.env.NODE_ENV !== 'production') {
    if (!props.name && !props.label) {
        console.warn('[BaseCheckbox] Рекомендуется указать name или label для доступности')
    }
}

// TypeScript С‚РёРїРёР·Р°С†РёСЏ emits
const emit = defineEmits<{
  'update:modelValue': [value: boolean]
}>();

// Methods
const toggle = (): void => {
    if (props.disabled) return
    emit('update:modelValue', !props.modelValue)
}
</script>

<!-- Компонент полностью мигрирован на Tailwind CSS --> 

