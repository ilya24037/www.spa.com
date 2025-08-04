<template>
    <input
        :id="id"
        ref="input"
        :type="type"
        :value="modelValue"
        :class="classes"
        :placeholder="placeholder"
        :disabled="disabled"
        :required="required"
        :readonly="readonly"
        :autocomplete="autocomplete"
        @input="$emit('update:modelValue', $event.target.value)"
        @focus="$emit('focus', $event)"
        @blur="$emit('blur', $event)"
    />
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'

const props = defineProps({
    id: String,
    type: {
        type: String,
        default: 'text'
    },
    modelValue: [String, Number],
    placeholder: String,
    disabled: Boolean,
    required: Boolean,
    readonly: Boolean,
    autocomplete: String,
    error: Boolean,
    focus: Boolean
})

defineEmits(['update:modelValue', 'focus', 'blur'])

const input = ref(null)

const classes = computed(() => {
    const base = 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm'
    const error = props.error ? 'border-red-300 text-red-900 focus:border-red-500 focus:ring-red-500' : ''
    const disabled = props.disabled ? 'bg-gray-100 cursor-not-allowed' : ''
    
    return `${base} ${error} ${disabled}`.trim()
})

onMounted(() => {
    if (props.focus) {
        input.value?.focus()
    }
})

defineExpose({ focus: () => input.value?.focus() })
</script>