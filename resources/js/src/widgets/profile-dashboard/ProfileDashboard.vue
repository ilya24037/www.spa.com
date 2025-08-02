<!-- resources/js/src/widgets/profile-dashboard/ProfileDashboard.vue -->
<template>
  <div :class="CONTAINER_CLASSES">
    <div :class="LAYOUT_CLASSES">
      <!-- Боковая панель -->
      <div :class="SIDEBAR_CLASSES">
        <!-- Профильная карточка -->
        <div :class="PROFILE_CARD_CLASSES">
          <div :class="AVATAR_SECTION_CLASSES">
            <img
              :src="user.avatar || '/images/default-avatar.svg'"
              :alt="user.name"
              :class="AVATAR_CLASSES"
            >
            <div>
              <h3 :class="USER_NAME_CLASSES">{{ user.name }}</h3>
              <p :class="USER_EMAIL_CLASSES">{{ user.email }}</p>
              <div v-if="user.is_master" :class="MASTER_BADGE_CLASSES">
                <svg :class="MASTER_ICON_CLASSES" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                Мастер
              </div>
            </div>
          </div>
        </div>

        <!-- Статистика -->
        <div :class="STATS_CARD_CLASSES">
          <h4 :class="STATS_TITLE_CLASSES">Статистика</h4>
          <div :class="STATS_GRID_CLASSES">
            <div :class="STAT_ITEM_CLASSES">
              <div :class="STAT_VALUE_CLASSES">{{ counts.ads }}</div>
              <div :class="STAT_LABEL_CLASSES">Объявлений</div>
            </div>
            
            <div v-if="user.is_master" :class="STAT_ITEM_CLASSES">
              <div :class="STAT_VALUE_CLASSES">{{ counts.bookings }}</div>
              <div :class="STAT_LABEL_CLASSES">Записей</div>
            </div>
            
            <div v-if="user.is_master" :class="STAT_ITEM_CLASSES">
              <div :class="STAT_VALUE_CLASSES">{{ counts.reviews }}</div>
              <div :class="STAT_LABEL_CLASSES">Отзывов</div>
            </div>
            
            <div :class="STAT_ITEM_CLASSES">
              <div :class="STAT_VALUE_CLASSES">{{ counts.favorites }}</div>
              <div :class="STAT_LABEL_CLASSES">Избранных</div>
            </div>
          </div>
        </div>

        <!-- Навигация -->
        <nav :class="NAVIGATION_CLASSES">
          <ProfileTabs
            :active-tab="activeTab"
            :tabs="availableTabs"
            @tab-change="setActiveTab"
          />
        </nav>
      </div>

      <!-- Основной контент -->
      <div :class="MAIN_CONTENT_CLASSES">
        <!-- Хлебные крошки и заголовок -->
        <div :class="CONTENT_HEADER_CLASSES">
          <div>
            <h1 :class="PAGE_TITLE_CLASSES">{{ currentTabLabel }}</h1>
            <p v-if="currentTabDescription" :class="PAGE_DESCRIPTION_CLASSES">
              {{ currentTabDescription }}
            </p>
          </div>
          
          <!-- Кнопки действий -->
          <div :class="ACTIONS_CLASSES">
            <button
              v-if="activeTab === 'ads'"
              @click="createNewAd"
              :class="PRIMARY_BUTTON_CLASSES"
            >
              <svg :class="BUTTON_ICON_CLASSES" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
              </svg>
              Создать объявление
            </button>
            
            <button
              v-if="user.is_master && activeTab === 'profile'"
              @click="editProfile"
              :class="SECONDARY_BUTTON_CLASSES"
            >
              <svg :class="BUTTON_ICON_CLASSES" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
              </svg>
              Редактировать
            </button>
          </div>
        </div>

        <!-- Контент вкладки -->
        <div :class="TAB_CONTENT_CLASSES">
          <!-- Мои объявления -->
          <div v-if="activeTab === 'ads'" :class="CONTENT_SECTION_CLASSES">
            <AdList
              :ads="userAds"
              :loading="adsLoading"
              :status-filter="adStatusFilter"
              @status-change="handleAdStatusChange"
              @edit="editAd"
              @delete="deleteAd"
              @duplicate="duplicateAd"
            />
          </div>

          <!-- Записи (для мастеров) -->
          <div v-else-if="activeTab === 'bookings' && user.is_master" :class="CONTENT_SECTION_CLASSES">
            <BookingsList
              :bookings="userBookings"
              :loading="bookingsLoading"
              @confirm="confirmBooking"
              @cancel="cancelBooking"
              @complete="completeBooking"
            />
          </div>

          <!-- Отзывы -->
          <div v-else-if="activeTab === 'reviews'" :class="CONTENT_SECTION_CLASSES">
            <ReviewsList
              :reviews="userReviews"
              :loading="reviewsLoading"
              :is-master="user.is_master"
              @respond="respondToReview"
            />
          </div>

          <!-- Избранное -->
          <div v-else-if="activeTab === 'favorites'" :class="CONTENT_SECTION_CLASSES">
            <FavoritesList
              :favorites="userFavorites"
              :loading="favoritesLoading"
              @remove="removeFromFavorites"
              @go-to="goToMaster"
            />
          </div>

          <!-- Профиль -->
          <div v-else-if="activeTab === 'profile'" :class="CONTENT_SECTION_CLASSES">
            <ProfileSettings
              :user="user"
              :loading="profileLoading"
              @update="updateProfile"
            />
          </div>

          <!-- Финансы (для мастеров) -->
          <div v-else-if="activeTab === 'finances' && user.is_master" :class="CONTENT_SECTION_CLASSES">
            <FinancialDashboard
              :stats="financialStats"
              :transactions="transactions"
              :loading="financesLoading"
            />
          </div>

          <!-- Настройки -->
          <div v-else-if="activeTab === 'settings'" :class="CONTENT_SECTION_CLASSES">
            <UserSettings
              :settings="userSettings"
              :loading="settingsLoading"
              @update="updateSettings"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { ProfileTabs } from '@/src/features/profile-navigation'
