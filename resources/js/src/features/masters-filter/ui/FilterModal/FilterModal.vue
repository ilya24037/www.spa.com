<template>
  <Modal
    :model-value="modelValue"
    @update:model-value="$emit('update:modelValue', $event)"
    title="Р¤РёР»СЊС‚СЂС‹"
    size="md"
  >
    <FilterPanel @apply="handleApply" @reset="handleReset">
      <slot />
    </FilterPanel>
  </Modal>
</template>

<script setup lang="ts">
import Modal from '@/src/shared/ui/molecules/Modal/Modal.vue'
import FilterPanel from '../FilterPanel/FilterPanel.vue'

defineProps<{
  modelValue: boolean
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'apply': []
  'reset': []
}>()

const handleApply = () => {
  emit('apply')
  emit('update:modelValue', false)
}

const handleReset = () => {
  emit('reset')
}
</script>
