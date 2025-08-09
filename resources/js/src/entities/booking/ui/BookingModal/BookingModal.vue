<template>
  <Teleport to="body">
    <div 
      class="fixed inset-0 z-50 overflow-y-auto"
      data-testid="booking-modal-wrapper"
      role="dialog"
      aria-modal="true"
      aria-labelledby="booking-modal-title"
      @keydown="handleKeydown"
    >
      <div class="flex items-center justify-center min-h-screen p-4">
        <!-- Overlay -->
        <div 
          class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"
          data-testid="booking-modal-overlay"
          @click="handleOverlayClick"
        />

        <!-- Modal -->
        <div 
          class="relative bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto"
          data-testid="booking-modal-content"
        >
          <!-- Header -->
          <div class="sticky top-0 bg-white border-b px-6 py-4 flex items-center justify-between">
            <h3 
              id="booking-modal-title"
              class="text-lg font-semibold"
              data-testid="booking-modal-title"
            >
              Р—Р°РїРёСЃСЊ Рє РјР°СЃС‚РµСЂСѓ
            </h3>
            <button 
              class="p-2 hover:bg-gray-500 rounded-lg"
              data-testid="booking-modal-close"
              aria-label="Р—Р°РєСЂС‹С‚СЊ РјРѕРґР°Р»СЊРЅРѕРµ РѕРєРЅРѕ"
              @click="closeModal"
            >
              <XMarkIcon class="w-5 h-5" aria-hidden="true" />
            </button>
          </div>

          <!-- Content -->
          <form 
            class="p-6" 
            data-testid="booking-form"
            novalidate
            @submit.prevent="submitBooking"
          >
            <!-- РћР±С‰Р°СЏ РѕС€РёР±РєР° -->
            <div 
              v-if="formErrors.general"
              class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg"
              data-testid="general-error"
              role="alert"
            >
              <p class="text-red-600 text-sm font-medium">
                {{ formErrors.general }}
              </p>
            </div>
            <!-- РњР°СЃС‚РµСЂ -->
            <div 
              class="flex items-center gap-4 mb-6 p-4 bg-gray-500 rounded-lg"
              data-testid="master-info"
            >
              <img 
                :src="master.avatar || '/images/no-avatar.jpg'"
                :alt="`РђРІР°С‚Р°СЂ ${master.display_name}`"
                class="w-16 h-16 rounded-full object-cover"
                data-testid="master-avatar"
                @error="(e: Event) => (e.target as HTMLImageElement).src = '/images/no-avatar.jpg'"
              >
              <div>
                <h4 
                  class="font-medium"
                  data-testid="master-name"
                >
                  {{ master.display_name }}
                </h4>
                <p 
                  class="text-sm text-gray-500"
                  data-testid="master-district"
                >
                  {{ master.district }}
                </p>
              </div>
            </div>

            <!-- Р’С‹Р±РѕСЂ СѓСЃР»СѓРіРё -->
            <div class="mb-6">
              <label 
                for="service-select"
                class="block text-sm font-medium text-gray-500 mb-2"
              >
                Р’С‹Р±РµСЂРёС‚Рµ СѓСЃР»СѓРіСѓ *
              </label>
              <select 
                id="service-select"
                v-model="form.service_id"
                class="w-full border-gray-500 rounded-lg"
                :class="{ 'border-red-500': formErrors.service_id }"
                data-testid="service-select"
                required
                aria-describedby="service-error"
              >
                <option value="">
                  Р’С‹Р±РµСЂРёС‚Рµ СѓСЃР»СѓРіСѓ
                </option>
                <option 
                  v-for="service in master.services"
                  :key="service.id"
                  :value="service.id"
                >
                  {{ service.name }} - {{ service.price }}в‚Ѕ ({{ service.duration }} РјРёРЅ)
                </option>
              </select>
              <p 
                v-if="formErrors.service_id"
                id="service-error"
                class="mt-1 text-sm text-red-600"
                data-testid="service-error"
                role="alert"
              >
                {{ formErrors.service_id }}
              </p>
            </div>

            <!-- Р”Р°С‚Р° Рё РІСЂРµРјСЏ -->
            <div class="grid grid-cols-2 gap-4 mb-6">
              <div>
                <label 
                  for="booking-date"
                  class="block text-sm font-medium text-gray-500 mb-2"
                >
                  Р”Р°С‚Р° *
                </label>
                <VueDatePicker 
                  id="booking-date"
                  v-model="form.booking_date"
                  :min-date="new Date()"
                  :disabled-dates="state.disabledDates"
                  locale="ru"
                  format="dd.MM.yyyy"
                  placeholder="Р’С‹Р±РµСЂРёС‚Рµ РґР°С‚Сѓ"
                  :enable-time-picker="false"
                  data-testid="date-picker"
                  :class="{ 'border-red-500': formErrors.booking_date }"
                  aria-describedby="date-error"
                  @update:model-value="fetchAvailableSlots"
                />
                <p 
                  v-if="formErrors.booking_date"
                  id="date-error"
                  class="mt-1 text-sm text-red-600"
                  data-testid="date-error"
                  role="alert"
                >
                  {{ formErrors.booking_date }}
                </p>
              </div>
                            
              <div>
                <label 
                  for="time-select"
                  class="block text-sm font-medium text-gray-500 mb-2"
                >
                  Р’СЂРµРјСЏ *
                </label>
                <select 
                  id="time-select"
                  v-model="form.start_time"
                  class="w-full border-gray-500 rounded-lg"
                  :class="{ 'border-red-500': formErrors.start_time }"
                  :disabled="!form.booking_date || state.loadingSlots"
                  data-testid="time-select"
                  required
                  aria-describedby="time-error"
                >
                  <option value="">
                    {{ state.loadingSlots ? 'Р—Р°РіСЂСѓР·РєР°...' : 'Р’С‹Р±РµСЂРёС‚Рµ РІСЂРµРјСЏ' }}
                  </option>
                  <option 
                    v-for="slot in state.availableSlots"
                    :key="slot"
                    :value="slot"
                  >
                    {{ slot }}
                  </option>
                </select>
                <p 
                  v-if="formErrors.start_time"
                  id="time-error"
                  class="mt-1 text-sm text-red-600"
                  data-testid="time-error"
                  role="alert"
                >
                  {{ formErrors.start_time }}
                </p>
              </div>
            </div>

            <!-- РўРёРї СѓСЃР»СѓРіРё -->
            <div class="mb-6">
              <label class="block text-sm font-medium text-gray-500 mb-2">
                Р“РґРµ РїСЂРѕРІРµСЃС‚Рё СЃРµР°РЅСЃ?
              </label>
              <div class="grid grid-cols-2 gap-4">
                <label 
                  v-if="master.home_service"
                  class="relative flex items-center p-4 border rounded-lg cursor-pointer"
                  :class="{ 'border-indigo-500 bg-indigo-50': form.is_home_service }"
                >
                  <input 
                    v-model="form.is_home_service"
                    type="radio"
                    :value="true"
                    class="sr-only"
                  >
                  <div>
                    <p class="font-medium">Р’С‹РµР·Рґ РЅР° РґРѕРј</p>
                    <p class="text-sm text-gray-500">+500в‚Ѕ Рє СЃС‚РѕРёРјРѕСЃС‚Рё</p>
                  </div>
                </label>
                                
                <label 
                  v-if="master.salon_service"
                  class="relative flex items-center p-4 border rounded-lg cursor-pointer"
                  :class="{ 'border-indigo-500 bg-indigo-50': !form.is_home_service }"
                >
                  <input 
                    v-model="form.is_home_service"
                    type="radio"
                    :value="false"
                    class="sr-only"
                  >
                  <div>
                    <p class="font-medium">Р’ СЃР°Р»РѕРЅРµ</p>
                    <p class="text-sm text-gray-500">{{ master.salon_address }}</p>
                  </div>
                </label>
              </div>
            </div>

            <!-- РђРґСЂРµСЃ (РґР»СЏ РІС‹РµР·РґР°) -->
            <div v-if="form.is_home_service" class="mb-6">
              <label class="block text-sm font-medium text-gray-500 mb-2">
                РђРґСЂРµСЃ *
              </label>
              <input 
                v-model="form.address"
                type="text"
                class="w-full border-gray-500 rounded-lg"
                placeholder="РЈР»РёС†Р°, РґРѕРј, РєРІР°СЂС‚РёСЂР°"
                required
              >
            </div>

            <!-- РљРѕРЅС‚Р°РєС‚РЅС‹Рµ РґР°РЅРЅС‹Рµ -->
            <div class="grid grid-cols-2 gap-4 mb-6">
              <div>
                <label 
                  for="client-name"
                  class="block text-sm font-medium text-gray-500 mb-2"
                >
                  Р’Р°С€Рµ РёРјСЏ *
                </label>
                <input 
                  id="client-name"
                  v-model="form.client_name"
                  type="text"
                  class="w-full border-gray-500 rounded-lg"
                  :class="{ 'border-red-500': formErrors.client_name }"
                  data-testid="client-name-input"
                  placeholder="Р’РІРµРґРёС‚Рµ РІР°С€Рµ РёРјСЏ"
                  required
                  aria-describedby="name-error"
                  minlength="2"
                  maxlength="50"
                >
                <p 
                  v-if="formErrors.client_name"
                  id="name-error"
                  class="mt-1 text-sm text-red-600"
                  data-testid="name-error"
                  role="alert"
                >
                  {{ formErrors.client_name }}
                </p>
              </div>
                            
              <div>
                <label 
                  for="client-phone"
                  class="block text-sm font-medium text-gray-500 mb-2"
                >
                  РўРµР»РµС„РѕРЅ *
                </label>
                <vue-tel-input
                  id="client-phone"
                  v-model="form.client_phone"
                  :preferred-countries="['ru', 'ua', 'by']"
                  :only-countries="['ru', 'ua', 'by', 'kz']"
                  mode="international"
                  data-testid="client-phone-input"
                  :class="{ 'border-red-500': formErrors.client_phone }"
                  aria-describedby="phone-error"
                  required
                />
                <p 
                  v-if="formErrors.client_phone"
                  id="phone-error"
                  class="mt-1 text-sm text-red-600"
                  data-testid="phone-error"
                  role="alert"
                >
                  {{ formErrors.client_phone }}
                </p>
              </div>
            </div>

            <!-- Email -->
            <div class="mb-6">
              <label class="block text-sm font-medium text-gray-500 mb-2">
                Email
              </label>
              <input 
                v-model="form.client_email"
                type="email"
                class="w-full border-gray-500 rounded-lg"
                placeholder="Р”Р»СЏ РѕС‚РїСЂР°РІРєРё РїРѕРґС‚РІРµСЂР¶РґРµРЅРёСЏ"
              >
            </div>

            <!-- РљРѕРјРјРµРЅС‚Р°СЂРёР№ -->
            <div class="mb-6">
              <label class="block text-sm font-medium text-gray-500 mb-2">
                РџРѕР¶РµР»Р°РЅРёСЏ
              </label>
              <textarea 
                v-model="form.client_comment"
                rows="3"
                class="w-full border-gray-500 rounded-lg"
                placeholder="РћСЃРѕР±С‹Рµ РїРѕР¶РµР»Р°РЅРёСЏ РёР»Рё РІРѕРїСЂРѕСЃС‹"
              />
            </div>

            <!-- РЎРїРѕСЃРѕР± РѕРїР»Р°С‚С‹ -->
            <div class="mb-6">
              <label class="block text-sm font-medium text-gray-500 mb-2">
                РЎРїРѕСЃРѕР± РѕРїР»Р°С‚С‹
              </label>
              <div class="grid grid-cols-3 gap-3">
                <label 
                  v-for="method in paymentMethods"
                  :key="method.value"
                  class="relative flex items-center justify-center p-3 border rounded-lg cursor-pointer"
                  :class="{ 'border-indigo-500 bg-indigo-50': form.payment_method === method.value }"
                >
                  <input 
                    v-model="form.payment_method"
                    type="radio"
                    :value="method.value"
                    class="sr-only"
                  >
                  <span class="text-sm font-medium">{{ method.label }}</span>
                </label>
              </div>
            </div>

            <!-- РС‚РѕРіРѕ -->
            <div 
              class="bg-gray-500 rounded-lg p-4 mb-6"
              data-testid="price-summary"
              role="region"
              aria-label="РЎРІРѕРґРєР° РїРѕ СЃС‚РѕРёРјРѕСЃС‚Рё"
            >
              <div class="flex justify-between text-sm mb-2">
                <span>РЈСЃР»СѓРіР°:</span>
                <span data-testid="service-price">{{ selectedServicePrice }}в‚Ѕ</span>
              </div>
              <div 
                v-if="form.is_home_service" 
                class="flex justify-between text-sm mb-2"
              >
                <span>Р’С‹РµР·Рґ:</span>
                <span data-testid="home-service-fee">{{ homeServiceFee }}в‚Ѕ</span>
              </div>
              <div class="flex justify-between font-medium text-lg border-t pt-2">
                <span>РС‚РѕРіРѕ:</span>
                <span 
                  data-testid="total-price"
                  class="text-indigo-600 font-bold"
                >
                  {{ totalPrice }}в‚Ѕ
                </span>
              </div>
            </div>

            <!-- РљРЅРѕРїРєРё -->
            <div class="flex gap-3" data-testid="form-actions">
              <button 
                type="submit"
                :disabled="state.loading || !isFormValid"
                class="flex-1 bg-indigo-600 text-white py-3 rounded-lg font-medium hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                data-testid="submit-button"
                :aria-label="state.loading ? 'РћС‚РїСЂР°РІРєР° С„РѕСЂРјС‹' : 'РћС‚РїСЂР°РІРёС‚СЊ Р·Р°СЏРІРєСѓ РЅР° Р±СЂРѕРЅРёСЂРѕРІР°РЅРёРµ'"
              >
                <span v-if="state.loading" class="flex items-center justify-center gap-2">
                  <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                    <circle
                      class="opacity-25"
                      cx="12"
                      cy="12"
                      r="10"
                      stroke="currentColor"
                      stroke-width="4"
                    />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                  </svg>
                  РћС‚РїСЂР°РІРєР°...
                </span>
                <span v-else>Р—Р°РїРёСЃР°С‚СЊСЃСЏ</span>
              </button>
              <button 
                type="button"
                class="px-6 py-3 border border-gray-500 rounded-lg hover:bg-gray-500 transition-colors"
                data-testid="cancel-button"
                :disabled="state.loading"
                aria-label="РћС‚РјРµРЅРёС‚СЊ Р±СЂРѕРЅРёСЂРѕРІР°РЅРёРµ"
                @click="closeModal"
              >
                РћС‚РјРµРЅР°
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { XMarkIcon } from '@heroicons/vue/24/outline'
import type { AxiosResponse } from 'axios'
import axios from 'axios'
import { useToast } from '@/src/shared/composables/useToast'
import type {
    BookingModalProps,
    BookingModalEmits,
    BookingFormData,
    BookingModalState,
    PaymentMethodOption,
    AvailableSlotsResponse,
    BookingModalError,
    PriceCalculation,
    BookingFormValidation,
    BookingFormErrors
} from './BookingModal.types'

