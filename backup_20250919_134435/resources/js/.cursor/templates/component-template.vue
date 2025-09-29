<template>
  <div class="component-container">
    <!-- Component header -->
    <div class="component-header">
      <h3 class="text-lg font-semibold text-gray-900">
        {{ title }}
      </h3>
      <p v-if="description" class="text-sm text-gray-600">
        {{ description }}
      </p>
    </div>

    <!-- Component content -->
    <div class="component-content">
      <slot name="content">
        <!-- Default content here -->
        <p class="text-gray-500">Component content goes here</p>
      </slot>
    </div>

    <!-- Component actions -->
    <div v-if="$slots.actions" class="component-actions">
      <slot name="actions" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

// Props interface
interface Props {
  title: string
  description?: string
  variant?: 'default' | 'primary' | 'secondary'
  size?: 'sm' | 'md' | 'lg'
  disabled?: boolean
}

// Emits interface
interface Emits {
  (e: 'update:title', value: string): void
  (e: 'action', data: any): void
  (e: 'close'): void
}

// Define props and emits
const props = withDefaults(defineProps<Props>(), {
  variant: 'default',
  size: 'md',
  disabled: false
})

const emit = defineEmits<Emits>()

// Computed properties
const componentClasses = computed(() => [
  'component-container',
  `component--${props.variant}`,
  `component--${props.size}`,
  {
    'component--disabled': props.disabled
  }
])

const headerClasses = computed(() => [
  'component-header',
  `header--${props.size}`
])

// Methods
const handleAction = (data: any) => {
  if (!props.disabled) {
    emit('action', data)
  }
}

const handleClose = () => {
  emit('close')
}

const updateTitle = (newTitle: string) => {
  emit('update:title', newTitle)
}

// Expose methods to parent if needed
defineExpose({
  handleAction,
  handleClose,
  updateTitle
})
</script>

<style scoped>
.component-container {
  @apply bg-white rounded-lg border border-gray-200 shadow-sm p-4;
}

.component-header {
  @apply mb-4 pb-3 border-b border-gray-200;
}

.component-content {
  @apply mb-4;
}

.component-actions {
  @apply flex justify-end space-x-2 pt-3 border-t border-gray-200;
}

/* Variant styles */
.component--primary {
  @apply border-blue-200 bg-blue-50;
}

.component--secondary {
  @apply border-gray-300 bg-gray-50;
}

/* Size styles */
.component--sm {
  @apply p-3;
}

.component--sm .component-header {
  @apply mb-3 pb-2;
}

.component--lg {
  @apply p-6;
}

.component--lg .component-header {
  @apply mb-6 pb-4;
}

/* Disabled state */
.component--disabled {
  @apply opacity-50 cursor-not-allowed;
}

.component--disabled * {
  @apply pointer-events-none;
}

/* Responsive design */
@media (max-width: 640px) {
  .component-container {
    @apply p-3;
  }
  
  .component-actions {
    @apply flex-col space-y-2 space-x-0;
  }
}
</style>
