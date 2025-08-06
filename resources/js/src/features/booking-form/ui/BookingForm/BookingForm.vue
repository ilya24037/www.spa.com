<template>
  <!-- Error СЃРѕСЃС‚РѕСЏРЅРёРµ -->
  <ErrorState
    v-if="errorState.error"
    :error="errorState.error"
    size="medium"
    variant="card"
    :retryable="true"
    :dismissible="true"
    @retry="handleRetry"
    @dismiss="errorState.clearError"
    class="mb-6"
  />
  
  <!-- РћСЃРЅРѕРІРЅР°СЏ С„РѕСЂРјР° -->
  <div v-else class="booking-form">
    <!-- Р—Р°РіРѕР»РѕРІРѕРє -->
    <div class="mb-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-2">
        Р”Р°РЅРЅС‹Рµ РґР»СЏ Р·Р°РїРёСЃРё
      </h3>
      <p class="text-sm text-gray-600">
        РЈРєР°Р¶РёС‚Рµ РІР°С€Рё РєРѕРЅС‚Р°РєС‚РЅС‹Рµ РґР°РЅРЅС‹Рµ РґР»СЏ РїРѕРґС‚РІРµСЂР¶РґРµРЅРёСЏ Р·Р°РїРёСЃРё
      </p>
    </div>

    <!-- Р¤РѕСЂРјР° -->
    <form @submit.prevent="handleSubmit" class="space-y-6">
      
      <!-- РРјСЏ РєР»РёРµРЅС‚Р° -->
      <div>
        <label for="clientName" class="block text-sm font-medium text-gray-700 mb-2">
          Р’Р°С€Рµ РёРјСЏ <span class="text-red-500">*</span>
        </label>
        <input
          id="clientName"
          v-model="form.clientName"
          type="text"
          placeholder="Р’РІРµРґРёС‚Рµ РІР°С€Рµ РёРјСЏ"
          :class="[
            'w-full px-3 py-2 border rounded-lg text-sm transition-colors',
            errors.clientName 
              ? 'border-red-300 focus:border-red-500 focus:ring-red-500' 
              : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500'
          ]"
          @blur="validateField('clientName')"
        />
        <p v-if="errors.clientName" class="mt-1 text-sm text-red-600">
          {{ errors.clientName }}
        </p>
      </div>

      <!-- РўРµР»РµС„РѕРЅ -->
      <div>
        <label for="clientPhone" class="block text-sm font-medium text-gray-700 mb-2">
          РќРѕРјРµСЂ С‚РµР»РµС„РѕРЅР° <span class="text-red-500">*</span>
        </label>
        <input
          id="clientPhone"
          v-model="form.clientPhone"
          type="tel"
          placeholder="+7 (999) 999-99-99"
          :class="[
            'w-full px-3 py-2 border rounded-lg text-sm transition-colors',
            errors.clientPhone 
              ? 'border-red-300 focus:border-red-500 focus:ring-red-500' 
              : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500'
          ]"
          @input="formatPhone"
          @blur="validateField('clientPhone')"
        />
        <p v-if="errors.clientPhone" class="mt-1 text-sm text-red-600">
          {{ errors.clientPhone }}
        </p>
        <p class="mt-1 text-xs text-gray-500">
          РќР° СЌС‚РѕС‚ РЅРѕРјРµСЂ РїСЂРёРґРµС‚ SMS СЃ РїРѕРґС‚РІРµСЂР¶РґРµРЅРёРµРј Р·Р°РїРёСЃРё
        </p>
      </div>

      <!-- Email (РѕРїС†РёРѕРЅР°Р»СЊРЅРѕ) -->
      <div>
        <label for="clientEmail" class="block text-sm font-medium text-gray-700 mb-2">
          Email (РЅРµРѕР±СЏР·Р°С‚РµР»СЊРЅРѕ)
        </label>
        <input
          id="clientEmail"
          v-model="form.clientEmail"
          type="email"
          placeholder="your@email.com"
          :class="[
            'w-full px-3 py-2 border rounded-lg text-sm transition-colors',
            errors.clientEmail 
              ? 'border-red-300 focus:border-red-500 focus:ring-red-500' 
              : 'border-gray-300 focus:border-blue-500 focus:ring-blue-500'
          ]"
          @blur="validateField('clientEmail')"
        />
        <p v-if="errors.clientEmail" class="mt-1 text-sm text-red-600">
          {{ errors.clientEmail }}
        </p>
      </div>

      <!-- РљРѕРјРјРµРЅС‚Р°СЂРёР№ Рє Р·Р°РїРёСЃРё -->
      <div>
        <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
          РљРѕРјРјРµРЅС‚Р°СЂРёР№ Рє Р·Р°РїРёСЃРё
        </label>
        <textarea
          id="notes"
          v-model="form.notes"
          rows="3"
          placeholder="Р”РѕРїРѕР»РЅРёС‚РµР»СЊРЅС‹Рµ РїРѕР¶РµР»Р°РЅРёСЏ РёР»Рё РІРѕРїСЂРѕСЃС‹..."
          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 transition-colors resize-none"
          maxlength="500"
        ></textarea>
        <p class="mt-1 text-xs text-gray-500">
          {{ form.notes.length }}/500 СЃРёРјРІРѕР»РѕРІ
        </p>
      </div>

      <!-- РЎРѕРіР»Р°СЃРёРµ РЅР° РѕР±СЂР°Р±РѕС‚РєСѓ РґР°РЅРЅС‹С… -->
      <div class="flex items-start">
        <input
          id="dataProcessingConsent"
          v-model="form.dataProcessingConsent"
          type="checkbox"
          :class="[
            'h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 mt-0.5',
            errors.dataProcessingConsent ? 'border-red-300' : ''
          ]"
        />
        <label for="dataProcessingConsent" class="ml-2 text-sm text-gray-700">
          РЇ СЃРѕРіР»Р°СЃРµРЅ РЅР° 
          <a href="/privacy" target="_blank" class="text-blue-600 hover:text-blue-800 underline">
            РѕР±СЂР°Р±РѕС‚РєСѓ РїРµСЂСЃРѕРЅР°Р»СЊРЅС‹С… РґР°РЅРЅС‹С…
          </a>
          <span class="text-red-500">*</span>
        </label>
      </div>
      <p v-if="errors.dataProcessingConsent" class="text-sm text-red-600">
        {{ errors.dataProcessingConsent }}
      </p>

      <!-- РРЅС„РѕСЂРјР°С†РёСЏ Рѕ Р·Р°РїРёСЃРё -->
      <div v-if="bookingInfo" class="bg-gray-50 rounded-lg p-4">
        <h4 class="text-sm font-medium text-gray-900 mb-3">Р”РµС‚Р°Р»Рё Р·Р°РїРёСЃРё:</h4>
        <div class="space-y-2 text-sm text-gray-700">
          <div class="flex justify-between">
            <span>Р”Р°С‚Р° Рё РІСЂРµРјСЏ:</span>
            <span class="font-medium">{{ formatDateTime(bookingInfo.datetime) }}</span>
          </div>
          <div v-if="bookingInfo.service" class="flex justify-between">
            <span>РЈСЃР»СѓРіР°:</span>
            <span class="font-medium">{{ bookingInfo.service.name }}</span>
          </div>
          <div v-if="bookingInfo.service?.duration" class="flex justify-between">
            <span>РџСЂРѕРґРѕР»Р¶РёС‚РµР»СЊРЅРѕСЃС‚СЊ:</span>
            <span class="font-medium">{{ bookingInfo.service.duration }} РјРёРЅ</span>
          </div>
          <div v-if="bookingInfo.service?.price" class="flex justify-between items-center">
            <span>РЎС‚РѕРёРјРѕСЃС‚СЊ:</span>
            <span class="font-semibold text-lg text-green-600">
              {{ formatPrice(bookingInfo.service.price) }} в‚Ѕ
            </span>
          </div>
        </div>
      </div>

      <!-- РљРЅРѕРїРєРё РґРµР№СЃС‚РІРёР№ -->
      <div class="flex flex-col sm:flex-row gap-3">
        <button
          type="button"
          @click="$emit('cancel')"
          class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors"
        >
          РћС‚РјРµРЅРёС‚СЊ
        </button>
        
        <button
          type="submit"
          :disabled="!isValid || loading"
          :class="[
            'px-4 py-2 text-sm font-medium text-white rounded-lg transition-colors flex items-center justify-center',
            isValid && !loading
              ? 'bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500'
              : 'bg-gray-400 cursor-not-allowed'
          ]"
        >
          <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          {{ loading ? 'РЎРѕР·РґР°РЅРёРµ Р·Р°РїРёСЃРё...' : 'Р—Р°РїРёСЃР°С‚СЊСЃСЏ' }}
        </button>
      </div>

      <!-- РћС€РёР±РєРё С„РѕСЂРјС‹ -->
      <div v-if="formError" class="bg-red-50 border border-red-200 rounded-lg p-4">
        <div class="flex">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <div class="ml-3">
            <p class="text-sm text-red-800">{{ formError }}</p>
          </div>
        </div>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import dayjs from 'dayjs'
