<template>
  <Teleport to="body">
    <div 
      class="fixed inset-0 z-50 overflow-y-auto"
      data-testid="booking-modal-wrapper"
      role="dialog"
      aria-modal="true"
      :aria-labelledby="`${componentId}-title`"
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
              :id="`${componentId}-title`"
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

            <!-- Р'С‹Р±РѕСЂ СѓСЃР»СѓРіРё -->
            <div class="mb-6">
              <BaseSelect
                v-model="form.service_id"
                label="Р'С‹Р±РµСЂРёС‚Рµ СѓСЃР»СѓРіСѓ *"
                :options="serviceOptions"
                :error="formErrors.service_id"
                data-testid="service-select"
                required
              />
            </div>

            <!-- Р”Р°С‚Р° Рё РІСЂРµРјСЏ -->
            <div class="grid grid-cols-2 gap-4 mb-6">
              <div>
                <label 
                  :for="`${componentId}-date`"
                  class="block text-sm font-medium text-gray-500 mb-2"
                >
                  Р”Р°С‚Р° *
                </label>
                <VueDatePicker 
                  :id="`${componentId}-date`"
                  v-model="form.booking_date"
                  :min-date="new Date()"
                  :disabled-dates="state.disabledDates"
                  locale="ru"
                  format="dd.MM.yyyy"
                  placeholder="Р’С‹Р±РµСЂРёС‚Рµ РґР°С‚Сѓ"
                  :enable-time-picker="false"
                  data-testid="date-picker"
                  :class="{ 'border-red-500': formErrors.booking_date }"
                  :aria-describedby="`${componentId}-date-error`"
                  @update:model-value="fetchAvailableSlots"
                />
                <p 
                  v-if="formErrors.booking_date"
                  :id="`${componentId}-date-error`"
                  class="mt-1 text-sm text-red-600"
                  data-testid="date-error"
                  role="alert"
                >
                  {{ formErrors.booking_date }}
                </p>
              </div>
                            
              <div>
                <BaseSelect
                  v-model="form.start_time"
                  label="Р'СЂРµРјСЏ *"
                  :options="timeSlotOptions"
                  :disabled="!form.booking_date || state.loadingSlots"
                  :error="formErrors.start_time"
                  data-testid="time-select"
                  required
                />
              </div>
            </div>

            <!-- РўРёРї СѓСЃР»СѓРіРё -->
            <div class="mb-6">
              <label class="block text-sm font-medium text-gray-500 mb-2">
                Р"РґРµ РїСЂРѕРІРµСЃС‚Рё СЃРµР°РЅСЃ?
              </label>
              <div class="space-y-2">
                <BaseRadio
                  v-if="master.home_service"
                  v-model="form.is_home_service"
                  name="service_location"
                  :value="true"
                  label="Р'С‹РµР·Рґ РЅР° РґРѕРј (+500в‚Ѕ Рє СЃС‚РѕРёРјРѕСЃС‚Рё)"
                />
                <BaseRadio
                  v-if="master.salon_service"
                  v-model="form.is_home_service"
                  name="service_location"
                  :value="false"
                  :label="`Р' СЃР°Р»РѕРЅРµ (${master.salon_address || 'Р°РґСЂРµСЃ СЃР°Р»РѕРЅР°'})`"
                />
              </div>
            </div>

            <!-- РђРґСЂРµСЃ (РґР»СЏ РІС‹РµР·РґР°) -->
            <div v-if="form.is_home_service" class="mb-6">
              <BaseInput
                v-model="form.address"
                name="address"
                type="text"
                label="РђРґСЂРµСЃ *"
                placeholder="РЈР»РёС†Р°, РґРѕРј, РєРІР°СЂС‚РёСЂР°"
                :error="formErrors.address"
                required
              />
            </div>

            <!-- РљРѕРЅС‚Р°РєС‚РЅС‹Рµ РґР°РЅРЅС‹Рµ -->
            <div class="grid grid-cols-2 gap-4 mb-6">
              <div>
                <BaseInput
                  v-model="form.client_name"
                  name="client_name"
                  type="text"
                  label="Р'Р°С€Рµ РёРјСЏ *"
                  placeholder="Р'РІРµРґРёС‚Рµ РІР°С€Рµ РёРјСЏ"
                  :error="formErrors.client_name"
                  data-testid="client-name-input"
                  required
                  minlength="2"
                  maxlength="50"
                />
              </div>
                            
              <div>
                <label 
                  :for="`${componentId}-phone`"
                  class="block text-sm font-medium text-gray-500 mb-2"
                >
                  РўРµР»РµС„РѕРЅ *
                </label>
                <input
                  :id="`${componentId}-phone`"
                  v-model="form.client_phone"
                  v-maska="'+7 (###) ###-##-##'"
                  type="tel"
                  placeholder="+7 (999) 999-99-99"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                  data-testid="client-phone-input"
                  :class="{ 'border-red-500': formErrors.client_phone }"
                  :aria-describedby="`${componentId}-phone-error`"
                  required
                />
                <p 
                  v-if="formErrors.client_phone"
                  :id="`${componentId}-phone-error`"
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
              <BaseInput
                v-model="form.client_email"
                name="client_email"
                type="email"
                label="Email"
                placeholder="Р"Р»СЏ РѕС‚РїСЂР°РІРєРё РїРѕРґС‚РІРµСЂР¶РґРµРЅРёСЏ"
                :error="formErrors.client_email"
              />
            </div>

            <!-- РљРѕРјРјРµРЅС‚Р°СЂРёР№ -->
            <div class="mb-6">
              <BaseTextarea
                v-model="form.client_comment"
                label="РџРѕР¶РµР»Р°РЅРёСЏ"
                placeholder="РћСЃРѕР±С‹Рµ РїРѕР¶РµР»Р°РЅРёСЏ РёР»Рё РІРѕРїСЂРѕСЃС‹"
                :rows="3"
              />
            </div>

            <!-- РЎРїРѕСЃРѕР± РѕРїР»Р°С‚С‹ -->
            <div class="mb-6">
              <label class="block text-sm font-medium text-gray-500 mb-2">
                РЎРїРѕСЃРѕР± РѕРїР»Р°С‚С‹
              </label>
              <div class="space-y-2">
                <BaseRadio
                  v-for="method in paymentMethods"
                  :key="method.value"
                  v-model="form.payment_method"
                  name="payment_method"
                  :value="method.value"
                  :label="method.label"
                />
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
              <PrimaryButton
                type="submit"
                :disabled="state.loading || !isFormValid"
                :loading="state.loading"
                data-testid="submit-button"
                class="flex-1"
              >
                {{ state.loading ? 'РћС‚РїСЂР°РІРєР°...' : 'Р—Р°РїРёСЃР°С‚СЊСЃСЏ' }}
              </PrimaryButton>
              <SecondaryButton
                type="button"
                :disabled="state.loading"
                data-testid="cancel-button"
                @click="closeModal"
              >
                РћС‚РјРµРЅР°
              </SecondaryButton>
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
import { vMaska } from 'maska'
import { XMarkIcon } from '@heroicons/vue/24/outline'
import type { AxiosResponse } from 'axios'
import axios from 'axios'
import { useToast } from '@/src/shared/composables/useToast'
import BaseInput from '@/src/shared/ui/atoms/BaseInput/BaseInput.vue'
import BaseSelect from '@/src/shared/ui/atoms/BaseSelect/BaseSelect.vue'
import BaseTextarea from '@/src/shared/ui/atoms/BaseTextarea/BaseTextarea.vue'
import BaseRadio from '@/src/shared/ui/atoms/BaseRadio/BaseRadio.vue'
import PrimaryButton from '@/src/shared/ui/atoms/PrimaryButton/PrimaryButton.vue'
import SecondaryButton from '@/src/shared/ui/atoms/SecondaryButton/SecondaryButton.vue'
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

// Уникальный ID для экземпляра компонента
const componentId = `booking-modal-${Math.random().toString(36).substr(2, 9)}`

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
    { value: 'cash', label: 'Наличные' },
    { value: 'card', label: 'Картой' },
    { value: 'online', label: 'Онлайн' }
]

// Computed для опций селектов
const serviceOptions = computed(() => {
    const options = [{ value: '', label: 'Выберите услугу' }]
    if (props.master.services) {
        props.master.services.forEach(service => {
            options.push({
                value: service.id.toString(),
                label: `${service.name} - ${service.price}₽ (${service.duration} мин)`
            })
        })
    }
    return options
})

const timeSlotOptions = computed(() => {
    const options = [{ 
        value: '', 
        label: state.value.loadingSlots ? 'Загрузка...' : 'Выберите время' 
    }]
    state.value.availableSlots.forEach(slot => {
        options.push({ value: slot, label: slot })
    })
    return options
})

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

