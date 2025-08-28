<template>
  <div class="bg-white rounded-lg p-5">
    <BaseTextarea
      v-model="localScheduleNotes"
      label="Дополнительная информация о графике работы"
      placeholder="Например: возможны изменения графика по договоренности, предварительная запись обязательна и т.д."
      :rows="3"
      :error="errors.schedule_notes"
      :maxlength="1000"
      :show-counter="true"
      @update:modelValue="emitScheduleNotes"
    />
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import BaseTextarea from '@/src/shared/ui/atoms/BaseTextarea/BaseTextarea.vue'

const props = defineProps({
  scheduleNotes: { type: String, default: '' },
  errors: { type: Object, default: () => ({}) }
})

const emit = defineEmits(['update:schedule-notes'])

const localScheduleNotes = ref(props.scheduleNotes || '')

watch(() => props.scheduleNotes, (val) => { 
  localScheduleNotes.value = val || '' 
})

const emitScheduleNotes = () => {
  // ВАЖНО: Всегда отправляем строку, не null
  emit('update:schedule-notes', localScheduleNotes.value || '')
}
</script>

<!-- Все стили теперь используют Tailwind CSS в template -->