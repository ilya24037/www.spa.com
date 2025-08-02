<!-- resources/js/src/widgets/master-profile/MasterProfile.vue -->
<template>
  <div :class="CONTAINER_CLASSES">
    <!-- Мобильная навигация -->
    <div :class="MOBILE_NAV_CLASSES">
      <select
        v-model="activeSection"
        @change="scrollToSection"
        :class="MOBILE_SELECT_CLASSES"
      >
        <option value="gallery">Фотографии</option>
        <option value="info">О мастере</option>
        <option value="services">Услуги</option>
        <option value="reviews">Отзывы</option>
        <option value="contacts">Контакты</option>
      </select>
    </div>

    <div :class="LAYOUT_CLASSES">
      <!-- Левая колонка -->
      <div :class="LEFT_COLUMN_CLASSES">
        <!-- Галерея фотографий -->
        <section id="gallery" :class="SECTION_CLASSES">
          <MasterGallery
            :photos="master.photos"
            :master-name="master.name"
            :autoplay="false"
          />
        </section>

        <!-- Информация о мастере -->
        <section id="info" :class="SECTION_CLASSES">
          <MasterInfo :master="master" />
          
          <!-- Параметры мастера -->
          <div v-if="hasParameters" :class="PARAMETERS_SECTION_CLASSES">
            <MasterParameters :master="master" />
          </div>
        </section>

        <!-- Услуги и цены -->
        <section id="services" :class="SECTION_CLASSES">
          <MasterServices :master="master" />
        </section>

        <!-- Отзывы -->
        <section id="reviews" :class="SECTION_CLASSES">
          <MasterReviews
            :master="master"
            :initial-reviews="initialReviews"
            @load-more="handleLoadMoreReviews"
          />
        </section>

        <!-- Похожие мастера -->
        <section v-if="similarMasters.length > 0" :class="SECTION_CLASSES">
          <h3 :class="SECTION_TITLE_CLASSES">Похожие мастера</h3>
          <div :class="SIMILAR_MASTERS_CLASSES">
            <MasterCard
              v-for="similarMaster in similarMasters"
              :key="similarMaster.id"
              :master="similarMaster"
              @click="goToMaster(similarMaster)"
            />
          </div>
        </section>
      </div>

      <!-- Правая колонка -->
      <div :class="RIGHT_COLUMN_CLASSES">
        <!-- Sticky блок с контактами и бронированием -->
        <div :class="STICKY_BLOCK_CLASSES">
          <!-- Контактная информация -->
          <div :class="CONTACT_SECTION_CLASSES">
            <MasterContact :master="master" />
          </div>

          <!-- Виджет бронирования -->
          <div :class="BOOKING_SECTION_CLASSES">
            <BookingWidget
              :master="master"
              @booking-success="handleBookingSuccess"
              @booking-error="handleBookingError"
            />
          </div>

          <!-- Быстрые действия -->
          <div :class="QUICK_ACTIONS_CLASSES">
            <!-- Избранное -->
            <button
              @click="toggleFavorite"
              :class="getFavoriteButtonClasses()"
              :disabled="favoriteLoading"
            >
              <svg
                :class="FAVORITE_ICON_CLASSES"
                :fill="master.is_favorite ? 'currentColor' : 'none'"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
              </svg>
              {{ master.is_favorite ? 'В избранном' : 'В избранное' }}
            </button>

            <!-- Поделиться -->
            <button
              @click="shareMaster"
              :class="SHARE_BUTTON_CLASSES"
            >
              <svg :class="SHARE_ICON_CLASSES" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z"/>
              </svg>
              Поделиться
            </button>

            <!-- Пожаловаться -->
            <button
              @click="reportMaster"
              :class="REPORT_BUTTON_CLASSES"
            >
              <svg :class="REPORT_ICON_CLASSES" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
              </svg>
              Пожаловаться
            </button>
          </div>

          <!-- Статистика просмотров -->
          <div :class="STATS_CLASSES">
            <div :class="STAT_ITEM_CLASSES">
              <svg :class="STAT_ICON_CLASSES" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
              </svg>
              <span :class="STAT_TEXT_CLASSES">
                {{ formatViews(master.views_count) }} просмотров
              </span>
            </div>

            <div v-if="master.last_activity_at" :class="STAT_ITEM_CLASSES">
              <svg :class="STAT_ICON_CLASSES" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              <span :class="STAT_TEXT_CLASSES">
                {{ formatLastActivity(master.last_activity_at) }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Десктопная навигация (sticky) -->
    <div :class="DESKTOP_NAV_CLASSES">
      <nav :class="NAV_CONTAINER_CLASSES">
        <button
          v-for="section in sections"
          :key="section.id"
          @click="scrollToSection(section.id)"
          :class="getNavButtonClasses(section.id)"
        >
          {{ section.label }}
        </button>
      </nav>
    </div>

    <!-- Модальные окна и уведомления -->
    <MasterProfileModals
      :show-share="showShareModal"
      :show-report="showReportModal"
      :master="master"
      @close-share="showShareModal = false"
      @close-report="showReportModal = false"
      @report-sent="handleReportSent"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import {
  MasterGallery,
  MasterInfo,
  MasterParameters,
  MasterServices,
  MasterReviews,
  MasterContact,
  useMaster
} from '@/src/entities/master'
import { BookingWidget } from '@/src/entities/booking'
import { MasterCard } from '@/src/entities/master'
import MasterProfileModals from './MasterProfileModals.vue'
import dayjs from 'dayjs'
import relativeTime from 'dayjs/plugin/relativeTime'
import 'dayjs/locale/ru'

dayjs.extend(relativeTime)
dayjs.locale('ru')

// 🎯 Стили согласно дизайн-системе
const CONTAINER_CLASSES = 'relative'
const MOBILE_NAV_CLASSES = 'md:hidden mb-6'
const MOBILE_SELECT_CLASSES = 'w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500'
const LAYOUT_CLASSES = 'grid grid-cols-1 lg:grid-cols-3 gap-6'
const LEFT_COLUMN_CLASSES = 'lg:col-span-2 space-y-6'
const RIGHT_COLUMN_CLASSES = 'lg:col-span-1'
const SECTION_CLASSES = 'bg-white rounded-lg shadow-sm p-6'
const PARAMETERS_SECTION_CLASSES = 'mt-6 pt-6 border-t border-gray-200'
const SECTION_TITLE_CLASSES = 'text-xl font-semibold text-gray-900 mb-4'
const SIMILAR_MASTERS_CLASSES = 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2 gap-4'
const STICKY_BLOCK_CLASSES = 'sticky top-6 space-y-4'
const CONTACT_SECTION_CLASSES = 'bg-white rounded-lg shadow-sm p-6'
const BOOKING_SECTION_CLASSES = 'bg-white rounded-lg shadow-sm p-6'
const QUICK_ACTIONS_CLASSES = 'bg-white rounded-lg shadow-sm p-4 space-y-3'
const FAVORITE_BUTTON_BASE_CLASSES = 'w-full flex items-center gap-2 px-4 py-2 rounded-lg font-medium transition-colors'
const FAVORITE_BUTTON_ACTIVE_CLASSES = 'bg-red-50 text-red-700 border border-red-200'
const FAVORITE_BUTTON_INACTIVE_CLASSES = 'bg-gray-50 text-gray-700 border border-gray-200 hover:bg-gray-100'
const FAVORITE_ICON_CLASSES = 'w-5 h-5'
const SHARE_BUTTON_CLASSES = 'w-full flex items-center gap-2 px-4 py-2 bg-gray-50 text-gray-700 border border-gray-200 rounded-lg font-medium hover:bg-gray-100 transition-colors'
const SHARE_ICON_CLASSES = 'w-5 h-5'
const REPORT_BUTTON_CLASSES = 'w-full flex items-center gap-2 px-4 py-2 bg-gray-50 text-gray-700 border border-gray-200 rounded-lg font-medium hover:bg-gray-100 transition-colors'
const REPORT_ICON_CLASSES = 'w-5 h-5'
const STATS_CLASSES = 'bg-white rounded-lg shadow-sm p-4 space-y-2'
const STAT_ITEM_CLASSES = 'flex items-center gap-2 text-sm text-gray-600'
const STAT_ICON_CLASSES = 'w-4 h-4'
const STAT_TEXT_CLASSES = 'text-sm'
const DESKTOP_NAV_CLASSES = 'hidden lg:block fixed top-20 left-1/2 transform -translate-x-1/2 z-30'
const NAV_CONTAINER_CLASSES = 'flex gap-1 bg-white rounded-full shadow-lg p-1'
const NAV_BUTTON_BASE_CLASSES = 'px-4 py-2 text-sm font-medium rounded-full transition-colors'
const NAV_BUTTON_ACTIVE_CLASSES = 'bg-blue-600 text-white'
const NAV_BUTTON_INACTIVE_CLASSES = 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'

const props = defineProps({
  master: {
    type: Object,
    required: true
  },
  initialReviews: {
    type: Array,
    default: () => []
  },
  similarMasters: {
    type: Array,
    default: () => []
  }
})

// Состояние
const activeSection = ref('gallery')
const favoriteLoading = ref(false)
const showShareModal = ref(false)
const showReportModal = ref(false)

// Композабл для работы с мастером
const { toggleFavorite: masterToggleFavorite, incrementViews } = useMaster(props.master.id)

// Секции навигации
const sections = [
  { id: 'gallery', label: 'Фотографии' },
  { id: 'info', label: 'О мастере' },
  { id: 'services', label: 'Услуги' },
  { id: 'reviews', label: 'Отзывы' },
  { id: 'contacts', label: 'Контакты' }
]

// Вычисляемые свойства
const hasParameters = computed(() => 
  props.master.age || props.master.height || props.master.weight || 
  props.master.hair_color || props.master.features
)

// Методы
const getFavoriteButtonClasses = () => {
  return [
    FAVORITE_BUTTON_BASE_CLASSES,
    props.master.is_favorite ? FAVORITE_BUTTON_ACTIVE_CLASSES : FAVORITE_BUTTON_INACTIVE_CLASSES
  ].join(' ')
}

const getNavButtonClasses = (sectionId) => {
  return [
    NAV_BUTTON_BASE_CLASSES,
    activeSection.value === sectionId ? NAV_BUTTON_ACTIVE_CLASSES : NAV_BUTTON_INACTIVE_CLASSES
  ].join(' ')
}

const scrollToSection = (sectionId) => {
  activeSection.value = sectionId
  
  const element = document.getElementById(sectionId)
  if (element) {
    element.scrollIntoView({ 
      behavior: 'smooth',
      block: 'start',
      inline: 'nearest'
    })
  }
}

const toggleFavorite = async () => {
  if (favoriteLoading.value) return
  
  favoriteLoading.value = true
  try {
    await masterToggleFavorite()
    props.master.is_favorite = !props.master.is_favorite
  } catch (error) {
    console.error('Ошибка при добавлении в избранное:', error)
  } finally {
    favoriteLoading.value = false
  }
}

const shareMaster = () => {
  showShareModal.value = true
}

const reportMaster = () => {
  showReportModal.value = true
}

const handleBookingSuccess = (bookingData) => {
  console.log('Booking success:', bookingData)
  // Показать уведомление об успешном бронировании
}

const handleBookingError = (error) => {
  console.error('Booking error:', error)
  // Показать уведомление об ошибке
}

const handleLoadMoreReviews = () => {
  // Загружаем дополнительные отзывы
  console.log('Loading more reviews...')
}

const handleReportSent = () => {
  // Обработка отправленной жалобы
  console.log('Report sent')
}

const goToMaster = (master) => {
  router.visit(`/masters/${master.id}`)
}

const formatViews = (views) => {
  if (!views) return '0'
  
  if (views >= 1000000) {
    return `${(views / 1000000).toFixed(1)}М`
  } else if (views >= 1000) {
    return `${(views / 1000).toFixed(1)}К`
  }
  
  return views.toString()
}

const formatLastActivity = (timestamp) => {
  return dayjs(timestamp).fromNow()
}

// Intersection Observer для активной секции
let observer = null

const setupIntersectionObserver = () => {
  const options = {
    root: null,
    rootMargin: '-20% 0px -60% 0px',
    threshold: 0
  }

  observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        activeSection.value = entry.target.id
      }
    })
  }, options)

  // Наблюдаем за всеми секциями
  sections.forEach(section => {
    const element = document.getElementById(section.id)
    if (element) {
      observer.observe(element)
    }
  })
}

// Жизненный цикл
onMounted(() => {
  // Увеличиваем счетчик просмотров
  incrementViews()
  
  // Настраиваем наблюдатель
  setupIntersectionObserver()
})

onUnmounted(() => {
  if (observer) {
    observer.disconnect()
  }
})
</script>