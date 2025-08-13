<template>
  <Dropdown 
    align="right" 
    width="200"
    content-classes="py-1 bg-white"
  >
    <template #trigger>
      <Button
        variant="ghost"
        size="sm"
        :aria-label="ariaLabel || 'Открыть меню действий'"
        class="!p-2"
      >
        <svg 
          class="w-5 h-5" 
          fill="none" 
          stroke="currentColor" 
          viewBox="0 0 24 24"
        >
          <path 
            stroke-linecap="round" 
            stroke-linejoin="round" 
            stroke-width="2" 
            d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"
          />
        </svg>
      </Button>
    </template>

    <template #content>
      <div class="action-dropdown-menu">
        <slot />
      </div>
    </template>
  </Dropdown>
</template>

<script setup lang="ts">
import Dropdown from '@/src/shared/ui/molecules/Navigation/Dropdown.vue'
import Button from '@/src/shared/ui/atoms/Button/Button.vue'

interface Props {
  ariaLabel?: string
}

defineProps<Props>()
</script>

<style scoped>
.action-dropdown-menu {
  min-width: 200px;
}

/* Стили для пунктов меню */
:deep(.dropdown-item) {
  @apply block w-full px-4 py-2 text-left text-sm text-gray-700;
  @apply hover:bg-gray-100 transition-colors;
  @apply cursor-pointer;
}

:deep(.dropdown-item:focus) {
  @apply outline-none bg-gray-100;
}

:deep(.dropdown-item.danger) {
  @apply text-red-600 hover:bg-red-50;
}

:deep(.dropdown-divider) {
  @apply my-1 border-t border-gray-200;
}
</style>