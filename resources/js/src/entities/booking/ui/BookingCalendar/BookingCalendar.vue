<template>
  <div class="booking-calendar">
    <!-- Р—Р°РіРѕР»РѕРІРѕРє -->
    <div class="mb-6">
      <h3 class="text-lg font-semibold text-gray-500 mb-2">
        Р’С‹Р±РµСЂРёС‚Рµ РґР°С‚Сѓ Рё РІСЂРµРјСЏ
      </h3>
      <p class="text-sm text-gray-500">
        Р”РѕСЃС‚СѓРїРЅРѕРµ РІСЂРµРјСЏ РґР»СЏ Р·Р°РїРёСЃРё Рє РјР°СЃС‚РµСЂСѓ
      </p>
    </div>

    <!-- РљР°Р»РµРЅРґР°СЂСЊ РІС‹Р±РѕСЂР° РґР°С‚С‹ -->
    <div class="mb-6">
      <h4 class="text-md font-medium text-gray-500 mb-3">
        Р”Р°С‚Р° Р·Р°РїРёСЃРё
      </h4>
      <div class="grid grid-cols-7 gap-1 mb-4">
        <!-- Р—Р°РіРѕР»РѕРІРєРё РґРЅРµР№ РЅРµРґРµР»Рё -->
        <div 
          v-for="day in weekDays" 
          :key="day"
          class="p-2 text-center text-xs font-medium text-gray-500"
        >
          {{ day }}
        </div>
        
        <!-- Р”Р°С‚С‹ -->
        <button
          v-for="date in calendarDates" 
          :key="date.key"
          :disabled="!date.available || date.isPast"
          :class="[
            'p-2 text-sm rounded-lg transition-colors',
            date.isToday && 'font-semibold',
            date.available && !date.isPast ? 'hover:bg-blue-50 cursor-pointer' : 'cursor-not-allowed',
            selectedDate?.isSame(date.date, 'day') 
              ? 'bg-blue-600 text-white' 
              : date.available && !date.isPast 
                ? 'text-gray-500' 
                : 'text-gray-500',
            !date.available && !date.isPast && 'bg-red-50 text-red-400'
          ]"
          @click="selectDate(date)"
        >
          {{ date.date.format('D') }}
        </button>
      </div>
    </div>

    <!-- Р’С‹Р±РѕСЂ РІСЂРµРјРµРЅРё -->
    <div v-if="selectedDate" class="mb-6">
      <h4 class="text-md font-medium text-gray-500 mb-3">
        Р’СЂРµРјСЏ Р·Р°РїРёСЃРё РЅР° {{ selectedDate.format('D MMMM') }}
      </h4>
      
      <div v-if="loadingTimeSlots" class="flex justify-center py-8">
        <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600" />
      </div>
      
      <div v-else-if="availableTimeSlots.length === 0" class="text-center py-8">
        <p class="text-gray-500">
          РќР° РІС‹Р±СЂР°РЅРЅСѓСЋ РґР°С‚Сѓ РЅРµС‚ СЃРІРѕР±РѕРґРЅРѕРіРѕ РІСЂРµРјРµРЅРё
        </p>
        <p class="text-sm text-gray-500 mt-1">
          РџРѕРїСЂРѕР±СѓР№С‚Рµ РІС‹Р±СЂР°С‚СЊ РґСЂСѓРіСѓСЋ РґР°С‚Сѓ
        </p>
      </div>
      
      <div v-else class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-2">
        <button
          v-for="timeSlot in availableTimeSlots"
          :key="timeSlot.time"
          :disabled="!timeSlot.available"
          :class="[
            'p-2 text-sm rounded-lg border transition-colors',
            timeSlot.available ? 'hover:bg-blue-50 cursor-pointer' : 'cursor-not-allowed',
            selectedTime === timeSlot.time
              ? 'bg-blue-600 text-white border-blue-600'
              : timeSlot.available
                ? 'bg-white text-gray-500 border-gray-500'
                : 'bg-gray-500 text-gray-500 border-gray-500'
          ]"
          @click="selectTime(timeSlot)"
        >
          {{ timeSlot.time }}
        </button>
      </div>
    </div>

    <!-- Р’С‹Р±СЂР°РЅРЅРѕРµ РІСЂРµРјСЏ -->
    <div v-if="selectedDate && selectedTime" class="bg-blue-50 rounded-lg p-4">
      <div class="flex items-center">
        <div class="flex-shrink-0">
          <svg
            class="h-5 w-5 text-blue-600"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
            />
          </svg>
        </div>
        <div class="ml-3">
          <p class="text-sm font-medium text-blue-900">
            Р’С‹Р±СЂР°РЅРѕ РІСЂРµРјСЏ Р·Р°РїРёСЃРё
          </p>
          <p class="text-sm text-blue-700">
            {{ selectedDate.format('DD MMMM YYYY') }} РІ {{ selectedTime }}
          </p>
        </div>
      </div>
    </div>

    <!-- РћС€РёР±РєРё -->
    <div v-if="error" class="mt-4 bg-red-50 border border-red-200 rounded-lg p-4">
      <div class="flex">
        <div class="flex-shrink-0">
          <svg
            class="h-5 w-5 text-red-400"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
            />
          </svg>
        </div>
        <div class="ml-3">
          <p class="text-sm text-red-800">
            {{ error }}
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import dayjs from 'dayjs'
import 'dayjs/locale/ru'
import updateLocale from 'dayjs/plugin/updateLocale'