// Props Рё emits СЃ TypeScript С‚РёРїРёР·Р°С†РёРµР№
const props = defineProps<BookingModalProps>()
const emit = defineEmits<BookingModalEmits>()

// Toast РґР»СЏ Р·Р°РјРµРЅС‹ // Removed // Removed // Removed alert() - use toast notifications instead - use toast notifications instead - use toast notifications instead
const toast = useToast()

// Р¤РѕСЂРјР° Р±СЂРѕРЅРёСЂРѕРІР°РЅРёСЏ СЃ С‚РёРїРёР·Р°С†РёРµР№
const form = ref<BookingFormData>({
    master_profile_id: props.master.id,
    service_id: props.service?.id || '',
    booking_date: null,
    start_time: '',
    is_home_service: props.master.home_service || false,
    address: '',
    client_name: '',
    client_phone: '',
    client_email: '',
    client_comment: '',
    payment_method: 'cash'
})

// РЎРѕСЃС‚РѕСЏРЅРёРµ РєРѕРјРїРѕРЅРµРЅС‚Р°
const state = ref<BookingModalState>({
    loading: false,
    loadingSlots: false,
    availableSlots: [],
    disabledDates: []
})

// РћС€РёР±РєРё РІР°Р»РёРґР°С†РёРё
const formErrors = ref<BookingFormErrors>({})

