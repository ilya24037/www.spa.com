<template>
  <div class="booking-widget">
    <!-- Р—Р°РіРѕР»РѕРІРѕРє РІРёРґР¶РµС‚Р° -->
    <div class="mb-6">
      <h2 class="text-xl font-bold text-gray-500 mb-2">
        Р—Р°РїРёСЃР°С‚СЊСЃСЏ Рє РјР°СЃС‚РµСЂСѓ
      </h2>
      <p class="text-sm text-gray-500">
        Р’С‹Р±РµСЂРёС‚Рµ СѓРґРѕР±РЅРѕРµ РІСЂРµРјСЏ Рё РѕСЃС‚Р°РІСЊС‚Рµ Р·Р°СЏРІРєСѓ
      </p>
    </div>

    <!-- РРЅРґРёРєР°С‚РѕСЂ СЌС‚Р°РїРѕРІ -->
    <div class="mb-6">
      <div class="flex items-center">
        <!-- Р­С‚Р°Рї 1: Р’С‹Р±РѕСЂ РІСЂРµРјРµРЅРё -->
        <div class="flex items-center">
          <div
            :class="[
              'w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium',
              currentStep >= 1 ? 'bg-blue-600 text-white' : 'bg-gray-500 text-gray-500'
            ]"
          >
            1
          </div>
          <span
            :class="[
              'ml-2 text-sm font-medium',
              currentStep >= 1 ? 'text-blue-600' : 'text-gray-500'
            ]"
          >
            Р’СЂРµРјСЏ
          </span>
        </div>

        <!-- Р Р°Р·РґРµР»РёС‚РµР»СЊ -->
        <div
          :class="[
            'flex-1 h-0.5 mx-4',
            currentStep >= 2 ? 'bg-blue-600' : 'bg-gray-500'
          ]"
        />

        <!-- Р­С‚Р°Рї 2: Р—Р°РїРѕР»РЅРµРЅРёРµ С„РѕСЂРјС‹ -->
        <div class="flex items-center">
          <div
            :class="[
              'w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium',
              currentStep >= 2 ? 'bg-blue-600 text-white' : 'bg-gray-500 text-gray-500'
            ]"
          >
            2
          </div>
          <span
            :class="[
              'ml-2 text-sm font-medium',
              currentStep >= 2 ? 'text-blue-600' : 'text-gray-500'
            ]"
          >
            Р”Р°РЅРЅС‹Рµ
          </span>
        </div>

        <!-- Р Р°Р·РґРµР»РёС‚РµР»СЊ -->
        <div
          :class="[
            'flex-1 h-0.5 mx-4',
            currentStep >= 3 ? 'bg-blue-600' : 'bg-gray-500'
          ]"
        />

        <!-- Р­С‚Р°Рї 3: РџРѕРґС‚РІРµСЂР¶РґРµРЅРёРµ -->
        <div class="flex items-center">
          <div
            :class="[
              'w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium',
              currentStep >= 3 ? 'bg-green-600 text-white' : 'bg-gray-500 text-gray-500'
            ]"
          >
            вњ“
          </div>
          <span
            :class="[
              'ml-2 text-sm font-medium',
              currentStep >= 3 ? 'text-green-600' : 'text-gray-500'
            ]"
          >
            Р“РѕС‚РѕРІРѕ
          </span>
        </div>
      </div>
    </div>

    <!-- РЎРѕРґРµСЂР¶РёРјРѕРµ СЌС‚Р°РїРѕРІ -->
    <div class="min-h-[400px]">
      <!-- Р­С‚Р°Рї 1: Р’С‹Р±РѕСЂ РІСЂРµРјРµРЅРё -->
      <div v-if="currentStep === 1" class="animate-fade-in">
        <BookingCalendar
          :master-id="master.id"
          :selected-service="selectedService"
          @selection-change="handleTimeSelection"
        />
        
        <div v-if="bookingData.date && bookingData.time" class="mt-6">
          <button
            class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 transition-colors font-medium"
            @click="nextStep"
          >
            РџСЂРѕРґРѕР»Р¶РёС‚СЊ Рє Р·Р°РїРѕР»РЅРµРЅРёСЋ РґР°РЅРЅС‹С…
          </button>
        </div>
      </div>

      <!-- Р­С‚Р°Рї 2: Р—Р°РїРѕР»РЅРµРЅРёРµ С„РѕСЂРјС‹ -->
      <div v-else-if="currentStep === 2" class="animate-fade-in">
        <BookingForm
          :booking-info="bookingData"
          :loading="submitting"
          @submit="handleFormSubmit"
          @cancel="prevStep"
        />
      </div>

      <!-- Р­С‚Р°Рї 3: РЈСЃРїРµС€РЅРѕРµ СЃРѕР·РґР°РЅРёРµ -->
      <div v-else-if="currentStep === 3" class="animate-fade-in">
        <div class="text-center py-8">
          <!-- РРєРѕРЅРєР° СѓСЃРїРµС…Р° -->
          <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4">
            <svg
              class="h-8 w-8 text-green-600"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M5 13l4 4L19 7"
              />
            </svg>
          </div>

          <!-- РЎРѕРѕР±С‰РµРЅРёРµ РѕР± СѓСЃРїРµС…Рµ -->
          <h3 class="text-lg font-semibold text-gray-500 mb-2">
            Р—Р°РїРёСЃСЊ СѓСЃРїРµС€РЅРѕ СЃРѕР·РґР°РЅР°!
          </h3>
          <p class="text-sm text-gray-500 mb-6">
            РњР°СЃС‚РµСЂ РїРѕР»СѓС‡РёР» СѓРІРµРґРѕРјР»РµРЅРёРµ Рѕ РІР°С€РµР№ Р·Р°РїРёСЃРё Рё СЃРІСЏР¶РµС‚СЃСЏ СЃ РІР°РјРё РґР»СЏ РїРѕРґС‚РІРµСЂР¶РґРµРЅРёСЏ
          </p>

          <!-- Р”РµС‚Р°Р»Рё Р·Р°РїРёСЃРё -->
          <div class="bg-green-50 rounded-lg p-4 mb-6 text-left">
            <h4 class="font-medium text-gray-500 mb-3">
              Р”РµС‚Р°Р»Рё Р·Р°РїРёСЃРё:
            </h4>
            <div class="space-y-2 text-sm text-gray-500">
              <div class="flex justify-between">
                <span>РќРѕРјРµСЂ Р·Р°РїРёСЃРё:</span>
                <span class="font-medium">{{ createdBooking.bookingNumber }}</span>
              </div>
              <div class="flex justify-between">
                <span>Р”Р°С‚Р° Рё РІСЂРµРјСЏ:</span>
                <span class="font-medium">{{ formatDateTime(bookingData.datetime) }}</span>
              </div>
              <div v-if="selectedService" class="flex justify-between">
                <span>РЈСЃР»СѓРіР°:</span>
                <span class="font-medium">{{ selectedService.name }}</span>
              </div>
              <div class="flex justify-between">
                <span>РњР°СЃС‚РµСЂ:</span>
                <span class="font-medium">{{ master.name }}</span>
              </div>
            </div>
          </div>

          <!-- Р”РµР№СЃС‚РІРёСЏ -->
          <div class="space-y-3">
            <button
              class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 transition-colors font-medium"
              @click="reset"
            >
              Р—Р°РїРёСЃР°С‚СЊСЃСЏ РµС‰Рµ СЂР°Р·
            </button>
            <button
              class="w-full text-gray-500 py-2 px-4 rounded-lg hover:bg-gray-500 transition-colors"
              @click="$emit('close')"
            >
              Р—Р°РєСЂС‹С‚СЊ
            </button>
          </div>
        </div>
      </div>

      <!-- РћС€РёР±РєР° СЃРѕР·РґР°РЅРёСЏ Р·Р°РїРёСЃРё -->
      <div v-else-if="currentStep === 'error'" class="animate-fade-in">
        <div class="text-center py-8">
          <!-- РРєРѕРЅРєР° РѕС€РёР±РєРё -->
          <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-4">
            <svg
              class="h-8 w-8 text-red-600"
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

          <!-- РЎРѕРѕР±С‰РµРЅРёРµ РѕР± РѕС€РёР±РєРµ -->
          <h3 class="text-lg font-semibold text-gray-500 mb-2">
            РћС€РёР±РєР° РїСЂРё СЃРѕР·РґР°РЅРёРё Р·Р°РїРёСЃРё
          </h3>
          <p class="text-sm text-gray-500 mb-6">
            {{ errorMessage || 'РџСЂРѕРёР·РѕС€Р»Р° РѕС€РёР±РєР°. РџРѕРїСЂРѕР±СѓР№С‚Рµ РµС‰Рµ СЂР°Р·.' }}
          </p>

          <!-- Р”РµР№СЃС‚РІРёСЏ -->
          <div class="space-y-3">
            <button
              class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 transition-colors font-medium"
              @click="currentStep = 2"
            >
              РџРѕРїСЂРѕР±РѕРІР°С‚СЊ РµС‰Рµ СЂР°Р·
            </button>
            <button
              class="w-full text-gray-500 py-2 px-4 rounded-lg hover:bg-gray-500 transition-colors"
              @click="reset"
            >
              РќР°С‡Р°С‚СЊ СЃРЅР°С‡Р°Р»Р°
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- РќР°РІРёРіР°С†РёСЏ (С‚РѕР»СЊРєРѕ РґР»СЏ РїРµСЂРІС‹С… РґРІСѓС… СЌС‚Р°РїРѕРІ) -->
    <div v-if="currentStep <= 2 && currentStep !== 'error'" class="mt-6 flex justify-between">
      <button
        v-if="currentStep > 1"
        class="flex items-center text-gray-500 hover:text-gray-500 transition-colors"
        @click="prevStep"
      >
        <svg
          class="w-4 h-4 mr-1"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M15 19l-7-7 7-7"
          />
        </svg>
        РќР°Р·Р°Рґ
      </button>
      <div v-else />

      <div class="text-sm text-gray-500">
        Р­С‚Р°Рї {{ currentStep }} РёР· 2
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import dayjs from 'dayjs'
import 'dayjs/locale/ru'
import BookingCalendar from '@/src/entities/booking/ui/BookingCalendar/BookingCalendar.vue'
import BookingForm from '@/src/features/booking-form/ui/BookingForm/BookingForm.vue'

