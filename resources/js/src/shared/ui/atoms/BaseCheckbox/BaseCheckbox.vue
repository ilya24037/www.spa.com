<!-- Р‘Р°Р·РѕРІС‹Р№ С‡РµРєР±РѕРєСЃ РґР»СЏ Boolean Р·РЅР°С‡РµРЅРёР№ (РєР°Рє РЅР° РђРІРёС‚Рѕ) -->
<template>
  <div class="checkbox-container" @click="toggle">
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
    
    <span 
      v-if="label || $slots.label" 
      class="checkbox-label"
      :class="{ 'disabled': disabled }"
    >
      <slot name="label">
        {{ label }}
      </slot>
    </span>
    
    <slot />
  </div>
</template>

<script setup lang="ts">
// TypeScript РёРЅС‚РµСЂС„РµР№СЃС‹
interface BaseCheckboxProps {
  modelValue?: boolean
  label?: string
  disabled?: boolean
}

const props = withDefaults(defineProps<BaseCheckboxProps>(), {
    modelValue: false,
    label: '',
    disabled: false
});

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
</style> 

