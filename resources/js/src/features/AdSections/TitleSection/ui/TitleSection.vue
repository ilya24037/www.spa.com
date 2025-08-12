<template>
  <div class="form-field">
    <BaseInput
      v-model="localTitle"
      label="Название объявления"
      placeholder=""
      hint="Например, «Маникюр, педикюр и наращивание ногтей» или «Ремонт квартир под ключ»"
      :maxlength="60"
      :show-counter="true"
      :clearable="true"
      :error="errors.title"
      @update:modelValue="emitTitle"
    />
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import BaseInput from '@/src/shared/ui/atoms/BaseInput/BaseInput.vue'

const props = defineProps({
  title: { type: String, default: '' },
  errors: { type: Object, default: () => ({}) }
})

const emit = defineEmits(['update:title'])

const localTitle = ref(props.title)

watch(() => props.title, (newValue) => {
  localTitle.value = newValue
})

const emitTitle = () => {
  emit('update:title', localTitle.value)
}
</script>

<style scoped>
.form-field {
  margin-bottom: 1.5rem;
}
</style>