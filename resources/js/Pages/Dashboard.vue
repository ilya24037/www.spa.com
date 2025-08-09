<!-- Панель управления - полная FSD миграция -->
<template>
  <Head title="Панель управления" />
  
  <!-- ProfileDashboard Widget - единая точка входа -->
  <ProfileDashboard
    :ads="ads"
    :stats="dashboardStats"
    :counts="counts"
    @stats-loading="handleStatsLoading"
    @data-loaded="handleDataLoaded"
  >
    <!-- Основной контент через slot -->
    <div class="space-y-6">
      <!-- Быстрые действия -->
      <div class="bg-white rounded-lg shadow-sm border p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">
          Быстрые действия
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <Link
            href="/ads/create"
            class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Создать объявление
          </Link>
          
          <Link
            href="/profile/edit"
            class="inline-flex items-center justify-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            Редактировать профиль
          </Link>
          
          <Link
            href="/bookings"
            class="inline-flex items-center justify-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4m-6 0h6m-6 0L8 21l-2-2m6-16L14 21l2-2" />
            </svg>
            Мои записи
          </Link>
          
          <Link
            href="/messages"
            class="inline-flex items-center justify-center px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
            Сообщения
            <span v-if="counts.unreadMessages > 0" class="ml-2 bg-red-500 text-white text-xs px-2 py-1 rounded-full">
              {{ counts.unreadMessages }}
            </span>
          </Link>
        </div>
      </div>

      <!-- Последние объявления -->
      <div class="bg-white rounded-lg shadow-sm border p-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold text-gray-900">
            Последние объявления
          </h2>
          <Link
            href="/ads"
            class="text-blue-600 hover:text-blue-800 text-sm font-medium"
          >
            Посмотреть все →
          </Link>
        </div>
        
        <!-- Loading состояние -->
        <div v-if="isLoading" class="space-y-4">
          <div v-for="i in 3" :key="i" class="animate-pulse">
            <div class="h-20 bg-gray-200 rounded-lg"></div>
          </div>
        </div>
        
        <!-- Пустое состояние -->
        <div v-else-if="!ads || ads.length === 0" class="text-center py-8">
          <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <p class="text-gray-500 text-lg mb-2">У вас пока нет объявлений</p>
          <p class="text-gray-400 mb-4">Создайте первое объявление, чтобы начать получать клиентов</p>
          <Link
            href="/ads/create"
            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
          >
            Создать объявление
          </Link>
        </div>
        
        <!-- Список объявлений -->
        <div v-else class="space-y-4">
          <AdCardListItem
            v-for="ad in recentAds"
            :key="ad.id"
            :ad="ad"
            :show-actions="true"
            @edit="handleEdit"
            @delete="handleDelete"
            @toggle-status="handleToggleStatus"
          />
        </div>
      </div>

      <!-- Последние отзывы -->
      <div v-if="recentReviews && recentReviews.length > 0" class="bg-white rounded-lg shadow-sm border p-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold text-gray-900">
            Последние отзывы
          </h2>
          <Link
            href="/reviews"
            class="text-blue-600 hover:text-blue-800 text-sm font-medium"
          >
            Посмотреть все →
          </Link>
        </div>
        
        <div class="space-y-4">
          <ReviewCard
            v-for="review in recentReviews"
            :key="review.id"
            :review="review"
            :compact="true"
          />
        </div>
      </div>
    </div>
  </ProfileDashboard>
</template>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

// FSD imports
import { ProfileDashboard } from '@/src/widgets/profile-dashboard'
import { AdCardListItem } from '@/src/entities/ad/ui/AdCard'
import { ReviewCard } from '@/src/entities/review/ui/ReviewCard'

// Stores
import { useProfileNavigationStore } from '@/src/features/profile-navigation/model/navigation.store'

// Props из Inertia
interface DashboardPageProps {
  ads?: Ad[]
  stats?: DashboardStats
  counts?: {
    totalAds: number
    activeAds: number
    waitingAds: number
    completedAds: number
    unreadMessages: number
    totalViews: number
    totalFavorites: number
  }
  recentReviews?: Review[]
}

interface Ad {
  id: number
  title: string
  status: 'active' | 'pending' | 'archived'
  created_at: string
  views: number
  favorites: number
  [key: string]: any
}

interface Review {
  id: number
  rating: number
  comment: string
  created_at: string
  client_name: string
  [key: string]: any
}

interface DashboardStats {
  views: number
  favorites: number
  messages: number
  earnings: number
  viewsTrend?: number
  favoritesTrend?: number
  messagesTrend?: number
  earningsTrend?: number
}

const props = withDefaults(defineProps<DashboardPageProps>(), {
  ads: () => [],
  recentReviews: () => [],
  counts: () => ({
    totalAds: 0,
    activeAds: 0,
    waitingAds: 0,
    completedAds: 0,
    unreadMessages: 0,
    totalViews: 0,
    totalFavorites: 0
  }),
  stats: () => ({
    views: 0,
    favorites: 0,
    messages: 0,
    earnings: 0
  })
})

// Store
const navigationStore = useProfileNavigationStore()

// Local state
const isLoading = ref(false)

// Computed
const dashboardStats = computed<DashboardStats>(() => ({
  views: props.counts?.totalViews || 0,
  favorites: props.counts?.totalFavorites || 0,
  messages: props.counts?.unreadMessages || 0,
  earnings: 0, // Пока нет данных о заработке
  viewsTrend: 5, // Примерный рост
  favoritesTrend: 2,
  messagesTrend: -1,
  earningsTrend: 0
}))

const recentAds = computed(() => 
  props.ads?.slice(0, 5) || []
)

const recentReviews = computed(() =>
  props.recentReviews?.slice(0, 3) || []
)

// Methods
const handleStatsLoading = () => {
  isLoading.value = true
}

const handleDataLoaded = () => {
  isLoading.value = false
}

const handleEdit = (adId: number) => {
  window.location.href = `/ads/${adId}/edit`
}

const handleDelete = async (adId: number) => {
  if (confirm('Вы уверены, что хотите удалить это объявление?')) {
    try {
      // API call to delete ad
      await fetch(`/api/ads/${adId}`, { method: 'DELETE' })
      window.location.reload()
    } catch (error) {
      logger.error('Error deleting ad:', error)
    }
  }
}

const handleToggleStatus = async (adId: number, newStatus: string) => {
  try {
    // API call to toggle status
    await fetch(`/api/ads/${adId}/status`, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ status: newStatus })
    })
    window.location.reload()
  } catch (error) {
    logger.error('Error toggling status:', error)
  }
}

// Initialize navigation store
import { onMounted } from 'vue'
import { logger } from '@/src/shared/utils/logger'
onMounted(() => {
  // Обновить счетчики в табах
  navigationStore.updateAllCounts({
    waiting: props.counts?.waitingAds || 0,
    active: props.counts?.activeAds || 0,
    completed: props.counts?.completedAds || 0,
    drafts: 0, // Нужно добавить в props
    favorites: props.counts?.totalFavorites || 0,
    settings: 0
  })
})
</script>

<style scoped>
/* Стили специфичные для дашборда */
.dashboard-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
}

/* Анимации для статистики */
.stat-card {
  transition: transform 0.2s ease-in-out;
}

.stat-card:hover {
  transform: translateY(-2px);
}
</style>