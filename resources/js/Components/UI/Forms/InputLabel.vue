<template>
    <label :for="for" :class="classes">
        <slot />
    </label>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    for: {
        type: String,
        default: ''
    },
    value: {
        type: String,
        default: ''
    },
    required: {
        type: Boolean,
        default: false
    }
})

const classes = computed(() => {
    return 'block font-medium text-sm text-gray-700'
})
</script>