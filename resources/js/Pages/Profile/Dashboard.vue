<!-- resources/js/Pages/Profile/Dashboard.vue - FSD Refactored -->
<template>
  <Head title="Личный кабинет" />
  
  <ProfileLayout 
    :user="user"
    :counts="counts"
    :active-tab="activeTab"
    @tab-change="handleTabChange"
  >
    <!-- Контент личного кабинета -->
    <div class="space-y-6">
      
      <!-- Заголовок страницы -->
      <div class="flex items-start justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ getPageTitle() }}</h1>
          <p class="text-gray-600 mt-1">{{ getPageDescription() }}</p>
        </div>
        
        <!-- Действия для текущей вкладки -->
        <div v-if="showActions" class="flex gap-3">
          <button
            v-if="activeTab === 'ads'"
            @click="goToCreateAd"
            class="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Создать объявление
          </button>
        </div>
      </div>

      <!-- Контент вкладки -->
      <div class="bg-white rounded-lg shadow-sm">
        
        <!-- Мои объявления -->
        <div v-if="activeTab === 'ads'" class="p-6">
          <div class="mb-6">
            <h3 class="text-lg font-semibold mb-4">Мои объявления</h3>
            <AdStatusFilter 
              :current-status="adStatusFilter"
              :counts="adStatusCounts"
              @change="handleStatusFilterChange"
            />
          </div>
          
          <AdCardList 
            :ads="filteredAds"
            :loading="adsLoading"
            layout="grid"
            @edit="editAd"
            @delete="deleteAd"
            @duplicate="duplicateAd"
            @status-change="handleAdStatusChange"
          />
          
          <div v-if="filteredAds.length === 0 && !adsLoading" class="text-center py-8">
            <p class="text-gray-500 mb-4">У вас пока нет объявлений</p>
            <button
              @click="goToCreateAd"
              class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
            >
              Создать первое объявление
            </button>
          </div>
        </div>
        
        <!-- Заказы -->
        <div v-else-if="activeTab === 'bookings'" class="p-6">
          <h3 class="text-lg font-semibold mb-4">Заказы</h3>
          <BookingWidget 
            :bookings="userBookings"
            :loading="bookingsLoading"
            mode="list"
            @confirm="confirmBooking"
            @cancel="cancelBooking" 
            @complete="completeBooking"
          />
        </div>
        
        <!-- Отзывы -->
        <div v-else-if="activeTab === 'reviews'" class="p-6">
          <h3 class="text-lg font-semibold mb-4">Отзывы</h3>
          <div class="space-y-4">
            <div v-for="review in userReviews" :key="review.id" class="border rounded-lg p-4">
              <div class="flex items-start justify-between">
                <div>
                  <h4 class="font-medium">{{ review.client_name }}</h4>
                  <div class="flex items-center gap-2 mt-1">
                    <StarRating :rating="review.rating" :readonly="true" size="sm" />
                    <span class="text-sm text-gray-600">{{ formatDate(review.created_at) }}</span>
                  </div>
                  <p class="text-gray-700 mt-2">{{ review.comment }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Избранное -->
        <div v-else-if="activeTab === 'favorites'" class="p-6">
          <h3 class="text-lg font-semibold mb-4">Избранное</h3>
          <MasterCardList 
            :masters="userFavorites"
            :loading="favoritesLoading"
            layout="grid"
            @click="goToMaster"
            @favorite="removeFromFavorites"
          />
        </div>
        
        <!-- Сообщения -->
        <div v-else-if="activeTab === 'messages'" class="p-6">
          <h3 class="text-lg font-semibold mb-4">Сообщения</h3>
          <div class="text-center py-8">
            <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
            </svg>
            <p class="text-gray-500">Сообщений пока нет</p>
          </div>
        </div>
        
        <!-- Настройки -->
        <div v-else-if="activeTab === 'settings'" class="p-6">
          <h3 class="text-lg font-semibold mb-4">Настройки</h3>
          <div class="space-y-6">
            <div>
              <h4 class="font-medium mb-2">Профиль</h4>
              <p class="text-gray-600 text-sm mb-4">Управление информацией профиля</p>
              <button 
                @click="goToProfileEdit"
                class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
              >
                Редактировать профиль
              </button>
            </div>
            
            <div>
              <h4 class="font-medium mb-2">Уведомления</h4>
              <p class="text-gray-600 text-sm mb-4">Настройка уведомлений и оповещений</p>
              <button 
                @click="goToNotificationSettings"
                class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
              >
                Настройки уведомлений
              </button>
            </div>
          </div>
        </div>
      </div>
      
    </div>
  </ProfileLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, router } from '@inertiajs/vue3'

