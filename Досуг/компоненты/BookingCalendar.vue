<template>
  <div class="booking-calendar">
    <!-- Календарь бронирования, адаптированный из dosugbar -->
    <div class="calendar-header">
      <h3>Выберите дату и время</h3>
    </div>
    
    <div class="calendar-map">
      <!-- Легенда статусов -->
      <ul class="legend">
        <li class="available">Свободно</li>
        <li class="busy">Занято</li>
        <li class="pending">Ожидает подтверждения</li>
        <li class="selected">Выбрано</li>
      </ul>
      
      <!-- Календарная сетка -->
      <div class="calendar-grid">
        <div v-for="day in days" :key="day.date" class="calendar-day">
          <div class="day-header">{{ formatDate(day.date) }}</div>
          <div class="time-slots">
            <button
              v-for="slot in day.slots"
              :key="slot.time"
              :class="['time-slot', slot.status]"
              :disabled="slot.status === 'busy'"
              @click="selectSlot(day.date, slot)"
            >
              {{ slot.time }}
            </button>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Действия -->
    <div v-if="selectedSlot" class="booking-actions">
      <button class="btn-primary" @click="confirmBooking">
        Подтвердить бронирование
      </button>
      <button class="btn-secondary" @click="cancelSelection">
        Отмена
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'

interface TimeSlot {
  time: string
  status: 'available' | 'busy' | 'pending' | 'selected'
}

interface Day {
  date: Date
  slots: TimeSlot[]
}

const days = ref<Day[]>([])
const selectedSlot = ref<{ date: Date; slot: TimeSlot } | null>(null)

// Форматирование даты
const formatDate = (date: Date): string => {
  return new Intl.DateTimeFormat('ru-RU', {
    weekday: 'short',
    day: 'numeric',
    month: 'short'
  }).format(date)
}

// Выбор слота времени
const selectSlot = (date: Date, slot: TimeSlot) => {
  if (selectedSlot.value) {
    selectedSlot.value.slot.status = 'available'
  }
  slot.status = 'selected'
  selectedSlot.value = { date, slot }
}

// Подтверждение бронирования
const confirmBooking = () => {
  if (selectedSlot.value) {
    // Логика отправки бронирования
    console.log('Booking confirmed:', selectedSlot.value)
  }
}

// Отмена выбора
const cancelSelection = () => {
  if (selectedSlot.value) {
    selectedSlot.value.slot.status = 'available'
    selectedSlot.value = null
  }
}

// Инициализация демо-данных
const initCalendar = () => {
  const today = new Date()
  for (let i = 0; i < 7; i++) {
    const date = new Date(today)
    date.setDate(today.getDate() + i)
    
    const slots: TimeSlot[] = []
    for (let hour = 9; hour < 21; hour++) {
      slots.push({
        time: `${hour}:00`,
        status: Math.random() > 0.3 ? 'available' : 'busy'
      })
    }
    
    days.value.push({ date, slots })
  }
}

initCalendar()
</script>

<style scoped>
/* Стили адаптированы из booking.css */
.booking-calendar {
  background: #fff;
  border-radius: 8px;
  padding: 20px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.calendar-header h3 {
  margin: 0 0 20px;
  color: #333;
}

.legend {
  display: flex;
  gap: 20px;
  margin-bottom: 20px;
  padding: 0;
  list-style: none;
}

.legend li {
  position: relative;
  padding-left: 25px;
  font-size: 14px;
}

.legend li::before {
  content: '';
  position: absolute;
  left: 0;
  top: 50%;
  transform: translateY(-50%);
  width: 15px;
  height: 15px;
  border-radius: 3px;
}

.legend .available::before {
  background: #4caf50;
}

.legend .busy::before {
  background: #f44336;
}

.legend .pending::before {
  background: #ff9800;
}

.legend .selected::before {
  background: #2196f3;
}

.calendar-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 15px;
}

.calendar-day {
  border: 1px solid #e0e0e0;
  border-radius: 6px;
  overflow: hidden;
}

.day-header {
  background: #f5f5f5;
  padding: 10px;
  text-align: center;
  font-weight: 500;
}

.time-slots {
  padding: 10px;
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.time-slot {
  padding: 8px;
  border: 1px solid #e0e0e0;
  border-radius: 4px;
  background: white;
  cursor: pointer;
  transition: all 0.3s;
}

.time-slot:hover:not(:disabled) {
  background: #f0f0f0;
}

.time-slot.available {
  border-color: #4caf50;
  color: #4caf50;
}

.time-slot.busy {
  background: #fafafa;
  color: #999;
  cursor: not-allowed;
}

.time-slot.selected {
  background: #2196f3;
  color: white;
  border-color: #2196f3;
}

.booking-actions {
  margin-top: 20px;
  display: flex;
  gap: 10px;
  justify-content: flex-end;
}

.btn-primary,
.btn-secondary {
  padding: 10px 20px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 500;
  transition: background 0.3s;
}

.btn-primary {
  background: #2196f3;
  color: white;
}

.btn-primary:hover {
  background: #1976d2;
}

.btn-secondary {
  background: #e0e0e0;
  color: #333;
}

.btn-secondary:hover {
  background: #d0d0d0;
}
</style>