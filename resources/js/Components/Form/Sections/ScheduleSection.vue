<template>
    <div class="schedule-section">
        <h2 class="form-group-title">График работы</h2>
        <div class="schedule-container">
            <div class="quick-actions" style="margin-bottom: 20px;">
                <button type="button" @click="setFullWeek" class="quick-btn">Круглосуточно всю неделю</button>
                <button type="button" @click="setWorkdays" class="quick-btn">Будни 9:00-18:00</button>
                <button type="button" @click="clearAll" class="quick-btn">Очистить всё</button>
            </div>
            <div class="days-list">
                <div v-for="day in days" :key="day.id" class="day-item">
                    <div class="day-name">{{ day.name }}</div>
                    <div class="day-schedule">
                        <div class="work-toggle">
                            <label class="toggle-switch">
                                <input type="checkbox" v-model="localSchedule[day.id].enabled" @change="toggleDay(day.id)">
                                <span class="toggle-slider"></span>
                            </label>
                            <span class="toggle-label">{{ localSchedule[day.id].enabled ? 'Работаю' : 'Выходной' }}</span>
                        </div>
                        <div v-if="localSchedule[day.id].enabled" class="time-selection">
                            <div class="time-field">
                                <label>С:</label>
                                <select v-model="localSchedule[day.id].from" @change="emitSchedule" class="time-select">
                                    <option v-for="t in times" :key="t" :value="t">{{ t === '24:00' ? 'Круглосуточно' : t }}</option>
                                </select>
                            </div>
                            <div class="time-field" v-if="localSchedule[day.id].from !== '24:00'">
                                <label>До:</label>
                                <select v-model="localSchedule[day.id].to" @change="emitSchedule" class="time-select">
                                    <option v-for="t in timesTo" :key="t" :value="t">{{ t }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="additional-info" style="margin-top: 20px;">
                <label class="additional-label">Дополнительная информация о графике работы:</label>
                <textarea v-model="localNotes" @input="emitNotes" placeholder="Например: возможны изменения графика по договоренности, предварительная запись обязательна и т.д." class="additional-textarea" rows="3"></textarea>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, watch, computed } from 'vue'

const props = defineProps({
    schedule: { type: [Object, String], default: () => ({}) },
    scheduleNotes: { type: String, default: '' },
    errors: { type: Object, default: () => ({}) }
})

// Schedule section загружен
const emit = defineEmits(['update:schedule', 'update:scheduleNotes'])

const days = [
    { id: 'monday', name: 'Понедельник' },
    { id: 'tuesday', name: 'Вторник' },
    { id: 'wednesday', name: 'Среда' },
    { id: 'thursday', name: 'Четверг' },
    { id: 'friday', name: 'Пятница' },
    { id: 'saturday', name: 'Суббота' },
    { id: 'sunday', name: 'Воскресенье' }
]
const times = [
    '24:00', // Круглосуточно - первым в списке
    '00:00','00:30','01:00','01:30','02:00','02:30','03:00','03:30','04:00','04:30','05:00','05:30','06:00','06:30','07:00','07:30','08:00','08:30','09:00','09:30','10:00','10:30','11:00','11:30','12:00','12:30','13:00','13:30','14:00','14:30','15:00','15:30','16:00','16:30','17:00','17:30','18:00','18:30','19:00','19:30','20:00','20:30','21:00','21:30','22:00','22:30','23:00','23:30'
]
const timesTo = times.filter(t => t !== '00:00')

const initSchedule = () => {
    const initial = {}
    days.forEach(day => {
        initial[day.id] = { enabled: false, from: '', to: '' }
    })
    return initial
}

// Преобразуем schedule в объект если это строка
const getScheduleObject = () => {
    let scheduleObj = {}
    if (typeof props.schedule === 'string') {
        try {
            scheduleObj = JSON.parse(props.schedule) || {}
        } catch (e) {
            scheduleObj = {}
        }
    } else if (props.schedule && typeof props.schedule === 'object') {
        scheduleObj = props.schedule
    }
    return scheduleObj
}

const localSchedule = reactive({ ...initSchedule(), ...getScheduleObject() })
const localNotes = ref(props.scheduleNotes || '')

// Флаг для предотвращения рекурсивных обновлений
let isUpdatingFromProps = false

