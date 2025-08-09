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
      v-if="label" 
      class="checkbox-label"
      :class="{ 'disabled': disabled }"
    >
      {{ label }}
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
  display: flex;
  align-items: center;
  cursor: pointer;
  gap: 12px;
  padding: 8px 0;
  user-select: none;
}

.checkbox-container.disabled {
  cursor: not-allowed;
  opacity: 0.5;
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

.custom-checkbox:hover:not(.disabled) {
  border-color: #8c8c8c;
}

.custom-checkbox.checked {
  background: #007bff;
  border-color: #007bff;
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
  font-size: 16px;
  color: #1a1a1a;
  font-weight: 400;
  line-height: 1.4;
  cursor: pointer;
  user-select: none;
}

.checkbox-label.disabled {
  color: #8c8c8c;
  cursor: not-allowed;
}
</style> 

