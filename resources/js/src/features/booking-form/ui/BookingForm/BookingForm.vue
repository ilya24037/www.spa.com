<template>
  <!-- Error состояние -->
  <ErrorState
    v-if="errorState.error.value"
    :error="errorState.error.value"
    size="medium"
    variant="card"
    :retryable="true"
    :dismissible="true"
    class="mb-6"
    @retry="handleRetry"
    @dismiss="errorState.clearError"
  />
  
  <!-- Основная форма -->
  <div v-else class="booking-form">
    <!-- Заголовок -->
    <div class="mb-6">
      <h3 class="text-lg font-semibold text-gray-500 mb-2">
        Данные для записи
      </h3>
      <p class="text-sm text-gray-500">
        Укажите ваши контактные данные для подтверждения записи
      </p>
    </div>

    <!-- Форма -->
    <form class="space-y-6" @submit.prevent="handleSubmit">
      <!-- Имя клиента -->
      <div>
        <label for="clientName" class="block text-sm font-medium text-gray-500 mb-2">
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
              : 'border-gray-500 focus:border-blue-500 focus:ring-blue-500'
          ]"
          @blur="validateField('clientName')"
        >
        <p v-if="errors.clientName" class="mt-1 text-sm text-red-600">
          {{ errors.clientName }}
        </p>
      </div>

      <!-- Телефон клиента -->
      <div>
        <label for="clientPhone" class="block text-sm font-medium text-gray-500 mb-2">
          Телефон <span class="text-red-500">*</span>
        </label>
        <input
          id="clientPhone"
          v-model="form.clientPhone"
          type="tel"
          placeholder="+7 (___) ___-__-__"
          :class="[
            'w-full px-3 py-2 border rounded-lg text-sm transition-colors',
            errors.clientPhone 
              ? 'border-red-300 focus:border-red-500 focus:ring-red-500' 
              : 'border-gray-500 focus:border-blue-500 focus:ring-blue-500'
          ]"
          @blur="validateField('clientPhone')"
          @input="formatPhone"
        >
        <p v-if="errors.clientPhone" class="mt-1 text-sm text-red-600">
          {{ errors.clientPhone }}
        </p>
      </div>

      <!-- Email клиента (необязательно) -->
      <div>
        <label for="clientEmail" class="block text-sm font-medium text-gray-500 mb-2">
          Email (необязательно)
        </label>
        <input
          id="clientEmail"
          v-model="form.clientEmail"
          type="email"
          placeholder="example@email.com"
          :class="[
            'w-full px-3 py-2 border rounded-lg text-sm transition-colors',
            errors.clientEmail 
              ? 'border-red-300 focus:border-red-500 focus:ring-red-500' 
              : 'border-gray-500 focus:border-blue-500 focus:ring-blue-500'
          ]"
          @blur="validateField('clientEmail')"
        >
        <p v-if="errors.clientEmail" class="mt-1 text-sm text-red-600">
          {{ errors.clientEmail }}
        </p>
      </div>

      <!-- Дополнительные заметки -->
      <div>
        <label for="notes" class="block text-sm font-medium text-gray-500 mb-2">
          Дополнительные пожелания
        </label>
        <textarea
          id="notes"
          v-model="form.notes"
          rows="3"
          placeholder="Опишите особые пожелания или уточнения..."
          class="w-full px-3 py-2 border border-gray-500 rounded-lg text-sm transition-colors focus:border-blue-500 focus:ring-blue-500"
        />
      </div>

      <!-- Согласие на обработку данных -->
      <div>
        <div class="flex items-start">
          <div class="flex items-center h-5">
            <input
              id="dataProcessingConsent"
              v-model="form.dataProcessingConsent"
              type="checkbox"
              :class="[
                'h-4 w-4 rounded border-gray-500 text-blue-600 transition-colors',
                errors.dataProcessingConsent 
                  ? 'focus:ring-red-500' 
                  : 'focus:ring-blue-500'
              ]"
              @change="validateField('dataProcessingConsent')"
            >
          </div>
          <div class="ml-3 text-sm">
            <label for="dataProcessingConsent" class="font-medium text-gray-500">
              Согласие на обработку персональных данных <span class="text-red-500">*</span>
            </label>
            <p class="text-gray-500">
              Я согласен на обработку моих персональных данных в соответствии с 
              <a href="/privacy" class="text-blue-600 hover:text-blue-800">политикой конфиденциальности</a>
            </p>
          </div>
        </div>
        <p v-if="errors.dataProcessingConsent" class="mt-1 text-sm text-red-600">
          {{ errors.dataProcessingConsent }}
        </p>
      </div>

      <!-- Информация о записи -->
      <div v-if="props.bookingInfo" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <h4 class="font-medium text-blue-900 mb-3">
          Детали записи
        </h4>
        <div class="space-y-2 text-sm text-blue-800">
          <div v-if="props.bookingInfo.datetime" class="flex justify-between">
            <span>Дата и время:</span>
            <span class="font-medium">{{ formatDateTime(props.bookingInfo.datetime) }}</span>
          </div>
          <div v-if="props.bookingInfo.service" class="flex justify-between">
            <span>Услуга:</span>
            <span class="font-medium">{{ props.bookingInfo.service.name }}</span>
          </div>
          <div v-if="props.bookingInfo.service?.duration" class="flex justify-between">
            <span>Продолжительность:</span>
            <span class="font-medium">{{ props.bookingInfo.service.duration }} мин</span>
          </div>
          <div v-if="props.bookingInfo.service?.price" class="flex justify-between items-center">
            <span>Стоимость:</span>
            <span class="font-semibold text-lg text-green-600">
              {{ formatPrice(props.bookingInfo.service.price) }} ₽
            </span>
          </div>
        </div>
      </div>

      <!-- Кнопки действий -->
      <div class="flex flex-col sm:flex-row gap-3">
        <button
          type="button"
          class="px-4 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-500 rounded-lg hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors"
          @click="emit('cancel')"
        >
          Отменить
        </button>
        
        <button
          type="submit"
          :disabled="!isValid || props.loading"
          :class="[
            'px-4 py-2 text-sm font-medium text-white rounded-lg transition-colors flex items-center justify-center',
            isValid && !props.loading
              ? 'bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500'
              : 'bg-gray-500 cursor-not-allowed'
          ]"
        >
          <svg
            v-if="props.loading"
            class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
            fill="none"
            viewBox="0 0 24 24"
          >
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
          {{ props.loading ? 'Создание записи...' : 'Записаться' }}
        </button>
      </div>

      <!-- Ошибки формы -->
      <div v-if="formError" class="bg-red-50 border border-red-200 rounded-lg p-4">
        <div class="flex">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="ml-3">
            <p class="text-sm text-red-800">
              {{ formError }}
            </p>
          </div>
        </div>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, withDefaults, defineProps, defineEmits } from 'vue'