// РќР°СЃС‚СЂРѕР№РєР° dayjs
dayjs.locale('ru')

// Props
const props = defineProps({
    master: {
        type: Object,
        required: true
    },
    selectedService: {
        type: Object,
        default: null
    },
    isOpen: {
        type: Boolean,
        default: true
    }
})

// Events
const emit = defineEmits(['booking-created', 'close'])

// РЎРѕСЃС‚РѕСЏРЅРёРµ РєРѕРјРїРѕРЅРµРЅС‚Р°
const currentStep = ref(1)
const bookingData = ref({
    date: null,
    time: null,
    datetime: null,
    service: null
})
const createdBooking = ref(null)
const submitting = ref(false)
const errorMessage = ref(null)

// РњРµС‚РѕРґС‹ РЅР°РІРёРіР°С†РёРё
const nextStep = () => {
    if (currentStep.value < 3) {
        currentStep.value++
    }
}

const prevStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--
    }
}

const reset = () => {
    currentStep.value = 1
    bookingData.value = {
        date: null,
        time: null,
        datetime: null,
        service: null
    }
    createdBooking.value = null
    submitting.value = false
    errorMessage.value = null
}

// РћР±СЂР°Р±РѕС‚С‡РёРєРё СЃРѕР±С‹С‚РёР№
const handleTimeSelection = (selection) => {
    bookingData.value = {
        ...selection,
        service: props.selectedService
    }
}

