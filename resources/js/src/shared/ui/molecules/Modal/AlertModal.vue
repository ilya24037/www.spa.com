<template>
  <BaseModal
    :model-value="modelValue"
    :title="title"
    :variant="variant"
    :size="size"
    :show-footer="true"
    :show-confirm-button="true"
    :confirm-text="buttonText"
    @update:model-value="$emit('update:modelValue', $event)"
    @confirm="handleConfirm"
    @close="handleClose"
  >
    <div class="alert-content">
      <div class="alert-icon-wrapper" :class="iconWrapperClasses">
        <component
          :is="iconComponent"
          class="alert-icon"
          :class="iconClasses"
        />
      </div>
      
      <div class="alert-text">
        <p class="alert-message" :class="messageClasses">
          {{ message }}
        </p>
        
        <div v-if="$slots.content" class="alert-extra">
          <slot name="content" />
        </div>
      </div>
    </div>
  </BaseModal>
</template>

<script setup lang="ts">
import { computed, h } from 'vue'
import BaseModal from './BaseModal.vue'

type AlertVariant = 'info' | 'warning' | 'danger' | 'success'

interface Props {
  modelValue: boolean
  title?: string
  message?: string
  variant?: AlertVariant
  size?: 'sm' | 'md'
  buttonText?: string
}

const props = withDefaults(defineProps<Props>(), {
    title: 'РЈРІРµРґРѕРјР»РµРЅРёРµ',
    message: 'РРЅС„РѕСЂРјР°С†РёРѕРЅРЅРѕРµ СЃРѕРѕР±С‰РµРЅРёРµ',
    variant: 'info',
    size: 'sm',
    buttonText: 'РћРљ'
})

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  close: []
}>()

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
        info: 'text-gray-500',
        warning: 'text-gray-500', 
        danger: 'text-gray-500',
        success: 'text-gray-500'
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
                d: 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z'
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

// Methods
const handleConfirm = () => {
    handleClose()
}

const handleClose = () => {
    emit('update:modelValue', false)
    emit('close')
}
</script>

<style scoped>
.alert-content {
  @apply flex items-start gap-4;
}

.alert-icon-wrapper {
  @apply flex-shrink-0 w-12 h-12 rounded-full flex items-center justify-center;
}

.alert-icon {
  @apply w-6 h-6;
}

.alert-text {
  @apply flex-1;
}

.alert-message {
  @apply text-base text-gray-500 mb-2;
}

.alert-extra {
  @apply mt-4;
}

/* РђРґР°РїС‚РёРІРЅРѕСЃС‚СЊ */
@media (max-width: 640px) {
  .alert-content {
    @apply flex-col text-center gap-3;
  }
  
  .alert-icon-wrapper {
    @apply mx-auto;
  }
  
  .alert-message {
    @apply text-sm;
  }
}

/* РђРЅРёРјР°С†РёРё РґР»СЏ РёРєРѕРЅРѕРє */
@keyframes pulse-in {
  0% {
    transform: scale(0.8);
    opacity: 0;
  }
  50% {
    transform: scale(1.1);
  }
  100% {
    transform: scale(1);
    opacity: 1;
  }
}

.alert-icon {
  animation: pulse-in 0.3s ease-out;
}
</style>

