<template>
    <div class="time-slot-picker">
        <!-- Р’С‹Р±РѕСЂ РґР°С‚С‹ -->
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Р’С‹Р±РµСЂРёС‚Рµ РґР°С‚Сѓ
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
            
            <!-- РљР°Р»РµРЅРґР°СЂСЊ РґР»СЏ РґСЂСѓРіРёС… РґР°С‚ -->
            <button @click="showCalendar = true"
                    class="mt-2 text-sm text-purple-600 hover:text-purple-700 font-medium">
                Р’С‹Р±СЂР°С‚СЊ РґСЂСѓРіСѓСЋ РґР°С‚Сѓ в†’
            </button>
        </div>

        <!-- Р’С‹Р±РѕСЂ РІСЂРµРјРµРЅРё -->
        <div v-if="selectedDate">
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Р”РѕСЃС‚СѓРїРЅРѕРµ РІСЂРµРјСЏ
            </label>
            
            <!-- Р—Р°РіСЂСѓР·РєР° -->
            <div v-if="loadingSlots" class="py-8 text-center">
                <div class="inline-flex items-center gap-2 text-gray-500">
                    <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Р—Р°РіСЂСѓР¶Р°РµРј РґРѕСЃС‚СѓРїРЅРѕРµ РІСЂРµРјСЏ...
                </div>
            </div>
            
            <!-- РќРµС‚ СЃР»РѕС‚РѕРІ -->
            <div v-else-if="!timeSlots.length" class="py-8 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="mt-2 text-gray-600">
                    Рљ СЃРѕР¶Р°Р»РµРЅРёСЋ, РЅР° СЌС‚Сѓ РґР°С‚Сѓ РЅРµС‚ СЃРІРѕР±РѕРґРЅРѕРіРѕ РІСЂРµРјРµРЅРё
                </p>
                <button @click="selectedDate = null"
                        class="mt-2 text-sm text-purple-600 hover:text-purple-700">
                    Р’С‹Р±СЂР°С‚СЊ РґСЂСѓРіСѓСЋ РґР°С‚Сѓ
                </button>
            </div>
            
            <!-- РЎР»РѕС‚С‹ РІСЂРµРјРµРЅРё -->
            <div v-else>
                <!-- Р“СЂСѓРїРїРёСЂРѕРІРєР° РїРѕ РїРµСЂРёРѕРґР°Рј РґРЅСЏ -->
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
                
                <!-- Р›РµРіРµРЅРґР° -->
                <div class="mt-4 flex items-center gap-4 text-xs text-gray-500">
                    <div class="flex items-center gap-1">
                        <div class="w-3 h-3 bg-white border border-gray-300 rounded"></div>
                        <span>РЎРІРѕР±РѕРґРЅРѕ</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <div class="w-3 h-3 bg-gray-100 rounded"></div>
                        <span>Р—Р°РЅСЏС‚Рѕ</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <div class="w-3 h-3 bg-purple-600 rounded"></div>
                        <span>Р’С‹Р±СЂР°РЅРѕ</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- РњРѕРґР°Р»СЊРЅРѕРµ РѕРєРЅРѕ РєР°Р»РµРЅРґР°СЂСЏ -->
        <Modal v-if="showCalendar" @close="showCalendar = false">
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4">Р’С‹Р±РµСЂРёС‚Рµ РґР°С‚Сѓ</h3>
                <BookingCalendar 
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
import Modal from '@/src/shared/ui/organisms/Modal/Modal.vue'
import { BookingCalendar } from '@/src/features/booking'

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
        default: 60 // РјРёРЅСѓС‚С‹
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
        if (isToday(date)) label = 'РЎРµРіРѕРґРЅСЏ'
        else if (isTomorrow(date)) label = 'Р—Р°РІС‚СЂР°'
        
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
        morning: { key: 'morning', label: 'РЈС‚СЂРѕ', slots: [] },
        afternoon: { key: 'afternoon', label: 'Р”РµРЅСЊ', slots: [] },
        evening: { key: 'evening', label: 'Р’РµС‡РµСЂ', slots: [] }
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
    // Р’РѕР·РІСЂР°С‰Р°РµРј РІСЃРµ РґР°С‚С‹ РёР· props.availableSlots
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
    
    // Р—Р°РіСЂСѓР¶Р°РµРј СЃР»РѕС‚С‹ РґР»СЏ РІС‹Р±СЂР°РЅРЅРѕР№ РґР°С‚С‹
    loadSlotsForDate(date)
}

const loadSlotsForDate = async (date) => {
    // Р•СЃР»Рё СЃР»РѕС‚С‹ СѓР¶Рµ РµСЃС‚СЊ РІ props, РЅРµ Р·Р°РіСЂСѓР¶Р°РµРј
    if (props.availableSlots[date]) return
    
    loadingSlots.value = true
    
    try {
        // Р­РјРёС‚РёРј СЃРѕР±С‹С‚РёРµ РґР»СЏ Р·Р°РіСЂСѓР·РєРё СЃР»РѕС‚РѕРІ СЂРѕРґРёС‚РµР»СЊСЃРєРёРј РєРѕРјРїРѕРЅРµРЅС‚РѕРј
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
/* РђРЅРёРјР°С†РёСЏ РїРѕСЏРІР»РµРЅРёСЏ СЃР»РѕС‚РѕРІ */
.time-slot-picker button {
    transition: all 0.2s ease;
}

.time-slot-picker button:active:not(:disabled) {
    transform: scale(0.95);
}

/* РђРЅРёРјР°С†РёСЏ Р·Р°РіСЂСѓР·РєРё */
@keyframes spin {
    to { transform: rotate(360deg); }
}

.animate-spin {
    animation: spin 1s linear infinite;
}
</style>