import 'dayjs/locale/ru'
import { useErrorHandler } from '@/src/shared/composables/useErrorHandler'
import { ErrorState } from '@/src/shared/ui/molecules/ErrorState'

// РќР°СЃС‚СЂРѕР№РєР° dayjs
dayjs.locale('ru')

// Error handler (Р±РµР· toast - РїРѕРєР°Р·С‹РІР°РµРј С‡РµСЂРµР· ErrorState)
const errorState = useErrorHandler(false)

// Props С‚РёРїРёР·Р°С†РёСЏ
interface BookingFormProps {
  bookingInfo?: {
    datetime: string
    service?: {
      name: string
      duration?: number
      price?: number
    }
  } | null
  loading?: boolean
}

const props = withDefaults(defineProps<BookingFormProps>(), {
  bookingInfo: null,
  loading: false
})

// Events С‚РёРїРёР·Р°С†РёСЏ
interface BookingFormEmits {
  submit: [bookingData: any]
  cancel: []
  'form-change': [data: { isValid: boolean; formData: any }]
  retryRequested: []
}

const emit = defineEmits<BookingFormEmits>()

// РЎРѕСЃС‚РѕСЏРЅРёРµ С„РѕСЂРјС‹
const form = ref({
  clientName: '',
  clientPhone: '',
  clientEmail: '',
  notes: '',
  dataProcessingConsent: false
})

