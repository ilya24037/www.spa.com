<template>
  <div class="rounded-lg p-5">
    <BaseTextarea
      v-model="localDescription"
      placeholder="Напишите подробное описание о себе и о своих услугах. Подробное, интересное, смысловое описание значительно увеличивает эффективность вашей анкеты."
      :rows="5"
      :required="true"
      :error="errors.description || (forceValidation && !localDescription ? 'Пожалуйста, заполните описание объявления' : '')"
      :maxlength="2000"
      :show-counter="true"
      @update:modelValue="emitDescription"
    />
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import BaseTextarea from '@/src/shared/ui/atoms/BaseTextarea/BaseTextarea.vue'

const props = defineProps({
  description: { type: String, default: '' },
  errors: { type: Object, default: () => ({}) },
  forceValidation: { type: Boolean, default: false }
})

const emit = defineEmits(['update:description', 'clearForceValidation'])

const localDescription = ref(props.description || '')

watch(() => props.description, (val) => { 
  localDescription.value = val || '' 
})

const emitDescription = () => {
  // ВАЖНО: Всегда отправляем строку, не null
  emit('update:description', localDescription.value || '')
  
  // Сбрасываем принудительную валидацию если поле заполнено
  if (props.forceValidation && localDescription.value) {
    emit('clearForceValidation')
  }
}
</script>

<!-- Все стили теперь используют Tailwind CSS в template -->
