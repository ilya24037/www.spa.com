<template>
    <div class="time-slot-picker">
        <!-- Выбор даты -->
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Выберите дату
            </label>
            <div class="grid grid-cols-3 gap-2">
                <button v-for="date in availableDates"
                        :key="date.value"
                        @click="selectedDate = date.value"
                        :class="[
                            'px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                            selectedDate === date.value
                                ? 'bg-purple-600 text-white'
                                : 'bg-gray-100 hover:bg-gray-200 text-gray-700'
                        ]">
                    <div>{{ date.label }}</div>
                    <div class="text-xs opacity-75">{{ date.weekday }}</div>
                </button>
            </div>
            
            <!-- Календарь для других дат -->
            <button @click="showCalendar = true"
                    class="mt-2 text-sm text-purple-600 hover:text-purple-700 font-medium">
                Выбрать другую дату →
            </button>
        </div>

        <!-- Выбор времени -->
        <div v-if="selectedDate">
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Доступное время
            </label>
            
            <!-- Загрузка -->
            <div v-if="loadingSlots" class="py-8 text-center">
                <div class="inline-flex items-center gap-2 text-gray-500">
                    <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Загружаем доступное время...
                </div>
            </div>
            
            <!-- Нет слотов -->
            <div v-else-if="!timeSlots.length" class="py-8 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="mt-2 text-gray-600">
                    К сожалению, на эту дату нет свободного времени
                </p>
                <button @click="selectedDate = null"
                        class="mt-2 text-sm text-purple-600 hover:text-purple-700">
                    Выбрать другую дату
                </button>
            </div>
            
            <!-- Слоты времени -->
            <div v-else>
                <!-- Группировка по периодам дня -->
                <div v-for="period in timePeriods" :key="period.key" class="mb-4">
                    <h4 v-if="period.slots.length" class="text-xs font-medium text-gray-500 uppercase mb-2">
                        {{ period.label }}
                    </h4>
                    <div class="grid grid-cols-3 gap-2">
                        <button v-for="slot in period.slots"
                                :key="slot.time"
                                @click="selectTimeSlot(slot)"
                                :disabled="!slot.available"
                                :class="[
                                    'px-3 py-2 rounded-lg text-sm font-medium transition-all',
                                    selectedTime === slot.time
                                        ? 'bg-purple-600 text-white ring-2 ring-purple-600 ring-offset-2'
                                        : slot.available
                                            ? 'bg-white border border-gray-300 hover:border-purple-400 hover:bg-purple-50 text-gray-700'
                                            : 'bg-gray-100 text-gray-400 cursor-not-allowed'
                                ]">
                            {{ slot.time }}
                        </button>
                    </div>
                </div>
                
                <!-- Легенда -->
                <div class="mt-4 flex items-center gap-4 text-xs text-gray-500">
                    <div class="flex items-center gap-1">
                        <div class="w-3 h-3 bg-white border border-gray-300 rounded"></div>
                        <span>Свободно</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <div class="w-3 h-3 bg-gray-100 rounded"></div>
                        <span>Занято</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <div class="w-3 h-3 bg-purple-600 rounded"></div>
                        <span>Выбрано</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Модальное окно календаря -->
        <Modal v-if="showCalendar" @close="showCalendar = false">
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4">Выберите дату</h3>
                <Calendar 
                    :available-dates="allAvailableDates"
                    :selected-date="selectedDate"
                    @select="handleDateSelect"
                />
            </div>
        </Modal>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { format, addDays, isToday, isTomorrow, parseISO } from 'date-fns'
import { ru } from 'date-fns/locale'
import Modal from '@/Components/UI/Modal.vue'
import Calendar from '@/Components/Booking/Calendar.vue'

const props = defineProps({
    masterId: {
        type: Number,
        required: true
    },
    serviceId: {
        type: Number,
        default: null
    },
    duration: {
        type: Number,
        default: 60 // минуты
    },
    availableSlots: {
        type: Object,
        default: () => ({})
    }
})

const emit = defineEmits(['select', 'update:modelValue'])

// State
const selectedDate = ref(null)
const selectedTime = ref(null)
const loadingSlots = ref(false)
const showCalendar = ref(false)

// Computed
const availableDates = computed(() => {
    const dates = []
    const today = new Date()
    
    for (let i = 0; i < 7; i++) {
        const date = addDays(today, i)
        const dateStr = format(date, 'yyyy-MM-dd')
        
        let label = format(date, 'd MMM', { locale: ru })
        if (isToday(date)) label = 'Сегодня'
        else if (isTomorrow(date)) label = 'Завтра'
        
        dates.push({
            value: dateStr,
            label: label,
            weekday: format(date, 'EEE', { locale: ru }),
            date: date
        })
    }
    
    return dates
})

const timeSlots = computed(() => {
    if (!selectedDate.value || !props.availableSlots[selectedDate.value]) {
        return []
    }
    
    return props.availableSlots[selectedDate.value] || []
})

const timePeriods = computed(() => {
    const periods = {
        morning: { key: 'morning', label: 'Утро', slots: [] },
        afternoon: { key: 'afternoon', label: 'День', slots: [] },
        evening: { key: 'evening', label: 'Вечер', slots: [] }
    }
    
    timeSlots.value.forEach(slot => {
        const hour = parseInt(slot.time.split(':')[0])
        
        if (hour < 12) {
            periods.morning.slots.push(slot)
        } else if (hour < 17) {
            periods.afternoon.slots.push(slot)
        } else {
            periods.evening.slots.push(slot)
        }
    })
    
    return Object.values(periods).filter(p => p.slots.length > 0)
})

const allAvailableDates = computed(() => {
    // Возвращаем все даты из props.availableSlots
    return Object.keys(props.availableSlots)
})

// Methods
const selectTimeSlot = (slot) => {
    if (!slot.available) return
    
    selectedTime.value = slot.time
    
    emit('select', {
        date: selectedDate.value,
        time: slot.time,
        datetime: `${selectedDate.value} ${slot.time}`,
        slot: slot
    })
}

const handleDateSelect = (date) => {
    selectedDate.value = date
    selectedTime.value = null
    showCalendar.value = false
    
    // Загружаем слоты для выбранной даты
    loadSlotsForDate(date)
}

const loadSlotsForDate = async (date) => {
    // Если слоты уже есть в props, не загружаем
    if (props.availableSlots[date]) return
    
    loadingSlots.value = true
    
    try {
        // Эмитим событие для загрузки слотов родительским компонентом
        emit('load-slots', date)
    } finally {
        setTimeout(() => {
            loadingSlots.value = false
        }, 500)
    }
}

// Watchers
watch(selectedDate, (newDate) => {
    if (newDate) {
        loadSlotsForDate(newDate)
    }
})

// Auto-select first available date
const firstAvailableDate = availableDates.value.find(date => 
    props.availableSlots[date.value]?.some(slot => slot.available)
)
if (firstAvailableDate) {
    selectedDate.value = firstAvailableDate.value
}
</script>

<style scoped>
/* Анимация появления слотов */
.time-slot-picker button {
    transition: all 0.2s ease;
}

.time-slot-picker button:active:not(:disabled) {
    transform: scale(0.95);
}

/* Анимация загрузки */
@keyframes spin {
    to { transform: rotate(360deg); }
}

.animate-spin {
    animation: spin 1s linear infinite;
}
</style>