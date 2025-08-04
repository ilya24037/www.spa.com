<template>
  <div class="booking-form-container">
    <!-- Прогресс-бар -->
    <div class="mb-8">
      <div class="flex items-center justify-between">
        <div 
          v-for="(step, index) in steps" 
          :key="index"
          class="flex items-center flex-1"
        >
          <!-- Кружок шага -->
          <div 
            class="relative flex items-center justify-center w-10 h-10 rounded-full transition-all duration-300"
            :class="{
              'bg-blue-600 text-white': currentStep > index,
              'bg-blue-600 text-white ring-4 ring-blue-200': currentStep === index,
              'bg-gray-200 text-gray-500': currentStep < index
            }"
          >
            <span v-if="currentStep > index" class="text-lg">✓</span>
            <span v-else class="text-sm font-medium">{{ index + 1 }}</span>
          </div>
          
          <!-- Линия между шагами -->
          <div 
            v-if="index < steps.length - 1"
            class="flex-1 h-1 mx-3 transition-all duration-300"
            :class="currentStep > index ? 'bg-blue-600' : 'bg-gray-200'"
          />
        </div>
      </div>
      
      <!-- Названия шагов -->
      <div class="flex justify-between mt-2">
        <span 
          v-for="(step, index) in steps" 
          :key="`label-${index}`"
          class="text-xs flex-1 text-center transition-colors duration-300"
          :class="currentStep >= index ? 'text-gray-900 font-medium' : 'text-gray-500'"
        >
          {{ step.label }}
        </span>
      </div>
    </div>

    <!-- Контент формы -->
    <form 
      @submit.prevent="handleSubmit" 
      class="space-y-6"
      role="form"
      aria-label="Форма бронирования услуги"
    >
      <!-- Шаг 1: Выбор услуги -->
      <div v-show="currentStep === 0" class="animate-fadeIn">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Выберите услугу</h3>
        
        <div class="space-y-3">
          <label 
            v-for="service in master.services"
            :key="service.id"
            class="relative flex items-start p-4 border rounded-lg cursor-pointer transition-all hover:border-blue-400"
            :class="{
              'border-blue-600 bg-blue-50': formData.service?.id === service.id,
              'border-gray-200': formData.service?.id !== service.id
            }"
            data-testid="service-option"
          >
            <input 
              type="radio"
              :value="service"
              v-model="formData.service"
              class="sr-only"
            >
            
            <div class="flex-1">
              <div class="flex items-start justify-between">
                <div>
                  <h4 class="font-medium text-gray-900">{{ service.name }}</h4>
                  <p class="text-sm text-gray-600 mt-1">{{ service.description }}</p>
                  <div class="flex items-center gap-4 mt-2 text-sm text-gray-500">
                    <span class="flex items-center">
                      <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      {{ service.duration }} мин
                    </span>
                    <span v-if="service.is_home_service" class="flex items-center text-green-600">
                      <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                      </svg>
                      С выездом
                    </span>
                  </div>
                </div>
                <span class="text-lg font-semibold text-gray-900 ml-4">
                  {{ formatPrice(service.price) }}
                </span>
              </div>
            </div>

            <!-- Чекбокс индикатор -->
            <div 
              class="absolute top-4 right-4 w-5 h-5 rounded-full border-2 transition-all"
              :class="{
                'border-blue-600 bg-blue-600': formData.service?.id === service.id,
                'border-gray-300': formData.service?.id !== service.id
              }"
            >
              <svg 
                v-if="formData.service?.id === service.id"
                class="w-3 h-3 text-white mx-auto mt-0.5" 
                fill="currentColor" 
                viewBox="0 0 20 20"
              >
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
              </svg>
            </div>
          </label>
        </div>
      </div>

      <!-- Шаг 2: Выбор даты и времени -->
      <div v-show="currentStep === 1" class="animate-fadeIn">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Выберите дату и время</h3>
        
        <!-- Календарь -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Выбор даты -->
          <div>
            <h4 class="font-medium text-gray-700 mb-3">Выберите дату</h4>
            <div class="border rounded-lg p-4">
              <BookingCalendar 
                :availableDates="availableDates"
                v-model="formData.date"
                @update:modelValue="loadAvailableSlots"
              />
            </div>
          </div>

          <!-- Выбор времени -->
          <div>
            <h4 class="font-medium text-gray-700 mb-3">Доступное время</h4>
            <div class="border rounded-lg p-4 min-h-[300px]">
              <div v-if="!formData.date" class="text-center text-gray-500 py-12">
                Сначала выберите дату
              </div>
              
              <div v-else-if="loadingSlots" class="text-center py-12">
                <div class="inline-flex items-center">
                  <svg class="animate-spin h-5 w-5 text-blue-600 mr-2" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  Загружаем слоты...
                </div>
              </div>
              
              <div v-else-if="availableSlots.length === 0" class="text-center text-gray-500 py-12">
                На выбранную дату нет свободных слотов
              </div>
              
              <div v-else class="grid grid-cols-3 gap-2">
                <button
                  v-for="slot in availableSlots"
                  :key="slot.time"
                  type="button"
                  @click="formData.time = slot.time"
                  :disabled="!slot.available"
                  class="py-2 px-3 text-sm rounded-lg font-medium transition-all"
                  :class="{
                    'bg-blue-600 text-white': formData.time === slot.time,
                    'bg-gray-100 hover:bg-gray-200 text-gray-700': slot.available && formData.time !== slot.time,
                    'bg-gray-50 text-gray-400 cursor-not-allowed': !slot.available
                  }"
                >
                  {{ slot.time }}
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Тип услуги -->
        <div v-if="formData.service?.is_home_service" class="mt-6">
          <h4 class="font-medium text-gray-700 mb-3">Где провести сеанс?</h4>
          <div class="grid grid-cols-2 gap-4">
            <label class="relative">
              <input 
                type="radio" 
                value="salon" 
                v-model="formData.locationType"
                class="sr-only"
              >
              <div 
                class="p-4 border rounded-lg cursor-pointer transition-all"
                :class="{
                  'border-blue-600 bg-blue-50': formData.locationType === 'salon',
                  'border-gray-200 hover:border-gray-300': formData.locationType !== 'salon'
                }"
              >
                <div class="flex items-center">
                  <svg class="w-5 h-5 mr-3 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                  </svg>
                  <div>
                    <div class="font-medium">В салоне</div>
                    <div class="text-sm text-gray-500">{{ master.salon_address }}</div>
                  </div>
                </div>
              </div>
            </label>

            <label class="relative">
              <input 
                type="radio" 
                value="home" 
                v-model="formData.locationType"
                class="sr-only"
              >
              <div 
                class="p-4 border rounded-lg cursor-pointer transition-all"
                :class="{
                  'border-blue-600 bg-blue-50': formData.locationType === 'home',
                  'border-gray-200 hover:border-gray-300': formData.locationType !== 'home'
                }"
              >
                <div class="flex items-center">
                  <svg class="w-5 h-5 mr-3 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                  </svg>
                  <div>
                    <div class="font-medium">С выездом</div>
                    <div class="text-sm text-gray-500">+{{ formatPrice(500) }} к стоимости</div>
                  </div>
                </div>
              </div>
            </label>
          </div>
        </div>
      </div>

      <!-- Шаг 3: Контактные данные -->
      <div v-show="currentStep === 2" class="animate-fadeIn">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Контактные данные</h3>
        
        <div class="space-y-4">
          <!-- Имя -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Ваше имя <span class="text-red-500">*</span>
            </label>
            <input 
              v-model="formData.name"
              type="text"
              required
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              :class="{'border-red-500': errors.name}"
              placeholder="Введите ваше имя"
              aria-label="Ваше имя"
              aria-required="true"
              :aria-describedby="errors.name ? 'name-error' : undefined"
            >
            <p v-if="errors.name" id="name-error" class="mt-1 text-sm text-red-600" role="alert">{{ errors.name }}</p>
          </div>

          <!-- Телефон -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Телефон <span class="text-red-500">*</span>
            </label>
            <input 
              v-model="formData.phone"
              type="tel"
              required
              v-maska="'+7 (###) ###-##-##'"
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              :class="{'border-red-500': errors.phone}"
              placeholder="+7 (999) 999-99-99"
              aria-label="Номер телефона"
              aria-required="true"
              :aria-describedby="errors.phone ? 'phone-error' : undefined"
            >
            <p v-if="errors.phone" id="phone-error" class="mt-1 text-sm text-red-600" role="alert">{{ errors.phone }}</p>
          </div>

          <!-- Email -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Email
            </label>
            <input 
              v-model="formData.email"
              type="email"
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              placeholder="example@mail.ru (необязательно)"
            >
          </div>

          <!-- Адрес (если выезд) -->
          <div v-if="formData.locationType === 'home'">
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Адрес <span class="text-red-500">*</span>
            </label>
            <input 
              v-model="formData.address"
              type="text"
              required
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              :class="{'border-red-500': errors.address}"
              placeholder="Улица, дом, квартира"
            >
            <p v-if="errors.address" class="mt-1 text-sm text-red-600">{{ errors.address }}</p>
            <p class="mt-1 text-sm text-gray-500">Точный адрес увидит только мастер после подтверждения</p>
          </div>

          <!-- Комментарий -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Комментарий для мастера
            </label>
            <textarea 
              v-model="formData.comment"
              rows="3"
              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
              placeholder="Особые пожелания, противопоказания..."
            ></textarea>
          </div>

          <!-- Согласие -->
          <label class="flex items-start">
            <input 
              v-model="formData.agreement"
              type="checkbox"
              required
              class="mt-1 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
            >
            <span class="ml-2 text-sm text-gray-600">
              Я согласен с <a href="/terms" class="text-blue-600 hover:underline">условиями сервиса</a> 
              и даю согласие на обработку персональных данных
            </span>
          </label>
        </div>
      </div>

      <!-- Шаг 4: Подтверждение -->
      <div v-show="currentStep === 3" class="animate-fadeIn">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Проверьте данные записи</h3>
        
        <!-- Сводка бронирования -->
        <div class="bg-gray-50 rounded-lg p-6 space-y-4">
          <!-- Мастер -->
          <div class="flex items-center">
            <img 
              :src="master.avatar || '/default-avatar.jpg'"
              :alt="master.name"
              class="w-16 h-16 rounded-full object-cover"
            >
            <div class="ml-4">
              <h4 class="font-medium text-gray-900">{{ master.name }}</h4>
              <div v-if="master.rating && master.reviewsCount" class="flex items-center text-sm text-gray-600">
                <svg class="w-4 h-4 text-yellow-400 mr-1" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                </svg>
                {{ master.rating }} ({{ master.reviewsCount }} отзывов)
              </div>
              <div v-else class="text-sm text-gray-500">
                Новый мастер
              </div>
            </div>
          </div>

          <div class="border-t pt-4 space-y-3">
            <!-- Услуга -->
            <div class="flex justify-between">
              <span class="text-gray-600">Услуга:</span>
              <span class="font-medium">{{ formData.service?.name }}</span>
            </div>

            <!-- Дата и время -->
            <div class="flex justify-between">
              <span class="text-gray-600">Дата и время:</span>
              <span class="font-medium">{{ formatDate(formData.date) }}, {{ formData.time }}</span>
            </div>

            <!-- Место -->
            <div class="flex justify-between">
              <span class="text-gray-600">Место:</span>
              <span class="font-medium">
                {{ formData.locationType === 'salon' ? 'В салоне' : 'С выездом' }}
              </span>
            </div>

            <!-- Адрес -->
            <div v-if="formData.address" class="flex justify-between">
              <span class="text-gray-600">Адрес:</span>
              <span class="font-medium">{{ formData.address }}</span>
            </div>

            <!-- Контакты -->
            <div class="flex justify-between">
              <span class="text-gray-600">Контакт:</span>
              <span class="font-medium">{{ formData.name }}, {{ formData.phone }}</span>
            </div>
          </div>

          <!-- Стоимость -->
          <div class="border-t pt-4">
            <div class="flex justify-between items-baseline">
              <span class="text-gray-600">Услуга:</span>
              <span class="font-medium">{{ formatPrice(formData.service?.price || 0) }}</span>
            </div>
            <div v-if="formData.locationType === 'home'" class="flex justify-between items-baseline mt-2">
              <span class="text-gray-600">Выезд:</span>
              <span class="font-medium">{{ formatPrice(500) }}</span>
            </div>
            <div class="flex justify-between items-baseline mt-3 pt-3 border-t">
              <span class="text-lg font-semibold">Итого:</span>
              <span class="text-xl font-bold text-blue-600">{{ formatPrice(totalPrice) }}</span>
            </div>
          </div>
        </div>

        <!-- Способ оплаты -->
        <div class="mt-6">
          <h4 class="font-medium text-gray-700 mb-3">Способ оплаты</h4>
          <div class="space-y-2">
            <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50">
              <input 
                type="radio" 
                value="cash" 
                v-model="formData.paymentMethod"
                class="text-blue-600"
              >
              <span class="ml-3">Наличными мастеру</span>
            </label>
            <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50">
              <input 
                type="radio" 
                value="card" 
                v-model="formData.paymentMethod"
                class="text-blue-600"
              >
              <span class="ml-3">Картой мастеру</span>
            </label>
          </div>
        </div>
      </div>

      <!-- Кнопки навигации -->
      <div class="flex justify-between pt-6 border-t">
        <button
          v-if="currentStep > 0"
          type="button"
          @click="previousStep"
          class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
        >
          Назад
        </button>
        <div v-else></div>

        <button
          v-if="currentStep < steps.length - 1"
          type="button"
          @click="nextStep"
          :disabled="!canProceed"
          class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:bg-gray-300 disabled:cursor-not-allowed"
        >
          Далее
        </button>

        <button
          v-if="currentStep === steps.length - 1"
          type="submit"
          :disabled="isSubmitting"
          class="px-8 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:bg-gray-300 disabled:cursor-not-allowed flex items-center"
        >
          <span v-if="!isSubmitting">Подтвердить запись</span>
          <span v-else class="flex items-center">
            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Оформляем...
          </span>
        </button>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onUnmounted, type Ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { vMaska } from 'maska'
