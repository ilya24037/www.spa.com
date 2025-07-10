<!-- resources/js/Pages/Masters/Show.vue -->
<template>
  <Head :title="pageTitle" />

  <div class="py-6 lg:py-8">
    <!-- Хлебные крошки -->
    <Breadcrumbs
      :items="[
        { title: 'Главная', href: '/' },
        { title: 'Мастера', href: '/search' },
        { title: master.name }
      ]"
      class="mb-6"
    />

    <!-- Основной контент -->
    <div class="flex gap-6">
      <!-- Левая колонка -->
      <div class="flex-1 space-y-6">
        <!-- Галерея и основная информация -->
        <ContentCard class="p-0 overflow-hidden">
          <div class="lg:flex">
            <!-- Галерея -->
            <div class="lg:w-2/5 bg-gray-50">
              <MasterGallery 
                :master="master"
                @toggle-favorite="toggleFavorite"
              />
            </div>
            
            <!-- Информация -->
            <div class="lg:w-3/5 p-6">
              <MasterHeader 
                :master="master"
                @show-reviews="scrollToReviews"
              />
            </div>
          </div>
        </ContentCard>
        
        <!-- Услуги -->
        <ServicesSection
          :services="displayServices"
          @book-service="openBookingWithService"
          @select-service="selectedService = $event"
        />
        
        <!-- Отзывы -->
        <div ref="reviewsSection">
          <ReviewsSection
            :reviews="master.reviews || []"
            :can-write-review="canWriteReview"
            :average-rating="master.rating || 5.0"
            @write-review="openReviewModal"
            @vote-review="voteReview"
            @show-photo="showPhotoModal"
          />
        </div>
      </div>
      
      <!-- Правая колонка - виджет бронирования -->
      <SidebarWrapper
        v-model="showMobilePricePanel"
        :always-visible-desktop="true"
        position="right"
        :sticky="true"
        :sticky-top="90"
        width-class="w-80 lg:w-96"
        mobile-mode="bottom-sheet"
        content-class="p-0"
        :show-desktop-header="false"
      >
        <BookingWidget
          :master="master"
          :selected-service="selectedService"
          @open-booking="showBookingModal = true"
          @share="shareMaster"
        />
      </SidebarWrapper>
    </div>

    <!-- Похожие мастера -->
    <div class="mt-12">
      <SimilarMastersSection
        :masters="similarMasters || []"
        :current-master="master"
      />
    </div>

    <!-- Мобильная кнопка записи -->
    <div class="lg:hidden fixed bottom-0 left-0 right-0 bg-white border-t p-4 z-40">
      <div class="flex items-center justify-between mb-2">
        <span class="text-2xl font-bold">от {{ formatPrice(master.price_from) }} ₽</span>
        <button 
          @click="showMobilePricePanel = true"
          class="text-indigo-600 text-sm"
        >
          Подробнее
        </button>
      </div>
      <button 
        @click="showBookingModal = true"
        class="w-full bg-indigo-600 text-white py-3 rounded-lg font-medium"
      >
        Записаться
      </button>
    </div>

    <!-- Модальные окна -->
    <BookingModal
      v-if="showBookingModal"
      v-model="showBookingModal"
      :master="master"
      :selected-service="selectedService"
      @close="showBookingModal = false"
      @success="handleBookingSuccess"
    />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import { useFavoritesStore } from '@/stores/favorites'

// Компоненты Layout
import ContentCard from '@/Components/Layout/ContentCard.vue'
import SidebarWrapper from '@/Components/Layout/SidebarWrapper.vue'
import Breadcrumbs from '@/Components/Common/Breadcrumbs.vue'

// Компоненты мастера - исправленные пути
import MasterHeader from '@/Components/Masters/MasterHeader/index.vue'
import MasterGallery from '@/Components/Masters/MasterGallery/index.vue'
import BookingWidget from '@/Components/Masters/BookingWidget/index.vue'
import ServicesSection from '@/Components/Masters/ServicesSection.vue'
import ReviewsSection from '@/Components/Masters/ReviewsSection.vue'
import SimilarMastersSection from '@/Components/Masters/SimilarMastersSection.vue'

// Модальные окна
import BookingModal from '@/Components/Booking/BookingModal.vue'

// Пропсы
const props = defineProps({
  master: {
    type: Object,
    required: true
  },
  similarMasters: {
    type: Array,
    default: () => []
  },
  canWriteReview: {
    type: Boolean,
    default: false
  },
  meta: {
    type: Object,
    default: () => ({})
  }
})

// Stores
const favoritesStore = useFavoritesStore()

// Состояния
const showBookingModal = ref(false)
const showMobilePricePanel = ref(false)
const selectedService = ref(null)
const reviewsSection = ref(null)

// Вычисляемые свойства
const pageTitle = computed(() => 
  `${props.master.name} - Массажист в ${props.master.city || 'Москве'}`
)

// Тестовые услуги если их нет
const displayServices = computed(() => {
  return props.master.services || [
    { id: 1, name: 'Классический массаж', category: 'Массаж', duration: 60, price: 3000 },
    { id: 2, name: 'Расслабляющий массаж', category: 'Массаж', duration: 90, price: 4500 }
  ]
})

// Методы
const formatPrice = (price) => {
  return new Intl.NumberFormat('ru-RU').format(price || 0)
}

const toggleFavorite = () => {
  favoritesStore.toggle(props.master)
}

const openBookingWithService = (service) => {
  selectedService.value = service
  showBookingModal.value = true
}

const scrollToReviews = () => {
  reviewsSection.value?.scrollIntoView({ 
    behavior: 'smooth', 
    block: 'start' 
  })
}

const shareMaster = async () => {
  const shareData = {
    title: props.master.name,
    text: `Массажист ${props.master.name} в ${props.master.city}`,
    url: window.location.href
  }
  
  try {
    if (navigator.share) {
      await navigator.share(shareData)
    } else {
      // Копируем ссылку
      await navigator.clipboard.writeText(window.location.href)
      // Показать уведомление
    }
  } catch (err) {
    console.error('Ошибка при share:', err)
  }
}

const handleBookingSuccess = () => {
  // Обработка успешного бронирования
  showBookingModal.value = false
  // Показать уведомление об успехе
}

const openReviewModal = () => {
  // Открыть модалку для написания отзыва
  console.log('Открыть модалку отзыва')
}

const voteReview = (reviewId, vote) => {
  // Голосование за полезность отзыва
  console.log('Голосование за отзыв:', reviewId, vote)
}

const showPhotoModal = (photo) => {
  // Показать фото в модальном окне
  console.log('Показать фото:', photo)
}
</script>