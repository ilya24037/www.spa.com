<template>
  <div class="adformlocation">
    <h3 class="text-lg font-medium mb-4">
      {{ title }}
    </h3>
    <slot />
  </div>
</template>

<script setup lang="ts">
defineProps<{
  title?: string
  modelValue?: any
}>()

defineEmits<{
  'update:modelValue': [value: any]
}>()
</script>