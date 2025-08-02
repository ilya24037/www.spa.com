<template>
  <div class="booking-form">
    <!-- Заголовок -->
    <div class="mb-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-2">
        Данные для записи
      </h3>
      <p class="text-sm text-gray-600">
        Укажите ваши контактные данные для подтверждения записи
      </p>
    </div>

    <!-- Форма -->
    <form @submit.prevent="handleSubmit" class="space-y-6">
      
      <!-- Имя клиента -->
      <div>
        <label for="clientName" class="block text-sm font-medium text-gray-700 mb-2">
          Ваше имя <span class="text-red-500">*</span>
        </label>
        <input
          id="clientName"
          v-model="form.clientName"
          type="text"
          placeholder="Введите ваше имя"
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

      <!-- Телефон -->
      <div>
        <label for="clientPhone" class="block text-sm font-medium text-gray-700 mb-2">
          Номер телефона <span class="text-red-500">*</span>
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
          На этот номер придет SMS с подтверждением записи
        </p>
      </div>

      <!-- Email (опционально) -->
      <div>
        <label for="clientEmail" class="block text-sm font-medium text-gray-700 mb-2">
          Email (необязательно)
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

      <!-- Комментарий к записи -->
      <div>
        <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
          Комментарий к записи
        </label>
        <textarea
          id="notes"
          v-model="form.notes"
          rows="3"
          placeholder="Дополнительные пожелания или вопросы..."
          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 transition-colors resize-none"
          maxlength="500"
        ></textarea>
        <p class="mt-1 text-xs text-gray-500">
          {{ form.notes.length }}/500 символов
        </p>
      </div>

      <!-- Согласие на обработку данных -->
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
          Я согласен на 
          <a href="/privacy" target="_blank" class="text-blue-600 hover:text-blue-800 underline">
            обработку персональных данных
          </a>
          <span class="text-red-500">*</span>
        </label>
      </div>
      <p v-if="errors.dataProcessingConsent" class="text-sm text-red-600">
        {{ errors.dataProcessingConsent }}
      </p>

      <!-- Информация о записи -->
      <div v-if="bookingInfo" class="bg-gray-50 rounded-lg p-4">
        <h4 class="text-sm font-medium text-gray-900 mb-3">Детали записи:</h4>
        <div class="space-y-2 text-sm text-gray-700">
          <div class="flex justify-between">
            <span>Дата и время:</span>
            <span class="font-medium">{{ formatDateTime(bookingInfo.datetime) }}</span>
          </div>
          <div v-if="bookingInfo.service" class="flex justify-between">
            <span>Услуга:</span>
            <span class="font-medium">{{ bookingInfo.service.name }}</span>
          </div>
          <div v-if="bookingInfo.service?.duration" class="flex justify-between">
            <span>Продолжительность:</span>
            <span class="font-medium">{{ bookingInfo.service.duration }} мин</span>
          </div>
          <div v-if="bookingInfo.service?.price" class="flex justify-between items-center">
            <span>Стоимость:</span>
            <span class="font-semibold text-lg text-green-600">
              {{ formatPrice(bookingInfo.service.price) }} ₽
            </span>
          </div>
        </div>
      </div>

      <!-- Кнопки действий -->
      <div class="flex flex-col sm:flex-row gap-3">
        <button
          type="button"
          @click="$emit('cancel')"
          class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors"
        >
          Отменить
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
          {{ loading ? 'Создание записи...' : 'Записаться' }}
        </button>
      </div>

      <!-- Ошибки формы -->
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

<script setup>
import { ref, computed, watch } from 'vue'
import dayjs from 'dayjs'
import 'dayjs/locale/ru'

// Настройка dayjs
dayjs.locale('ru')

// Props
const props = defineProps({
  bookingInfo: {
    type: Object,
    default: null
  },
  loading: {
    type: Boolean,
    default: false
  }
})

// Events
const emit = defineEmits(['submit', 'cancel', 'form-change'])

// Состояние формы
const form = ref({
  clientName: '',
  clientPhone: '',
  clientEmail: '',
  notes: '',
  dataProcessingConsent: false
})

const errors = ref({})
const formError = ref(null)

// Вычисляемые свойства
const isValid = computed(() => {
  return form.value.clientName.trim() !== '' &&
         form.value.clientPhone.trim() !== '' &&
         form.value.dataProcessingConsent &&
         Object.keys(errors.value).length === 0
})

// Методы валидации
const validateField = (fieldName) => {
  errors.value = { ...errors.value }
  delete errors.value[fieldName]

  switch (fieldName) {
    case 'clientName':
      if (!form.value.clientName.trim()) {
        errors.value.clientName = 'Укажите ваше имя'
      } else if (form.value.clientName.trim().length < 2) {
        errors.value.clientName = 'Имя должно содержать минимум 2 символа'
      }
      break

    case 'clientPhone':
      const phoneRegex = /^\+7 \(\d{3}\) \d{3}-\d{2}-\d{2}$/
      if (!form.value.clientPhone.trim()) {
        errors.value.clientPhone = 'Укажите номер телефона'
      } else if (!phoneRegex.test(form.value.clientPhone)) {
        errors.value.clientPhone = 'Некорректный формат телефона'
      }
      break

    case 'clientEmail':
      if (form.value.clientEmail.trim()) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
        if (!emailRegex.test(form.value.clientEmail)) {
          errors.value.clientEmail = 'Некорректный формат email'
        }
      }
      break

    case 'dataProcessingConsent':
      if (!form.value.dataProcessingConsent) {
        errors.value.dataProcessingConsent = 'Необходимо согласие на обработку данных'
      }
      break
  }
}

const validateForm = () => {
  validateField('clientName')
  validateField('clientPhone')
  validateField('clientEmail')
  
  if (!form.value.dataProcessingConsent) {
    errors.value.dataProcessingConsent = 'Необходимо согласие на обработку данных'
  }

  return Object.keys(errors.value).length === 0
}

// Методы форматирования
const formatPhone = (event) => {
  let value = event.target.value.replace(/\D/g, '')
  
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
}

const formatDateTime = (datetime) => {
  return dayjs(datetime).format('DD MMMM YYYY в HH:mm')
}

const formatPrice = (price) => {
  return new Intl.NumberFormat('ru-RU').format(price)
}

// Обработчики событий
const handleSubmit = () => {
  formError.value = null
  
  if (!validateForm()) {
    formError.value = 'Проверьте правильность заполнения формы'
    return
  }

  if (!props.bookingInfo) {
    formError.value = 'Сначала выберите дату и время записи'
    return
  }

  // Подготавливаем данные для отправки
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
}

// Наблюдатели
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

/* Анимация для полей с ошибками */
.booking-form input.border-red-300,
.booking-form textarea.border-red-300 {
  @apply animate-pulse;
}

/* Стилизация чекбокса */
input[type="checkbox"]:checked {
  background-image: url("data:image/svg+xml,%3csvg viewBox='0 0 16 16' fill='white' xmlns='http://www.w3.org/2000/svg'%3e%3cpath d='m13.854 3.646-7.5 7.5a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L6 10.293l7.146-7.147a.5.5 0 0 1 .708.708z'/%3e%3c/svg%3e");
}

/* Анимация загрузки */
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