import BookingCalendar from './Calendar.vue'
import { useBookingStore } from '@/stores/bookingStore'
import { useToast } from '@/src/shared/composables/useToast'
import type {
  BookingFormProps,
  BookingFormEmits,
  FormData,
  FormErrors,
  Step,
  TimeSlot,
  BookingData,
  BookingResult,
  ApiError,
  StepIndex
} from './BookingForm.types'

// Toast для замены alert()
const toast = useToast()

// Props
const props = defineProps<BookingFormProps>()

// Emit
const emit = defineEmits<BookingFormEmits>()

// Подключаем Store
const bookingStore = useBookingStore()

// Состояние формы
const currentStep: Ref<StepIndex> = ref(0)
const isSubmitting: Ref<boolean> = ref(false)
const loadingSlots: Ref<boolean> = ref(false)

const steps: Step[] = [
  { label: 'Услуга' },
  { label: 'Дата и время' },
  { label: 'Контакты' },
  { label: 'Подтверждение' }
]

// Данные формы
const formData: Ref<FormData> = ref({
  service: null,
  date: null,
  time: null,
  locationType: 'salon',
  name: '',
  phone: '',
  email: '',
  address: '',
  comment: '',
  paymentMethod: 'cash',
  agreement: false
})

// Ошибки валидации
const errors: Ref<FormErrors> = ref({})

