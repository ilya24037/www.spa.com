<template>
  <!-- SEO метатеги -->
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
      <!-- Загрузка -->
      <div v-if="isLoading" class="animate-pulse space-y-8">
        <div class="bg-white rounded-lg h-96"></div>
        <div class="bg-white rounded-lg h-64"></div>
      </div>

      <!-- Ошибка -->
      <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-6 text-center">
        <h3 class="text-red-800 font-semibold mb-2">Произошла ошибка</h3>
        <p class="text-red-600">{{ error }}</p>
        <Link href="/masters" class="mt-4 inline-block text-red-700 underline">
          Вернуться к каталогу
        </Link>
      </div>

      <!-- Основной контент -->
      <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Левая колонка -->
        <div class="lg:col-span-2 space-y-8">
          <!-- Галерея -->
          <MasterGallery
            :gallery="gallery"
            :master-name="master.name"
            :is-premium="master.is_premium"
            class="bg-white rounded-lg shadow-sm overflow-hidden"
          />

          <!-- Детальная информация -->
          <MasterDetails 
            :master="master"
            :stats="masterStats"
            class="bg-white rounded-lg shadow-sm"
          />

          <!-- Услуги -->
          <ServicesSection 
            :services="master.services || []"
            :master-id="master.id"
            @select-service="handleServiceSelect"
          />

          <!-- Отзывы -->
          <ReviewsSection 
            :master-id="master.id"
            :reviews="reviews"
            :can-review="canReview"
            :loading="reviewsLoading"
            @review-added="handleReviewAdded"
          />
        </div>

        <!-- Правая колонка -->
        <div class="space-y-6">
          <div id="booking-widget" class="lg:sticky lg:top-20">
            <BookingWidget 
              :master="master"
              :selected-service="selectedService"
              :available-slots="availableSlots"
              @booking-created="handleBookingCreated"
            />

            <!-- Контакты на мобильных -->
            <div class="mt-6 lg:hidden">
              <MasterContactInfo :master="master" />
            </div>
          </div>
        </div>
      </div>

      <!-- Похожие мастера -->
      <SimilarMastersSection 
        v-if="similarMasters.length"
        :masters="similarMasters"
        :current-master-id="master.id"
        class="mt-16"
      />
    </div>
  </div>

  <!-- Модалка успеха бронирования -->
  <BookingSuccessModal 
    v-if="showBookingSuccess"
    :booking="lastBooking"
    @close="showBookingSuccess = false"
  />
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { useToast } from '@/Composables/useToast'
import { useFavoritesStore } from '@/stores/favorites'

// Компоненты
import MetaTags                from '@/Components/MetaTags.vue'
import MasterHeader            from '@/Components/Masters/MasterHeader/index.vue'
import MasterGallery           from '@/Components/Masters/MasterGallery/index.vue'
import MasterDetails           from '@/Components/Masters/MasterDetails/index.vue'
import BookingWidget           from '@/Components/Masters/BookingWidget/index.vue'
import MasterContactInfo       from '@/Components/Masters/MasterContactInfo.vue'
import ServicesSection         from '@/Components/Masters/ServicesSection.vue'
import ReviewsSection          from '@/Components/Masters/ReviewsSection.vue'
import SimilarMastersSection   from '@/Components/Masters/SimilarMastersSection.vue'
import BookingSuccessModal     from '@/Components/Booking/BookingSuccessModal.vue'

// Пропсы
const props = defineProps({
  master:         { type: Object, required: true },
  gallery:        { type: Array,   default: () => [] },
  reviews:        { type: Array,   default: () => [] },
  similarMasters: { type: Array,   default: () => [] },
  availableSlots: { type: Object,  default: () => ({}) },
  canReview:      { type: Boolean, default: false },
})

// Toast и стор
const { showSuccess, showError } = useToast()
const favoritesStore = useFavoritesStore()
const page = usePage()

// Локальный стейт
const isLoading         = ref(false)
const error             = ref(null)
const selectedService   = ref(null)
const reviewsLoading    = ref(false)
const showBookingSuccess= ref(false)
const lastBooking       = ref(null)

// Вычисления
const masterStats = computed(() => ({
  experience   : props.master.experience_years || 0,
  services     : props.master.services?.length || 0,
  clients      : props.master.completed_bookings || 0,
  certificates : props.master.certificates?.length || 0,
  rating       : props.master.rating || 0,
  reviews      : props.master.reviews_count || 0,
}))

const metaDescription = computed(() =>
  props.master.bio
  || `${props.master.name} - профессиональный мастер массажа. Опыт ${props.master.experience_years || 5} лет. Рейтинг ${props.master.rating || 5.0}. Запись онлайн.`
)

const metaKeywords = computed(() => {
  const services = props.master.services?.map(s => s.name).join(', ') || 'массаж'
  return `${props.master.name}, мастер массажа, ${services}, ${props.master.city || 'Москва'}`
})

// Методы
async function toggleFavorite() {
  try {
    await favoritesStore.toggleFavorite(props.master.id)
    showSuccess(
      favoritesStore.isFavorite(props.master.id)
        ? 'Добавлено в избранное'
        : 'Удалено из избранного'
    )
  } catch {
    showError('Не удалось изменить избранное')
  }
}

function shareMaster() {
  if (navigator.share) {
    navigator.share({
      title: props.master.name,
      text: metaDescription.value,
      url: window.location.href,
    }).catch(() => {})
  } else {
    navigator.clipboard.writeText(window.location.href)
    showSuccess('Ссылка скопирована')
  }
}

function handleServiceSelect(service) {
  selectedService.value = service
  if (window.innerWidth < 1024) {
    document.getElementById('booking-widget')?.scrollIntoView({
      behavior: 'smooth', block: 'start'
    })
  }
}

function handleBookingCreated(booking) {
  lastBooking.value = booking
  showBookingSuccess.value = true
  selectedService.value = null
  // при желании: обновить availableSlots через Inertia.reload
}

function handleReviewAdded(review) {
  props.reviews.unshift(review)
  props.master.reviews_count++
  props.master.rating = review.new_rating || props.master.rating
  showSuccess('Спасибо за ваш отзыв!')
}

// Flash-уведомления
watch(() => page.props.flash, flash => {
  if (flash?.success) showSuccess(flash.success)
  if (flash?.error)   showError(flash.error)
}, { immediate: true })
</script>

<style scoped>
.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
@keyframes pulse {
  0%,100% { opacity:1 }
  50%     { opacity:0.5 }
}
@media (max-width:1023px) {
  .lg\:sticky { position:relative!important; top:auto!important }
}
</style>
