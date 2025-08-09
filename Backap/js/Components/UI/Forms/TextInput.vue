<template>
    <input
        :id="id"
        :type="type"
        :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)"
        v-bind="$attrs"
        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
    />
</template>

<script setup>
defineProps({
    id: String,
    type: {
        type: String,
        default: 'text'
    },
    modelValue: [String, Number]
})

defineEmits(['update:modelValue'])
</script>