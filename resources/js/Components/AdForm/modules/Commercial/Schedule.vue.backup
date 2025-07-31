<template>
  <FormSection
    title="График работы"
    hint="Укажите время, когда вы доступны для клиентов"
    required
    :error="errors.schedule"
  >
    <div class="schedule-container">
      <!-- Заголовки дней недели -->
      <div class="schedule-grid">
        <div class="day-headers">
          <div 
            v-for="day in daysOfWeek" 
            :key="day.key"
            class="day-header"
          >
            <span class="day-name">{{ day.name }}</span>
            <span class="day-short">{{ day.short }}</span>
          </div>
        </div>

        <!-- Переключатели дней -->
        <div class="day-toggles">
          <label 
            v-for="day in daysOfWeek" 
            :key="day.key"
            class="day-toggle"
          >
            <input
              type="checkbox"
              :checked="isDayEnabled(day.key)"
              @change="toggleDay(day.key, $event.target.checked)"
              class="day-checkbox"
            />
            <span class="day-toggle-label">Работаю</span>
          </label>
        </div>

        <!-- Время работы -->
        <div class="time-inputs">
          <div 
            v-for="day in daysOfWeek" 
            :key="day.key"
            class="day-time-inputs"
          >
            <div v-if="isDayEnabled(day.key)" class="time-range">
              <select 
                :value="getDaySchedule(day.key).start"
                @change="updateDayTime(day.key, 'start', $event.target.value)"
                class="time-select"
              >
                <option value="">Начало</option>
                <option v-for="time in timeOptions" :key="time" :value="time">
                  {{ time }}
                </option>
              </select>
              
              <span class="time-separator">—</span>
              
              <select 
                :value="getDaySchedule(day.key).end"
                @change="updateDayTime(day.key, 'end', $event.target.value)"
                class="time-select"
              >
                <option value="">Конец</option>
                <option v-for="time in timeOptions" :key="time" :value="time">
                  {{ time }}
                </option>
              </select>
            </div>
            
            <div v-else class="day-off">
              Выходной
            </div>
          </div>
        </div>
      </div>

      <!-- Быстрые настройки -->
      <div class="quick-schedules">
        <p class="quick-label">Быстрые настройки:</p>
        <div class="quick-buttons">
          <button
            v-for="preset in schedulePresets"
            :key="preset.name"
            type="button"
            @click="applyPreset(preset)"
            class="quick-btn"
          >
            {{ preset.name }}
          </button>
        </div>
      </div>

      <!-- Дополнительные заметки -->
      <FormField
        label="Дополнительная информация"
        hint="Особенности расписания, перерывы, праздники"
        :error="errors.schedule_notes"
      >
        <textarea
          v-model="localScheduleNotes"
          @input="updateScheduleNotes"
          rows="3"
          placeholder="Например: обеденный перерыв с 13:00 до 14:00, не работаю в праздники..."
          class="notes-textarea"
          maxlength="300"
        ></textarea>
        
        <div class="notes-counter">
          {{ localScheduleNotes.length }}/300
        </div>
      </FormField>

      <!-- Предварительный просмотр -->
      <div v-if="hasWorkingDays" class="schedule-preview">
        <div class="preview-header">
          <span class="preview-icon">📅</span>
          <span class="preview-title">Ваш график:</span>
        </div>
        
        <div class="preview-schedule">
          <div 
            v-for="day in daysOfWeek" 
            :key="day.key"
            class="preview-day"
          >
            <span class="preview-day-name">{{ day.name }}:</span>
            <span 
              v-if="isDayEnabled(day.key)"
              class="preview-day-time"
            >
              {{ formatDaySchedule(day.key) }}
            </span>
            <span v-else class="preview-day-off">выходной</span>
          </div>
        </div>
      </div>
    </div>
  </FormSection>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import FormSection from '@/Components/UI/Forms/FormSection.vue'
import FormField from '@/Components/UI/Forms/FormField.vue'

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

// Дни недели
const daysOfWeek = [
  { key: 'monday', name: 'Понедельник', short: 'Пн' },
  { key: 'tuesday', name: 'Вторник', short: 'Вт' },
  { key: 'wednesday', name: 'Среда', short: 'Ср' },
  { key: 'thursday', name: 'Четверг', short: 'Чт' },
  { key: 'friday', name: 'Пятница', short: 'Пт' },
  { key: 'saturday', name: 'Суббота', short: 'Сб' },
  { key: 'sunday', name: 'Воскресенье', short: 'Вс' }
]

// Варианты времени
const timeOptions = [
  '08:00', '08:30', '09:00', '09:30', '10:00', '10:30', '11:00', '11:30',
  '12:00', '12:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30',
  '16:00', '16:30', '17:00', '17:30', '18:00', '18:30', '19:00', '19:30',
  '20:00', '20:30', '21:00', '21:30', '22:00', '22:30', '23:00', '23:30'
]