// Доступные даты и слоты
const availableDates: Ref<string[]> = ref([])
const availableSlots: Ref<TimeSlot[]> = ref([])

// Вычисляемые свойства
const totalPrice = computed(() => {
  let total = formData.value.service?.price || 0
  if (formData.value.locationType === 'home') {
    total += 500 // Доплата за выезд
  }
  return total
})

const canProceed = computed(() => {
  switch (currentStep.value) {
    case 0:
      return formData.value.service !== null
    case 1:
      return formData.value.date && formData.value.time
    case 2:
      return formData.value.name && formData.value.phone && formData.value.agreement &&
        (formData.value.locationType === 'salon' || formData.value.address)
    case 3:
      return formData.value.paymentMethod
    default:
      return false
  }
})

// Методы
const formatPrice = (price: number): string => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    maximumFractionDigits: 0
  }).format(price)
}

const formatDate = (date: string | null): string => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('ru-RU', {
    weekday: 'long',
    day: 'numeric',
    month: 'long'
  })
}

const nextStep = (): void => {
  if (canProceed.value && currentStep.value < steps.length - 1) {
    currentStep.value++
  }
}

const previousStep = (): void => {
  if (currentStep.value > 0) {
    currentStep.value--
  }
}

const loadAvailableSlots = async (date: string): Promise<void> => {
  if (!date) return
  
  loadingSlots.value = true
  try {
    // Используем Store для загрузки слотов
    const slots = await bookingStore.loadTimeSlots(props.master.id, date)
    availableSlots.value = slots
  } catch (error: unknown) {
    console.error('Ошибка загрузки слотов:', error)
    const errorMessage = error instanceof Error ? error.message : 'Неизвестная ошибка'
    toast.error('Ошибка загрузки времени: ' + errorMessage)
    availableSlots.value = []
  } finally {
    loadingSlots.value = false
  }
}