// РњРµС‚РѕРґС‹ РѕРїР»Р°С‚С‹
const paymentMethods: PaymentMethodOption[] = [
    { value: 'cash', label: 'РќР°Р»РёС‡РЅС‹Рµ' },
    { value: 'card', label: 'РљР°СЂС‚РѕР№' },
    { value: 'online', label: 'РћРЅР»Р°Р№РЅ' }
]

// Computed СЃРІРѕР№СЃС‚РІР° РґР»СЏ СЂР°СЃС‡РµС‚Р° СЃС‚РѕРёРјРѕСЃС‚Рё
const selectedServicePrice = computed<number>(() => {
    if (!form.value.service_id) return 0
    const serviceId = typeof form.value.service_id === 'string' 
        ? parseInt(form.value.service_id) 
        : form.value.service_id
    const service = props.master.services.find(s => s.id === serviceId)
    return service?.price || 0
})

const homeServiceFee = computed<number>(() => {
    return form.value.is_home_service ? 500 : 0
})

const totalPrice = computed<number>(() => {
    return selectedServicePrice.value + homeServiceFee.value
})

const priceCalculation = computed<PriceCalculation>(() => ({
    servicePrice: selectedServicePrice.value,
    homeServiceFee: homeServiceFee.value,
    totalPrice: totalPrice.value
}))

