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
        :model-value="localScheduleNotes"
        @update:model-value="(value) => store.updateField('schedule_notes', value)"
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
import { computed } from 'vue'
import FormSection from '@/Components/UI/Forms/FormSection.vue'
import { useAdFormStore } from '../../../stores/adFormStore'

// Микрокомпоненты
import WeekdayGrid from './components/WeekdayGrid.vue'
import QuickPresets from './components/QuickPresets.vue'
import ScheduleNotes from './components/ScheduleNotes.vue'
import SchedulePreview from './components/SchedulePreview.vue'
import ScheduleTips from './components/ScheduleTips.vue'

// AVITO-STYLE: Используем централизованный store
const store = useAdFormStore()

const props = defineProps({
  errors: { type: Object, default: () => ({}) }
})

// Читаем данные ТОЛЬКО из store (как на Avito)
const schedule = computed(() => {
  let scheduleData = store.formData.schedule || {}
  
  // Если пришла строка, парсим JSON
  if (typeof scheduleData === 'string') {
    try {
      scheduleData = JSON.parse(scheduleData) || {}
    } catch (e) {
      scheduleData = {}
    }
  }
  
  return scheduleData
})

const scheduleNotes = computed(() => store.formData.schedule_notes || '')

// Локальное состояние для UI (нужно для компонентов)
const localSchedule = computed(() => schedule.value)
const localScheduleNotes = computed(() => scheduleNotes.value)

// Методы обновляют ТОЛЬКО store (как на Avito/Ozon)
const updateSchedule = (newSchedule) => {
  console.log('updateSchedule called:', newSchedule)
  store.updateField('schedule', newSchedule)
}

const applyPreset = (presetSchedule) => {
  console.log('applyPreset called:', presetSchedule)
  store.updateField('schedule', presetSchedule)
}
</script>