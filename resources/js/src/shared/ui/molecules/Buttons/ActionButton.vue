<template>
  <button 
    :type="type"
    :disabled="disabled || loading"
    :class="buttonClasses"
    @click="handleClick"
  >
    <!-- РРєРѕРЅРєР° Р·Р°РіСЂСѓР·РєРё -->
    <span v-if="loading" class="loading-spinner"></span>
    
    <!-- РРєРѕРЅРєР° РєРЅРѕРїРєРё -->
    <span v-if="!loading && $slots.icon" class="button-icon">
      <slot name="icon" />
    </span>
    
    <!-- РўРµРєСЃС‚ РєРЅРѕРїРєРё -->
    <span class="button-text">
      <slot>{{ text }}</slot>
    </span>
  </button>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  variant: {
    type: String,
    default: 'primary',
    validator: (value) => ['primary', 'secondary', 'success', 'danger', 'ghost'].includes(value)
  },
  size: {
    type: String,
    default: 'medium',
    validator: (value) => ['small', 'medium', 'large'].includes(value)
  },
  type: {
    type: String,
    default: 'button'
  },
  disabled: {
    type: Boolean,
    default: false
  },
  loading: {
    type: Boolean,
    default: false
  },
  text: {
    type: String,
    default: ''
  },
  fullWidth: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['click'])

const buttonClasses = computed(() => [
  'action-button',
  `action-button--${props.variant}`,
  `action-button--${props.size}`,
  {
    'action-button--disabled': props.disabled,
    'action-button--loading': props.loading,
    'action-button--full-width': props.fullWidth
  }
])

const handleClick = (event) => {
  if (!props.disabled && !props.loading) {
    emit('click', event)
  }
}
</script>

<style scoped>
.action-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  border: none;
  border-radius: 6px;
  font-weight: 500;
  text-align: center;
  cursor: pointer;
  transition: all 0.2s ease;
  white-space: nowrap;
  user-select: none;
}

.action-button:focus {
  outline: none;
  box-shadow: 0 0 0 2px rgba(24, 144, 255, 0.2);
}

/* Р’Р°СЂРёР°РЅС‚С‹ РєРЅРѕРїРѕРє */
.action-button--primary {
  background: #1890ff;
  color: #fff;
}

.action-button--primary:hover:not(:disabled) {
  background: #40a9ff;
}

.action-button--secondary {
  background: #f5f5f5;
  color: #1a1a1a;
  border: 1px solid #d9d9d9;
}

.action-button--secondary:hover:not(:disabled) {
  background: #fafafa;
  border-color: #40a9ff;
}

.action-button--success {
  background: #52c41a;
  color: #fff;
}

.action-button--success:hover:not(:disabled) {
  background: #73d13d;
}

.action-button--danger {
  background: #ff4d4f;
  color: #fff;
}

.action-button--danger:hover:not(:disabled) {
  background: #ff7875;
}

.action-button--ghost {
  background: transparent;
  color: #1890ff;
  border: 1px solid #1890ff;
}

.action-button--ghost:hover:not(:disabled) {
  background: #f0f8ff;
}

/* Р Р°Р·РјРµСЂС‹ РєРЅРѕРїРѕРє */
.action-button--small {
  padding: 6px 12px;
  font-size: 14px;
  min-height: 32px;
}

.action-button--medium {
  padding: 10px 16px;
  font-size: 16px;
  min-height: 40px;
}

.action-button--large {
  padding: 14px 24px;
  font-size: 18px;
  min-height: 48px;
}

/* РЎРѕСЃС‚РѕСЏРЅРёСЏ РєРЅРѕРїРѕРє */
.action-button--disabled,
.action-button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.action-button--loading {
  cursor: wait;
}

.action-button--full-width {
  width: 100%;
}

/* РЎРїРёРЅРЅРµСЂ Р·Р°РіСЂСѓР·РєРё */
.loading-spinner {
  width: 16px;
  height: 16px;
  border: 2px solid transparent;
  border-top: 2px solid currentColor;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.button-icon {
  display: flex;
  align-items: center;
  justify-content: center;
}

.button-text {
  line-height: 1;
}
</style>