const validateForm = (): boolean => {
  errors.value = {}
  
  if (!formData.value.name) {
    errors.value.name = 'Введите ваше имя'
  }
  
  if (!formData.value.phone || formData.value.phone.length < 18) {
    errors.value.phone = 'Введите корректный номер телефона'
  }
  
  if (formData.value.locationType === 'home' && !formData.value.address) {
    errors.value.address = 'Введите адрес для выезда'
  }
  
  return Object.keys(errors.value).length === 0
}

const handleSubmit = async (): Promise<void> => {
  if (!validateForm()) {
    currentStep.value = 2
    return
  }
  
  isSubmitting.value = true
  
  try {
    // Проверяем обязательные поля
    if (!formData.value.service?.id || !formData.value.date || !formData.value.time) {
      toast.error('Заполните все обязательные поля')
      return
    }
    
    // Подготавливаем данные для Store
    const bookingData: BookingData = {
      masterId: props.master.id,
      serviceId: formData.value.service.id,
      date: formData.value.date,
      time: formData.value.time,
      locationType: formData.value.locationType,
      clientName: formData.value.name,
      clientPhone: formData.value.phone,
      clientEmail: formData.value.email || undefined,
      address: formData.value.address || undefined,
      comment: formData.value.comment || undefined,
      paymentMethod: formData.value.paymentMethod
    }
    
    // Используем Store для создания бронирования
    const result: BookingResult = await bookingStore.createBooking(bookingData)
    
    // Успешное бронирование
    emit('success', result)
    
    // Можно перенаправить на страницу успеха
    // router.visit(`/booking/success/${result.id}`)
  } catch (error: unknown) {
    console.error('❌ Ошибка бронирования:', error)
    
    const apiError = error as ApiError
    
    // Показываем ошибки валидации
    if (apiError.response?.status === 422) {
      errors.value = apiError.response.data.errors
      currentStep.value = 2 // Возвращаемся к форме
    } else {
      // Показываем общую ошибку
      const errorMessage = apiError.message || 'Произошла ошибка при создании записи'
      toast.error(errorMessage)
    }
  } finally {
    isSubmitting.value = false
  }
}

// Загрузка доступных дат при инициализации
const loadAvailableDates = async (): Promise<void> => {
  try {
    const dates = await bookingStore.loadAvailableDates(props.master.id)
    availableDates.value = dates
  } catch (error: unknown) {
    console.error('Ошибка загрузки дат:', error)
    const errorMessage = error instanceof Error ? error.message : 'Неизвестная ошибка'
    toast.error('Ошибка загрузки доступных дат: ' + errorMessage)
    // Используем тестовые данные если API не работает
    availableDates.value = bookingStore.generateTestDates()
  }
}

// Инициализация
watch(() => props.master, async (master) => {
  if (master && master.id) {
    await loadAvailableDates()
  }
}, { immediate: true })

// Очистка при закрытии
onUnmounted(() => {
  bookingStore.resetCurrentBooking()
})
</script>

<style scoped>
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

.animate-fadeIn {
  animation: fadeIn 0.3s ease-out;
}

/* Стили для календаря загрузятся из компонента Calendar.vue */
</style>