// Р’Р°Р»РёРґР°С†РёСЏ С„РѕСЂРјС‹
const isFormValid = computed<boolean>(() => {
    const validation: BookingFormValidation = {
        service_id: !!form.value.service_id,
        booking_date: !!form.value.booking_date,
        start_time: !!form.value.start_time,
        client_name: form.value.client_name.trim().length >= 2,
        client_phone: form.value.client_phone.length >= 10,
        address: !form.value.is_home_service || form.value.address.trim().length >= 5
    }
  
    return Object.values(validation).every(Boolean)
})

// РћС‡РёСЃС‚РєР° РѕС€РёР±РѕРє
const clearErrors = (): void => {
    formErrors.value = {}
}

// Р’Р°Р»РёРґР°С†РёСЏ РїРѕР»РµР№
const validateForm = (): boolean => {
    clearErrors()
    const errors: BookingFormErrors = {}
  
    if (!form.value.service_id) {
        errors.service_id = 'Р’С‹Р±РµСЂРёС‚Рµ СѓСЃР»СѓРіСѓ'
    }
  
    if (!form.value.booking_date) {
        errors.booking_date = 'Р’С‹Р±РµСЂРёС‚Рµ РґР°С‚Сѓ'
    }
  
    if (!form.value.start_time) {
        errors.start_time = 'Р’С‹Р±РµСЂРёС‚Рµ РІСЂРµРјСЏ'
    }
  
    if (form.value.client_name.trim().length < 2) {
        errors.client_name = 'РРјСЏ РґРѕР»Р¶РЅРѕ СЃРѕРґРµСЂР¶Р°С‚СЊ РјРёРЅРёРјСѓРј 2 СЃРёРјРІРѕР»Р°'
    }
  
    if (form.value.client_phone.length < 10) {
        errors.client_phone = 'РќРµРєРѕСЂСЂРµРєС‚РЅС‹Р№ РЅРѕРјРµСЂ С‚РµР»РµС„РѕРЅР°'
    }
  
    if (form.value.is_home_service && form.value.address.trim().length < 5) {
        errors.address = 'РЈРєР°Р¶РёС‚Рµ РїРѕР»РЅС‹Р№ Р°РґСЂРµСЃ'
    }
  
    if (form.value.client_email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.value.client_email)) {
        errors.client_email = 'РќРµРєРѕСЂСЂРµРєС‚РЅС‹Р№ email'
    }
  
    formErrors.value = errors
    return Object.keys(errors).length === 0
}

