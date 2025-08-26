<template>
  <label :for="name" class="block text-sm font-medium text-gray-500 mb-1">
    {{ value }}
  </label>
</template>

<script setup>
defineProps({
    name: String,
    value: String
})
</script>