import { useErrorHandler } from '@/src/shared/composables/useErrorHandler'
import { ErrorState } from '@/src/shared/ui/molecules/ErrorState'

// Типы
interface BookingService {
  name: string
  duration?: number
  price?: number
}

interface BookingInfo {
  datetime: string
  service?: BookingService
}

interface BookingFormData {
  clientName: string
  clientPhone: string
  clientEmail: string
  notes: string
  dataProcessingConsent: boolean
}

interface BookingFormProps {
  bookingInfo?: BookingInfo | null
  loading?: boolean
}

interface BookingFormEmits {
  submit: [bookingData: BookingFormData]
  cancel: []
  'form-change': [data: { isValid: boolean; formData: BookingFormData }]
  retryRequested: []
}

type ValidateFieldName = keyof Pick<BookingFormData, 'clientName' | 'clientPhone' | 'clientEmail' | 'dataProcessingConsent'>

// Props и Emits
const props = withDefaults(defineProps<BookingFormProps>(), {
    bookingInfo: null,
    loading: false
})

const emit = defineEmits<BookingFormEmits>()

// Error handler
const errorState = useErrorHandler(false)

// Состояние формы
const form = ref<BookingFormData>({
    clientName: '',
    clientPhone: '',
    clientEmail: '',
    notes: '',
    dataProcessingConsent: false
})

const errors = ref<Record<string, string>>({})
const formError = ref<string | null>(null)

// Вычисляемые свойства
const isValid = computed(() => {
    return form.value.clientName.trim() !== '' &&
         form.value.clientPhone.trim() !== '' &&
         form.value.dataProcessingConsent &&
         Object.keys(errors.value).length === 0
})

// Методы валидации
const validateField = (fieldName: ValidateFieldName): void => {
    try {
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

        // Эмитируем изменения формы
        emit('form-change', {
            isValid: isValid.value,
            formData: form.value
        })

    } catch (error: any) {
        errorState.handleError(error, 'validation' as any)
    }
}

// Форматирование телефона
const formatPhone = (event: Event): void => {
    const input = event.target as HTMLInputElement
    let value = input.value.replace(/\D/g, '')
  
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
    if (value.length >= 5) {
        formatted += ') ' + value.slice(4, 7)
    }
    if (value.length >= 8) {
        formatted += '-' + value.slice(7, 9)
    }
    if (value.length >= 10) {
        formatted += '-' + value.slice(9, 11)
    }
  
    form.value.clientPhone = formatted
}

// Валидация всей формы
const validateForm = (): boolean => {
    const fieldsToValidate: ValidateFieldName[] = ['clientName', 'clientPhone', 'clientEmail', 'dataProcessingConsent']
  
    fieldsToValidate.forEach(field => validateField(field))
  
    return isValid.value
}

// Обработка отправки формы
const handleSubmit = async (): Promise<void> => {
    try {
        formError.value = null
    
        if (!validateForm()) {
            formError.value = 'Пожалуйста, исправьте ошибки в форме'
            return
        }

        emit('submit', form.value)

    } catch (error: any) {
        const errorMessage = error?.message || 'Произошла ошибка при создании записи'
        formError.value = errorMessage
        errorState.handleError(error, 'form_submit' as any)
    }
}

// Метод для повторной попытки после ошибки
const handleRetry = async (): Promise<void> => {
    errorState.clearError()
    emit('retryRequested')
}

// Вспомогательные функции
const formatDateTime = (dateTime: string): string => {
    try {
        return new Date(dateTime).toLocaleDateString('ru-RU', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        })
    } catch {
        return dateTime
    }
}

const formatPrice = (price: number): string => {
    return new Intl.NumberFormat('ru-RU').format(price)
}

// Очистка формы при необходимости
const resetForm = (): void => {
    form.value = {
        clientName: '',
        clientPhone: '',
        clientEmail: '',
        notes: '',
        dataProcessingConsent: false
    }
    errors.value = {}
    formError.value = null
}

// Экспорт методов для внешнего использования
defineExpose({
    resetForm,
    validateForm,
    form: computed(() => form.value),
    isValid
})

// Наблюдатели
watch(() => form.value, () => {
    emit('form-change', {
        isValid: isValid.value,
        formData: form.value
    })
}, { deep: true })
</script>

<style scoped>
.booking-form {
  @apply max-w-2xl;
}

/* Анимации для ошибок */
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>