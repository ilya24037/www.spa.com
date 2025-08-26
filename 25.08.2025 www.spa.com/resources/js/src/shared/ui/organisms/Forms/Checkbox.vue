<template>
  <input
    :id="id"
    type="checkbox"
    :value="value"
    :checked="checked"
    class="rounded border-gray-500 text-indigo-600 shadow-sm focus:ring-indigo-500"
    @change="$emit('update:checked', $event.target.checked)"
  >
</template>

<script setup>
defineProps({
    id: String,
    value: [String, Number, Boolean],
    checked: {
        type: Boolean,
        default: false
    }
})

defineEmits(['update:checked'])
</script>