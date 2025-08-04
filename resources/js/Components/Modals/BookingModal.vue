<template>
  <div v-if="show" class="modal-overlay" @click="$emit('close')">
    <div class="modal-content" @click.stop>
      <div class="modal-header">
        <h3 class="modal-title">Записаться к {{ master.display_name }}</h3>
        <button @click="$emit('close')" class="modal-close">
          <XMarkIcon class="w-6 h-6" />
        </button>
      </div>
      
      <div class="modal-body">
        <p class="text-gray-600 mb-4">
          Выберите удобное время для записи
        </p>
        
        <div class="booking-form">
          <div class="form-group">
            <label class="form-label">Дата</label>
            <input type="date" class="form-input" v-model="booking.date">
          </div>
          
          <div class="form-group">
            <label class="form-label">Время</label>
            <select class="form-input" v-model="booking.time">
              <option value="">Выберите время</option>
              <option value="10:00">10:00</option>
              <option value="11:00">11:00</option>
              <option value="12:00">12:00</option>
              <option value="13:00">13:00</option>
              <option value="14:00">14:00</option>
              <option value="15:00">15:00</option>
              <option value="16:00">16:00</option>
              <option value="17:00">17:00</option>
              <option value="18:00">18:00</option>
              <option value="19:00">19:00</option>
              <option value="20:00">20:00</option>
            </select>
          </div>
          
          <div class="form-group">
            <label class="form-label">Ваше имя</label>
            <input type="text" class="form-input" v-model="booking.name" placeholder="Введите ваше имя">
          </div>
          
          <div class="form-group">
            <label class="form-label">Телефон</label>
            <input type="tel" class="form-input" v-model="booking.phone" placeholder="+7 (999) 123-45-67">
          </div>
        </div>
        
        <div class="modal-actions">
          <button @click="submitBooking" class="submit-btn">
            Записаться
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'
import { useToast } from '@/src/shared/composables/useToast'

// Toast для замены alert()
const toast = useToast()

const props = defineProps({
  show: Boolean,
  master: Object
})

const emit = defineEmits(['close'])

const booking = ref({
  date: '',
  time: '',
  name: '',
  phone: ''
})

const submitBooking = () => {
  // Логика отправки бронирования
  console.log('Бронирование:', booking.value)
  toast.success('Заявка отправлена! Мастер свяжется с вами.')
  emit('close')
}
</script>

<style scoped>
.modal-overlay {
  @apply fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50;
}

.modal-content {
  @apply bg-white rounded-lg p-6 max-w-md w-full mx-4 max-h-[90vh] overflow-y-auto;
}

.modal-header {
  @apply flex justify-between items-center mb-4;
}

.modal-title {
  @apply text-xl font-bold text-gray-900;
}

.modal-close {
  @apply text-gray-400 hover:text-gray-600;
}

.form-group {
  @apply mb-4;
}

.form-label {
  @apply block text-sm font-medium text-gray-700 mb-2;
}

.form-input {
  @apply w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent;
}

.modal-actions {
  @apply mt-6;
}

.submit-btn {
  @apply w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700 transition-colors;
}
</style>