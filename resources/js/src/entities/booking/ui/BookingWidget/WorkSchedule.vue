<!-- resources/js/Components/Masters/BookingWidget/WorkSchedule.vue -->
<template>
  <div class="work-schedule">
    <!-- Р—Р°РіРѕР»РѕРІРѕРє -->
    <div class="flex items-center justify-between mb-4">
      <h3 class="font-semibold text-gray-900">Р“СЂР°С„РёРє СЂР°Р±РѕС‚С‹</h3>
      <button 
        @click="$emit('show-calendar')"
        class="text-indigo-600 text-sm hover:text-indigo-700"
      >
        РљР°Р»РµРЅРґР°СЂСЊ
      </button>
    </div>
    
    <!-- РћРїРёСЃР°РЅРёРµ РіСЂР°С„РёРєР°, РµСЃР»Рё РµСЃС‚СЊ -->
    <div v-if="scheduleDescription" class="mb-4 text-sm text-gray-600">
      {{ scheduleDescription }}
    </div>
    
    <!-- Р“СЂР°С„РёРє РїРѕ РґРЅСЏРј -->
    <div class="space-y-2">
      <div 
        v-for="day in weekSchedule"
        :key="day.name"
        class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0"
        :class="{ 'font-medium': day.isToday }"
      >
        <span class="text-sm" :class="day.isToday ? 'text-indigo-600' : 'text-gray-700'">
          {{ day.name }}
          <span v-if="day.isToday" class="text-xs text-indigo-600 ml-1">(СЃРµРіРѕРґРЅСЏ)</span>
        </span>
        <span class="text-sm" :class="day.isWorking ? 'text-gray-900' : 'text-gray-400'">
          {{ day.hours || 'Р’С‹С…РѕРґРЅРѕР№' }}
        </span>
      </div>
    </div>
    
    <!-- Р‘Р»РёР¶Р°Р№С€РёРµ СЃРІРѕР±РѕРґРЅС‹Рµ СЃР»РѕС‚С‹ -->
    <div v-if="nearestSlots.length > 0" class="mt-4 pt-4 border-t">
      <p class="text-sm font-medium text-gray-900 mb-2">Р‘Р»РёР¶Р°Р№С€РёРµ СЃР»РѕС‚С‹:</p>
      <div class="space-y-1">
        <button
          v-for="slot in nearestSlots"
          :key="slot.date + slot.time"
          @click="$emit('show-calendar')"
          class="w-full text-left px-3 py-2 text-sm bg-green-50 text-green-700 rounded-lg hover:bg-green-100 transition-colors"
        >
          <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          {{ slot.label }}
        </button>
      </div>
    </div>
    
    <!-- РРЅС„РѕСЂРјР°С†РёСЏ Рѕ РІС‹РµР·РґРµ -->
    <div v-if="master.provides_home_service" class="mt-4 pt-4 border-t">
      <div class="flex items-start gap-2">
        <svg class="w-5 h-5 text-indigo-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
        </svg>
        <div>
          <p class="text-sm font-medium text-gray-900">Р’С‹РµР·Рґ РЅР° РґРѕРј</p>
          <p class="text-xs text-gray-600 mt-0.5">
            {{ master.home_service_info || 'Р”РѕСЃС‚СѓРїРµРЅ РІС‹РµР·Рґ Рє РєР»РёРµРЅС‚Сѓ' }}
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  schedule: {
    type: Object,
    default: () => ({})
  },
  scheduleDescription: {
    type: String,
    default: ''
  },
  master: {
    type: Object,
    default: () => ({})
  },
  nearestAvailableSlots: {
    type: Array,
    default: () => []
  }
})

defineEmits(['show-calendar'])

// Р”РЅРё РЅРµРґРµР»Рё
const weekDays = [
  { key: 'monday', name: 'РџРѕРЅРµРґРµР»СЊРЅРёРє' },
  { key: 'tuesday', name: 'Р’С‚РѕСЂРЅРёРє' },
  { key: 'wednesday', name: 'РЎСЂРµРґР°' },
  { key: 'thursday', name: 'Р§РµС‚РІРµСЂРі' },
  { key: 'friday', name: 'РџСЏС‚РЅРёС†Р°' },
  { key: 'saturday', name: 'РЎСѓР±Р±РѕС‚Р°' },
  { key: 'sunday', name: 'Р’РѕСЃРєСЂРµСЃРµРЅСЊРµ' }
]

