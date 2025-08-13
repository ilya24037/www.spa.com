<template>
  <button
    type="button"
    :class="itemClasses"
    @click="handleClick"
  >
    <span v-if="icon" class="dropdown-item-icon">
      <component :is="icon" />
    </span>
    <span class="dropdown-item-text">
      <slot>{{ text }}</slot>
    </span>
  </button>
</template>

<script setup lang="ts">
import { computed, type Component } from 'vue'

interface Props {
  text?: string
  icon?: Component | string
  variant?: 'default' | 'danger'
  disabled?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'default',
  disabled: false
})

const emit = defineEmits<{
  click: [event: MouseEvent]
}>()

const itemClasses = computed(() => [
  'dropdown-item',
  {
    'danger': props.variant === 'danger',
    'disabled': props.disabled
  }
])

const handleClick = (event: MouseEvent) => {
  if (!props.disabled) {
    emit('click', event)
  }
}
</script>

<style scoped>
.dropdown-item {
  @apply flex items-center gap-2 w-full px-4 py-2;
  @apply text-left text-sm text-gray-700;
  @apply hover:bg-gray-100 transition-colors;
  @apply cursor-pointer;
}

.dropdown-item:focus {
  @apply outline-none bg-gray-100;
}

.dropdown-item.danger {
  @apply text-red-600 hover:bg-red-50;
}

.dropdown-item.disabled {
  @apply opacity-50 cursor-not-allowed;
}

.dropdown-item-icon {
  @apply w-4 h-4 flex-shrink-0;
}

.dropdown-item-text {
  @apply flex-1;
}
</style>