// Р—Р°РіСЂСѓР·РєР° РґРѕСЃС‚СѓРїРЅС‹С… СЃР»РѕС‚РѕРІ
const fetchAvailableSlots = async (): Promise<void> => {
    if (!form.value.booking_date || !form.value.service_id) return
  
    state.value.loadingSlots = true
    clearErrors()
  
    try {
        const response: AxiosResponse<AvailableSlotsResponse> = await axios.get('/api/bookings/available-slots', {
            params: {
                master_profile_id: props.master.id,
                service_id: form.value.service_id,
                date: form.value.booking_date
            }
        })
    
        state.value.availableSlots = response.data.slots || []
    
        if (response.data.disabled_dates) {
            state.value.disabledDates = response.data.disabled_dates.map(date => new Date(date))
        }
    
        if (state.value.availableSlots.length === 0) {
            toast.info('РќР° РІС‹Р±СЂР°РЅРЅСѓСЋ РґР°С‚Сѓ РЅРµС‚ СЃРІРѕР±РѕРґРЅРѕРіРѕ РІСЂРµРјРµРЅРё')
        }
    
    } catch (error: unknown) {
        const bookingError: BookingModalError = {
            type: 'slots',
            message: 'РћС€РёР±РєР° Р·Р°РіСЂСѓР·РєРё РґРѕСЃС‚СѓРїРЅРѕРіРѕ РІСЂРµРјРµРЅРё',
            originalError: error
        }
        toast.error(bookingError.message)
        formErrors.value.general = bookingError.message
    } finally {
        state.value.loadingSlots = false
    }
}

