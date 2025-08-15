<!-- Универсальная обертка для полей формы с автоматической доступностью -->
<template>
  <div 
    class="form-field"
    :class="{
      'form-field--error': !!error,
      'form-field--disabled': disabled,
      'form-field--required': required
    }"
  >
    <!-- Label -->
    <label 
      v-if="label"
      :for="fieldId"
      class="form-field__label"
    >
      {{ label }}
      <span v-if="required" class="form-field__required" aria-label="обязательное поле">*</span>
    </label>
    
    <!-- Hint above field -->
    <div 
      v-if="hint && hintPosition === 'above'"
      :id="`${fieldId}-hint`"
      class="form-field__hint form-field__hint--above"
    >
      {{ hint }}
    </div>
    
    <!-- Field content -->
    <div class="form-field__content">
      <slot 
        :id="fieldId"
        :name="fieldName"
        :aria-invalid="!!error"
        :aria-describedby="ariaDescribedBy"
        :aria-required="required"
        :disabled="disabled"
      />
    </div>
    
    <!-- Error message -->
    <div 
      v-if="error"
      :id="`${fieldId}-error`"
      class="form-field__error"
      role="alert"
      aria-live="polite"
    >
      <svg class="form-field__error-icon" width="16" height="16" viewBox="0 0 16 16" fill="none">
        <path 
          d="M8 1.5C4.41 1.5 1.5 4.41 1.5 8C1.5 11.59 4.41 14.5 8 14.5C11.59 14.5 14.5 11.59 14.5 8C14.5 4.41 11.59 1.5 8 1.5ZM8 9.5C7.45 9.5 7 9.05 7 8.5V5.5C7 4.95 7.45 4.5 8 4.5C8.55 4.5 9 4.95 9 5.5V8.5C9 9.05 8.55 9.5 8 9.5ZM9 11.5H7V10H9V11.5Z" 
          fill="currentColor"
        />
      </svg>
      {{ error }}
    </div>
    
    <!-- Hint below field -->
    <div 
      v-if="hint && hintPosition === 'below' && !error"
      :id="`${fieldId}-hint`"
      class="form-field__hint form-field__hint--below"
    >
      {{ hint }}
    </div>
    
    <!-- Help text -->
    <div 
      v-if="helpText && !error"
      class="form-field__help"
    >
      <slot name="help">
        {{ helpText }}
      </slot>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useId } from '@/src/shared/composables/useId'

// TypeScript интерфейсы
interface FormFieldProps {
  id?: string
  name?: string
  label?: string
  error?: string
  hint?: string
  helpText?: string
  hintPosition?: 'above' | 'below'
  required?: boolean
  disabled?: boolean
}

const props = withDefaults(defineProps<FormFieldProps>(), {
  hintPosition: 'below',
  required: false,
  disabled: false
})

// Generate unique ID if not provided
const fieldId = computed(() => props.id || useId('field'))

// Auto-generate name from label if not provided
const fieldName = computed(() => {
  if (props.name) return props.name
  if (props.label) {
    // Преобразуем label в snake_case для name
    return props.label.toLowerCase()
      .replace(/[^\w\s]/g, '')
      .replace(/\s+/g, '_')
  }
  return fieldId.value
})

// Compute aria-describedby value
const ariaDescribedBy = computed(() => {
  const ids = []
  if (props.error) {
    ids.push(`${fieldId.value}-error`)
  } else if (props.hint) {
    ids.push(`${fieldId.value}-hint`)
  }
  return ids.length > 0 ? ids.join(' ') : undefined
})

// Development mode warnings
if (process.env.NODE_ENV !== 'production') {
  if (!props.label) {
    console.warn('[FormField] Рекомендуется указать label для доступности')
  }
}
</script>

<style scoped>
.form-field {
  display: flex;
  flex-direction: column;
  gap: 8px;
  width: 100%;
}

.form-field__label {
  font-size: 16px;
  font-weight: 500;
  color: #1a1a1a;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 4px;
}

.form-field__required {
  color: #dc3545;
  font-weight: 400;
}

.form-field__content {
  width: 100%;
}

.form-field__hint {
  font-size: 14px;
  color: #666666;
  line-height: 1.4;
}

.form-field__hint--above {
  margin-bottom: 4px;
}

.form-field__hint--below {
  margin-top: 4px;
}

.form-field__error {
  display: flex;
  align-items: flex-start;
  gap: 6px;
  font-size: 14px;
  color: #dc3545;
  line-height: 1.4;
  animation: fadeIn 0.2s ease-out;
}

.form-field__error-icon {
  flex-shrink: 0;
  margin-top: 1px;
}

.form-field__help {
  font-size: 13px;
  color: #8c8c8c;
  line-height: 1.4;
  margin-top: 4px;
}

/* States */
.form-field--error .form-field__label {
  color: #dc3545;
}

.form-field--disabled {
  opacity: 0.6;
  pointer-events: none;
}

.form-field--disabled .form-field__label {
  cursor: not-allowed;
}

/* Animation */
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(-4px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Responsive */
@media (max-width: 640px) {
  .form-field__label {
    font-size: 15px;
  }
  
  .form-field__hint,
  .form-field__error {
    font-size: 13px;
  }
  
  .form-field__help {
    font-size: 12px;
  }
}
</style>