import AdList from './components/AdList.vue'
import BookingsList from './components/BookingsList.vue'
import ReviewsList from './components/ReviewsList.vue'
import FavoritesList from './components/FavoritesList.vue'
import ProfileSettings from './components/ProfileSettings.vue'
import FinancialDashboard from './components/FinancialDashboard.vue'
import UserSettings from './components/UserSettings.vue'

// 🎯 Стили согласно дизайн-системе
const CONTAINER_CLASSES = 'min-h-screen bg-gray-50'
const LAYOUT_CLASSES = 'flex gap-6 max-w-7xl mx-auto p-6'
const SIDEBAR_CLASSES = 'w-80 flex-shrink-0 space-y-6'
const PROFILE_CARD_CLASSES = 'bg-white rounded-lg shadow-sm p-6'
const AVATAR_SECTION_CLASSES = 'flex items-center gap-4'
const AVATAR_CLASSES = 'w-16 h-16 rounded-full object-cover'
const USER_NAME_CLASSES = 'font-semibold text-gray-900'
const USER_EMAIL_CLASSES = 'text-sm text-gray-600'
const MASTER_BADGE_CLASSES = 'flex items-center gap-1 mt-1 px-2 py-0.5 bg-green-100 text-green-700 text-xs font-medium rounded-full'
const MASTER_ICON_CLASSES = 'w-3 h-3'
const STATS_CARD_CLASSES = 'bg-white rounded-lg shadow-sm p-6'
const STATS_TITLE_CLASSES = 'font-semibold text-gray-900 mb-4'
const STATS_GRID_CLASSES = 'grid grid-cols-2 gap-4'
const STAT_ITEM_CLASSES = 'text-center'
const STAT_VALUE_CLASSES = 'text-2xl font-bold text-blue-600'
const STAT_LABEL_CLASSES = 'text-sm text-gray-600 mt-1'
const NAVIGATION_CLASSES = 'bg-white rounded-lg shadow-sm'
const MAIN_CONTENT_CLASSES = 'flex-1 min-w-0'
const CONTENT_HEADER_CLASSES = 'flex items-start justify-between mb-6'
const PAGE_TITLE_CLASSES = 'text-2xl font-bold text-gray-900'
const PAGE_DESCRIPTION_CLASSES = 'text-gray-600 mt-1'
const ACTIONS_CLASSES = 'flex gap-3'
const PRIMARY_BUTTON_CLASSES = 'flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors'
const SECONDARY_BUTTON_CLASSES = 'flex items-center gap-2 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-colors'
const BUTTON_ICON_CLASSES = 'w-5 h-5'
const TAB_CONTENT_CLASSES = 'space-y-6'
const CONTENT_SECTION_CLASSES = 'bg-white rounded-lg shadow-sm'

const props = defineProps({
  user: {
    type: Object,
    required: true
  },
  initialTab: {
    type: String,
    default: 'ads'
  },
  counts: {
    type: Object,
    default: () => ({
      ads: 0,
      bookings: 0,
      reviews: 0,
      favorites: 0
    })
  }
})

// Состояние
const activeTab = ref(props.initialTab)
const adsLoading = ref(false)
const bookingsLoading = ref(false)
const reviewsLoading = ref(false)
const favoritesLoading = ref(false)
const profileLoading = ref(false)
const financesLoading = ref(false)
const settingsLoading = ref(false)

// Данные для разных вкладок
const userAds = ref([])
const userBookings = ref([])
const userReviews = ref([])
const userFavorites = ref([])
const financialStats = ref({})
const transactions = ref([])
const userSettings = ref({})

// Фильтры
const adStatusFilter = ref('all')

