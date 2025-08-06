<!-- resources/js/Pages/Dashboard.vue - FSDactored СЃ Loading СЃРѕСЃС‚РѕСЏРЅРёСЏРјРё -->
<template>
  <ProfileLayout>
    <!-- Loading СЃРѕСЃС‚РѕСЏРЅРёРµ -->
    <PageLoader 
      v-if="pageLoader.isLoading.value"
      type="dashboard"
      :message="pageLoader.message.value"
      :show-progress="true"
      :progress="pageLoader.progress.value"
      :skeleton-count="4"
    />
    
    <!-- РћСЃРЅРѕРІРЅРѕР№ РєРѕРЅС‚РµРЅС‚ -->
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
import { logger } from '@/src/shared/lib/logger'
import { onMounted } from 'vue'
import ProfileLayout from '@/src/shared/layouts/ProfileLayout/ProfileLayout.vue'
import { ProfileDashboard } from '@/src/widgets/profile-dashboard'
import PageLoader from '@/src/shared/ui/organisms/PageLoader/PageLoader.vue'
import { usePageLoading } from '@/src/shared/composables/usePageLoading'

// РўРёРїРёР·Р°С†РёСЏ props
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

// Props РёР· Inertia СЃ С‚РёРїРёР·Р°С†РёРµР№
const _props = withDefaults(defineProps<DashboardProps>(), {
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

// РЈРїСЂР°РІР»РµРЅРёРµ Р·Р°РіСЂСѓР·РєРѕР№ СЃС‚СЂР°РЅРёС†С‹
const pageLoader = usePageLoading({
  type: 'dashboard',
  autoStart: true,
  showProgress: true,
  timeout: 15000,
  onStart: () => {
    // Dashboard loading started
  },
  onComplete: () => {
    // Dashboard loading completed
  },
  onError: (error) => {
    logger.error('Dashboard loading error:', error)
  }
})

// РћР±СЂР°Р±РѕС‚С‡РёРєРё Р·Р°РіСЂСѓР·РєРё РґР°РЅРЅС‹С…
const handleStatsLoading = (): void => {
  pageLoader.setProgress(60, 'Р—Р°РіСЂСѓР¶Р°РµРј СЃС‚Р°С‚РёСЃС‚РёРєСѓ...')
}

const handleDataLoaded = (): void => {
  pageLoader.setProgress(90, 'Р¤РёРЅР°Р»РёР·Р°С†РёСЏ РґР°РЅРЅС‹С…...')
  setTimeout(() => {
    pageLoader.completeLoading()
  }, 500)
}

// Р›РѕРіРёРєР° Р·Р°РіСЂСѓР·РєРё РїСЂРё РјРѕРЅС‚РёСЂРѕРІР°РЅРёРё
onMounted(() => {
  // РџРѕСЌС‚Р°РїРЅР°СЏ Р·Р°РіСЂСѓР·РєР° РґР»СЏ Р»СѓС‡С€РµРіРѕ UX
  setTimeout(() => {
    pageLoader.setProgress(20, 'Р—Р°РіСЂСѓР¶Р°РµРј СЃС‡РµС‚С‡РёРєРё...')
  }, 300)

  setTimeout(() => {
    pageLoader.setProgress(40, 'Р—Р°РіСЂСѓР¶Р°РµРј РѕР±СЉСЏРІР»РµРЅРёСЏ...')
  }, 800)

  setTimeout(() => {
    pageLoader.setProgress(70, 'РћР±СЂР°Р±Р°С‚С‹РІР°РµРј СЃС‚Р°С‚РёСЃС‚РёРєСѓ...')
  }, 1200)

  setTimeout(() => {
    pageLoader.setProgress(90, 'РџРѕРґРіРѕС‚Р°РІР»РёРІР°РµРј РёРЅС‚РµСЂС„РµР№СЃ...')
  }, 1600)

  setTimeout(() => {
    pageLoader.completeLoading()
  }, 2000)
})
</script>

<style scoped>
/* РџР»Р°РІРЅС‹Рµ РїРµСЂРµС…РѕРґС‹ РјРµР¶РґСѓ СЃРѕСЃС‚РѕСЏРЅРёСЏРјРё */
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