watch(() => props.schedule, (val) => {
    if (val && !isUpdatingFromProps) {
        isUpdatingFromProps = true
        const scheduleObj = getScheduleObject()
        days.forEach(day => {
            if (scheduleObj[day.id]) {
                localSchedule[day.id] = { ...scheduleObj[day.id] }
            }
        })
        isUpdatingFromProps = false
    }
}, { deep: true })

// Эмитим изменения только при пользовательских действиях
const emitSchedule = () => {
    if (!isUpdatingFromProps) {
        emit('update:schedule', { ...localSchedule })
    }
}

watch(() => props.scheduleNotes, (val) => {
    localNotes.value = val || ''
})

const emitNotes = () => {
    emit('update:scheduleNotes', localNotes.value)
}

const toggleDay = (dayId) => {
    if (!localSchedule[dayId].enabled) {
        localSchedule[dayId].from = ''
        localSchedule[dayId].to = ''
    } else {
        // Устанавливаем время по умолчанию при включении дня
        // только если время еще не установлено
        if (!localSchedule[dayId].from && !localSchedule[dayId].to) {
            localSchedule[dayId].from = '09:00'
            localSchedule[dayId].to = '18:00'
        }
    }
    emitSchedule()
}
const setFullWeek = () => {
    days.forEach(day => {
        localSchedule[day.id] = { enabled: true, from: '24:00', to: '' }
    })
    emitSchedule()
}
const setWorkdays = () => {
    clearAll()
    const workdays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday']
    workdays.forEach(dayId => {
        localSchedule[dayId] = { enabled: true, from: '09:00', to: '18:00' }
    })
    emitSchedule()
}
const clearAll = () => {
    days.forEach(day => {
        localSchedule[day.id] = { enabled: false, from: '', to: '' }
    })
    emitSchedule()
}
</script>

<style scoped>
.schedule-section {
    background: white;
    border-radius: 8px;
    padding: 20px;
}

.form-group-title {
    font-size: 18px;
    font-weight: 600;
    color: #333;
    margin-bottom: 16px;
}

.quick-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.quick-btn {
    padding: 8px 16px;
    background: #f5f5f5;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s;
}

.quick-btn:hover {
    background: #e9e9e9;
    border-color: #ccc;
}

.days-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.day-item {
    border: 1px solid #e5e5e5;
    border-radius: 8px;
    padding: 16px;
    background: #fafafa;
}

.day-name {
    font-weight: 600;
    color: #333;
    margin-bottom: 12px;
    font-size: 16px;
}

.day-schedule {
    display: flex;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
}

.work-toggle {
    display: flex;
    align-items: center;
    gap: 8px;
}

.toggle-switch {
    position: relative;
    width: 50px;
    height: 24px;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: 0.3s;
    border-radius: 24px;
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: 0.3s;
    border-radius: 50%;
}

input:checked + .toggle-slider {
    background-color: #4CAF50;
}

input:checked + .toggle-slider:before {
    transform: translateX(26px);
}

.toggle-label {
    font-size: 14px;
    color: #666;
    min-width: 70px;
}

.time-selection {
    display: flex;
    gap: 12px;
    align-items: center;
}

.time-field {
    display: flex;
    align-items: center;
    gap: 6px;
}

.time-field label {
    font-size: 14px;
    color: #666;
    min-width: 25px;
}

.time-select {
    padding: 6px 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    background: white;
    min-width: 80px;
}

.time-select:focus {
    outline: none;
    border-color: #4CAF50;
    box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.2);
}

.additional-info {
    border-top: 1px solid #e5e5e5;
    padding-top: 16px;
}

.additional-label {
    display: block;
    font-size: 14px;
    font-weight: 500;
    color: #333;
    margin-bottom: 8px;
}

.additional-textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
    font-family: inherit;
    resize: vertical;
    min-height: 80px;
}

.additional-textarea:focus {
    outline: none;
    border-color: #4CAF50;
    box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.2);
}

/* Мобильная адаптация */
@media (max-width: 768px) {
    .day-schedule {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
    
    .time-selection {
        flex-wrap: wrap;
    }
    
    .quick-actions {
        justify-content: center;
    }
    
    .quick-btn {
        flex: 1;
        min-width: 120px;
    }
}
</style> 