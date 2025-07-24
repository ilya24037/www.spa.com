<template>
  <Head :title="`${ad.title || 'Черновик'} - SPA Platform`" />
  
  <div class="master-page">
    <!-- Контент с максимальной шириной как на главной -->
    <div class="py-6 lg:py-8">
      <!-- Основной контент -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Левая колонка: Фото и основная информация -->
        <div class="lg:col-span-2">
          <!-- Кнопки управления черновиком НАД фото -->
          <div class="mb-4 flex justify-start gap-3">
            <Link 
              :href="route('ads.edit', ad.id)"
              class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2 text-sm font-medium shadow-lg"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
              </svg>
              Редактировать
            </Link>
            <button 
              @click.stop.prevent="() => { console.log('Delete button clicked'); showDeleteModal = true }"
              class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition flex items-center gap-2 text-sm font-medium shadow-lg"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
              </svg>
              Удалить
            </button>
          </div>

          <!-- Универсальная галерея фото -->
          <PhotoGallery 
            :photos="ad.photos || []"
            mode="full"
            :show-badges="false"
            :show-thumbnails="true"
            :show-counter="true"
            :enable-lightbox="true"
          />
          
          <!-- Основная информация -->
          <div class="bg-white rounded-lg p-6 shadow-sm mt-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-4">{{ ad.title || 'Без названия' }}</h1>
            
            <div class="flex items-center gap-4 mb-4">
              <div class="flex items-center gap-1">
                <MapPinIcon class="w-5 h-5 text-gray-400" />
                <span>{{ ad.city }}{{ ad.district ? ', ' + ad.district : '' }}</span>
              </div>
            </div>
            
            <div v-if="ad.description" class="text-gray-700 mb-4">
              {{ ad.description }}
            </div>
          </div>
          
          <!-- Услуги -->
          <div v-if="ad.services && ad.services.length" class="bg-white rounded-lg p-6 shadow-sm mt-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Услуги и цены</h2>
            
            <div class="space-y-4">
              <div 
                v-for="service in ad.services" 
                :key="service.id"
                class="flex justify-between items-center p-4 border border-gray-200 rounded-lg"
              >
                <div>
                  <h3 class="font-medium">{{ service.name }}</h3>
                  <p class="text-sm text-gray-500">{{ service.duration }} мин</p>
                </div>
                <div class="text-right">
                  <div class="font-bold text-lg">{{ formatPrice(service.price) }} ₽</div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Отзывы -->
          <div v-if="ad.reviews && ad.reviews.length" class="bg-white rounded-lg p-6 shadow-sm mt-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Отзывы</h2>
            
            <div class="space-y-4">
              <div 
                v-for="review in ad.reviews" 
                :key="review.id"
                class="border-b border-gray-200 pb-4 last:border-b-0"
              >
                <div class="flex items-center gap-3 mb-2">
                  <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center">
                    {{ review.client_name?.charAt(0) || 'А' }}
                  </div>
                  <div>
                    <div class="font-medium">{{ review.client_name || 'Анонимный клиент' }}</div>
                    <div class="flex items-center gap-1">
                      <svg v-for="n in 5" :key="n" 
                        :class="n <= review.rating ? 'text-yellow-400' : 'text-gray-300'"
                        class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"
                      >
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                      </svg>
                    </div>
                  </div>
                </div>
                <p class="text-gray-700">{{ review.comment }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Правая колонка: Информация о черновике -->
        <div class="space-y-6">
          <!-- Статус черновика -->
          <div class="bg-white rounded-lg p-6 shadow-sm sticky top-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Статус черновика</h2>
            
            <!-- Информация -->
            <div class="space-y-3 mb-6">
              <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                <div class="flex">
                  <svg class="w-5 h-5 text-yellow-400 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-yellow-800">Черновик не опубликован</p>
                    <p class="text-sm text-yellow-700 mt-1">Для публикации завершите заполнение всех обязательных полей</p>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Действия -->
            <div class="space-y-3">
              <Link 
                :href="route('ads.edit', ad.id)"
                class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 transition font-medium text-center block"
              >
                Продолжить редактирование
              </Link>
              
              <button 
                @click="showDeleteModal = true"
                class="w-full border border-red-300 text-red-600 py-3 px-4 rounded-lg hover:bg-red-50 transition font-medium"
              >
                Удалить черновик
              </button>
            </div>
            
            <!-- График работы / Информация -->
            <div class="mt-6 pt-6 border-t border-gray-200">
              <h3 class="font-semibold text-gray-900 mb-3">Контактная информация</h3>
              <div class="space-y-2">
                <div v-if="ad.phone" class="flex justify-between text-sm">
                  <span class="text-gray-600">Телефон</span>
                  <span class="font-medium">{{ ad.phone }}</span>
                </div>
                <div v-if="ad.address" class="flex justify-between text-sm">
                  <span class="text-gray-600">Адрес</span>
                  <span class="font-medium">{{ ad.address }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Создано</span>
                  <span class="font-medium">{{ formatDate(ad.created_at) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">ID</span>
                  <span class="font-medium">{{ ad.id }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Модальное окно подтверждения удаления -->
    <ConfirmModal
      :show="showDeleteModal"
      @cancel="() => { console.log('Modal canceled'); showDeleteModal = false }"
      @confirm="() => { console.log('Modal confirmed'); deleteDraft() }"
      title="Удалить черновик?"
      message="Это действие нельзя отменить. Черновик будет удален навсегда."
      confirm-text="Удалить"
      cancel-text="Отмена"
    />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { MapPinIcon } from '@heroicons/vue/24/outline'
import ConfirmModal from '@/Components/UI/ConfirmModal.vue'
import PhotoGallery from '@/Components/Gallery/PhotoGallery.vue'

// Импортируем route из window.route (Ziggy)
const route = window.route || ((name, params) => {
  console.warn('Route helper not found, using fallback')
  // Fallback для роутов
  if (name === 'my-ads.destroy' && params) {
    return `/my-ads/${params}`
  }
  if (name === 'ads.edit' && params) {
    return `/ads/${params}/edit`
  }
  if (name === 'my-ads.index') {
    return '/my-ads'
  }
  return '/'
})

const props = defineProps({
  ad: Object
})

// Состояние
const showDeleteModal = ref(false)

// Остальные методы
const formatPrice = (price) => {
  if (!price) return '0'
  return new Intl.NumberFormat('ru-RU').format(price)
}

const formatDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('ru-RU', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

// Удаление черновика
const deleteDraft = () => {
  console.log('Deleting draft with ID:', props.ad.id)
  
  // Используем общий роут для удаления
  console.log('Deleting with my-ads route')
  
  // Используем общий роут удаления my-ads.destroy
  router.delete(`/my-ads/${props.ad.id}`, {
    preserveScroll: false,
    preserveState: false,
    onBefore: () => {
      console.log('Before delete request')
      showDeleteModal.value = false // Закрываем модалку сразу
      return true
    },
    onSuccess: (page) => {
      console.log('Delete successful, redirecting...')
      // Контроллер сам перенаправит куда нужно
    },
    onError: (errors) => {
      console.error('Delete failed:', errors)
      alert('Ошибка удаления: ' + JSON.stringify(errors))
    }
  })
}
</script>

<style scoped>
/* Полная ширина страницы как у мастера */
.master-page {
  margin-left: calc(-50vw + 50%);
  margin-right: calc(-50vw + 50%);
  width: 100vw;
  padding-left: 2rem;
  padding-right: 2rem;
  box-sizing: border-box;
}

@media (max-width: 768px) {
  .master-page {
    padding-left: 1rem;
    padding-right: 1rem;
  }
}

/* Плавная анимация для галереи */
.transition-all {
  transition-property: all;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 300ms;
}
</style>