const errors = ref({})
const formError = ref(null)

// Р’С‹С‡РёСЃР»СЏРµРјС‹Рµ СЃРІРѕР№СЃС‚РІР°
const isValid = computed(() => {
  return form.value.clientName.trim() !== '' &&
         form.value.clientPhone.trim() !== '' &&
         form.value.dataProcessingConsent &&
         Object.keys(errors.value).length === 0
})

// РњРµС‚РѕРґС‹ РІР°Р»РёРґР°С†РёРё
type ValidateFieldName = 'clientName' | 'clientPhone' | 'clientEmail' | 'dataProcessingConsent'

const validateField = (fieldName: ValidateFieldName): void => {
  try {
    errors.value = { ...errors.value }
    delete errors.value[fieldName]

    switch (fieldName) {
      case 'clientName':
        if (!form.value.clientName.trim()) {
          errors.value.clientName = 'РЈРєР°Р¶РёС‚Рµ РІР°С€Рµ РёРјСЏ'
        } else if (form.value.clientName.trim().length < 2) {
          errors.value.clientName = 'РРјСЏ РґРѕР»Р¶РЅРѕ СЃРѕРґРµСЂР¶Р°С‚СЊ РјРёРЅРёРјСѓРј 2 СЃРёРјРІРѕР»Р°'
        }
        break

      case 'clientPhone':
        const phoneRegex = /^\+7 \(\d{3}\) \d{3}-\d{2}-\d{2}$/
        if (!form.value.clientPhone.trim()) {
          errors.value.clientPhone = 'РЈРєР°Р¶РёС‚Рµ РЅРѕРјРµСЂ С‚РµР»РµС„РѕРЅР°'
        } else if (!phoneRegex.test(form.value.clientPhone)) {
          errors.value.clientPhone = 'РќРµРєРѕСЂСЂРµРєС‚РЅС‹Р№ С„РѕСЂРјР°С‚ С‚РµР»РµС„РѕРЅР°'
        }
        break

      case 'clientEmail':
        if (form.value.clientEmail.trim()) {
          const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
          if (!emailRegex.test(form.value.clientEmail)) {
            errors.value.clientEmail = 'РќРµРєРѕСЂСЂРµРєС‚РЅС‹Р№ С„РѕСЂРјР°С‚ email'
          }
        }
        break

      case 'dataProcessingConsent':
        if (!form.value.dataProcessingConsent) {
          errors.value.dataProcessingConsent = 'РќРµРѕР±С…РѕРґРёРјРѕ СЃРѕРіР»Р°СЃРёРµ РЅР° РѕР±СЂР°Р±РѕС‚РєСѓ РґР°РЅРЅС‹С…'
        }
        break
    }
  } catch (error: unknown) {
    errorState.handleError({
      message: 'РћС€РёР±РєР° РІР°Р»РёРґР°С†РёРё РїРѕР»СЏ',
      details: `РќРµ СѓРґР°РµС‚СЃСЏ РїСЂРѕРІРµСЂРёС‚СЊ РїРѕР»Рµ ${fieldName}`
    }, 'validation')
  }
}

