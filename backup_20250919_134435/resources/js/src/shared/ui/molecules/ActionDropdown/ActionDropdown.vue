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
          fill="currentColor" 
          viewBox="0 0 24 24"
        >
          <circle cx="5" cy="12" r="2" />
          <circle cx="12" cy="12" r="2" />
          <circle cx="19" cy="12" r="2" />
        </svg>
      </Button>
    </template>

    <template #content>
      <div class="min-w-[200px]">
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
/* Стили для пунктов меню - оставляем :deep() для дочерних компонентов */
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