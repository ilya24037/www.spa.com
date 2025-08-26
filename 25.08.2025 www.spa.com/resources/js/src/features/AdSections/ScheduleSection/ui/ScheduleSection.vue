<template>
    <div class="schedule-section">
        <h2 class="form-group-title">График работы</h2>
        
        <!-- Онлайн запись -->
        <div class="online-booking-section">
            <h3 class="subsection-title">Онлайн запись</h3>
            <p class="subsection-description">
                Разрешить клиентам записываться к вам онлайн через сайт
            </p>
            <div class="radio-group">
                <BaseRadio
                    v-model="localOnlineBooking"
                    :value="true"
                    name="online_booking"
                    label="Да"
                    description="Клиенты смогут записываться онлайн"
                    @update:modelValue="emitOnlineBooking"
                />
                <BaseRadio
                    v-model="localOnlineBooking"
                    :value="false"
                    name="online_booking"
                    label="Нет"
                    description="Только запись по телефону"
                    @update:modelValue="emitOnlineBooking"
                />
            </div>
        </div>

        <div class="schedule-container">
            <div class="quick-actions" style="margin-bottom: 20px;">
                <SecondaryButton @click="setFullWeek">
                    Круглосуточно всю неделю
                </SecondaryButton>
                <SecondaryButton @click="setWorkdays">
                    Будни 9:00-18:00
                </SecondaryButton>
                <SecondaryButton @click="clearAll">
                    Очистить всё
                </SecondaryButton>
            </div>
            <div class="days-list">
                <div v-for="day in days" :key="day.id" class="day-item">
                    <div class="day-name">{{ day.name }}</div>
                    <div class="day-schedule">
                        <div class="work-toggle">
                            <BaseCheckbox
                                v-model="localSchedule[day.id].enabled"
                                :name="`schedule_${day.id}`"
                                :label="localSchedule[day.id].enabled ? 'Работаю' : 'Выходной'"
                                @update:modelValue="toggleDay(day.id)"
                            />
                        </div>
                        <div v-if="localSchedule[day.id].enabled" class="time-selection">
                            <BaseSelect
                                v-model="localSchedule[day.id].from"
                                label="С"
                                :options="timeOptionsFrom"
                                @update:modelValue="emitSchedule"
                                class="time-field"
                            />
                            <BaseSelect
                                v-if="localSchedule[day.id].from !== '24:00'"
                                v-model="localSchedule[day.id].to"
                                label="До"
                                :options="timeOptionsTo"
                                @update:modelValue="emitSchedule"
                                class="time-field"
                            />
                        </div>
                    </div>
                </div>
            </div>
            <div class="additional-info">
                <BaseTextarea
                    v-model="localNotes"
                    label="Дополнительная информация о графике работы"
                    placeholder="Например: возможны изменения графика по договоренности, предварительная запись обязательна и т.д."
                    :rows="3"
                    @update:modelValue="emitNotes"
                />
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, watch, computed } from 'vue'
import BaseSelect from '@/src/shared/ui/atoms/BaseSelect/BaseSelect.vue'
import BaseTextarea from '@/src/shared/ui/atoms/BaseTextarea/BaseTextarea.vue'
import BaseCheckbox from '@/src/shared/ui/atoms/BaseCheckbox/BaseCheckbox.vue'
import BaseRadio from '@/src/shared/ui/atoms/BaseRadio/BaseRadio.vue'
import SecondaryButton from '@/src/shared/ui/atoms/SecondaryButton/SecondaryButton.vue'

// TypeScript интерфейсы
interface Props {
    schedule?: Record<string, any>
    scheduleNotes?: string
    onlineBooking?: boolean
    errors?: Record<string, any>
}

interface Emits {
    'update:schedule': [value: Record<string, any>]
    'update:scheduleNotes': [value: string]
    'update:online-booking': [value: boolean]
}

// Props и Emits
const props = withDefaults(defineProps<Props>(), {
    schedule: () => ({}),
    scheduleNotes: '',
    onlineBooking: false,
    errors: () => ({})
})