// Готовые расписания
const schedulePresets = [
  {
    name: '9:00-18:00 (пн-пт)',
    schedule: {
      monday: { start: '09:00', end: '18:00' },
      tuesday: { start: '09:00', end: '18:00' },
      wednesday: { start: '09:00', end: '18:00' },
      thursday: { start: '09:00', end: '18:00' },
      friday: { start: '09:00', end: '18:00' }
    }
  },
  {
    name: '10:00-20:00 (ежедневно)',
    schedule: {
      monday: { start: '10:00', end: '20:00' },
      tuesday: { start: '10:00', end: '20:00' },
      wednesday: { start: '10:00', end: '20:00' },
      thursday: { start: '10:00', end: '20:00' },
      friday: { start: '10:00', end: '20:00' },
      saturday: { start: '10:00', end: '20:00' },
      sunday: { start: '10:00', end: '20:00' }
    }
  },
  {
    name: '12:00-22:00 (пн-сб)',
    schedule: {
      monday: { start: '12:00', end: '22:00' },
      tuesday: { start: '12:00', end: '22:00' },
      wednesday: { start: '12:00', end: '22:00' },
      thursday: { start: '12:00', end: '22:00' },
      friday: { start: '12:00', end: '22:00' },
      saturday: { start: '12:00', end: '22:00' }
    }
  }
]

// Методы
const isDayEnabled = (day) => {
  return localSchedule.value[day] && 
         localSchedule.value[day].start && 
         localSchedule.value[day].end
}

const getDaySchedule = (day) => {
  return localSchedule.value[day] || { start: '', end: '' }
}

const toggleDay = (day, enabled) => {
  if (enabled) {
    localSchedule.value[day] = { start: '10:00', end: '18:00' }
  } else {
    delete localSchedule.value[day]
  }
  updateSchedule()
}

const updateDayTime = (day, timeType, value) => {
  if (!localSchedule.value[day]) {
    localSchedule.value[day] = {}
  }
  localSchedule.value[day][timeType] = value
  updateSchedule()
}

const updateSchedule = () => {
  emit('update:schedule', { ...localSchedule.value })
}

const updateScheduleNotes = () => {
  emit('update:scheduleNotes', localScheduleNotes.value)
}

const applyPreset = (preset) => {
  localSchedule.value = { ...preset.schedule }
  updateSchedule()
}

const formatDaySchedule = (day) => {
  const schedule = getDaySchedule(day)
  if (schedule.start && schedule.end) {
    return `${schedule.start} — ${schedule.end}`
  }
  return ''
}

// Computed
const hasWorkingDays = computed(() => {
  return daysOfWeek.some(day => isDayEnabled(day.key))
})
</script>

<style scoped>
.schedule-container {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.schedule-grid {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 0.5rem;
  padding: 1rem;
}

.day-headers,
.day-toggles,
.time-inputs {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 0.5rem;
}

.day-header {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.day-name {
  font-size: 0.75rem;
  font-weight: 500;
  color: #374151;
}

.day-short {
  font-size: 0.875rem;
  font-weight: 600;
  color: #1f2937;
}

.day-toggle {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
  cursor: pointer;
}

.day-checkbox {
  width: 1rem;
  height: 1rem;
}

.day-toggle-label {
  font-size: 0.75rem;
  color: #6b7280;
}

.day-time-inputs {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.time-range {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
  width: 100%;
}

.time-select {
  width: 100%;
  padding: 0.375rem 0.5rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  font-size: 0.75rem;
  background: white;
}

.time-select:focus {
  outline: none;
  border-color: #3b82f6;
}

.time-separator {
  color: #6b7280;
  font-weight: 500;
}

.day-off {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem 0.5rem;
  font-size: 0.75rem;
  color: #9ca3af;
  text-align: center;
}

.quick-schedules {
  padding-top: 1rem;
  border-top: 1px solid #e5e7eb;
}

.quick-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
  margin-bottom: 0.75rem;
}

.quick-buttons {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.quick-btn {
  padding: 0.5rem 1rem;
  background: #f3f4f6;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  color: #374151;
  cursor: pointer;
  transition: all 0.2s;
}

.quick-btn:hover {
  background: #e5e7eb;
  border-color: #9ca3af;
}

.notes-textarea {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  font-size: 1rem;
  resize: vertical;
  min-height: 80px;
  transition: border-color 0.2s;
}

.notes-textarea:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.notes-counter {
  position: absolute;
  bottom: 0.5rem;
  right: 0.75rem;
  font-size: 0.75rem;
  color: #6b7280;
  background: white;
  padding: 0.25rem;
}

.schedule-preview {
  padding: 1rem;
  background: #f0fdf4;
  border: 1px solid #22c55e;
  border-radius: 0.5rem;
}

.preview-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
}

.preview-icon {
  font-size: 1rem;
}

.preview-title {
  font-size: 0.875rem;
  font-weight: 500;
  color: #166534;
}

.preview-schedule {
  display: grid;
  gap: 0.25rem;
}

.preview-day {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.875rem;
}

.preview-day-name {
  font-weight: 500;
  color: #166534;
}

.preview-day-time {
  color: #166534;
}

.preview-day-off {
  color: #9ca3af;
  font-style: italic;
}

@media (max-width: 1024px) {
  .day-headers,
  .day-toggles,
  .time-inputs {
    grid-template-columns: repeat(7, minmax(80px, 1fr));
  }
}

@media (max-width: 768px) {
  .schedule-grid {
    padding: 0.75rem;
  }
  
  .day-headers,
  .day-toggles,
  .time-inputs {
    grid-template-columns: repeat(7, minmax(60px, 1fr));
    gap: 0.25rem;
  }
  
  .day-name {
    display: none;
  }
  
  .time-select {
    font-size: 0.625rem;
    padding: 0.25rem;
  }
  
  .quick-buttons {
    justify-content: center;
  }
}
</style>