// Вычисляемые свойства
const availableTabs = computed(() => {
  const baseTabs = [
    { key: 'ads', label: 'Объявления', count: props.counts.ads },
    { key: 'reviews', label: 'Отзывы', count: props.counts.reviews },
    { key: 'favorites', label: 'Избранное', count: props.counts.favorites },
    { key: 'profile', label: 'Профиль' },
    { key: 'settings', label: 'Настройки' }
  ]

  // Добавляем вкладки для мастеров
  if (props.user.is_master) {
    baseTabs.splice(1, 0, 
      { key: 'bookings', label: 'Записи', count: props.counts.bookings },
      { key: 'finances', label: 'Финансы', additional: true }
    )
  }

  return baseTabs
})

const currentTabLabel = computed(() => {
  const tab = availableTabs.value.find(t => t.key === activeTab.value)
  return tab?.label || 'Профиль'
})

const currentTabDescription = computed(() => {
  const descriptions = {
    ads: 'Управляйте своими объявлениями',
    bookings: 'Записи клиентов на ваши услуги',
    reviews: 'Отзывы о вашей работе',
    favorites: 'Избранные мастера и услуги',
    profile: 'Настройки профиля и публичная информация',
    finances: 'Доходы, расходы и финансовая отчетность',
    settings: 'Настройки аккаунта и уведомлений'
  }
  
  return descriptions[activeTab.value]
})

// Методы
const setActiveTab = (tab) => {
  activeTab.value = tab
  loadTabData(tab)
}

const loadTabData = async (tab) => {
  switch (tab) {
    case 'ads':
      adsLoading.value = true
      try {
        // Загрузка объявлений пользователя
        await new Promise(resolve => setTimeout(resolve, 500))
        userAds.value = [] // API вызов
      } finally {
        adsLoading.value = false
      }
      break

    case 'bookings':
      if (props.user.is_master) {
        bookingsLoading.value = true
        try {
          await new Promise(resolve => setTimeout(resolve, 500))
          userBookings.value = [] // API вызов
        } finally {
          bookingsLoading.value = false
        }
      }
      break

    case 'reviews':
      reviewsLoading.value = true
      try {
        await new Promise(resolve => setTimeout(resolve, 500))
        userReviews.value = [] // API вызов
      } finally {
        reviewsLoading.value = false
      }
      break

    case 'favorites':
      favoritesLoading.value = true
      try {
        await new Promise(resolve => setTimeout(resolve, 500))
        userFavorites.value = [] // API вызов
      } finally {
        favoritesLoading.value = false
      }
      break

    case 'finances':
      if (props.user.is_master) {
        financesLoading.value = true
        try {
          await new Promise(resolve => setTimeout(resolve, 500))
          financialStats.value = {} // API вызов
          transactions.value = [] // API вызов
        } finally {
          financesLoading.value = false
        }
      }
      break

    case 'settings':
      settingsLoading.value = true
      try {
        await new Promise(resolve => setTimeout(resolve, 500))
        userSettings.value = {} // API вызов
      } finally {
        settingsLoading.value = false
      }
      break
  }
}

const createNewAd = () => {
  router.visit('/ads/create')
}

const editProfile = () => {
  router.visit('/profile/edit')
}

const editAd = (ad) => {
  router.visit(`/ads/${ad.id}/edit`)
}

const deleteAd = async (ad) => {
  if (confirm('Вы уверены, что хотите удалить это объявление?')) {
    // API вызов для удаления
    console.log('Deleting ad:', ad.id)
  }
}

const duplicateAd = (ad) => {
  router.visit(`/ads/create?duplicate=${ad.id}`)
}

const handleAdStatusChange = (status) => {
  adStatusFilter.value = status
  // Фильтрация объявлений
}

const confirmBooking = async (booking) => {
  // API вызов для подтверждения записи
  console.log('Confirming booking:', booking.id)
}

const cancelBooking = async (booking) => {
  if (confirm('Вы уверены, что хотите отменить эту запись?')) {
    // API вызов для отмены записи
    console.log('Cancelling booking:', booking.id)
  }
}

const completeBooking = async (booking) => {
  // API вызов для завершения записи
  console.log('Completing booking:', booking.id)
}

const respondToReview = async (review, response) => {
  // API вызов для ответа на отзыв
  console.log('Responding to review:', review.id, response)
}

const removeFromFavorites = async (favorite) => {
  // API вызов для удаления из избранного
  console.log('Removing from favorites:', favorite.id)
}

const goToMaster = (master) => {
  router.visit(`/masters/${master.id}`)
}

const updateProfile = async (profileData) => {
  profileLoading.value = true
  try {
    // API вызов для обновления профиля
    console.log('Updating profile:', profileData)
  } finally {
    profileLoading.value = false
  }
}

const updateSettings = async (settings) => {
  settingsLoading.value = true
  try {
    // API вызов для обновления настроек
    console.log('Updating settings:', settings)
  } finally {
    settingsLoading.value = false
  }
}

// Жизненный цикл
onMounted(() => {
  loadTabData(activeTab.value)
})

// Отслеживание изменения URL для активной вкладки
watch(() => activeTab.value, (newTab) => {
  // Обновляем URL без перезагрузки страницы
  const url = new URL(window.location)
  url.searchParams.set('tab', newTab)
  window.history.replaceState({}, '', url)
})
</script>