const validateForm = (): boolean => {
  try {
    validateField('clientName')
    validateField('clientPhone')
    validateField('clientEmail')
    
    if (!form.value.dataProcessingConsent) {
      errors.value.dataProcessingConsent = 'РќРµРѕР±С…РѕРґРёРјРѕ СЃРѕРіР»Р°СЃРёРµ РЅР° РѕР±СЂР°Р±РѕС‚РєСѓ РґР°РЅРЅС‹С…'
    }

    return Object.keys(errors.value).length === 0
  } catch (error: unknown) {
    errorState.handleError({
      message: 'РћС€РёР±РєР° РІР°Р»РёРґР°С†РёРё С„РѕСЂРјС‹',
      details: 'РќРµ СѓРґР°РµС‚СЃСЏ РїСЂРѕРІРµСЂРёС‚СЊ РєРѕСЂСЂРµРєС‚РЅРѕСЃС‚СЊ РґР°РЅРЅС‹С…'
    }, 'validation')
    return false
  }
}

// РњРµС‚РѕРґС‹ С„РѕСЂРјР°С‚РёСЂРѕРІР°РЅРёСЏ
const formatPhone = (event: Event): void => {
  try {
    const target = event.target as HTMLInputElement
    let value = target.value.replace(/\D/g, '')
    
    if (value.startsWith('8')) {
      value = '7' + value.slice(1)
    }
    
    if (!value.startsWith('7')) {
      value = '7' + value
    }
    
    if (value.length > 11) {
      value = value.slice(0, 11)
    }
    
    let formatted = '+7'
    if (value.length > 1) {
      formatted += ' (' + value.slice(1, 4)
    }
    if (value.length > 4) {
      formatted += ') ' + value.slice(4, 7)
    }
    if (value.length > 7) {
      formatted += '-' + value.slice(7, 9)
    }
    if (value.length > 9) {
      formatted += '-' + value.slice(9, 11)
    }
    
    form.value.clientPhone = formatted
  } catch (error: unknown) {
    errorState.handleError({
      message: 'РћС€РёР±РєР° С„РѕСЂРјР°С‚РёСЂРѕРІР°РЅРёСЏ С‚РµР»РµС„РѕРЅР°',
      details: 'Р’РІРµРґРёС‚Рµ РєРѕСЂСЂРµРєС‚РЅС‹Р№ РЅРѕРјРµСЂ С‚РµР»РµС„РѕРЅР°'
    }, 'validation')
  }
}

const formatDateTime = (datetime: string): string => {
  try {
    return dayjs(datetime).format('DD MMMM YYYY РІ HH:mm')
  } catch (error: unknown) {
    return 'РќРµРєРѕСЂСЂРµРєС‚РЅР°СЏ РґР°С‚Р°'
  }
}

const formatPrice = (price: number | undefined): string => {
  try {
    if (!price) return '0'
    return new Intl.NumberFormat('ru-RU').format(price)
  } catch (error: unknown) {
    return '0'
  }
}