// 🎯 FSD Импорты
import { ProfileLayout } from '@/src/shared'
import { StarRating } from '@/src/shared'
import { AdCardList, AdStatusFilter } from '@/src/entities/ad'
import { BookingWidget } from '@/src/entities/booking' 
import { MasterCardList } from '@/src/entities/master'

// Props из Inertia
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
const favoritesLoading = ref(false)
const adStatusFilter = ref('all')

// Данные
const userAds = ref([])
const userBookings = ref([])
const userReviews = ref([])
const userFavorites = ref([])

// Вычисляемые свойства
const showActions = computed(() => {
  return ['ads', 'bookings'].includes(activeTab.value)
})

const adStatusCounts = computed(() => ({
  all: props.counts.ads || 0,
  active: 0,
  draft: 0,
  waiting_payment: 0,
  archived: 0
}))

const filteredAds = computed(() => {
  if (adStatusFilter.value === 'all') {
    return userAds.value
  }
  return userAds.value.filter(ad => ad.status === adStatusFilter.value)
})

// Методы
const handleTabChange = (tab) => {
  activeTab.value = tab
  loadTabData(tab)
  
  // Обновляем URL без перезагрузки
  const url = new URL(window.location)
  url.searchParams.set('tab', tab)
  window.history.replaceState({}, '', url)
}

const loadTabData = async (tab) => {
  switch (tab) {
    case 'ads':
      await loadUserAds()
      break
    case 'bookings':
      await loadUserBookings()
      break
    case 'favorites':
      await loadUserFavorites()
      break
    case 'reviews':
      await loadUserReviews()
      break
  }
}

const loadUserAds = async () => {
  adsLoading.value = true
  try {
    // API вызов для загрузки объявлений
    // const response = await api.getUserAds()
    // userAds.value = response.data
    
    // Моковые данные
    userAds.value = []
  } finally {
    adsLoading.value = false
  }
}

const loadUserBookings = async () => {
  bookingsLoading.value = true
  try {
    // API вызов для загрузки заказов
    userBookings.value = []
  } finally {
    bookingsLoading.value = false
  }
}

const loadUserFavorites = async () => {
  favoritesLoading.value = true
  try {
    // API вызов для загрузки избранного
    userFavorites.value = []
  } finally {
    favoritesLoading.value = false
  }
}

const loadUserReviews = async () => {
  try {
    // API вызов для загрузки отзывов
    userReviews.value = []
  } finally {
    // reviewsLoading.value = false
  }
}

const getPageTitle = () => {
  const titles = {
    ads: 'Мои объявления',
    bookings: 'Заказы',
    reviews: 'Отзывы',
    favorites: 'Избранное',
    messages: 'Сообщения',
    settings: 'Настройки'
  }
  return titles[activeTab.value] || 'Личный кабинет'
}

const getPageDescription = () => {
  const descriptions = {
    ads: 'Управляйте своими объявлениями',
    bookings: 'Отслеживайте заказы и записи',
    reviews: 'Просматривайте отзывы о ваших услугах',
    favorites: 'Ваши избранные мастера',
    messages: 'Переписка с клиентами',
    settings: 'Настройки профиля и безопасности'
  }
  return descriptions[activeTab.value] || 'Добро пожаловать в личный кабинет'
}

const handleStatusFilterChange = (status) => {
  adStatusFilter.value = status
}

const handleAdStatusChange = (ad, newStatus) => {
  // Обновление статуса объявления
  console.log('Changing ad status:', ad.id, newStatus)
}

const editAd = (ad) => {
  router.visit(`/ads/${ad.id}/edit`)
}

const deleteAd = (ad) => {
  if (confirm('Вы уверены, что хотите удалить объявление?')) {
    // API вызов для удаления
    console.log('Deleting ad:', ad.id)
  }
}

const duplicateAd = (ad) => {
  router.visit(`/ads/create?duplicate=${ad.id}`)
}

const confirmBooking = (booking) => {
  console.log('Confirming booking:', booking.id)
}

const cancelBooking = (booking) => {
  if (confirm('Вы уверены, что хотите отменить запись?')) {
    console.log('Cancelling booking:', booking.id)
  }
}

const completeBooking = (booking) => {
  console.log('Completing booking:', booking.id)
}

const goToMaster = (master) => {
  router.visit(`/masters/${master.id}`)
}

const removeFromFavorites = (master) => {
  console.log('Removing from favorites:', master.id)
}

const goToCreateAd = () => {
  router.visit('/ads/create')
}

const goToProfileEdit = () => {
  router.visit('/profile/edit')
}

const goToNotificationSettings = () => {
  router.visit('/settings/notifications')
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('ru-RU')
}

// Жизненный цикл
onMounted(() => {
  loadTabData(activeTab.value)
})
</script>