// РќР°СЃС‚СЂРѕР№РєР° dayjs
dayjs.extend(updateLocale)
dayjs.locale('ru')

// Props
const props = defineProps({
    masterId: {
        type: [String, Number],
        required: true
    },
    selectedService: {
        type: Object,
        default: null
    },
    minDate: {
        type: String,
        default: () => dayjs().format('YYYY-MM-DD')
    },
    maxDate: {
        type: String,
        default: () => dayjs().add(30, 'days').format('YYYY-MM-DD')
    }
})

// Events
const emit = defineEmits(['update:selectedDate', 'update:selectedTime', 'selection-change'])

// РЎРѕСЃС‚РѕСЏРЅРёРµ РєРѕРјРїРѕРЅРµРЅС‚Р°
const selectedDate = ref(null)
const selectedTime = ref(null)
const availableTimeSlots = ref([])
const loadingTimeSlots = ref(false)
const error = ref(null)

// РљРѕРЅСЃС‚Р°РЅС‚С‹
const weekDays = ['РџРЅ', 'Р’С‚', 'РЎСЂ', 'Р§С‚', 'РџС‚', 'РЎР±', 'Р’СЃ']

// Р’С‹С‡РёСЃР»СЏРµРјС‹Рµ СЃРІРѕР№СЃС‚РІР°
const calendarDates = computed(() => {
    const startDate = dayjs(props.minDate)
    const endDate = dayjs(props.maxDate)
    const dates = []
  
    // РќР°С…РѕРґРёРј РїРµСЂРІС‹Р№ РїРѕРЅРµРґРµР»СЊРЅРёРє РґР»СЏ РѕС‚РѕР±СЂР°Р¶РµРЅРёСЏ РїРѕР»РЅРѕР№ РЅРµРґРµР»Рё
    const firstDay = startDate.startOf('month').startOf('week').add(1, 'day')
  
    // Р“РµРЅРµСЂРёСЂСѓРµРј РґР°С‚С‹ РґР»СЏ РєР°Р»РµРЅРґР°СЂСЏ
    let currentDate = firstDay
    const today = dayjs()
  
    for (let i = 0; i < 42; i++) { // 6 РЅРµРґРµР»СЊ РјР°РєСЃРёРјСѓРј
        if (currentDate.isAfter(endDate)) break
    
        dates.push({
            date: currentDate,
            key: currentDate.format('YYYY-MM-DD'),
            available: currentDate.isBetween(startDate, endDate, 'day', '[]'),
            isToday: currentDate.isSame(today, 'day'),
            isPast: currentDate.isBefore(today, 'day')
        })
    
        currentDate = currentDate.add(1, 'day')
    }
  
    return dates
})

// РњРµС‚РѕРґС‹
const selectDate = async (dateObj) => {
    if (!dateObj.available || dateObj.isPast) return
  
    selectedDate.value = dateObj.date
    selectedTime.value = null
    error.value = null
  
    emit('update:selectedDate', dateObj.date.format('YYYY-MM-DD'))
    emit('update:selectedTime', null)
  
    await loadTimeSlots(dateObj.date)
}

