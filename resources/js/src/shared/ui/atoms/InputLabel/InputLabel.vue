<template>
  <label class="block text-sm font-medium text-gray-500">
    <span v-if="value">{{ value }}</span>
    <slot v-else />
  </label>
</template>

<script setup lang="ts">
interface Props {
  value?: string
}

defineProps<Props>()
</script>