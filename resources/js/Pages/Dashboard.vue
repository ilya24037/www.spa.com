<!-- resources/js/Pages/Dashboard.vue - FSD Refactored с Loading состояниями -->
<template>
  <ProfileLayout>
    <!-- Loading состояние -->
    <PageLoader 
      v-if="pageLoader.isLoading.value"
      type="dashboard"
      :message="pageLoader.message.value"
      :show-progress="true"
      :progress="pageLoader.progress.value"
      :skeleton-count="4"
    />
    
    <!-- Основной контент -->
    <ProfileDashboard 
      v-else
      :ads="ads"
      :counts="counts"
      :stats="userStats"
      @stats-loading="handleStatsLoading"
      @data-loaded="handleDataLoaded"
    />
  </ProfileLayout>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import ProfileLayout from '@/src/shared/layouts/ProfileLayout/ProfileLayout.vue'
import { ProfileDashboard } from '@/src/widgets/profile-dashboard'
import PageLoader from '@/src/shared/ui/organisms/PageLoader/PageLoader.vue'
import { usePageLoading } from '@/src/shared/composables/usePageLoading'

// Типизация props
interface DashboardCounts {
  ads: number
  bookings: number
  reviews: number
  favorites: number
  waiting: number
  active: number
  drafts: number
  archived: number
}

interface UserStats {
  views: number
  calls: number
  bookings: number
  revenue: number
}

interface DashboardProps {
  ads: any[]
  counts: DashboardCounts
  userStats: UserStats
}

// Props из Inertia с типизацией
const props = withDefaults(defineProps<DashboardProps>(), {
  ads: () => [],
  counts: () => ({
    ads: 0,
    bookings: 0,
    reviews: 0,
    favorites: 0,
    waiting: 0,
    active: 0,
    drafts: 0,
    archived: 0
  }),
  userStats: () => ({
    views: 0,
    calls: 0,
    bookings: 0,
    revenue: 0
  })
})

// Управление загрузкой страницы
const pageLoader = usePageLoading({
  type: 'dashboard',
  autoStart: true,
  showProgress: true,
  timeout: 15000,
  onStart: () => {
    console.log('Dashboard loading started')
  },
  onComplete: () => {
    console.log('Dashboard loading completed')
  },
  onError: (error) => {
    console.error('Dashboard loading error:', error)
  }
})

// Обработчики загрузки данных
const handleStatsLoading = (): void => {
  pageLoader.setProgress(60, 'Загружаем статистику...')
}

const handleDataLoaded = (): void => {
  pageLoader.setProgress(90, 'Финализация данных...')
  setTimeout(() => {
    pageLoader.completeLoading()
  }, 500)
}

// Логика загрузки при монтировании
onMounted(() => {
  // Поэтапная загрузка для лучшего UX
  setTimeout(() => {
    pageLoader.setProgress(20, 'Загружаем счетчики...')
  }, 300)

  setTimeout(() => {
    pageLoader.setProgress(40, 'Загружаем объявления...')
  }, 800)

  setTimeout(() => {
    pageLoader.setProgress(70, 'Обрабатываем статистику...')
  }, 1200)

  setTimeout(() => {
    pageLoader.setProgress(90, 'Подготавливаем интерфейс...')
  }, 1600)

  setTimeout(() => {
    pageLoader.completeLoading()
  }, 2000)
})
</script>

<style scoped>
/* Плавные переходы между состояниями */
.dashboard-transition-enter-active,
.dashboard-transition-leave-active {
  transition: all 0.3s ease;
}

.dashboard-transition-enter-from {
  opacity: 0;
  transform: translateY(20px);
}

.dashboard-transition-leave-to {
  opacity: 0;
  transform: translateY(-20px);
}
</style>