const emit = defineEmits<Emits>()

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
    '00:00','00:30','01:00','01:30','02:00','02:30','03:00','03:30','04:00','04:30','05:00','05:30','06:00','06:30','07:00','07:30','08:00','08:30','09:00','09:30','10:00','10:30','11:00','11:30','12:00','12:30','13:00','13:30','14:00','14:30','15:00','15:30','16:00','16:30','17:00','17:30','18:00','18:30','19:00','19:30','20:00','20:30','21:00','21:30','22:00','22:30','23:00','23:30','24:00'
]
const timesTo = times.filter(t => t !== '00:00')

// Опции для селектов времени
const timeOptionsFrom = computed(() => [
    { value: '', label: '--:--' },
    ...times.map(t => ({
        value: t,
        label: t === '24:00' ? 'Круглосуточно' : t
    }))
])

const timeOptionsTo = computed(() => [
    { value: '', label: '--:--' },
    ...timesTo.map(t => ({
        value: t,
        label: t
    }))
])

const initSchedule = () => {
    const initial = {}
    days.forEach(day => {
        initial[day.id] = { enabled: false, from: '', to: '' }
    })
    return initial
}

// Локальное состояние для графика работы
const localSchedule = reactive({ ...initSchedule() })
const localNotes = ref('')
const localOnlineBooking = ref(false)

// Инициализация при монтировании
const initializeSchedule = () => {
    // Инициализируем структуру дней
    days.forEach(day => {
        if (!localSchedule[day.id]) {
            localSchedule[day.id] = { enabled: false, from: '', to: '' }
        }
    })
    
    // Загружаем данные из props
    if (props.schedule && typeof props.schedule === 'object') {
        Object.keys(props.schedule).forEach(dayId => {
            if (localSchedule[dayId]) {
                localSchedule[dayId] = { ...props.schedule[dayId] }
            }
        })
    }
    
    // Загружаем заметки
    if (props.scheduleNotes) {
        localNotes.value = props.scheduleNotes
    }
    
    // Загружаем онлайн запись
    if (props.onlineBooking !== undefined) {
        localOnlineBooking.value = props.onlineBooking
    }
}

// Отслеживаем изменения из родителя
watch(() => props.schedule, (newValue) => {
    if (newValue && typeof newValue === 'object') {
        Object.keys(newValue).forEach(dayId => {
            if (localSchedule[dayId]) {
                localSchedule[dayId] = { ...newValue[dayId] }
            }
        })
    }
}, { deep: true })

watch(() => props.scheduleNotes, (newValue) => {
    if (newValue !== undefined) {
        localNotes.value = newValue
    }
})

watch(() => props.onlineBooking, (newValue) => {
    if (newValue !== undefined) {
        localOnlineBooking.value = newValue
    }
})

// Инициализируем при монтировании
initializeSchedule()

// Эмитим изменения при пользовательских действиях
const emitSchedule = () => {
    emit('update:schedule', { ...localSchedule })
}



const emitNotes = () => {
    // ВАЖНО: Всегда отправляем строку, не null
    emit('update:scheduleNotes', localNotes.value || '')
}

const emitOnlineBooking = () => {
    emit('update:online-booking', localOnlineBooking.value)
}

const toggleDay = (dayId) => {
    if (!localSchedule[dayId].enabled) {
        localSchedule[dayId].from = ''
        localSchedule[dayId].to = ''
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

.online-booking-section {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 24px;
}

.subsection-title {
    font-size: 16px;
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.subsection-title::before {
    content: "📅";
    font-size: 18px;
}

.subsection-description {
    font-size: 14px;
    color: #666;
    margin-bottom: 16px;
    line-height: 1.4;
}

.radio-group {
    display: flex;
    gap: 24px;
    flex-wrap: wrap;
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
    align-items: flex-end;
}

.time-field {
    min-width: 120px;
}

.additional-info {
    border-top: 1px solid #e5e5e5;
    padding-top: 20px;
    margin-top: 20px;
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
