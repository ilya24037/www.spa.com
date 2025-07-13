<template>
  <MetaTags 
    :title="`${master.name} - Мастер массажа в ${master.city || 'Москве'}`"
    :description="metaDescription"
    :image="master.avatar"
    :keywords="metaKeywords"
  />

  <div class="min-h-screen bg-gray-50">
    <!-- Шапка профиля -->
    <MasterHeader 
      :master="master"
      :is-loading="isLoading"
      @toggle-favorite="toggleFavorite"
      @share="shareMaster"
    />

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Error Boundary -->
      <MasterErrorBoundary ref="errorBoundary" @retry="handleRetry">
        <!-- Загрузка -->
        <div v-if="isLoading" class="animate-pulse space-y-8">
          <div class="bg-white rounded-lg h-96"></div>
          <div class="bg-white rounded-lg h-64"></div>
        </div>

        <!-- Основной контент -->
        <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-8">
          <!-- Левая колонка -->
          <div class="lg:col-span-2 space-y-8">
            <!-- Улучшенная галерея с lazy loading -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden p-6">
              <LazyImageGallery
                :images="gallery"
                :master-name="master.name"
                class="mb-4"
              />
              
              <!-- Теги мастера -->
              <div v-if="masterTags.length" class="flex flex-wrap gap-2">
                <span 
                  v-for="tag in masterTags"
                  :key="tag"
                  class="px-3 py-1 bg-indigo-100 text-indigo-800 text-sm rounded-full"
                >
                  {{ tag }}
                </span>
              </div>
            </div>

            <!-- Детальная информация -->
            <MasterDetails 
              :master="master"
              :stats="masterStats"
              class="bg-white rounded-lg shadow-sm"
            />

            <!-- Услуги с фильтрацией -->
            <div class="bg-white rounded-lg shadow-sm">
              <ServicesSection 
                :services="filteredServices"
                :master-id="master.id"
                @select-service="handleServiceSelect"
              />
              
              <!-- Фильтр услуг -->
              <div v-if="master.services?.length > 4" class="p-4 border-t">
                <input 
                  v-model="serviceFilter"
                  type="text"
                  placeholder="Поиск услуги..."
                  class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                />
              </div>
            </div>

            <!-- Отзывы с пагинацией -->
            <ReviewsSection 
              :master-id="master.id"
              :reviews="paginatedReviews"
              :can-review="canReview"
              :loading="reviewsLoading"
              :has-more="hasMoreReviews"
              @review-added="handleReviewAdded"
              @load-more="loadMoreReviews"
            />
          </div>

          <!-- Правая колонка -->
          <div class="space-y-6">
            <!-- Улучшенный виджет бронирования -->
            <div id="booking-widget" class="lg:sticky lg:top-20">
              <BookingWidget 
                :master="master"
                :selected-service="selectedService"
                :available-slots="availableSlots"
                :is-loading="bookingLoading"
                @booking-created="handleBookingCreated"
                @phone-call="trackPhoneCall"
              />

              <!-- Быстрые действия -->
              <div class="mt-4 grid grid-cols-2 gap-3">
                <button 
                  @click="toggleFavorite"
                  :disabled="favoriteLoading"
                  class="flex items-center justify-center gap-2 px-4 py-2 border rounded-lg hover:bg-gray-50 transition-colors"
                  :class="{ 'text-red-600 border-red-200': isFavorite }"
                >
                  <svg class="w-5 h-5" :class="{ 'fill-current': isFavorite }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                  </svg>
                  <span class="text-sm">
                    {{ isFavorite ? 'В избранном' : 'В избранное' }}
                  </span>
                </button>

                <button 
                  @click="shareMaster"
                  class="flex items-center justify-center gap-2 px-4 py-2 border rounded-lg hover:bg-gray-50 transition-colors"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z" />
                  </svg>
                  <span class="text-sm">Поделиться</span>
                </button>
              </div>

              <!-- Контакты на мобильных -->
              <div class="mt-6 lg:hidden">
                <MasterContactInfo :master="master" />
              </div>
            </div>

            <!-- Статистика мастера -->
            <div class="bg-white rounded-lg shadow-sm p-6">
              <h3 class="font-semibold mb-4">Статистика</h3>
              <div class="grid grid-cols-2 gap-4">
                <div class="text-center">
                  <div class="text-2xl font-bold text-indigo-600">{{ master.views_count || 0 }}</div>
                  <div class="text-sm text-gray-500">Просмотров</div>
                </div>
                <div class="text-center">
                  <div class="text-2xl font-bold text-green-600">{{ master.completed_bookings || 0 }}</div>
                  <div class="text-sm text-gray-500">Клиентов</div>
                </div>
                <div class="text-center">
                  <div class="text-2xl font-bold text-yellow-600">{{ master.rating || 5.0 }}</div>
                  <div class="text-sm text-gray-500">Рейтинг</div>
                </div>
                <div class="text-center">
                  <div class="text-2xl font-bold text-purple-600">{{ master.services?.length || 0 }}</div>
                  <div class="text-sm text-gray-500">Услуг</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Похожие мастера -->
        <Suspense>
          <SimilarMastersSection 
            v-if="similarMasters.length"
            :masters="similarMasters"
            :current-master-id="master.id"
            class="mt-16"
          />
          <template #fallback>
            <div class="mt-16 bg-white rounded-lg shadow-sm p-6">
              <div class="animate-pulse h-48 bg-gray-200 rounded"></div>
            </div>
          </template>
        </Suspense>
      </MasterErrorBoundary>
    </div>
  </div>

  <!-- Модалка успеха бронирования -->
  <Teleport to="body">
    <BookingSuccessModal 
      v-if="showBookingSuccess"
      :booking="lastBooking"
      @close="showBookingSuccess = false"
    />
  </Teleport>
