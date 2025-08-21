<template>
  <div class="booking-status">
    <!-- РљР°СЂС‚РѕС‡РєР° Р±СЂРѕРЅРёСЂРѕРІР°РЅРёСЏ -->
    <div
      :class="[
        'bg-white rounded-lg border-2 p-4 transition-all',
        getStatusBorderColor(booking.status),
        'hover:shadow-md'
      ]"
    >
      <!-- Р—Р°РіРѕР»РѕРІРѕРє СЃ РЅРѕРјРµСЂРѕРј Рё СЃС‚Р°С‚СѓСЃРѕРј -->
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-3">
          <div class="font-medium text-gray-500">
            Р—Р°РїРёСЃСЊ {{ booking.bookingNumber }}
          </div>
          <span
            :class="[
              'px-2 py-1 rounded-full text-xs font-medium',
              getStatusColor(booking.status)
            ]"
          >
            {{ getStatusText(booking.status) }}
          </span>
        </div>
        
        <!-- РњРµРЅСЋ РґРµР№СЃС‚РІРёР№ -->
        <div class="relative">
          <button
            class="p-1 rounded-full hover:bg-gray-500 transition-colors"
            @click="showActions = !showActions"
          >
            <svg
              class="w-5 h-5 text-gray-500"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 5v.01M12 12v.01M12 19v.01"
              />
            </svg>
          </button>
          
          <!-- Р’С‹РїР°РґР°СЋС‰РµРµ РјРµРЅСЋ -->
          <div v-if="showActions" class="absolute right-0 top-8 bg-white rounded-lg shadow-lg border py-1 z-10 min-w-[150px]">
            <button
              v-if="canCancel"
              class="w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50 transition-colors"
              @click="handleCancel"
            >
              РћС‚РјРµРЅРёС‚СЊ Р·Р°РїРёСЃСЊ
            </button>
            <button
              v-if="canReschedule"
              class="w-full px-4 py-2 text-left text-sm text-blue-600 hover:bg-blue-50 transition-colors"
              @click="handleReschedule"
            >
              РџРµСЂРµРЅРµСЃС‚Рё РІСЂРµРјСЏ
            </button>
            <button
              v-if="canComplete"
              class="w-full px-4 py-2 text-left text-sm text-green-600 hover:bg-green-50 transition-colors"
              @click="handleComplete"
            >
              Р—Р°РІРµСЂС€РёС‚СЊ
            </button>
            <button
              class="w-full px-4 py-2 text-left text-sm text-gray-500 hover:bg-gray-500 transition-colors"
              @click="showDetails = !showDetails"
            >
              {{ showDetails ? 'Скрыть детали' : 'Показать детали' }}
            </button>
          </div>
        </div>
      </div>

      <!-- РћСЃРЅРѕРІРЅР°СЏ РёРЅС„РѕСЂРјР°С†РёСЏ -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <div class="space-y-2">
          <div class="flex items-center text-sm text-gray-500">
            <svg
              class="w-4 h-4 mr-2"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
              />
            </svg>
            <span class="font-medium">{{ formatDateTime(booking.startTime) }}</span>
          </div>
          
          <div v-if="booking.serviceName" class="flex items-center text-sm text-gray-500">
            <svg
              class="w-4 h-4 mr-2"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
              />
            </svg>
            <span>{{ booking.serviceName }}</span>
          </div>
          
          <div v-if="booking.duration" class="flex items-center text-sm text-gray-500">
            <svg
              class="w-4 h-4 mr-2"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
              />
            </svg>
            <span>{{ booking.duration }} РјРёРЅСѓС‚</span>
          </div>
        </div>
        
        <div class="space-y-2">
          <div class="flex items-center text-sm text-gray-500">
            <svg
              class="w-4 h-4 mr-2"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
              />
            </svg>
            <span>{{ booking.masterName }}</span>
          </div>
          
          <div v-if="booking.price" class="flex items-center text-sm">
            <svg
              class="w-4 h-4 mr-2 text-green-600"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"
              />
            </svg>
            <span class="font-semibold text-green-600">{{ formatPrice(booking.price) }} в‚Ѕ</span>
          </div>
          
          <div class="flex items-center text-xs text-gray-500">
            <svg
              class="w-3 h-3 mr-1"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
              />
            </svg>
            <span>РЎРѕР·РґР°РЅРѕ {{ formatCreatedAt(booking.createdAt) }}</span>
          </div>
        </div>
      </div>

      <!-- РРЅРґРёРєР°С‚РѕСЂ РІСЂРµРјРµРЅРё РґРѕ Р·Р°РїРёСЃРё -->
      <div v-if="timeUntilBooking" class="mb-4">
        <div
          :class="[
            'text-center py-2 px-4 rounded-lg text-sm font-medium',
            getTimeIndicatorColor(timeUntilBooking)
          ]"
        >
          {{ getTimeUntilText(timeUntilBooking) }}
        </div>
      </div>

      <!-- Р”РµС‚Р°Р»СЊРЅР°СЏ РёРЅС„РѕСЂРјР°С†РёСЏ (СЂР°СЃРєСЂС‹РІР°СЋС‰Р°СЏСЃСЏ) -->
      <div v-if="showDetails" class="border-t pt-4 mt-4 space-y-3">
        <!-- РљРѕРЅС‚Р°РєС‚РЅР°СЏ РёРЅС„РѕСЂРјР°С†РёСЏ РєР»РёРµРЅС‚Р° -->
        <div v-if="booking.clientName || booking.clientPhone">
          <h4 class="text-sm font-medium text-gray-500 mb-2">
            РљРѕРЅС‚Р°РєС‚С‹ РєР»РёРµРЅС‚Р°:
          </h4>
          <div class="space-y-1 text-sm text-gray-500">
            <div v-if="booking.clientName" class="flex items-center">
              <svg
                class="w-4 h-4 mr-2"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                />
              </svg>
              <span>{{ booking.clientName }}</span>
            </div>
            <div v-if="booking.clientPhone" class="flex items-center">
              <svg
                class="w-4 h-4 mr-2"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"
                />
              </svg>
              <a :href="`tel:${booking.clientPhone}`" class="text-blue-600 hover:text-blue-800">
                {{ booking.clientPhone }}
              </a>
            </div>
          </div>
        </div>

        <!-- РљРѕРјРјРµРЅС‚Р°СЂРёРё -->
        <div v-if="booking.notes">
          <h4 class="text-sm font-medium text-gray-500 mb-2">
            РљРѕРјРјРµРЅС‚Р°СЂРёР№:
          </h4>
          <p class="text-sm text-gray-500 bg-gray-500 p-3 rounded-lg">
            {{ booking.notes }}
          </p>
        </div>

        <!-- РСЃС‚РѕСЂРёСЏ РёР·РјРµРЅРµРЅРёР№ -->
        <div v-if="booking.statusHistory && booking.statusHistory.length">
          <h4 class="text-sm font-medium text-gray-500 mb-2">
            РСЃС‚РѕСЂРёСЏ:
          </h4>
          <div class="space-y-1">
            <div 
              v-for="history in booking.statusHistory" 
              :key="history.id"
              class="text-xs text-gray-500 flex justify-between"
            >
              <span>{{ getStatusText(history.status) }}</span>
              <span>{{ formatDateTime(history.createdAt) }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- РљРЅРѕРїРєРё Р±С‹СЃС‚СЂС‹С… РґРµР№СЃС‚РІРёР№ -->
      <div v-if="showQuickActions" class="flex gap-2 mt-4">
        <button
          v-if="canCancel"
          class="flex-1 bg-red-50 text-red-600 py-2 px-3 rounded-lg text-sm font-medium hover:bg-red-100 transition-colors"
          @click="handleCancel"
        >
          РћС‚РјРµРЅРёС‚СЊ
        </button>
        <button
          v-if="canReschedule"
          class="flex-1 bg-blue-50 text-blue-600 py-2 px-3 rounded-lg text-sm font-medium hover:bg-blue-100 transition-colors"
          @click="handleReschedule"
        >
          РџРµСЂРµРЅРµСЃС‚Рё
        </button>
        <button
          v-if="canComplete"
          class="flex-1 bg-green-50 text-green-600 py-2 px-3 rounded-lg text-sm font-medium hover:bg-green-100 transition-colors"
          @click="handleComplete"
        >
          Р—Р°РІРµСЂС€РёС‚СЊ
        </button>
      </div>
    </div>

    <!-- РњРѕРґР°Р»СЊРЅРѕРµ РѕРєРЅРѕ РїРѕРґС‚РІРµСЂР¶РґРµРЅРёСЏ РѕС‚РјРµРЅС‹ -->
    <div v-if="showCancelModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
        <h3 class="text-lg font-semibold text-gray-500 mb-4">
          РћС‚РјРµРЅРёС‚СЊ Р·Р°РїРёСЃСЊ?
        </h3>
        <p class="text-gray-500 mb-4">
          Р’С‹ СѓРІРµСЂРµРЅС‹, С‡С‚Рѕ С…РѕС‚РёС‚Рµ РѕС‚РјРµРЅРёС‚СЊ Р·Р°РїРёСЃСЊ РЅР° {{ formatDateTime(booking.startTime) }}?
        </p>
        
        <div class="mb-4">
          <label for="cancelReason" class="block text-sm font-medium text-gray-500 mb-2">
            РџСЂРёС‡РёРЅР° РѕС‚РјРµРЅС‹ (РЅРµРѕР±СЏР·Р°С‚РµР»СЊРЅРѕ):
          </label>
          <textarea
            id="cancelReason"
            v-model="cancelReason"
            rows="3"
            class="w-full px-3 py-2 border border-gray-500 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500"
            placeholder="РЈРєР°Р¶РёС‚Рµ РїСЂРёС‡РёРЅСѓ РѕС‚РјРµРЅС‹..."
          />
        </div>
        
        <div class="flex gap-3">
          <button
            class="flex-1 bg-gray-500 text-gray-500 py-2 px-4 rounded-lg font-medium hover:bg-gray-500 transition-colors"
            @click="showCancelModal = false"
          >
            РќРµ РѕС‚РјРµРЅСЏС‚СЊ
          </button>
          <button
            :disabled="cancelling"
            class="flex-1 bg-red-600 text-white py-2 px-4 rounded-lg font-medium hover:bg-red-700 transition-colors disabled:opacity-50"
            @click="confirmCancel"
          >
            {{ cancelling ? 'РћС‚РјРµРЅР°...' : 'РћС‚РјРµРЅРёС‚СЊ Р·Р°РїРёСЃСЊ' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import dayjs from 'dayjs'
import relativeTime from 'dayjs/plugin/relativeTime'
import 'dayjs/locale/ru'

// РќР°СЃС‚СЂРѕР№РєР° dayjs
dayjs.extend(relativeTime)
dayjs.locale('ru')

// Props
const props = defineProps({
    booking: {
        type: Object,
        required: true
    },
    showQuickActions: {
        type: Boolean,
        default: false
    },
    userRole: {
        type: String,
        default: 'client' // 'client' РёР»Рё 'master'
    }
})

// Events
const emit = defineEmits(['cancel', 'reschedule', 'complete', 'update'])

// РЎРѕСЃС‚РѕСЏРЅРёРµ РєРѕРјРїРѕРЅРµРЅС‚Р°
const showActions = ref(false)
const showDetails = ref(false)
const showCancelModal = ref(false)
const cancelReason = ref('')
const cancelling = ref(false)

// Р’С‹С‡РёСЃР»СЏРµРјС‹Рµ СЃРІРѕР№СЃС‚РІР°
const canCancel = computed(() => {
    return ['pending', 'confirmed'].includes(props.booking.status) &&
         dayjs(props.booking.startTime).isAfter(dayjs())
})

const canReschedule = computed(() => {
    return ['pending', 'confirmed'].includes(props.booking.status) &&
         dayjs(props.booking.startTime).isAfter(dayjs().add(2, 'hours'))
})

const canComplete = computed(() => {
    return props.userRole === 'master' && 
         ['confirmed', 'in_progress'].includes(props.booking.status)
})

const timeUntilBooking = computed(() => {
    const startTime = dayjs(props.booking.startTime)
    const now = dayjs()
  
    if (startTime.isBefore(now)) return null
  
    const diff = startTime.diff(now, 'hours')
  
    if (diff < 1) return 'soon'
    if (diff < 24) return 'today'
    if (diff < 168) return 'week'
  
    return 'future'
})

// РњРµС‚РѕРґС‹ С„РѕСЂРјР°С‚РёСЂРѕРІР°РЅРёСЏ
const formatDateTime = (datetime) => {
    return dayjs(datetime).format('DD MMMM YYYY РІ HH:mm')
}

const formatCreatedAt = (datetime) => {
    return dayjs(datetime).fromNow()
}

const formatPrice = (price) => {
    return new Intl.NumberFormat('ru-RU').format(price)
}

// РњРµС‚РѕРґС‹ РїРѕР»СѓС‡РµРЅРёСЏ СЃС‚РёР»РµР№
const getStatusColor = (status) => {
    const colors = {
        pending: 'bg-yellow-100 text-yellow-800',
        confirmed: 'bg-blue-100 text-blue-800',
        in_progress: 'bg-green-100 text-green-800',
        completed: 'bg-gray-500 text-gray-500',
        cancelled: 'bg-red-100 text-red-800',
        rescheduled: 'bg-purple-100 text-purple-800'
    }
    return colors[status] || 'bg-gray-500 text-gray-500'
}

const getStatusBorderColor = (status) => {
    const colors = {
        pending: 'border-yellow-200',
        confirmed: 'border-blue-200',
        in_progress: 'border-green-200',
        completed: 'border-gray-500',
        cancelled: 'border-red-200',
        rescheduled: 'border-purple-200'
    }
    return colors[status] || 'border-gray-500'
}

const getStatusText = (status) => {
    const texts = {
        pending: 'РћР¶РёРґР°РµС‚ РїРѕРґС‚РІРµСЂР¶РґРµРЅРёСЏ',
        confirmed: 'РџРѕРґС‚РІРµСЂР¶РґРµРЅРѕ',
        in_progress: 'Р’ РїСЂРѕС†РµСЃСЃРµ',
        completed: 'Р—Р°РІРµСЂС€РµРЅРѕ',
        cancelled: 'РћС‚РјРµРЅРµРЅРѕ',
        rescheduled: 'РџРµСЂРµРЅРµСЃРµРЅРѕ'
    }
    return texts[status] || 'РќРµРёР·РІРµСЃС‚РЅС‹Р№ СЃС‚Р°С‚СѓСЃ'
}

const getTimeIndicatorColor = (timeType) => {
    const colors = {
        soon: 'bg-red-100 text-red-800',
        today: 'bg-orange-100 text-orange-800',
        week: 'bg-blue-100 text-blue-800',
        future: 'bg-gray-500 text-gray-500'
    }
    return colors[timeType] || 'bg-gray-500 text-gray-500'
}

const getTimeUntilText = (timeType) => {
    const texts = {
        soon: 'Р—Р°РїРёСЃСЊ С‡РµСЂРµР· С‡Р°СЃ РёР»Рё РјРµРЅРµРµ',
        today: 'Р—Р°РїРёСЃСЊ СЃРµРіРѕРґРЅСЏ',
        week: 'Р—Р°РїРёСЃСЊ РЅР° СЌС‚РѕР№ РЅРµРґРµР»Рµ',
        future: 'Р—Р°РїРёСЃСЊ РІ Р±СѓРґСѓС‰РµРј'
    }
    return texts[timeType] || ''
}

// РћР±СЂР°Р±РѕС‚С‡РёРєРё СЃРѕР±С‹С‚РёР№
const handleCancel = () => {
    showCancelModal.value = true
    showActions.value = false
}

const confirmCancel = async () => {
    cancelling.value = true
  
    try {
        await emit('cancel', {
            bookingId: props.booking.id,
            reason: cancelReason.value.trim() || null
        })
    
        showCancelModal.value = false
        cancelReason.value = ''
    } catch (error) {
    } finally {
        cancelling.value = false
    }
}

const handleReschedule = () => {
    emit('reschedule', props.booking.id)
    showActions.value = false
}

const handleComplete = () => {
    emit('complete', props.booking.id)
    showActions.value = false
}

// Р—Р°РєСЂС‹С‚РёРµ РјРµРЅСЋ РїСЂРё РєР»РёРєРµ РІРЅРµ РµРіРѕ
const handleClickOutside = (event) => {
    if (!event.target.closest('.relative')) {
        showActions.value = false
    }
}

// РџРѕРґРєР»СЋС‡РµРЅРёРµ РѕР±СЂР°Р±РѕС‚С‡РёРєР° РєР»РёРєР°
document.addEventListener('click', handleClickOutside)
</script>

<style scoped>
.booking-status {
  @apply w-full;
}

/* РђРЅРёРјР°С†РёРё */
.booking-status .relative > div {
  transition: all 0.2s ease-in-out;
}

/* РЎС‚РёР»РёР·Р°С†РёСЏ РґР»СЏ mobile */
@media (max-width: 768px) {
  .booking-status .grid-cols-2 {
    @apply grid-cols-1;
  }
}
</style>

