<template>
  <Head :title="`${ad.title || 'Черновик'} - SPA Platform`" />
  
  <div>
    <!-- Контейнер как на главной -->
    <div class="py-6 lg:py-8">
      <!-- Хлебные крошки -->
      <div class="mb-6">
        <!-- Хлебные крошки -->
        <Breadcrumbs
          :items="breadcrumbItems"
        />
      </div>

      <!-- Основной контент с правильными отступами -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Левая колонка: Фото и основная информация -->
        <div class="lg:col-span-2">
          <!-- Кнопки управления черновиком НАД фото -->
          <div class="mb-4 flex justify-start gap-3">
            <Link 
              :href="route('ads.edit', ad.id)"
              @click="handleEditClick"
              class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2 text-sm font-medium shadow-lg"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
              </svg>
              Редактировать
            </Link>
            <button 
              @click.stop.prevent="handleDeleteClick"
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

                 <!-- Правая колонка: Бронирование и контакты (как у мастера) -->
         <div class="space-y-6">
           <!-- Бронирование -->
           <div class="bg-white rounded-lg p-6 shadow-sm sticky top-6">
             <h2 class="text-xl font-bold text-gray-900 mb-4">Записаться</h2>
             
             <!-- Цена -->
             <div class="mb-6">
               <div class="text-3xl font-bold text-gray-900 mb-2">
                 от {{ formatPrice(ad.price_from || 2000) }} ₽
               </div>
               <p class="text-gray-600">за сеанс</p>
             </div>
             
             
             
                           <!-- Кнопки -->
              <div class="space-y-3">
                <button 
                  @click="showPhone"
                  class="w-full border border-gray-300 py-3 px-4 rounded-lg hover:bg-gray-50 transition font-medium flex items-center justify-center gap-2"
                >
                  <PhoneIcon class="w-5 h-5" />
                  Показать телефон
                </button>
              </div>
             
             <!-- График работы / Информация -->
             <div class="mt-6 pt-6 border-t border-gray-200">
               <h3 class="font-semibold text-gray-900 mb-3">Время работы</h3>
               <div class="space-y-2">
                 <div v-if="ad.schedule" class="space-y-1">
                   <div 
                     v-for="(hours, day) in ad.schedule" 
                     :key="day"
                     class="flex justify-between text-sm"
                   >
                     <span class="text-gray-600">{{ getDayName(day) }}</span>
                     <span class="font-medium">{{ hours || 'Выходной' }}</span>
                   </div>
                 </div>
                 <div v-else class="text-sm text-gray-500">
                   По договоренности
                 </div>
               </div>
             </div>
             
             <!-- Дополнительная информация -->
             <div class="mt-6 pt-6 border-t border-gray-200">
               <h3 class="font-semibold text-gray-900 mb-3">Информация</h3>
               <div class="space-y-2">
                 <div v-if="ad.experience" class="flex justify-between text-sm">
                   <span class="text-gray-600">Опыт</span>
                   <span class="font-medium">{{ ad.experience }}</span>
                 </div>
                 <div v-if="ad.education" class="flex justify-between text-sm">
                   <span class="text-gray-600">Образование</span>
                   <span class="font-medium">{{ ad.education }}</span>
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
      @cancel="handleDeleteCancel"
      @confirm="handleDeleteConfirm"
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
import { MapPinIcon, PhoneIcon } from '@heroicons/vue/24/outline'
import ConfirmModal from '@/Components/UI/ConfirmModal.vue'
import PhotoGallery from '@/Components/Gallery/PhotoGallery.vue'
// 🎯 FSD Импорты
import { Breadcrumbs } from '@/src/shared'

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
  if (name === 'my-ads.index' || name === 'profile.items.draft') {
    return '/profile/items/draft/all'
  }
  return '/'
})

const props = defineProps({
  ad: Object
})

// Состояние
const showDeleteModal = ref(false)

// Обработчик клика по кнопке редактирования
const handleEditClick = (event) => {
  console.log('Edit button clicked')
  console.log('Modal open:', showDeleteModal.value)
  
  // Если модальное окно открыто, блокируем переход
  if (showDeleteModal.value) {
    console.log('Blocking edit - delete modal is open')
    event.preventDefault()
    event.stopPropagation()
    return false
  }
  
  console.log('Allowing edit navigation')
}

// Обработчик клика по кнопке удаления
const handleDeleteClick = (event) => {
  console.log('Delete button clicked, event:', event)
  event.stopPropagation()
  event.preventDefault()
  showDeleteModal.value = true
}

// Обработчик отмены удаления
const handleDeleteCancel = () => {
  console.log('Modal canceled')
  showDeleteModal.value = false
}

// Обработчик подтверждения удаления
const handleDeleteConfirm = () => {
  console.log('Modal confirmed, calling deleteDraft')
  deleteDraft()
}

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

const getDayName = (dayOfWeek) => {
  const days = [
    'Воскресенье', 'Понедельник', 'Вторник', 'Среда', 
    'Четверг', 'Пятница', 'Суббота'
  ]
  return days[dayOfWeek]
}

const showPhone = () => {
  if (props.ad.phone) {
    window.location.href = `tel:${props.ad.phone.replace(/\D/g, '')}`
  } else {
    alert('Телефон будет доступен после публикации объявления')
  }
}

// Удаление черновика
const deleteDraft = () => {
  console.log('=== STARTING DELETE DRAFT ===')
  console.log('Draft ID:', props.ad.id)
  console.log('Current URL:', window.location.href)
  
  // НЕ закрываем модалку - оставляем открытой до завершения операции
  console.log('Starting delete request with modal open...')
  
  // Используем специальный роут для черновиков
  router.delete(`/draft/${props.ad.id}`, {
    preserveScroll: false,
    preserveState: false,
    onStart: () => {
      console.log('Delete request started')
    },
    onSuccess: (page) => {
      console.log('=== DELETE SUCCESSFUL ===')
      console.log('Redirected to:', page.url)
      console.log('Page props:', page.props)
      // Закрываем модалку только при успехе
      showDeleteModal.value = false
      // Контроллер перенаправляет в личный кабинет
    },
    onError: (errors) => {
      console.log('=== DELETE FAILED ===')
      console.error('Delete failed with errors:', errors)
      // Показываем ошибку пользователю
      alert('Ошибка удаления: ' + (errors.message || JSON.stringify(errors)))
      // Модалка остается открытой при ошибке
    },
    onFinish: () => {
      console.log('Delete request finished')
    }
  })
}

const breadcrumbItems = [
  { title: 'Главная', href: '/' },
  { title: 'Мои объявления', href: '/profile/items/draft/all' },
  { title: props.ad.title || 'Черновик' }
]
</script>

<style scoped>
/* Убираем стили полной ширины - используем стандартную структуру как на главной */

/* Плавная анимация для галереи */
.transition-all {
  transition-property: all;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 300ms;
}
</style>