// Р¤РѕСЂРјР°С‚РёСЂРѕРІР°РЅРЅС‹Р№ РіСЂР°С„РёРє
const weekSchedule = computed(() => {
  const today = new Date().getDay()
  const todayIndex = today === 0 ? 6 : today - 1 // РљРѕРЅРІРµСЂС‚РёСЂСѓРµРј РІ РїРѕРЅРµРґРµР»СЊРЅРёРє = 0
  
  return weekDays.map((day, index) => {
    const daySchedule = props.schedule[day.key] || defaultSchedule[day.key]
    const isWorking = daySchedule && daySchedule.start && daySchedule.end
    
    return {
      name: day.name,
      isToday: index === todayIndex,
      isWorking,
      hours: isWorking ? `${daySchedule.start} - ${daySchedule.end}` : null
    }
  })
})

// Р Р°СЃРїРёСЃР°РЅРёРµ РїРѕ СѓРјРѕР»С‡Р°РЅРёСЋ
const defaultSchedule = {
  monday: { start: '10:00', end: '21:00' },
  tuesday: { start: '10:00', end: '21:00' },
  wednesday: { start: '10:00', end: '21:00' },
  thursday: { start: '10:00', end: '21:00' },
  friday: { start: '10:00', end: '21:00' },
  saturday: { start: '10:00', end: '20:00' },
  sunday: { start: '10:00', end: '20:00' }
}

// Р‘Р»РёР¶Р°Р№С€РёРµ СЃР»РѕС‚С‹
const nearestSlots = computed(() => {
  // Р•СЃР»Рё РµСЃС‚СЊ РґР°РЅРЅС‹Рµ Рѕ Р±Р»РёР¶Р°Р№С€РёС… СЃР»РѕС‚Р°С…
  if (props.nearestAvailableSlots.length) {
    return props.nearestAvailableSlots.slice(0, 3).map(slot => {
      const date = new Date(slot.date)
      const today = new Date()
      const tomorrow = new Date(today)
      tomorrow.setDate(tomorrow.getDate() + 1)
      
      let label = ''
      if (date.toDateString() === today.toDateString()) {
        label = `РЎРµРіРѕРґРЅСЏ ${slot.time}`
      } else if (date.toDateString() === tomorrow.toDateString()) {
        label = `Р—Р°РІС‚СЂР° ${slot.time}`
      } else {
        label = `${date.getDate()}.${(date.getMonth() + 1).toString().padStart(2, '0')} ${slot.time}`
      }
      
      return {
        ...slot,
        label
      }
    })
  }
  
  // РРЅР°С‡Рµ РіРµРЅРµСЂРёСЂСѓРµРј РїСЂРёРјРµСЂРЅС‹Рµ СЃР»РѕС‚С‹
  const slots = []
  const now = new Date()
  const currentHour = now.getHours()
  
    // РЎРµРіРѕРґРЅСЏ
    if (currentHour < 19) {
      slots.push({
        date: now.toISOString().split('T')[0],
        time: `${(currentHour + 2).toString().padStart(2, '0')}:00`,
        label: `РЎРµРіРѕРґРЅСЏ ${(currentHour + 2).toString().padStart(2, '0')}:00`
      })
    }
  
    // Р—Р°РІС‚СЂР°
    const tomorrow = new Date(now)
    tomorrow.setDate(now.getDate() + 1)
    slots.push({
      date: tomorrow.toISOString().split('T')[0],
      time: '12:00',
      label: `Р—Р°РІС‚СЂР° 12:00`
    })
  
    // РџРѕСЃР»РµР·Р°РІС‚СЂР°
    const dayAfterTomorrow = new Date(now)
    dayAfterTomorrow.setDate(now.getDate() + 2)
    slots.push({
      date: dayAfterTomorrow.toISOString().split('T')[0],
      time: '15:00',
      label: `${dayAfterTomorrow.getDate()}.${(dayAfterTomorrow.getMonth() + 1).toString().padStart(2, '0')} 15:00`
    })
  
    return slots
  }) 
  </script>