const handleFormSubmit = async (formData) => {
    submitting.value = true
    errorMessage.value = null

    try {
    // РРјРёС‚Р°С†РёСЏ API РІС‹Р·РѕРІР° - Р·Р°РјРµРЅРёС‚Рµ РЅР° СЂРµР°Р»СЊРЅС‹Р№ API
        await new Promise((resolve, reject) => {
            setTimeout(() => {
                // РЎР»СѓС‡Р°Р№РЅРѕ РёРјРёС‚РёСЂСѓРµРј РѕС€РёР±РєСѓ РґР»СЏ РґРµРјРѕРЅСЃС‚СЂР°С†РёРё
                if (Math.random() > 0.8) {
                    reject(new Error('Р’СЂРµРјСЏ СѓР¶Рµ Р·Р°РЅСЏС‚Рѕ. Р’С‹Р±РµСЂРёС‚Рµ РґСЂСѓРіРѕРµ РІСЂРµРјСЏ.'))
                } else {
                    resolve()
                }
            }, 2000)
        })

        // РЈСЃРїРµС€РЅРѕРµ СЃРѕР·РґР°РЅРёРµ Р·Р°РїРёСЃРё
        createdBooking.value = {
            id: Date.now(),
            bookingNumber: `BK-${Date.now().toString().slice(-6)}`,
            ...formData
        }

        currentStep.value = 3
        emit('booking-created', createdBooking.value)

    } catch (error) {
        errorMessage.value = error.message
        currentStep.value = 'error'
    } finally {
        submitting.value = false
    }
}

