<template>
  <Head title="Р РµРґР°РєС‚РёСЂРѕРІР°РЅРёРµ РѕР±СЉСЏРІР»РµРЅРёСЏ" />
  
  <div class="min-h-screen bg-gray-50">
    <!-- Loading СЃРѕСЃС‚РѕСЏРЅРёРµ -->
    <PageLoader 
      v-if="pageLoader.isLoading.value"
      type="form"
      :message="pageLoader.message.value"
      :show-progress="false"
      :skeleton-count="1"
    />
    
    <!-- РћСЃРЅРѕРІРЅРѕР№ РєРѕРЅС‚РµРЅС‚ -->
    <div v-else class="max-w-4xl mx-auto py-6 lg:py-8">
      <!-- РҐР»РµР±РЅС‹Рµ РєСЂРѕС€РєРё РІ СЃС‚РёР»Рµ Avito -->
      <nav class="flex items-center mb-6" aria-label="Breadcrumb">
        <button 
          @click="goBack"
          class="flex items-center text-gray-500 hover:text-gray-700 transition-colors mr-4"
        >
          <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
          </svg>
          РќР°Р·Р°Рґ
        </button>
        
        <ol class="flex items-center space-x-2 text-sm">
          <li>
            <Link 
              href="/profile/items/active/all"
              class="text-gray-500 hover:text-gray-700 transition-colors"
            >
              РњРѕРё РѕР±СЉСЏРІР»РµРЅРёСЏ
            </Link>
          </li>
          <li class="text-gray-400">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
            </svg>
          </li>
          <li>
            <span class="text-gray-900 font-medium">Р РµРґР°РєС‚РёСЂРѕРІР°РЅРёРµ РѕР±СЉСЏРІР»РµРЅРёСЏ</span>
          </li>
        </ol>
      </nav>
      
      <!-- РћСЃРЅРѕРІРЅРѕР№ РєРѕРЅС‚РµРЅС‚ -->
      <div class="bg-white rounded-lg shadow-sm">
        <!-- Р—Р°РіРѕР»РѕРІРѕРє СЃС‚СЂР°РЅРёС†С‹ -->
        <div class="px-6 py-4 border-b border-gray-200">
          <h1 class="text-2xl font-bold text-gray-900">Р РµРґР°РєС‚РёСЂРѕРІР°РЅРёРµ РѕР±СЉСЏРІР»РµРЅРёСЏ</h1>
          <p class="text-sm text-gray-600 mt-1">
            Р’РЅРµСЃРёС‚Рµ РёР·РјРµРЅРµРЅРёСЏ РІ РІР°С€Рµ РѕР±СЉСЏРІР»РµРЅРёРµ. Р’СЃРµ РїРѕР»СЏ СЃ * РѕР±СЏР·Р°С‚РµР»СЊРЅС‹ РґР»СЏ Р·Р°РїРѕР»РЅРµРЅРёСЏ.
          </p>
        </div>
        
        <!-- Р¤РѕСЂРјР° СЂРµРґР°РєС‚РёСЂРѕРІР°РЅРёСЏ -->
        <div class="p-6">
          <AdForm 
            :category="ad.category || 'massage'"
            :categories="[]"
            :ad-id="ad.id"
            :initial-data="ad"
            @success="handleSuccess"
            @form-loading="handleFormLoading"
            @data-loaded="handleDataLoaded"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { logger } from '@/src/shared/lib/logger'
import { Head, Link, router } from '@inertiajs/vue3'
import { onMounted } from 'vue'

// рџЋЇ FSD РРјРїРѕСЂС‚С‹
import AdForm from '@/src/entities/ad/ui/AdForm/AdForm.vue'
import PageLoader from '@/src/shared/ui/organisms/PageLoader/PageLoader.vue'
import { usePageLoading } from '@/src/shared/composables/usePageLoading'

// РўРёРїРёР·Р°С†РёСЏ РѕР±СЉСЏРІР»РµРЅРёСЏ
interface Ad {
  id: number | string
  title?: string
  name?: string
  description?: string
  category?: string
  price?: number
  location?: string
  photos?: any[]
  services?: any[]
  [key: string]: any
}

interface EditAdProps {
  ad: Ad
}

const props = defineProps<EditAdProps>()

// РЈРїСЂР°РІР»РµРЅРёРµ Р·Р°РіСЂСѓР·РєРѕР№ СЃС‚СЂР°РЅРёС†С‹
const pageLoader = usePageLoading({
  type: 'form',
  autoStart: true,
  timeout: 10000,
  onStart: () => {
    // EditAd loading started
  },
  onComplete: () => {
    // EditAd loading completed
  },
  onError: (error) => {
    logger.error('EditAd loading error:', error)
  }
})

// РћР±СЂР°Р±РѕС‚С‡РёРєРё Р·Р°РіСЂСѓР·РєРё С„РѕСЂРјС‹
const handleFormLoading = (): void => {
  pageLoader.setProgress(50, 'РџРѕРґРіРѕС‚Р°РІР»РёРІР°РµРј С„РѕСЂРјСѓ СЂРµРґР°РєС‚РёСЂРѕРІР°РЅРёСЏ...')
}

const handleDataLoaded = (): void => {
  pageLoader.setProgress(90, 'Р—Р°РіСЂСѓР¶Р°РµРј РґР°РЅРЅС‹Рµ РѕР±СЉСЏРІР»РµРЅРёСЏ...')
  setTimeout(() => {
    pageLoader.completeLoading()
  }, 300)
}

// РќР°РІРёРіР°С†РёСЏ
const goBack = (): void => {
  router.visit('/profile/items/active/all')
}

const handleSuccess = (): void => {
  router.visit('/profile/items/active/all')
}

// РРЅРёС†РёР°Р»РёР·Р°С†РёСЏ РїСЂРё РјРѕРЅС‚РёСЂРѕРІР°РЅРёРё
onMounted(() => {
  // РџСЂРѕРІРµСЂСЏРµРј РЅР°Р»РёС‡РёРµ РґР°РЅРЅС‹С… РѕР±СЉСЏРІР»РµРЅРёСЏ
  if (!props.ad || !props.ad.id) {
    const noDataError = {
      type: 'client' as const,
      message: 'Р”Р°РЅРЅС‹Рµ РѕР±СЉСЏРІР»РµРЅРёСЏ РЅРµ РЅР°Р№РґРµРЅС‹',
      code: 404
    }
    pageLoader.errorLoading(noDataError)
    return
  }

  // РџРѕСЌС‚Р°РїРЅР°СЏ Р·Р°РіСЂСѓР·РєР° РґР»СЏ Р»СѓС‡С€РµРіРѕ UX
  setTimeout(() => {
    pageLoader.setProgress(30, 'РРЅРёС†РёР°Р»РёР·РёСЂСѓРµРј СЂРµРґР°РєС‚РѕСЂ...')
  }, 200)

  setTimeout(() => {
    pageLoader.setProgress(60, 'Р—Р°РіСЂСѓР¶Р°РµРј РґР°РЅРЅС‹Рµ С„РѕСЂРјС‹...')
  }, 600)

  setTimeout(() => {
    pageLoader.setProgress(85, 'РџРѕРґРіРѕС‚Р°РІР»РёРІР°РµРј РёРЅС‚РµСЂС„РµР№СЃ...')
  }, 1000)

  setTimeout(() => {
    pageLoader.completeLoading()
  }, 1400)
})
</script> 
