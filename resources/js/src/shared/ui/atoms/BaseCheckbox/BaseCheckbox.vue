<!-- Р'Р°Р·РѕРІС‹Р№ С‡РµРєР±РѕРєСЃ РґР»СЏ Boolean Р·РЅР°С‡РµРЅРёР№ (РєР°Рє РЅР° РђРІРёС‚Рѕ) -->
<template>
  <div class="checkbox-container" @click="toggle">
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
      @change="toggle"
    >
    <div 
      class="custom-checkbox"
      :class="{ 
        'checked': modelValue,
        'disabled': disabled 
      }"
    >
      <svg 
        class="check-icon" 
        width="100%" 
        height="100%" 
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
      class="checkbox-label"
      :class="{ 'disabled': disabled }"
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
}

const props = withDefaults(defineProps<BaseCheckboxProps>(), {
    modelValue: false,
    name: '',
    label: '',
    disabled: false
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
const toggle = (event: MouseEvent): void => {
    if (props.disabled) return
  
    event.preventDefault()
    event.stopPropagation()
    emit('update:modelValue', !props.modelValue)
}
</script>

<style scoped>
.checkbox-container {
  display: flex !important;
  align-items: center !important;
  cursor: pointer !important;
  gap: 8px !important;
  padding: 4px 0 !important;
  user-select: none !important;
  min-height: 26px !important;
}

.checkbox-container.disabled {
  cursor: not-allowed;
  opacity: 0.5;
}

.custom-checkbox {
  width: 18px !important;
  height: 18px !important;
  border: 2px solid #d1d5db !important;
  border-radius: 4px !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  transition: all 0.2s ease !important;
  background: #ffffff !important;
  flex-shrink: 0 !important;
  cursor: pointer !important;
  box-shadow: none !important;
}

.custom-checkbox:hover:not(.disabled) {
  border-color: #9ca3af !important;
}

.custom-checkbox.checked {
  background: #6b7280 !important;
  border-color: #6b7280 !important;
}

.custom-checkbox.disabled {
  cursor: not-allowed;
  background: #f5f5f5;
}

.check-icon {
  width: 12px;
  height: 10px;
  color: #fff;
  opacity: 0;
  transition: opacity 0.2s ease;
}

.custom-checkbox.checked .check-icon {
  opacity: 1;
}

.checkbox-label {
  font-size: 14px !important;
  color: #374151 !important;
  font-weight: 400 !important;
  line-height: 1.4 !important;
  cursor: pointer !important;
  user-select: none !important;
}

.checkbox-label.disabled {
  color: #8c8c8c;
  cursor: not-allowed;
}

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
</style> 

