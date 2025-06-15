<template>
    <input
        :id="id"
        type="checkbox"
        :value="value"
        :checked="checked"
        @change="$emit('update:checked', $event.target.checked)"
        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
    />
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