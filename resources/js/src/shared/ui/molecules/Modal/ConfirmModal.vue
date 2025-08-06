<template>
  <BaseModal
    :model-value="modelValue"
    :title="title"
    :variant="variant"
    :size="size"
    :persistent="persistent"
    :close-on-backdrop="!persistent"
    :close-on-escape="!persistent"
    :show-footer="true"
    :show-cancel-button="true"
    :show-confirm-button="true"
    :confirm-text="confirmText"
    :cancel-text="cancelText"
    @update:model-value="$emit('update:modelValue', $event)"
    @confirm="handleConfirm"
    @cancel="handleCancel"
    @close="handleClose"
  >
    <!-- РРєРѕРЅРєР° РІ Р·Р°РІРёСЃРёРјРѕСЃС‚Рё РѕС‚ С‚РёРїР° -->
    <div class="confirm-content">
      <div class="confirm-icon-wrapper" :class="iconWrapperClasses">
        <component
          :is="iconComponent"
          class="confirm-icon"
          :class="iconClasses"
        />
      </div>
      
      <!-- РћСЃРЅРѕРІРЅРѕРµ СЃРѕРѕР±С‰РµРЅРёРµ -->
      <div class="confirm-text">
        <p class="confirm-message" :class="messageClasses">
          {{ message }}
        </p>
        
        <!-- Р”РѕРїРѕР»РЅРёС‚РµР»СЊРЅРѕРµ РѕРїРёСЃР°РЅРёРµ -->
        <p
          v-if="description"
          class="confirm-description"
        >
          {{ description }}
        </p>
        
        <!-- Р”РѕРїРѕР»РЅРёС‚РµР»СЊРЅР°СЏ РёРЅС„РѕСЂРјР°С†РёСЏ С‡РµСЂРµР· slot -->
        <div v-if="$slots.content" class="confirm-extra">
          <slot name="content" />
        </div>
        
        <!-- РџРѕР»Рµ РІРІРѕРґР° РґР»СЏ РїРѕРґС‚РІРµСЂР¶РґРµРЅРёСЏ (РЅР°РїСЂРёРјРµСЂ, РґР»СЏ СѓРґР°Р»РµРЅРёСЏ) -->
        <div
          v-if="requiresConfirmation"
          class="confirm-input-wrapper"
        >
          <label
            :for="inputId"
            class="confirm-input-label"
          >
            {{ confirmationLabel }}
          </label>
          <input
            :id="inputId"
            v-model="confirmationInput"
            type="text"
            class="confirm-input"
            :placeholder="confirmationPlaceholder"
            @keydown.enter="handleConfirm"
          />
        </div>
      </div>
    </div>
    
    <!-- РљР°СЃС‚РѕРјРЅС‹Р№ С„СѓС‚РµСЂ РµСЃР»Рё РЅСѓР¶РµРЅ -->
    <template v-if="$slots.footer" #footer="{ close }">
      <slot name="footer" :close="close" :confirm="handleConfirm" />
    </template>
  </BaseModal>
</template>

<script setup lang="ts">
import { ref, computed, h } from 'vue'
import BaseModal from './BaseModal.vue'
import { generateUniqueId } from '@/src/shared/lib/utils'

type ConfirmVariant = 'info' | 'warning' | 'danger' | 'success'

interface Props {
  modelValue: boolean
  title?: string
  message?: string
  description?: string
  variant?: ConfirmVariant
  size?: 'sm' | 'md' | 'lg'
  confirmText?: string
  cancelText?: string
  persistent?: boolean
  requiresConfirmation?: boolean
  confirmationText?: string
  confirmationLabel?: string
  confirmationPlaceholder?: string
}

const props = withDefaults(defineProps<Props>(), {
  title: 'РџРѕРґС‚РІРµСЂР¶РґРµРЅРёРµ РґРµР№СЃС‚РІРёСЏ',
  message: 'Р’С‹ СѓРІРµСЂРµРЅС‹, С‡С‚Рѕ С…РѕС‚РёС‚Рµ РїСЂРѕРґРѕР»Р¶РёС‚СЊ?',
  variant: 'info',
  size: 'sm',
  confirmText: 'РџРѕРґС‚РІРµСЂРґРёС‚СЊ',
  cancelText: 'РћС‚РјРµРЅР°',
  persistent: false,
  requiresConfirmation: false,
  confirmationText: '',
  confirmationLabel: 'Р’РІРµРґРёС‚Рµ РґР»СЏ РїРѕРґС‚РІРµСЂР¶РґРµРЅРёСЏ:',
  confirmationPlaceholder: 'Р’РІРµРґРёС‚Рµ С‚РµРєСЃС‚'
})

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  confirm: []
  cancel: []
  close: []
}>()

// Refs
const confirmationInput = ref('')
const inputId = generateUniqueId('confirm-input')