</template>

<script setup>
import { ref, computed, watch, onMounted, nextTick } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { useToast } from '@/Composables/useToast'
import { useFavoritesStore } from '@/stores/favorites'
import { useAnalytics } from '@/Composables/useAnalytics'

// Компоненты
import MetaTags from '@/Components/MetaTags.vue'
import MasterHeader from '@/Components/Masters/MasterHeader/index.vue'
import MasterDetails from '@/Components/Masters/MasterDetails/index.vue'
import BookingWidget from '@/Components/Masters/BookingWidget/index.vue'
import MasterContactInfo from '@/Components/Masters/MasterContactInfo.vue'
import ServicesSection from '@/Components/Masters/ServicesSection.vue'
import ReviewsSection from '@/Components/Masters/ReviewsSection.vue'
import SimilarMastersSection from '@/Components/Masters/SimilarMastersSection.vue'
import BookingSuccessModal from '@/Components/Booking/BookingSuccessModal.vue'
import MasterErrorBoundary from '@/Components/Masters/MasterErrorBoundary.vue'
import LazyImageGallery from '@/Components/Masters/LazyImageGallery.vue'

// Пропсы
const props = defineProps({
  master: { type: Object, required: true },
  gallery: { type: Array, default: () => [] },
  reviews: { type: Array, default: () => [] },
  similarMasters: { type: Array, default: () => [] },
  availableSlots: { type: Object, default: () => ({}) },
  canReview: { type: Boolean, default: false },
})

// Композаблы
const { showSuccess, showError } = useToast()
const { track } = useAnalytics()
const favoritesStore = useFavoritesStore()
const page = usePage()

// Реактивные данные
const isLoading = ref(false)
const selectedService = ref(null)
const reviewsLoading = ref(false)
const bookingLoading = ref(false)
const favoriteLoading = ref(false)
const showBookingSuccess = ref(false)
const lastBooking = ref(null)
const errorBoundary = ref(null)

// Пагинация отзывов
const reviewsPerPage = 5
const currentReviewPage = ref(1)
const serviceFilter = ref('')

// Вычисляемые свойства
const masterStats = computed(() => ({
  experience: props.master.experience_years || 0,
  services: props.master.services?.length || 0,
  clients: props.master.completed_bookings || 0,
  certificates: props.master.certificates?.length || 0,
  rating: props.master.rating || 0,
  reviews: props.master.reviews_count || 0,
}))

const metaDescription = computed(() =>
  props.master.bio || `${props.master.name} - профессиональный мастер массажа. Опыт ${props.master.experience_years || 5} лет. Рейтинг ${props.master.rating || 5.0}. Запись онлайн.`
)

const metaKeywords = computed(() => {
  const services = props.master.services?.map(s => s.name).join(', ') || 'массаж'
  return `${props.master.name}, мастер массажа, ${services}, ${props.master.city || 'Москва'}`
})

