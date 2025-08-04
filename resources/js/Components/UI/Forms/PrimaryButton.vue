<template>
    <button
        :type="type"
        :class="classes"
        :disabled="disabled"
    >
        <slot />
    </button>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    type: {
        type: String,
        default: 'submit'
    },
    disabled: {
        type: Boolean,
        default: false
    }
})

const classes = computed(() => {
    const base = 'inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150'
    const disabled = props.disabled ? 'opacity-25 cursor-not-allowed' : ''
    
    return `${base} ${disabled}`.trim()
})
</script>