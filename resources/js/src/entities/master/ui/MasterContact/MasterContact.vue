<!-- resources/js/src/entities/master/ui/MasterContact/MasterContact.vue -->
<template>
  <div :class="CONTAINER_CLASSES">
    <h3 :class="TITLE_CLASSES">Контакты</h3>
    
    <div :class="CONTACT_LIST_CLASSES">
      <!-- Телефон -->
      <div v-if="master.phone" :class="CONTACT_ITEM_CLASSES">
        <div :class="CONTACT_INFO_CLASSES">
          <svg :class="CONTACT_ICON_CLASSES" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
          </svg>
          <div>
            <p :class="CONTACT_LABEL_CLASSES">Телефон</p>
            <p :class="CONTACT_VALUE_CLASSES">{{ formatPhone(master.phone) }}</p>
          </div>
        </div>
        <button
          @click="callPhone"
          :class="CALL_BUTTON_CLASSES"
        >
          Позвонить
        </button>
      </div>

      <!-- WhatsApp -->
      <div v-if="master.whatsapp" :class="CONTACT_ITEM_CLASSES">
        <div :class="CONTACT_INFO_CLASSES">
          <svg :class="CONTACT_ICON_CLASSES" fill="currentColor" viewBox="0 0 24 24">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.886 3.488"/>
          </svg>
          <div>
            <p :class="CONTACT_LABEL_CLASSES">WhatsApp</p>
            <p :class="CONTACT_VALUE_CLASSES">{{ formatPhone(master.whatsapp) }}</p>
          </div>
        </div>
        <button
          @click="openWhatsApp"
          :class="WHATSAPP_BUTTON_CLASSES"
        >
          Написать
        </button>
      </div>

      <!-- Telegram -->
      <div v-if="master.telegram" :class="CONTACT_ITEM_CLASSES">
        <div :class="CONTACT_INFO_CLASSES">
          <svg :class="CONTACT_ICON_CLASSES" fill="currentColor" viewBox="0 0 24 24">
            <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
          </svg>
          <div>
            <p :class="CONTACT_LABEL_CLASSES">Telegram</p>
            <p :class="CONTACT_VALUE_CLASSES">@{{ master.telegram }}</p>
          </div>
        </div>
        <button
          @click="openTelegram"
          :class="TELEGRAM_BUTTON_CLASSES"
        >
          Написать
        </button>
      </div>
    </div>

    <!-- Способы связи -->
    <div v-if="master.contact_method" :class="CONTACT_METHOD_CLASSES">
      <h4 :class="METHOD_TITLE_CLASSES">Предпочтительный способ связи</h4>
      <p :class="METHOD_TEXT_CLASSES">{{ getContactMethodLabel(master.contact_method) }}</p>
    </div>

    <!-- График работы -->
    <div v-if="hasSchedule" :class="SCHEDULE_CLASSES">
      <h4 :class="SCHEDULE_TITLE_CLASSES">График работы</h4>
      <div :class="SCHEDULE_LIST_CLASSES">
        <div
          v-for="(hours, day) in schedule"
          :key="day"
          :class="SCHEDULE_ITEM_CLASSES"
        >
          <span :class="SCHEDULE_DAY_CLASSES">{{ getDayLabel(day) }}</span>
          <span :class="SCHEDULE_HOURS_CLASSES">{{ formatScheduleHours(hours) }}</span>
        </div>
      </div>
      
      <div v-if="master.schedule_notes" :class="SCHEDULE_NOTES_CLASSES">
        <p :class="NOTES_TEXT_CLASSES">{{ master.schedule_notes }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

// 🎯 Стили согласно дизайн-системе
const CONTAINER_CLASSES = 'space-y-6'
const TITLE_CLASSES = 'text-lg font-semibold text-gray-900'
const CONTACT_LIST_CLASSES = 'space-y-4'
const CONTACT_ITEM_CLASSES = 'flex items-center justify-between p-4 border border-gray-200 rounded-lg'
const CONTACT_INFO_CLASSES = 'flex items-center gap-3'
const CONTACT_ICON_CLASSES = 'w-5 h-5 text-gray-400 flex-shrink-0'
const CONTACT_LABEL_CLASSES = 'text-sm text-gray-600'
const CONTACT_VALUE_CLASSES = 'font-medium text-gray-900'
const CALL_BUTTON_CLASSES = 'px-4 py-2 bg-green-500 hover:bg-green-600 text-white text-sm font-medium rounded-lg transition-colors'
const WHATSAPP_BUTTON_CLASSES = 'px-4 py-2 bg-green-500 hover:bg-green-600 text-white text-sm font-medium rounded-lg transition-colors'
const TELEGRAM_BUTTON_CLASSES = 'px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium rounded-lg transition-colors'
const CONTACT_METHOD_CLASSES = 'p-4 bg-yellow-50 border border-yellow-200 rounded-lg'
const METHOD_TITLE_CLASSES = 'font-medium text-yellow-800 mb-1'
const METHOD_TEXT_CLASSES = 'text-sm text-yellow-700'
const SCHEDULE_CLASSES = 'space-y-3'
const SCHEDULE_TITLE_CLASSES = 'font-medium text-gray-900'
const SCHEDULE_LIST_CLASSES = 'space-y-2'
const SCHEDULE_ITEM_CLASSES = 'flex items-center justify-between py-2 border-b border-gray-100 last:border-b-0'
const SCHEDULE_DAY_CLASSES = 'text-sm text-gray-600'
const SCHEDULE_HOURS_CLASSES = 'text-sm font-medium text-gray-900'
const SCHEDULE_NOTES_CLASSES = 'mt-3 p-3 bg-gray-50 rounded-lg'
const NOTES_TEXT_CLASSES = 'text-sm text-gray-700'

const props = defineProps({
  master: {
    type: Object,
    required: true
  }
})

// Вычисляемые свойства
const schedule = computed(() => {
  if (!props.master.schedule) return {}
  
  if (typeof props.master.schedule === 'string') {
    try {
      return JSON.parse(props.master.schedule)
    } catch (e) {
      return {}
    }
  }
  
  return props.master.schedule
})

const hasSchedule = computed(() => {
  return Object.keys(schedule.value).length > 0
})

// Методы
const formatPhone = (phone) => {
  if (!phone) return ''
  
  // Убираем все нецифровые символы
  const cleaned = phone.replace(/\D/g, '')
  
  // Форматируем российский номер
  if (cleaned.length === 11 && cleaned.startsWith('7')) {
    return `+7 (${cleaned.slice(1, 4)}) ${cleaned.slice(4, 7)}-${cleaned.slice(7, 9)}-${cleaned.slice(9, 11)}`
  }
  
  return phone
}

const getContactMethodLabel = (method) => {
  const methods = {
    calls: 'Предпочитаю звонки',
    messages: 'Предпочитаю сообщения',
    both: 'Звонки и сообщения'
  }
  
  return methods[method] || method
}

const getDayLabel = (day) => {
  const days = {
    monday: 'Понедельник',
    tuesday: 'Вторник',
    wednesday: 'Среда',
    thursday: 'Четверг',
    friday: 'Пятница',
    saturday: 'Суббота',
    sunday: 'Воскресенье'
  }
  
  return days[day] || day
}

const formatScheduleHours = (hours) => {
  if (!hours) return 'Выходной'
  
  if (typeof hours === 'object' && hours.from && hours.to) {
    return `${hours.from} - ${hours.to}`
  }
  
  if (typeof hours === 'string') {
    return hours
  }
  
  return 'Круглосуточно'
}

const callPhone = () => {
  if (props.master.phone) {
    const cleanPhone = props.master.phone.replace(/\D/g, '')
    window.location.href = `tel:+${cleanPhone}`
  }
}

const openWhatsApp = () => {
  if (props.master.whatsapp) {
    const cleanPhone = props.master.whatsapp.replace(/\D/g, '')
    const message = `Здравствуйте! Интересует массаж.`
    window.open(`https://wa.me/${cleanPhone}?text=${encodeURIComponent(message)}`, '_blank')
  }
}

const openTelegram = () => {
  if (props.master.telegram) {
    const username = props.master.telegram.replace('@', '')
    window.open(`https://t.me/${username}`, '_blank')
  }
}
</script>