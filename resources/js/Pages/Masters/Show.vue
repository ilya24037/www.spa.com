<!-- resources/js/Pages/Masters/Show.vue - FSD Refactored СЃ Loading СЃРѕСЃС‚РѕСЏРЅРёСЏРјРё -->
<template>
  <MainLayout>
    <!-- Loading СЃРѕСЃС‚РѕСЏРЅРёРµ -->
    <PageLoader 
      v-if="pageLoader.isLoading.value"
      type="profile"
      :message="pageLoader.message.value"
      :show-progress="false"
      :skeleton-count="1"
    />
    
    <!-- РћСЃРЅРѕРІРЅРѕР№ РєРѕРЅС‚РµРЅС‚ -->
    <MasterProfile 
      v-else
      :master="master" 
      @profile-loading="handleProfileLoading"
      @gallery-loading="handleGalleryLoading"
      @reviews-loading="handleReviewsLoading"
      @content-loaded="handleContentLoaded"
    />
  </MainLayout>
</template>

<script setup lang="ts">
import { logger } from '@/src/shared/lib/logger'
import { onMounted } from 'vue'
import MainLayout from '@/src/shared/layouts/MainLayout/MainLayout.vue'
import MasterProfile from '@/src/widgets/master-profile/MasterProfile.vue'
import PageLoader from '@/src/shared/ui/organisms/PageLoader/PageLoader.vue'
import { usePageLoading } from '@/src/shared/composables/usePageLoading'

// РўРёРїРёР·Р°С†РёСЏ props
interface Master {
  id: number
  name: string
  display_name?: string
  avatar?: string
  specialty?: string
  description?: string
  rating?: number
  reviews_count?: number
  services?: any[]
  photos?: any[]
  location?: string
  [key: string]: any
}

interface MasterProfileProps {
  master: Master
}

const props = defineProps<MasterProfileProps>()

// РЈРїСЂР°РІР»РµРЅРёРµ Р·Р°РіСЂСѓР·РєРѕР№ СЃС‚СЂР°РЅРёС†С‹
const pageLoader = usePageLoading({
  type: 'profile',
  autoStart: true,
  timeout: 12000,
  onStart: () => {
    // Master profile loading started
  },
  onComplete: () => {
    // Master profile loading completed
  },
  onError: (error) => {
    logger.error('Master profile loading error:', error)
  }
})

// РћР±СЂР°Р±РѕС‚С‡РёРєРё Р·Р°РіСЂСѓР·РєРё СЂР°Р·РЅС‹С… СЃРµРєС†РёР№ РїСЂРѕС„РёР»СЏ
const handleProfileLoading = (): void => {
  pageLoader.setProgress(25, 'Р—Р°РіСЂСѓР¶Р°РµРј РёРЅС„РѕСЂРјР°С†РёСЋ Рѕ РјР°СЃС‚РµСЂРµ...')
}

const handleGalleryLoading = (): void => {
  pageLoader.setProgress(50, 'Р—Р°РіСЂСѓР¶Р°РµРј РіР°Р»РµСЂРµСЋ СЂР°Р±РѕС‚...')
}

const handleReviewsLoading = (): void => {
  pageLoader.setProgress(75, 'Р—Р°РіСЂСѓР¶Р°РµРј РѕС‚Р·С‹РІС‹...')
}

const handleContentLoaded = (): void => {
  pageLoader.setProgress(95, 'Р¤РёРЅР°Р»РёР·Р°С†РёСЏ РїСЂРѕС„РёР»СЏ...')
  setTimeout(() => {
    pageLoader.completeLoading()
  }, 300)
}

// Р›РѕРіРёРєР° Р·Р°РіСЂСѓР·РєРё РїСЂРё РјРѕРЅС‚РёСЂРѕРІР°РЅРёРё
onMounted(() => {
  // РџСЂРѕРІРµСЂСЏРµРј РЅР°Р»РёС‡РёРµ Р±Р°Р·РѕРІС‹С… РґР°РЅРЅС‹С… РјР°СЃС‚РµСЂР°
  if (!props.master || !props.master.id) {
    const noDataError = {
      type: 'client' as const,
      message: 'Р”Р°РЅРЅС‹Рµ РјР°СЃС‚РµСЂР° РЅРµ РЅР°Р№РґРµРЅС‹',
      code: 404
    }
    pageLoader.errorLoading(noDataError)
    return
  }

  // РџРѕСЌС‚Р°РїРЅР°СЏ Р·Р°РіСЂСѓР·РєР° СЂР°Р·РЅС‹С… СЃРµРєС†РёР№
  setTimeout(() => {
    pageLoader.setProgress(20, 'РћР±СЂР°Р±Р°С‚С‹РІР°РµРј РґР°РЅРЅС‹Рµ РїСЂРѕС„РёР»СЏ...')
  }, 400)

  setTimeout(() => {
    // РџСЂРѕРІРµСЂСЏРµРј РЅР°Р»РёС‡РёРµ С„РѕС‚РѕРіСЂР°С„РёР№
    if (props.master.photos && props.master.photos.length > 0) {
      pageLoader.setProgress(45, 'Р—Р°РіСЂСѓР¶Р°РµРј С„РѕС‚РѕРіСЂР°С„РёРё...')
    } else {
      pageLoader.setProgress(45, 'РџРѕРґРіРѕС‚Р°РІР»РёРІР°РµРј РїСЂРѕС„РёР»СЊ...')
    }
  }, 800)

  setTimeout(() => {
    // РџСЂРѕРІРµСЂСЏРµРј РЅР°Р»РёС‡РёРµ РѕС‚Р·С‹РІРѕРІ
    if (props.master.reviews_count && props.master.reviews_count > 0) {
      pageLoader.setProgress(70, 'Р—Р°РіСЂСѓР¶Р°РµРј РѕС‚Р·С‹РІС‹ РєР»РёРµРЅС‚РѕРІ...')
    } else {
      pageLoader.setProgress(70, 'РћР±СЂР°Р±Р°С‚С‹РІР°РµРј СѓСЃР»СѓРіРё...')
    }
  }, 1200)

  setTimeout(() => {
    pageLoader.setProgress(90, 'РџРѕРґРіРѕС‚Р°РІР»РёРІР°РµРј Рє РѕС‚РѕР±СЂР°Р¶РµРЅРёСЋ...')
  }, 1600)

  setTimeout(() => {
    pageLoader.completeLoading()
  }, 2000)
})
</script>

<style scoped>
/* РЎС‚РёР»Рё СЃС‚СЂР°РЅРёС†С‹ РјР°СЃС‚РµСЂР° СЃ Р°РЅРёРјР°С†РёСЏРјРё */
.master-profile-enter-active,
.master-profile-leave-active {
  transition: all 0.4s ease;
}

.master-profile-enter-from {
  opacity: 0;
  transform: translateY(30px);
}

.master-profile-leave-to {
  opacity: 0;
  transform: translateY(-30px);
}

/* РЎРїРµС†РёР°Р»СЊРЅР°СЏ Р°РЅРёРјР°С†РёСЏ РґР»СЏ РїСЂРѕС„РёР»СЏ */
.profile-fade-enter-active {
  transition: opacity 0.6s ease, transform 0.6s ease;
}

.profile-fade-enter-from {
  opacity: 0;
  transform: scale(0.98);
}
</style>
