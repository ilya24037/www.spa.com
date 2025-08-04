<template>
  <div class="booking-widget">
    <!-- Заголовок виджета -->
    <div class="mb-6">
      <h2 class="text-xl font-bold text-gray-900 mb-2">
        Записаться к мастеру
      </h2>
      <p class="text-sm text-gray-600">
        Выберите удобное время и оставьте заявку
      </p>
    </div>

    <!-- Индикатор этапов -->
    <div class="mb-6">
      <div class="flex items-center">
        <!-- Этап 1: Выбор времени -->
        <div class="flex items-center">
          <div :class="[
            'w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium',
            currentStep >= 1 ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-600'
          ]">
            1
          </div>
          <span :class="[
            'ml-2 text-sm font-medium',
            currentStep >= 1 ? 'text-blue-600' : 'text-gray-500'
          ]">
            Время
          </span>
        </div>

        <!-- Разделитель -->
        <div :class="[
          'flex-1 h-0.5 mx-4',
          currentStep >= 2 ? 'bg-blue-600' : 'bg-gray-200'
        ]"></div>

        <!-- Этап 2: Заполнение формы -->
        <div class="flex items-center">
          <div :class="[
            'w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium',
            currentStep >= 2 ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-600'
          ]">
            2
          </div>
          <span :class="[
            'ml-2 text-sm font-medium',
            currentStep >= 2 ? 'text-blue-600' : 'text-gray-500'
          ]">
            Данные
          </span>
        </div>

        <!-- Разделитель -->
        <div :class="[
          'flex-1 h-0.5 mx-4',
          currentStep >= 3 ? 'bg-blue-600' : 'bg-gray-200'
        ]"></div>

        <!-- Этап 3: Подтверждение -->
        <div class="flex items-center">
          <div :class="[
            'w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium',
            currentStep >= 3 ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-600'
          ]">
            ✓
          </div>
          <span :class="[
            'ml-2 text-sm font-medium',
            currentStep >= 3 ? 'text-green-600' : 'text-gray-500'
          ]">
            Готово
          </span>
        </div>
      </div>
    </div>

    <!-- Содержимое этапов -->
    <div class="min-h-[400px]">
      
      <!-- Этап 1: Выбор времени -->
      <div v-if="currentStep === 1" class="animate-fade-in">
        <BookingCalendar
          :master-id="master.id"
          :selected-service="selectedService"
          @selection-change="handleTimeSelection"
        />
        
        <div v-if="bookingData.date && bookingData.time" class="mt-6">
          <button
            @click="nextStep"
            class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 transition-colors font-medium"
          >
            Продолжить к заполнению данных
          </button>
        </div>
      </div>

      <!-- Этап 2: Заполнение формы -->
      <div v-else-if="currentStep === 2" class="animate-fade-in">
        <BookingForm
          :booking-info="bookingData"
          :loading="submitting"
          @submit="handleFormSubmit"
          @cancel="prevStep"
        />
      </div>

      <!-- Этап 3: Успешное создание -->
      <div v-else-if="currentStep === 3" class="animate-fade-in">
        <div class="text-center py-8">
          <!-- Иконка успеха -->
          <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4">
            <svg class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
          </div>

          <!-- Сообщение об успехе -->
          <h3 class="text-lg font-semibold text-gray-900 mb-2">
            Запись успешно создана!
          </h3>
          <p class="text-sm text-gray-600 mb-6">
            Мастер получил уведомление о вашей записи и свяжется с вами для подтверждения
          </p>

          <!-- Детали записи -->
          <div class="bg-green-50 rounded-lg p-4 mb-6 text-left">
            <h4 class="font-medium text-gray-900 mb-3">Детали записи:</h4>
            <div class="space-y-2 text-sm text-gray-700">
              <div class="flex justify-between">
                <span>Номер записи:</span>
                <span class="font-medium">{{ createdBooking.bookingNumber }}</span>
              </div>
              <div class="flex justify-between">
                <span>Дата и время:</span>
                <span class="font-medium">{{ formatDateTime(bookingData.datetime) }}</span>
              </div>
              <div v-if="selectedService" class="flex justify-between">
                <span>Услуга:</span>
                <span class="font-medium">{{ selectedService.name }}</span>
              </div>
              <div class="flex justify-between">
                <span>Мастер:</span>
                <span class="font-medium">{{ master.name }}</span>
              </div>
            </div>
          </div>

          <!-- Действия -->
          <div class="space-y-3">
            <button
              @click="reset"
              class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 transition-colors font-medium"
            >
              Записаться еще раз
            </button>
            <button
              @click="$emit('close')"
              class="w-full text-gray-600 py-2 px-4 rounded-lg hover:bg-gray-50 transition-colors"
            >
              Закрыть
            </button>
          </div>
        </div>
      </div>

      <!-- Ошибка создания записи -->
      <div v-else-if="currentStep === 'error'" class="animate-fade-in">
        <div class="text-center py-8">
          <!-- Иконка ошибки -->
          <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-4">
            <svg class="h-8 w-8 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>

          <!-- Сообщение об ошибке -->
          <h3 class="text-lg font-semibold text-gray-900 mb-2">
            Ошибка при создании записи
          </h3>
          <p class="text-sm text-gray-600 mb-6">
            {{ errorMessage || 'Произошла ошибка. Попробуйте еще раз.' }}
          </p>

          <!-- Действия -->
          <div class="space-y-3">
            <button
              @click="currentStep = 2"
              class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 transition-colors font-medium"
            >
              Попробовать еще раз
            </button>
            <button
              @click="reset"
              class="w-full text-gray-600 py-2 px-4 rounded-lg hover:bg-gray-50 transition-colors"
            >
              Начать сначала
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Навигация (только для первых двух этапов) -->
    <div v-if="currentStep <= 2 && currentStep !== 'error'" class="mt-6 flex justify-between">
      <button
        v-if="currentStep > 1"
        @click="prevStep"
        class="flex items-center text-gray-600 hover:text-gray-800 transition-colors"
      >
        <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        Назад
      </button>
      <div v-else></div>

      <div class="text-sm text-gray-500">
        Этап {{ currentStep }} из 2
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import dayjs from 'dayjs'
import 'dayjs/locale/ru'
import { BookingCalendar } from '../BookingCalendar'
import { BookingForm } from '../../../features/booking-form/ui/BookingForm'