// Р’СЃРїРѕРјРѕРіР°С‚РµР»СЊРЅС‹Рµ РјРµС‚РѕРґС‹
const formatDateTime = (datetime) => {
    return dayjs(datetime).format('DD MMMM YYYY РІ HH:mm')
}

// РќР°Р±Р»СЋРґР°С‚РµР»Рё
watch(() => props.isOpen, (isOpen) => {
    if (!isOpen) {
    // РЎР±СЂРѕСЃ СЃРѕСЃС‚РѕСЏРЅРёСЏ РїСЂРё Р·Р°РєСЂС‹С‚РёРё
        setTimeout(() => {
            reset()
        }, 300) // Р—Р°РґРµСЂР¶РєР° РґР»СЏ РїР»Р°РІРЅРѕРіРѕ Р·Р°РєСЂС‹С‚РёСЏ
    }
})

watch(() => props.selectedService, (newService) => {
    // РћР±РЅРѕРІР»СЏРµРј СѓСЃР»СѓРіСѓ РІ РґР°РЅРЅС‹С… Р±СЂРѕРЅРёСЂРѕРІР°РЅРёСЏ
    if (bookingData.value.service !== newService) {
        bookingData.value.service = newService
    }
})
</script>

<style scoped>
.booking-widget {
  @apply max-w-full;
}

/* РђРЅРёРјР°С†РёСЏ РїРµСЂРµС…РѕРґРѕРІ РјРµР¶РґСѓ СЌС‚Р°РїР°РјРё */
.animate-fade-in {
  animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* РџР»Р°РІРЅС‹Рµ РїРµСЂРµС…РѕРґС‹ РґР»СЏ РёРЅРґРёРєР°С‚РѕСЂР° СЌС‚Р°РїРѕРІ */
.booking-widget div[class*="bg-blue-600"],
.booking-widget div[class*="bg-green-600"] {
  transition: all 0.3s ease-in-out;
}

/* РЎС‚РёР»РёР·Р°С†РёСЏ РєРЅРѕРїРѕРє */
.booking-widget button {
  transition: all 0.2s ease-in-out;
}

.booking-widget button:hover:not(:disabled) {
  transform: translateY(-1px);
}

.booking-widget button:active:not(:disabled) {
  transform: translateY(0);
}
</style>

