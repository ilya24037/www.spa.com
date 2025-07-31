<template>
  <FormSection
    title="График работы"
    hint="Укажите время, когда вы доступны для клиентов"
    required
    :error="errors.schedule"
  >
    <div class="space-y-6">
      <!-- Сетка дней недели с временем -->
      <WeekdayGrid
        :schedule="localSchedule"
        @update:schedule="updateSchedule"
      />

      <!-- Быстрые настройки -->
      <QuickPresets
        @apply-preset="applyPreset"
      />

      <!-- Дополнительные заметки -->
      <ScheduleNotes
        v-model="localScheduleNotes"
        :error="errors.schedule_notes"
      />

      <!-- Предварительный просмотр -->
      <SchedulePreview
        :schedule="localSchedule"
        :notes="localScheduleNotes"
      />

      <!-- Советы -->
      <ScheduleTips />
    </div>
  </FormSection>
</template>

<script setup>
import { ref, watch } from 'vue'
import FormSection from '@/Components/UI/Forms/FormSection.vue'

// Микрокомпоненты
import WeekdayGrid from './components/WeekdayGrid.vue'
import QuickPresets from './components/QuickPresets.vue'
import ScheduleNotes from './components/ScheduleNotes.vue'
import SchedulePreview from './components/SchedulePreview.vue'
import ScheduleTips from './components/ScheduleTips.vue'

const props = defineProps({
  schedule: { type: [Object, String], default: () => ({}) },
  scheduleNotes: { type: String, default: '' },
  errors: { type: Object, default: () => ({}) }
})

const emit = defineEmits([
  'update:schedule',
  'update:scheduleNotes'
])

// Локальное состояние
const localSchedule = ref({})
const localScheduleNotes = ref(props.scheduleNotes || '')

// Инициализация расписания
const initializeSchedule = () => {
  let schedule = props.schedule
  
  // Если пришла строка, парсим JSON
  if (typeof schedule === 'string') {
    try {
      schedule = JSON.parse(schedule) || {}
    } catch (e) {
      schedule = {}
    }
  }
  
  localSchedule.value = { ...schedule }
}

// Отслеживание изменений пропсов
watch(() => props.schedule, () => {
  initializeSchedule()
}, { immediate: true })

watch(() => props.scheduleNotes, (newValue) => { 
  localScheduleNotes.value = newValue || '' 
})

// Отслеживание изменений локальных данных
watch(localScheduleNotes, (newValue) => {
  emit('update:scheduleNotes', newValue)
})

// Методы
const updateSchedule = (newSchedule) => {
  localSchedule.value = { ...newSchedule }
  emit('update:schedule', localSchedule.value)
}

const applyPreset = (presetSchedule) => {
  localSchedule.value = { ...presetSchedule }
  emit('update:schedule', localSchedule.value)
}
</script>