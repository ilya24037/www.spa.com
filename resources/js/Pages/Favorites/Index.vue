<!-- РЎС‚СЂР°РЅРёС†Р° РёР·Р±СЂР°РЅРЅРѕРіРѕ (/favorites) -->
<template>
    <Head title="РР·Р±СЂР°РЅРЅРѕРµ" />
    
    <!-- РћР±РµСЂС‚РєР° СЃ РїСЂР°РІРёР»СЊРЅС‹РјРё РѕС‚СЃС‚СѓРїР°РјРё РєР°Рє РІ Dashboard -->
    <div class="py-6 lg:py-8">
        <!-- Loading СЃРѕСЃС‚РѕСЏРЅРёРµ -->
        <PageLoader 
            v-if="pageLoader.isLoading.value"
            type="catalog"
            :message="pageLoader.message.value"
            :show-progress="false"
            :skeleton-count="3"
        />
        
        <!-- РћСЃРЅРѕРІРЅРѕР№ РєРѕРЅС‚РµРЅС‚ -->
        <template v-else>
            <!-- РћСЃРЅРѕРІРЅРѕР№ РєРѕРЅС‚РµРЅС‚ СЃ РіСЌРїРѕРј РјРµР¶РґСѓ Р±Р»РѕРєР°РјРё -->
            <div class="flex gap-6">
                
                <!-- Р‘РѕРєРѕРІР°СЏ РїР°РЅРµР»СЊ -->
                <ProfileSidebar 
                    :counts="counts"
                    :user-stats="userStats"
                />
                
                <!-- РћСЃРЅРѕРІРЅРѕР№ РєРѕРЅС‚РµРЅС‚ -->
                <main class="flex-1">
                    <ContentCard title="РР·Р±СЂР°РЅРЅС‹Рµ РјР°СЃС‚РµСЂР°">
                    <div v-if="favorites.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <MasterCard 
                            v-for="master in favorites"
                            :key="master.id"
                            :master="master as any"
                        />
                    </div>
                    
                    <div v-else class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                        </svg>
                        <p class="text-gray-500 text-lg mb-4">РЈ РІР°СЃ РїРѕРєР° РЅРµС‚ РёР·Р±СЂР°РЅРЅС‹С… РјР°СЃС‚РµСЂРѕРІ</p>
                        <Link 
                            href="/" 
                            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                        >
                            РќР°Р№С‚Рё РјР°СЃС‚РµСЂРѕРІ
                        </Link>
                    </div>
                </ContentCard>
            </main>
        </div>
        </template>
    </div>
</template>

<script setup lang="ts">
import { logger } from '@/src/shared/lib/logger'
import { Head } from '@inertiajs/vue3'
import { onMounted } from 'vue'

// рџЋЇ FSD РРјРїРѕСЂС‚С‹
import ProfileSidebar from '@/src/shared/ui/organisms/ProfileSidebar/ProfileSidebar.vue'
import ContentCard from '@/src/shared/ui/organisms/ContentCard/ContentCard.vue'
import MasterCard from '@/src/entities/master/ui/MasterCard/MasterCard.vue'
import PageLoader from '@/src/shared/ui/organisms/PageLoader/PageLoader.vue'
import { usePageLoading } from '@/src/shared/composables/usePageLoading'

// РўРёРїРёР·Р°С†РёСЏ РґР°РЅРЅС‹С…
interface Master {
  id: number | string
  name: string
  display_name?: string
  avatar?: string
  specialty?: string
  rating?: number
  [key: string]: any
}

interface UserStats {
  views: number
  calls: number
  bookings: number
  revenue: number
}

interface Counts {
  ads: number
  bookings: number
  reviews: number
  favorites: number
  [key: string]: number
}

interface FavoritesIndexProps {
  favorites: Master[]
  counts: Counts
  userStats: UserStats
}

// Props СЃ С‚РёРїРёР·Р°С†РёРµР№
const _props = withDefaults(defineProps<FavoritesIndexProps>(), {
  favorites: () => [],
  counts: () => ({
    ads: 0,
    bookings: 0,
    reviews: 0,
    favorites: 0
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
  type: 'catalog',
  autoStart: true,
  timeout: 8000,
  onStart: () => {
    // Favorites page loading started
  },
  onComplete: () => {
    // Favorites page loading completed
  },
  onError: (error) => {
    logger.error('Favorites page loading error:', error)
  }
})

// РРЅРёС†РёР°Р»РёР·Р°С†РёСЏ РїСЂРё РјРѕРЅС‚РёСЂРѕРІР°РЅРёРё
onMounted(() => {
  // РџРѕСЌС‚Р°РїРЅР°СЏ Р·Р°РіСЂСѓР·РєР° РґР»СЏ Р»СѓС‡С€РµРіРѕ UX
  setTimeout(() => {
    pageLoader.setProgress(40, 'Р—Р°РіСЂСѓР¶Р°РµРј РёР·Р±СЂР°РЅРЅС‹С… РјР°СЃС‚РµСЂРѕРІ...')
  }, 300)

  setTimeout(() => {
    pageLoader.setProgress(70, 'РћР±СЂР°Р±Р°С‚С‹РІР°РµРј СЃС‚Р°С‚РёСЃС‚РёРєСѓ...')
  }, 700)

  setTimeout(() => {
    pageLoader.setProgress(90, 'РџРѕРґРіРѕС‚Р°РІР»РёРІР°РµРј РёРЅС‚РµСЂС„РµР№СЃ...')
  }, 1100)

  setTimeout(() => {
    pageLoader.completeLoading()
  }, 1500)
})
</script>