const selectTime = (timeSlot) => {
    if (!timeSlot.available) return
  
    selectedTime.value = timeSlot.time
    emit('update:selectedTime', timeSlot.time)
  
    // РћС‚РїСЂР°РІР»СЏРµРј РїРѕР»РЅСѓСЋ РёРЅС„РѕСЂРјР°С†РёСЋ Рѕ РІС‹Р±РѕСЂРµ
    emit('selection-change', {
        date: selectedDate.value.format('YYYY-MM-DD'),
        time: timeSlot.time,
        datetime: selectedDate.value.format('YYYY-MM-DD') + ' ' + timeSlot.time,
        service: props.selectedService
    })
}

const loadTimeSlots = async (date) => {
    loadingTimeSlots.value = true
    error.value = null
  
    try {
    // РРјРёС‚Р°С†РёСЏ API РІС‹Р·РѕРІР° - Р·Р°РјРµРЅРёС‚Рµ РЅР° СЂРµР°Р»СЊРЅС‹Р№ API
        await new Promise(resolve => setTimeout(resolve, 500))
    
        // Р“РµРЅРµСЂРёСЂСѓРµРј РІСЂРµРјРµРЅРЅС‹Рµ СЃР»РѕС‚С‹ (9:00 - 21:00 СЃ РёРЅС‚РµСЂРІР°Р»РѕРј РІ С‡Р°СЃ)
        const slots = []
        const startHour = 9
        const endHour = 21
    
        for (let hour = startHour; hour < endHour; hour++) {
            const timeString = `${hour.toString().padStart(2, '0')}:00`
      
            // РЎР»СѓС‡Р°Р№РЅРѕ РґРµР»Р°РµРј РЅРµРєРѕС‚РѕСЂС‹Рµ СЃР»РѕС‚С‹ РЅРµРґРѕСЃС‚СѓРїРЅС‹РјРё РґР»СЏ РґРµРјРѕРЅСЃС‚СЂР°С†РёРё
            const available = Math.random() > 0.3
      
            slots.push({
                time: timeString,
                available: available,
                duration: props.selectedService?.duration || 60,
                price: props.selectedService?.price || 0
            })
        }
    
        availableTimeSlots.value = slots
    
    } catch (err) {
        error.value = 'РћС€РёР±РєР° Р·Р°РіСЂСѓР·РєРё РґРѕСЃС‚СѓРїРЅРѕРіРѕ РІСЂРµРјРµРЅРё. РџРѕРїСЂРѕР±СѓР№С‚Рµ РµС‰Рµ СЂР°Р·.'
    } finally {
        loadingTimeSlots.value = false
    }
}

// РќР°Р±Р»СЋРґР°С‚РµР»Рё
watch(() => props.masterId, () => {
    // РЎР±СЂРѕСЃ РїСЂРё СЃРјРµРЅРµ РјР°СЃС‚РµСЂР°
    selectedDate.value = null
    selectedTime.value = null
    availableTimeSlots.value = []
})

watch(() => props.selectedService, () => {
    // РџРµСЂРµР·Р°РіСЂСѓР·РєР° СЃР»РѕС‚РѕРІ РїСЂРё СЃРјРµРЅРµ СѓСЃР»СѓРіРё
    if (selectedDate.value) {
        loadTimeSlots(selectedDate.value)
    }
})

// РРЅРёС†РёР°Р»РёР·Р°С†РёСЏ
onMounted(() => {
    // РњРѕР¶РЅРѕ Р°РІС‚РѕРјР°С‚РёС‡РµСЃРєРё РІС‹Р±СЂР°С‚СЊ Р±Р»РёР¶Р°Р№С€СѓСЋ РґРѕСЃС‚СѓРїРЅСѓСЋ РґР°С‚Сѓ
    const tomorrow = dayjs().add(1, 'day')
    if (tomorrow.isBetween(dayjs(props.minDate), dayjs(props.maxDate), 'day', '[]')) {
        selectDate({
            date: tomorrow,
            available: true,
            isPast: false
        })
    }
})
</script>

<style scoped>
.booking-calendar {
  @apply max-w-full;
}

/* РђРЅРёРјР°С†РёСЏ РґР»СЏ РІС‹Р±СЂР°РЅРЅС‹С… СЌР»РµРјРµРЅС‚РѕРІ */
.booking-calendar button {
  transition: all 0.2s ease-in-out;
}

.booking-calendar button:hover:not(:disabled) {
  transform: translateY(-1px);
}

/* РЎС‚РёР»РёР·Р°С†РёСЏ Р·Р°РіСЂСѓР·РєРё */
@keyframes spin {
  to { transform: rotate(360deg); }
}

.animate-spin {
  animation: spin 1s linear infinite;
}
</style>