// Настройка dayjs
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

// Состояние компонента
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

// Методы навигации
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

// Обработчики событий
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
    // Имитация API вызова - замените на реальный API
    await new Promise((resolve, reject) => {
      setTimeout(() => {
        // Случайно имитируем ошибку для демонстрации
        if (Math.random() > 0.8) {
          reject(new Error('Время уже занято. Выберите другое время.'))
        } else {
          resolve()
        }
      }, 2000)
    })

    // Успешное создание записи
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

// Вспомогательные методы
const formatDateTime = (datetime) => {
  return dayjs(datetime).format('DD MMMM YYYY в HH:mm')
}

// Наблюдатели
watch(() => props.isOpen, (isOpen) => {
  if (!isOpen) {
    // Сброс состояния при закрытии
    setTimeout(() => {
      reset()
    }, 300) // Задержка для плавного закрытия
  }
})

watch(() => props.selectedService, (newService) => {
  // Обновляем услугу в данных бронирования
  if (bookingData.value.service !== newService) {
    bookingData.value.service = newService
  }
})
</script>

<style scoped>
.booking-widget {
  @apply max-w-full;
}

/* Анимации переходов между этапами */
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

/* Плавные переходы для индикатора этапов */
.booking-widget div[class*="bg-blue-600"],
.booking-widget div[class*="bg-green-600"] {
  transition: all 0.3s ease-in-out;
}

/* Стилизация кнопок */
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