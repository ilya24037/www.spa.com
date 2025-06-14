<template>
  <!-- Телепорт в body для правильного z-index -->
  <Teleport to="body">
    <!-- Оверлей и контейнер -->
    <Transition
      enter-active-class="transition-all duration-300 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-all duration-200 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div 
        v-if="show"
        class="fixed inset-0 z-50 overflow-y-auto"
        @click.self="handleBackdropClick"
      >
        <!-- Затемненный фон -->
        <div class="fixed inset-0 bg-black bg-opacity-50" />
        
        <!-- Центрирующий контейнер -->
        <div class="flex min-h-full items-center justify-center p-4">
          <!-- Модальное окно -->
          <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 scale-95 translate-y-4"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100 scale-100 translate-y-0"
            leave-to-class="opacity-0 scale-95 translate-y-4"
          >
            <div 
              v-if="show"
              class="relative w-full max-w-4xl bg-white rounded-2xl shadow-2xl"
              @click.stop
            >
              <!-- Шапка модального окна -->
              <div class="sticky top-0 z-10 bg-white border-b rounded-t-2xl">
                <div class="flex items-center justify-between px-6 py-4">
                  <div>
                    <h2 class="text-2xl font-bold text-gray-900">
                      Запись к мастеру
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                      {{ master.name }} • {{ master.specialization }}
                    </p>
                  </div>
                  
                  <!-- Кнопка закрытия -->
                  <button
                    @click="close"
                    class="p-2 rounded-lg hover:bg-gray-100 transition-colors group"
                    aria-label="Закрыть"
                  >
                    <svg 
                      class="w-6 h-6 text-gray-400 group-hover:text-gray-600" 
                      fill="none" 
                      viewBox="0 0 24 24" 
                      stroke="currentColor"
                    >
                      <path 
                        stroke-linecap="round" 
                        stroke-linejoin="round" 
                        stroke-width="2" 
                        d="M6 18L18 6M6 6l12 12" 
                      />
                    </svg>
                  </button>
                </div>
              </div>
              
              <!-- Контент модального окна -->
              <div class="px-6 py-6 max-h-[calc(100vh-200px)] overflow-y-auto">
                <!-- Информация о мастере -->
                <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-lg mb-6">
                  <img 
                    :src="master.photo || '/images/default-avatar.jpg'"
                    :alt="master.name"
                    class="w-16 h-16 rounded-full object-cover"
                  >
                  <div class="flex-1">
                    <h3 class="font-semibold text-gray-900">{{ master.name }}</h3>
                    <div class="flex items-center gap-4 text-sm text-gray-600 mt-1">
                      <div class="flex items-center">
                        <svg class="w-4 h-4 text-yellow-400 mr-1" fill="currentColor" viewBox="0 0 20 20">
                          <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        {{ master.rating }} ({{ master.reviews_count }} отзывов)
                      </div>
                      <span v-if="master.is_verified" class="flex items-center text-green-600">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        Проверен
                      </span>
                    </div>
                  </div>
                  <div class="text-right">
                    <p class="text-sm text-gray-500">от</p>
                    <p class="text-xl font-bold text-gray-900">{{ formatPrice(master.min_price) }}</p>
                  </div>
                </div>
                
                <!-- Форма бронирования -->
                <BookingForm 
                  :master="master"
                  @success="handleBookingSuccess"
                  @close="close"
                />
              </div>
              
              <!-- Мобильная кнопка закрытия -->
              <div class="lg:hidden sticky bottom-0 bg-white border-t px-6 py-4">
                <button
                  @click="close"
                  class="w-full py-3 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 transition-colors"
                >
                  Отмена
                </button>
              </div>
            </div>
          </Transition>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue'
import BookingForm from './BookingForm.vue'

// Props
const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  master: {
    type: Object,
    required: true
  }
})

// Emit
const emit = defineEmits(['close', 'success'])

// Методы
const close = () => {
  emit('close')
}

const handleBackdropClick = () => {
  close()
}

const handleBookingSuccess = (booking) => {
  emit('success', booking)
  close()
}

const formatPrice = (price) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    maximumFractionDigits: 0
  }).format(price)
}

// Обработка клавиши Escape
const handleEscape = (e) => {
  if (e.key === 'Escape' && props.show) {
    close()
  }
}

// Блокировка скролла body при открытии модального окна
watch(() => props.show, (newValue) => {
  if (newValue) {
    document.body.style.overflow = 'hidden'
  } else {
    document.body.style.overflow = ''
  }
})

// Слушатели событий
onMounted(() => {
  document.addEventListener('keydown', handleEscape)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleEscape)
  document.body.style.overflow = ''
})
</script>

<style scoped>
/* Дополнительные стили для плавности анимаций */
.transition-all {
  transition-property: all;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}

/* Стили для скроллбара в контенте */
.overflow-y-auto {
  scrollbar-width: thin;
  scrollbar-color: #e5e7eb #f9fafb;
}

.overflow-y-auto::-webkit-scrollbar {
  width: 8px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  background: #f9fafb;
  border-radius: 4px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  background: #e5e7eb;
  border-radius: 4px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: #d1d5db;
}

/* Адаптация для мобильных устройств */
@media (max-width: 640px) {
  .max-w-4xl {
    max-width: 100%;
    margin: 0;
    height: 100vh;
    border-radius: 0;
  }
  
  .rounded-2xl {
    border-radius: 0;
  }
  
  .rounded-t-2xl {
    border-radius: 0;
  }
  
  .max-h-[calc(100vh-200px)] {
    max-height: calc(100vh - 140px);
  }
}
</style>