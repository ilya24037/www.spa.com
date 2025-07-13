<template>
  <Teleport to="body">
    <div 
      v-if="show"
      class="fixed inset-0 z-50 overflow-y-auto bg-black bg-opacity-50 flex items-center justify-center p-4"
      @click.self="$emit('close')"
    >
      <div class="bg-white rounded-xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
        <!-- Заголовок модалки -->
        <div class="sticky top-0 bg-white border-b px-6 py-4 flex items-center justify-between">
          <div>
            <h2 class="text-xl font-bold text-gray-900">Предварительный просмотр</h2>
            <p class="text-sm text-gray-600">Так будет выглядеть ваша анкета для клиентов</p>
          </div>
          <button 
            @click="$emit('close')"
            class="p-2 hover:bg-gray-100 rounded-lg transition-colors"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Контент превью -->
        <div class="p-6">
          <!-- Предупреждение о незавершённых разделах -->
          <div v-if="incompleteFields.length" class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
            <div class="flex items-center mb-2">
              <svg class="w-5 h-5 text-yellow-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.98-.833-2.75 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
              </svg>
              <span class="font-medium text-yellow-800">Внимание: анкета не полностью заполнена</span>
            </div>
            <p class="text-yellow-700 text-sm mb-2">Рекомендуем заполнить эти поля для лучшего результата:</p>
            <ul class="text-yellow-700 text-sm list-disc list-inside">
              <li v-for="field in incompleteFields" :key="field">{{ field }}</li>
            </ul>
          </div>

          <!-- Превью анкеты -->
          <div class="bg-gray-50 rounded-xl p-6">
            <!-- Шапка профиля -->
            <div class="bg-white rounded-lg p-6 mb-6 shadow-sm">
              <div class="flex items-start gap-6">
                <!-- Аватар -->
                <div class="flex-shrink-0">
                  <div 
                    v-if="mainPhoto"
                    class="w-24 h-24 rounded-full overflow-hidden bg-gray-200"
                  >
                    <img 
                      :src="mainPhoto.preview || mainPhoto.url" 
                      :alt="formData.display_name"
                      class="w-full h-full object-cover"
                    />
                  </div>
                  <div 
                    v-else
                    class="w-24 h-24 rounded-full bg-gray-200 flex items-center justify-center"
                  >
                    <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                  </div>
                </div>

                <!-- Основная информация -->
                <div class="flex-1">
                  <div class="flex items-center gap-3 mb-2">
                    <h1 class="text-2xl font-bold text-gray-900">
                      {{ formData.display_name || 'Имя не указано' }}
                    </h1>
                    <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs font-medium">
                      Новый мастер
                    </span>
                  </div>
                  
                  <div class="flex items-center gap-4 text-sm text-gray-600 mb-3">
                    <span v-if="formData.age">{{ formData.age }} лет</span>
                    <span v-if="formData.experience_years">
                      Опыт {{ formData.experience_years }} {{ getYearWord(formData.experience_years) }}
                    </span>
                  </div>

                  <div class="flex items-center gap-2 mb-3">
                    <div class="flex text-yellow-400">
                      <svg v-for="i in 5" :key="i" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                      </svg>
                    </div>
                    <span class="font-semibold">5.0</span>
                    <span class="text-gray-500">(0 отзывов)</span>
                  </div>

                  <!-- Локация -->
                  <div class="text-sm text-gray-600">
                    <div v-if="formData.city" class="flex items-center gap-2 mb-1">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                      </svg>
                      <span>{{ formData.city }}{{ formData.district ? `, ${formData.district}` : '' }}</span>
                    </div>
                  </div>
                </div>

                <!-- Цена -->
                <div class="text-right">
                  <div class="text-3xl font-bold text-gray-900">
                    {{ formatPrice(formData.price_from) }}₽
                  </div>
                  <div v-if="formData.price_to" class="text-sm text-gray-500">
                    до {{ formatPrice(formData.price_to) }}₽
                  </div>
                  <div class="text-sm text-gray-500">за сеанс</div>
                </div>
              </div>
            </div>

            <!-- Описание -->
            <div v-if="formData.description" class="bg-white rounded-lg p-6 mb-6 shadow-sm">
              <h3 class="font-semibold text-gray-900 mb-3">О мастере</h3>
              <p class="text-gray-700 leading-relaxed whitespace-pre-line">{{ formData.description }}</p>
            </div>

            <!-- Фотографии -->
            <div v-if="photos.length" class="bg-white rounded-lg p-6 mb-6 shadow-sm">
              <h3 class="font-semibold text-gray-900 mb-4">Фотографии ({{ photos.length }})</h3>
              <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div 
                  v-for="(photo, index) in photos.slice(0, 8)" 
                  :key="index"
                  class="aspect-square bg-gray-200 rounded-lg overflow-hidden relative"
                >
                  <img 
                    :src="photo.preview || photo.url" 
                    :alt="`Фото ${index + 1}`"
                    class="w-full h-full object-cover"
                  />
                  <div v-if="photo.is_main" class="absolute top-2 left-2 bg-yellow-500 text-white text-xs px-2 py-1 rounded">
                    Главное
                  </div>
                </div>
              </div>
            </div>

            <!-- Услуги -->
            <div v-if="services.length" class="bg-white rounded-lg p-6 mb-6 shadow-sm">
              <h3 class="font-semibold text-gray-900 mb-4">Услуги</h3>
              <div class="space-y-4">
                <div 
                  v-for="(service, index) in services"
                  :key="index"
                  class="border border-gray-200 rounded-lg p-4"
                >
                  <div class="flex justify-between items-start mb-2">
                    <h4 class="font-medium text-gray-900">{{ service.name || `Услуга ${index + 1}` }}</h4>
                    <div class="text-right">
                      <div class="font-bold text-gray-900">{{ formatPrice(service.price) }}₽</div>
                      <div class="text-sm text-gray-500">{{ service.duration }} мин</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Контакты -->
            <div class="bg-white rounded-lg p-6 shadow-sm">
              <h3 class="font-semibold text-gray-900 mb-4">Контакты</h3>
              <div class="space-y-3">
                <div v-if="formData.phone" class="flex items-center gap-3">
                  <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                  </svg>
                  <span>{{ formData.show_phone ? formData.phone : 'Телефон скрыт' }}</span>
                </div>
                <div v-if="formData.whatsapp" class="flex items-center gap-3">
                  <span class="text-green-500">📱</span>
                  <span>WhatsApp: {{ formData.whatsapp }}</span>
                </div>
                <div v-if="formData.telegram" class="flex items-center gap-3">
                  <span class="text-blue-500">✈️</span>
                  <span>Telegram: {{ formData.telegram }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Футер модалки -->
        <div class="sticky bottom-0 bg-white border-t px-6 py-4 flex justify-between items-center">
          <div class="text-sm text-gray-600">
            <span v-if="progress < 100" class="text-yellow-600">
              ⚠️ Анкета заполнена на {{ progress }}%
            </span>
            <span v-else class="text-green-600">
              ✅ Анкета готова к публикации
            </span>
          </div>
          <div class="flex gap-3">
            <button 
              @click="$emit('close')"
              class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
            >
              Продолжить редактирование
            </button>
            <button 
              @click="$emit('publish')"
              :disabled="progress < 70"
              class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              {{ progress >= 100 ? 'Опубликовать' : 'Опубликовать как есть' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  formData: {
    type: Object,
    required: true
  },
  progress: {
    type: Number,
    default: 0
  }
})

defineEmits(['close', 'publish'])

// Вычисления
const photos = computed(() => props.formData.photos || [])
const services = computed(() => props.formData.services || [])
const mainPhoto = computed(() => photos.value.find(p => p.is_main) || photos.value[0])

const incompleteFields = computed(() => {
  const incomplete = []
  
  if (!props.formData.display_name) incomplete.push('Имя для объявления')
  if (!props.formData.description) incomplete.push('Описание')
  if (!props.formData.city) incomplete.push('Город')
  if (!props.formData.phone) incomplete.push('Телефон')
  if (!props.formData.price_from) incomplete.push('Цена от')
  if (!services.value.length) incomplete.push('Услуги')
  if (!photos.value.length) incomplete.push('Фотографии')
  
  return incomplete
})

// Методы
const formatPrice = (price) => {
  if (!price) return '0'
  return new Intl.NumberFormat('ru-RU').format(price)
}

const getYearWord = (years) => {
  if (!years) return 'лет'
  const lastDigit = years % 10
  const lastTwoDigits = years % 100
  
  if (lastTwoDigits >= 11 && lastTwoDigits <= 14) return 'лет'
  if (lastDigit === 1) return 'год'
  if (lastDigit >= 2 && lastDigit <= 4) return 'года'
  return 'лет'
}
</script>