const masterTags = computed(() => {
  const tags = []
  if (props.master.is_verified) tags.push('Проверен')
  if (props.master.is_premium) tags.push('Премиум')
  if (props.master.home_service) tags.push('Выезд')
  if (props.master.salon_service) tags.push('Салон')
  if (props.master.experience_years >= 5) tags.push('Опытный')
  return tags
})

const filteredServices = computed(() => {
  if (!serviceFilter.value) return props.master.services || []
  return props.master.services?.filter(service => 
    service.name.toLowerCase().includes(serviceFilter.value.toLowerCase()) ||
    service.description?.toLowerCase().includes(serviceFilter.value.toLowerCase())
  ) || []
})

const paginatedReviews = computed(() => {
  const start = 0
  const end = currentReviewPage.value * reviewsPerPage
  return props.reviews.slice(start, end)
})

const hasMoreReviews = computed(() => {
  return props.reviews.length > currentReviewPage.value * reviewsPerPage
})

const isFavorite = computed(() => 
  favoritesStore.isFavorite(props.master.id)
)

// Методы
async function toggleFavorite() {
  if (favoriteLoading.value) return
  
  favoriteLoading.value = true
  try {
    await favoritesStore.toggleFavorite(props.master.id)
    showSuccess(
      isFavorite.value ? 'Добавлено в избранное' : 'Удалено из избранного'
    )
    track('favorite_toggled', { master_id: props.master.id, action: isFavorite.value ? 'add' : 'remove' })
  } catch (error) {
    showError('Не удалось изменить избранное')
    errorBoundary.value?.showError()
  } finally {
    favoriteLoading.value = false
  }
}

function shareMaster() {
  const shareData = {
    title: props.master.name,
    text: metaDescription.value,
    url: window.location.href,
  }
  
  if (navigator.share) {
    navigator.share(shareData).catch(() => {
      fallbackShare()
    })
  } else {
    fallbackShare()
  }
  
  track('master_shared', { master_id: props.master.id })
}

function fallbackShare() {
  navigator.clipboard.writeText(window.location.href)
  showSuccess('Ссылка скопирована')
}

function handleServiceSelect(service) {
  selectedService.value = service
  track('service_selected', { master_id: props.master.id, service_id: service.id })
  
  // Плавный скролл к виджету на мобильных
  if (window.innerWidth < 1024) {
    nextTick(() => {
      document.getElementById('booking-widget')?.scrollIntoView({
        behavior: 'smooth', 
        block: 'start'
      })
    })
  }
}

function handleBookingCreated(booking) {
  lastBooking.value = booking
  showBookingSuccess.value = true
  selectedService.value = null
  track('booking_created', { master_id: props.master.id, booking_id: booking.id })
}

function handleReviewAdded(review) {
  props.reviews.unshift(review)
  props.master.reviews_count++
  props.master.rating = review.new_rating || props.master.rating
  showSuccess('Спасибо за ваш отзыв!')
  track('review_added', { master_id: props.master.id })
}

function loadMoreReviews() {
  currentReviewPage.value++
  track('reviews_loaded_more', { master_id: props.master.id, page: currentReviewPage.value })
}

function trackPhoneCall() {
  track('phone_call_clicked', { master_id: props.master.id })
}

function handleRetry() {
  // Перезагрузка страницы или повторный запрос данных
  window.location.reload()
}

// Отслеживание времени на странице
onMounted(() => {
  const startTime = Date.now()
  
  track('master_page_viewed', { 
    master_id: props.master.id,
    master_name: props.master.name 
  })
  
  // Отслеживание времени при уходе со страницы
  window.addEventListener('beforeunload', () => {
    const timeSpent = Date.now() - startTime
    track('time_on_page', { 
      master_id: props.master.id, 
      time_spent: Math.round(timeSpent / 1000) 
    })
  })
})

// Flash-уведомления
watch(() => page.props.flash, flash => {
  if (flash?.success) showSuccess(flash.success)
  if (flash?.error) showError(flash.error)
}, { immediate: true })
</script>

<style scoped>
.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

@media (max-width: 1023px) {
  .lg\:sticky { 
    position: relative !important; 
    top: auto !important; 
  }
}
</style>