// РћР±СЂР°Р±РѕС‚С‡РёРєРё СЃРѕР±С‹С‚РёР№
const handleSubmit = () => {
  try {
    errorState.clearError()
    formError.value = null
    
    if (!validateForm()) {
      formError.value = 'РџСЂРѕРІРµСЂСЊС‚Рµ РїСЂР°РІРёР»СЊРЅРѕСЃС‚СЊ Р·Р°РїРѕР»РЅРµРЅРёСЏ С„РѕСЂРјС‹'
      return
    }

    if (!props.bookingInfo) {
      formError.value = 'РЎРЅР°С‡Р°Р»Р° РІС‹Р±РµСЂРёС‚Рµ РґР°С‚Сѓ Рё РІСЂРµРјСЏ Р·Р°РїРёСЃРё'
      return
    }

    // РџРѕРґРіРѕС‚Р°РІР»РёРІР°РµРј РґР°РЅРЅС‹Рµ РґР»СЏ РѕС‚РїСЂР°РІРєРё
    const bookingData = {
      ...props.bookingInfo,
      client: {
        name: form.value.clientName.trim(),
        phone: form.value.clientPhone,
        email: form.value.clientEmail.trim() || null
      },
      notes: form.value.notes.trim() || null,
      dataProcessingConsent: form.value.dataProcessingConsent
    }

    emit('submit', bookingData)
  } catch (error: unknown) {
    errorState.handleError({
      message: 'РћС€РёР±РєР° РїСЂРё РѕС‚РїСЂР°РІРєРµ С„РѕСЂРјС‹',
      details: 'РџСЂРѕРІРµСЂСЊС‚Рµ РїСЂР°РІРёР»СЊРЅРѕСЃС‚СЊ Р·Р°РїРѕР»РЅРµРЅРёСЏ РІСЃРµС… РїРѕР»РµР№'
    }, 'validation')
  }
}

// РњРµС‚РѕРґ РґР»СЏ РїРѕРІС‚РѕСЂРЅРѕР№ РїРѕРїС‹С‚РєРё РїРѕСЃР»Рµ РѕС€РёР±РєРё
const handleRetry = async (): Promise<void> => {
  errorState.clearError()
  formError.value = null
  
  // РћС‡РёС‰Р°РµРј РѕС€РёР±РєРё РІР°Р»РёРґР°С†РёРё
  errors.value = {}
  
  // Р­РјРёС‚РёСЂСѓРµРј СЃРѕР±С‹С‚РёРµ РґР»СЏ СЂРѕРґРёС‚РµР»СЊСЃРєРѕРіРѕ РєРѕРјРїРѕРЅРµРЅС‚Р°
  await errorState.retryOperation(async () => {
    emit('retryRequested')
  })
}

// РќР°Р±Р»СЋРґР°С‚РµР»Рё
watch(form, () => {
  emit('form-change', {
    isValid: isValid.value,
    formData: form.value
  })
}, { deep: true })

watch(() => form.value.dataProcessingConsent, (newValue) => {
  if (newValue) {
    delete errors.value.dataProcessingConsent
  }
})
</script>

<style scoped>
.booking-form {
  @apply max-w-full;
}

/* РђРЅРёРјР°С†РёСЏ РґР»СЏ РїРѕР»РµР№ СЃ РѕС€РёР±РєР°РјРё */
.booking-form input.border-red-300,
.booking-form textarea.border-red-300 {
  @apply animate-pulse;
}

/* РЎС‚РёР»РёР·Р°С†РёСЏ С‡РµРєР±РѕРєСЃР° */
input[type="checkbox"]:checked {
  background-image: url("data:image/svg+xml,%3csvg viewBox='0 0 16 16' fill='white' xmlns='http://www.w3.org/2000/svg'%3e%3cpath d='m13.854 3.646-7.5 7.5a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L6 10.293l7.146-7.147a.5.5 0 0 1 .708.708z'/%3e%3c/svg%3e");
}

/* РђРЅРёРјР°С†РёСЏ Р·Р°РіСЂСѓР·РєРё */
@keyframes spin {
  to { transform: rotate(360deg); }
}

.animate-spin {
  animation: spin 1s linear infinite;
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: .5;
  }
}

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>

