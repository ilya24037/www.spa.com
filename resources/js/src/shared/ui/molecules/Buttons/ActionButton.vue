<!-- 
  ActionButton.vue - DEPRECATED
  Этот компонент оставлен для обратной совместимости.
  Используйте @/src/shared/ui/atoms/Button/Button.vue вместо него.
-->
<template>
  <Button
    :variant="mapVariant"
    :size="mapSize"
    :type="type"
    :disabled="disabled"
    :loading="loading"
    :full-width="fullWidth"
    @click="handleClick"
  >
    <slot>{{ text }}</slot>
  </Button>
</template>

<script setup>
import { computed } from 'vue'
import Button from '@/src/shared/ui/atoms/Button/Button.vue'

const props = defineProps({
    variant: {
        type: String,
        default: 'primary',
        validator: (value) => ['primary', 'secondary', 'success', 'danger', 'ghost'].includes(value)
    },
    size: {
        type: String,
        default: 'medium',
        validator: (value) => ['small', 'medium', 'large'].includes(value)
    },
    type: {
        type: String,
        default: 'button'
    },
    disabled: {
        type: Boolean,
        default: false
    },
    loading: {
        type: Boolean,
        default: false
    },
    text: {
        type: String,
        default: ''
    },
    fullWidth: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['click'])

// Маппинг вариантов ActionButton на Button
const mapVariant = computed(() => {
    const variantMap = {
        'primary': 'primary',
        'secondary': 'light',
        'success': 'success',
        'danger': 'danger',
        'ghost': 'ghost'
    }
    return variantMap[props.variant] || 'primary'
})

// Маппинг размеров ActionButton на Button
const mapSize = computed(() => {
    const sizeMap = {
        'small': 'sm',
        'medium': 'md',
        'large': 'lg'
    }
    return sizeMap[props.size] || 'md'
})

const handleClick = (event) => {
    if (!props.disabled && !props.loading) {
        emit('click', event)
    }
}
</script>

<style scoped>
/* Все стили делегированы компоненту Button */
</style>