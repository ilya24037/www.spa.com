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
              :href="`/ads/${ad.id}/edit`"
              class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2 text-sm font-medium shadow-lg"
              @click="handleEditClick"
            >
              <svg
                class="w-4 h-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                />
              </svg>
              Редактировать
            </Link>
            <button 
              class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition flex items-center gap-2 text-sm font-medium shadow-lg"
              @click.stop.prevent="handleDeleteClick"
            >
              <svg
                class="w-4 h-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                />
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

          <!-- Видео секция -->
          <div class="bg-white rounded-lg p-6 shadow-sm mt-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">
              Видео (отладка)
            </h2>
            
            <!-- Отладочная информация -->
            <div class="mb-4 p-4 bg-gray-100 rounded text-sm">
              <p><strong>ad.video существует:</strong> {{ !!ad.video }}</p>
              <p><strong>ad.video тип:</strong> {{ typeof ad.video }}</p>
              <p><strong>ad.video массив:</strong> {{ Array.isArray(ad.video) }}</p>
              <p><strong>ad.video длина:</strong> {{ ad.video?.length }}</p>
              <p><strong>ad.video содержимое:</strong> {{ JSON.stringify(ad.video) }}</p>
            </div>
            
            <div v-if="ad.video && ad.video.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div v-for="(videoUrl, index) in ad.video" :key="index" class="relative">
                <p class="text-xs text-gray-500 mb-2">URL: {{ videoUrl }}</p>
                <video 
                  :src="videoUrl" 
                  class="w-full h-64 object-cover rounded-lg bg-black" 
                  controls 
                  preload="metadata"
                  @error="console.error('Video error:', $event)"
                  @loadedmetadata="() => {}"
                >
                  Ваш браузер не поддерживает воспроизведение видео
                </video>
                <div class="mt-2 text-sm text-gray-600">
                  <p><strong>Индекс:</strong> {{ index }}</p>
                  <p><strong>URL:</strong> {{ videoUrl }}</p>
                </div>
              </div>
            </div>
            
            <div v-else class="text-center py-8 text-gray-500">
              <svg class="mx-auto h-12 w-12 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
              </svg>
              <p>Видео не добавлено</p>
              <p class="text-sm mt-1">Добавьте видео при редактировании черновика</p>
            </div>
          </div>

          <!-- Описание -->
          <div class="bg-white rounded-lg p-6 shadow-sm mt-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">
              Описание
            </h2>
            <div class="prose max-w-none">
              <p class="text-gray-600 leading-relaxed whitespace-pre-line">
                {{ ad.description || 'Описание не указано' }}
              </p>
            </div>
          </div>

          <!-- Дополнительная информация -->
          <div class="bg-white rounded-lg p-6 shadow-sm mt-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">
              Дополнительная информация
            </h2>
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Опыт работы
                </dt>
                <dd class="text-gray-600">
                  {{ ad.experience || 'Не указан' }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Формат работы
                </dt>
                <dd class="text-gray-600">
                  {{ ad.work_format || 'Не указан' }}
                </dd>
              </div>
              <div v-if="ad.clients && ad.clients.length > 0">
                <dt class="text-sm font-medium text-gray-500">
                  Категории клиентов
                </dt>
                <dd class="text-gray-600">
                  {{ ad.clients.join(', ') }}
                </dd>
              </div>
              <div v-if="ad.service_location && ad.service_location.length > 0">
                <dt class="text-sm font-medium text-gray-500">
                  Места оказания услуг
                </dt>
                <dd class="text-gray-600">
                  {{ getServiceLocationText(ad.service_location) }}
                </dd>
              </div>
            </dl>
          </div>
        </div>

        <!-- Правая колонка: контактная информация и цена -->
        <div class="space-y-6">
          <!-- Цена -->
          <div class="bg-white rounded-lg p-6 shadow-sm">
            <h2 class="text-xl font-bold text-gray-800 mb-4">
              Стоимость
            </h2>
            <div class="text-3xl font-bold text-gray-900 mb-2">
              {{ formatPrice(ad.price) }}
            </div>
            <p class="text-gray-600">
              {{ getPriceUnitText(ad.price_unit) }}
            </p>
            <div v-if="ad.discount" class="mt-2">
              <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                Скидка {{ ad.discount }}%
              </span>
            </div>
          </div>

          <!-- Контакты -->
          <div class="bg-white rounded-lg p-6 shadow-sm">
            <h2 class="text-xl font-bold text-gray-800 mb-4">
              Контактная информация
            </h2>
            <div class="space-y-3">
              <div v-if="ad.phone">
                <dt class="text-sm font-medium text-gray-500">
                  Телефон
                </dt>
                <dd class="text-gray-600">
                  {{ ad.phone }}
                </dd>
              </div>
              <div v-if="ad.contact_method">
                <dt class="text-sm font-medium text-gray-500">
                  Способ связи
                </dt>
                <dd class="text-gray-600">
                  {{ getContactMethodText(ad.contact_method) }}
                </dd>
              </div>
              <div v-if="ad.address">
                <dt class="text-sm font-medium text-gray-500">
                  Адрес
                </dt>
                <dd class="text-gray-600">
                  {{ ad.address }}
                </dd>
              </div>
            </div>
          </div>

          <!-- График работы -->
          <div v-if="ad.schedule && Object.keys(ad.schedule).length > 0" class="bg-white rounded-lg p-6 shadow-sm">
            <h2 class="text-xl font-bold text-gray-800 mb-4">
              График работы
            </h2>
            <div class="space-y-3">
              <div v-for="(hours, day) in ad.schedule" :key="day" class="flex justify-between items-center">
                <span class="text-sm font-medium text-gray-500">
                  {{ getDayName(day) }}
                </span>
                <span class="text-sm text-gray-600">
                  {{ hours || 'Выходной' }}
                </span>
              </div>
            </div>
            <div v-if="ad.schedule_notes" class="mt-4 p-3 bg-gray-50 rounded text-sm text-gray-600">
              <strong>Примечания:</strong> {{ ad.schedule_notes }}
            </div>
          </div>

          <!-- Статистика -->
          <div class="bg-white rounded-lg p-6 shadow-sm">
            <h2 class="text-xl font-bold text-gray-800 mb-4">
              Статистика
            </h2>
            <dl class="space-y-2">
              <div class="flex justify-between">
                <dt class="text-gray-500">
                  Просмотры
                </dt>
                <dd class="text-gray-600">
                  {{ ad.views_count || 0 }}
                </dd>
              </div>
              <div class="flex justify-between">
                <dt class="text-gray-500">
                  В избранном
                </dt>
                <dd class="text-gray-600">
                  {{ ad.favorites_count || 0 }}
                </dd>
              </div>
              <div class="flex justify-between">
                <dt class="text-gray-500">
                  Показы контактов
                </dt>
                <dd class="text-gray-600">
                  {{ ad.contacts_shown || 0 }}
                </dd>
              </div>
            </dl>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Модальное окно удаления -->
  <ConfirmModal
    v-model:show="showDeleteModal"
    title="Удаление черновика"
    message="Вы уверены, что хотите удалить этот черновик? Это действие нельзя отменить."
    confirm-text="Удалить"
    cancel-text="Отмена"
    confirm-variant="danger"
    @confirm="handleDeleteConfirm"
    @cancel="handleDeleteCancel"
  />
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import PhotoGallery from '@/src/features/gallery/ui/PhotoGallery/PhotoGallery.vue'
import Breadcrumbs from '@/src/shared/ui/molecules/Breadcrumbs/Breadcrumbs.vue'
import { ConfirmModal } from '@/src/shared/ui/molecules'

// Props
interface Props {
  ad: any
}

const props = defineProps<Props>()

// Состояние
const showDeleteModal = ref(false)

// Обработчики событий
const handleEditClick = (event: any) => {
  // Предотвращаем всплытие события
  if (showDeleteModal.value) {
    event.preventDefault()
    event.stopPropagation()
    return false
  }
}

// Обработчик клика по кнопке удаления
const handleDeleteClick = (event: any) => {
  event.stopPropagation()
  event.preventDefault()
  showDeleteModal.value = true
}

// Обработчик отмены удаления
const handleDeleteCancel = () => {
  showDeleteModal.value = false
}

// Обработчик подтверждения удаления
const handleDeleteConfirm = () => {
  deleteDraft()
}

// Остальные методы
const formatPrice = (price: any) => {
  if (!price) return '0'
  return new Intl.NumberFormat('ru-RU').format(price)
}

const formatDate = (date: any) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('ru-RU', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const getDayName = (dayOfWeek: any) => {
  const days = [
    'Воскресенье', 'Понедельник', 'Вторник', 'Среда', 
    'Четверг', 'Пятница', 'Суббота'
  ]
  return days[dayOfWeek]
}

const getServiceLocationText = (locations: any) => {
  if (!Array.isArray(locations)) return ''
  
  const locationTexts = {
    'my_place': 'У мастера',
    'client_home': 'Выезд к клиенту',
    'salon': 'В салоне'
  }
  
  return locations.map(loc => (locationTexts as any)[loc] || loc).join(', ')
}

const getPriceUnitText = (unit: any) => {
  const units = {
    'service': 'за услугу',
    'hour': 'за час',
    'session': 'за сеанс',
    'day': 'за день',
    'month': 'за месяц'
  }
  return (units as any)[unit] || unit
}

const getContactMethodText = (method: any) => {
  const methods = {
    'any': 'Любой способ',
    'calls': 'Только звонки',
    'messages': 'Только сообщения'
  }
  return (methods as any)[method] || method
}

const showPhone = () => {
  if (props.ad.phone) {
    (window as any).location.href = `tel:${props.ad.phone.replace(/\D/g, '')}`
  } else {
    // Используем простой alert вместо toast
    alert('Телефон будет доступен после публикации объявления')
  }
}

// Удаление черновика
const deleteDraft = () => {
  
  // НЕ закрываем модалку - оставляем открытой до завершения операции
  
  // Используем специальный роут для черновиков
  router.delete(`/draft/${props.ad.id}`, {
    preserveScroll: false,
    preserveState: false,
    onStart: () => {
    },
    onSuccess: (page) => {
      // Закрываем модалку только при успехе
      showDeleteModal.value = false
      // Контроллер перенаправляет в личный кабинет
    },
    onError: (errors) => {
      // Показываем ошибку пользователю
      // toast.error('Ошибка удаления: ' + (errors.message || JSON.stringify(errors))) // toast removed
      alert('Ошибка удаления: ' + (errors.message || JSON.stringify(errors)))
      // Модалка остается открытой при ошибке
    },
    onFinish: () => {
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