// РћС‚РїСЂР°РІРєР° С„РѕСЂРјС‹ Р±СЂРѕРЅРёСЂРѕРІР°РЅРёСЏ
const submitBooking = async (): Promise<void> => {
    if (!validateForm()) {
        toast.error('РџРѕР¶Р°Р»СѓР№СЃС‚Р°, РёСЃРїСЂР°РІСЊС‚Рµ РѕС€РёР±РєРё РІ С„РѕСЂРјРµ')
        return
    }
  
    state.value.loading = true
    clearErrors()
  
    try {
        await router.post('/bookings', form.value, {
            onSuccess: () => {
                toast.success('Р—Р°СЏРІРєР° РѕС‚РїСЂР°РІР»РµРЅР°! РћР¶РёРґР°Р№С‚Рµ РїРѕРґС‚РІРµСЂР¶РґРµРЅРёСЏ РјР°СЃС‚РµСЂР°.')
                emit('success', form.value)
                emit('close')
            },
            onError: (errors) => {
        
                if (typeof errors === 'object' && errors !== null) {
                    Object.keys(errors).forEach(key => {
                        if (key in formErrors.value) {
                            formErrors.value[key as keyof BookingFormErrors] = errors[key] as string
                        }
                    })
                }
        
                toast.error('РћС€РёР±РєР° РїСЂРё СЃРѕР·РґР°РЅРёРё Р·Р°РїРёСЃРё')
            }
        })
    } catch (error: unknown) {
        const bookingError: BookingModalError = {
            type: 'api',
            message: 'РћС€РёР±РєР° СЃРµС‚Рё. РџРѕРїСЂРѕР±СѓР№С‚Рµ РїРѕР·Р¶Рµ.',
            originalError: error
        }
        toast.error(bookingError.message)
        formErrors.value.general = bookingError.message
    } finally {
        state.value.loading = false
    }
}

// Р—Р°РєСЂС‹С‚РёРµ РјРѕРґР°Р»СЊРЅРѕРіРѕ РѕРєРЅР°
const closeModal = (): void => {
    emit('close')
}

// РћР±СЂР°Р±РѕС‚РєР° РєР»РёРєР° РїРѕ РѕРІРµСЂР»РµСЋ
const handleOverlayClick = (event: MouseEvent): void => {
    if (event.target === event.currentTarget) {
        closeModal()
    }
}

// РћР±СЂР°Р±РѕС‚РєР° Escape РєР»Р°РІРёС€Рё
const handleKeydown = (event: KeyboardEvent): void => {
    if (event.key === 'Escape') {
        closeModal()
    }
}

// РЎРјРµРЅР° С‚РёРїР° СѓСЃР»СѓРіРё (РґРѕРј/СЃР°Р»РѕРЅ)
const handleServiceTypeChange = (): void => {
    if (!form.value.is_home_service) {
        form.value.address = ''
        clearErrors()
    }
}

// Р—Р°РіСЂСѓР¶Р°РµРј СЃР»РѕС‚С‹ РїСЂРё РёР·РјРµРЅРµРЅРёРё РґР°С‚С‹
watch(() => form.value.booking_date, (newDate) => {
    form.value.start_time = ''
    state.value.availableSlots = []
  
    if (newDate) {
        fetchAvailableSlots()
    }
})

// РџРµСЂРµР·Р°РіСЂСѓР·РєР° СЃР»РѕС‚РѕРІ РїСЂРё СЃРјРµРЅРµ СѓСЃР»СѓРіРё
watch(() => form.value.service_id, () => {
    form.value.start_time = ''
    state.value.availableSlots = []
  
    if (form.value.booking_date && form.value.service_id) {
        fetchAvailableSlots()
    }
})

// РћС‚СЃР»РµР¶РёРІР°РЅРёРµ СЃРјРµРЅС‹ С‚РёРїР° СѓСЃР»СѓРіРё
watch(() => form.value.is_home_service, handleServiceTypeChange)

// РРЅРёС†РёР°Р»РёР·Р°С†РёСЏ РєРѕРјРїРѕРЅРµРЅС‚Р°
onMounted(() => {
    // Р•СЃР»Рё РїРµСЂРµРґР°РЅР° СѓСЃР»СѓРіР°, СЃСЂР°Р·Сѓ СѓСЃС‚Р°РЅР°РІР»РёРІР°РµРј РµС‘
    if (props.service) {
        form.value.service_id = props.service.id
    }
})
</script>