// Computed
const iconWrapperClasses = computed(() => {
  const classes = {
    info: 'bg-blue-100 text-blue-600',
    warning: 'bg-yellow-100 text-yellow-600',
    danger: 'bg-red-100 text-red-600',
    success: 'bg-green-100 text-green-600'
  }
  return classes[props.variant]
})

const iconClasses = computed(() => {
  const classes = {
    info: 'text-blue-600',
    warning: 'text-yellow-600',
    danger: 'text-red-600',
    success: 'text-green-600'
  }
  return classes[props.variant]
})

const messageClasses = computed(() => {
  const classes = {
    info: 'text-gray-900',
    warning: 'text-gray-900',
    danger: 'text-gray-900',
    success: 'text-gray-900'
  }
  return classes[props.variant]
})

const iconComponent = computed(() => {
  const icons = {
    info: () => h('svg', {
      fill: 'none',
      stroke: 'currentColor',
      viewBox: '0 0 24 24',
      class: 'w-6 h-6'
    }, [
      h('path', {
        'stroke-linecap': 'round',
        'stroke-linejoin': 'round',
        'stroke-width': '2',
        d: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
      })
    ]),
    
    warning: () => h('svg', {
      fill: 'none',
      stroke: 'currentColor',
      viewBox: '0 0 24 24',
      class: 'w-6 h-6'
    }, [
      h('path', {
        'stroke-linecap': 'round',
        'stroke-linejoin': 'round',
        'stroke-width': '2',
        d: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'
      })
    ]),
    
    danger: () => h('svg', {
      fill: 'none',
      stroke: 'currentColor',
      viewBox: '0 0 24 24',
      class: 'w-6 h-6'
    }, [
      h('path', {
        'stroke-linecap': 'round',
        'stroke-linejoin': 'round',
        'stroke-width': '2',
        d: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'
      })
    ]),
    
    success: () => h('svg', {
      fill: 'none',
      stroke: 'currentColor',
      viewBox: '0 0 24 24',
      class: 'w-6 h-6'
    }, [
      h('path', {
        'stroke-linecap': 'round',
        'stroke-linejoin': 'round',
        'stroke-width': '2',
        d: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'
      })
    ])
  }
  
  return icons[props.variant]
})

const canConfirm = computed(() => {
  if (!props.requiresConfirmation) return true
  return confirmationInput.value.trim() === props.confirmationText.trim()
})

// Methods
const handleConfirm = () => {
  if (!canConfirm.value) return
  
  emit('confirm')
  handleClose()
}

const handleCancel = () => {
  emit('cancel')
  handleClose()
}

const handleClose = () => {
  confirmationInput.value = ''
  emit('update:modelValue', false)
  emit('close')
}
</script>

<style scoped>
.confirm-content {
  @apply flex items-start gap-4;
}

.confirm-icon-wrapper {
  @apply flex-shrink-0 w-12 h-12 rounded-full flex items-center justify-center;
}

.confirm-icon {
  @apply w-6 h-6;
}

.confirm-text {
  @apply flex-1;
}

.confirm-message {
  @apply text-base font-medium text-gray-900 mb-2;
}

.confirm-description {
  @apply text-sm text-gray-600 mb-4;
}

.confirm-extra {
  @apply mb-4;
}

.confirm-input-wrapper {
  @apply mt-4;
}

.confirm-input-label {
  @apply block text-sm font-medium text-gray-700 mb-2;
}

.confirm-input {
  @apply w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500;
}

.confirm-input:focus {
  @apply ring-2 ring-blue-500 border-blue-500;
}

/* Р’Р°СЂРёР°РЅС‚С‹ РґР»СЏ СЂР°Р·РЅС‹С… С‚РёРїРѕРІ РїРѕРґС‚РІРµСЂР¶РґРµРЅРёСЏ */
.confirm-message--danger {
  @apply text-red-900;
}

.confirm-message--warning {
  @apply text-yellow-900;
}

.confirm-message--success {
  @apply text-green-900;
}

/* РђРґР°РїС‚РёРІРЅРѕСЃС‚СЊ */
@media (max-width: 640px) {
  .confirm-content {
    @apply flex-col text-center gap-3;
  }
  
  .confirm-icon-wrapper {
    @apply mx-auto;
  }
  
  .confirm-message {
    @apply text-sm;
  }
  
  .confirm-description {
    @apply text-xs;
  }
}

/* РђРЅРёРјР°С†РёРё РґР»СЏ РёРєРѕРЅРѕРє */
@keyframes bounce-in {
  0% {
    transform: scale(0.3);
    opacity: 0;
  }
  50% {
    transform: scale(1.05);
  }
  70% {
    transform: scale(0.9);
  }
  100% {
    transform: scale(1);
    opacity: 1;
  }
}

.confirm-icon {
  animation: bounce-in 0.4s ease-out;
}

/* Р’С‹СЃРѕРєРёР№ РєРѕРЅС‚СЂР°СЃС‚ */
@media (prefers-contrast: high) {
  .confirm-input {
    @apply border-2 border-gray-800;
  }
}
</style>

