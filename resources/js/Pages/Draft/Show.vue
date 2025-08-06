<template>
  <Head :title="`${ad.title || 'Черновик'} - SPA Platform`" />
  
  <div>
    <!-- Контейнер как на главной -->
    <div class="py-6 lg:py-8">
      <!-- Хлебные крошки -->
      <div class="mb-6">
        <!-- Хлебные крошки -->
        <Breadcrumbs
          :items="breadcrumbItems"
        />
      </div>

      <!-- РћСЃРЅРѕРІРЅРѕР№ РєРѕРЅС‚РµРЅС‚ СЃ РїСЂР°РІРёР»СЊРЅС‹РјРё РѕС‚СЃС‚СѓРїР°РјРё -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Р›РµРІР°СЏ РєРѕР»РѕРЅРєР°: Р¤РѕС‚Рѕ Рё РѕСЃРЅРѕРІРЅР°СЏ РёРЅС„РѕСЂРјР°С†РёСЏ -->
        <div class="lg:col-span-2">
          <!-- РљРЅРѕРїРєРё СѓРїСЂР°РІР»РµРЅРёСЏ С‡РµСЂРЅРѕРІРёРєРѕРј РќРђР” С„РѕС‚Рѕ -->
          <div class="mb-4 flex justify-start gap-3">
            <Link 
              :href="route('ads.edit', ad.id)"
              @click="handleEditClick"
              class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2 text-sm font-medium shadow-lg"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
              </svg>
              Р РµРґР°РєС‚РёСЂРѕРІР°С‚СЊ
            </Link>
            <button 
              @click.stop.prevent="handleDeleteClick"
              class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition flex items-center gap-2 text-sm font-medium shadow-lg"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
              </svg>
              РЈРґР°Р»РёС‚СЊ
            </button>
          </div>

          <!-- РЈРЅРёРІРµСЂСЃР°Р»СЊРЅР°СЏ РіР°Р»РµСЂРµСЏ С„РѕС‚Рѕ -->
          <PhotoGallery 
            :photos="ad.photos || []"
            mode="full"
            :show-badges="false"
            :show-thumbnails="true"
            :show-counter="true"
            :enable-lightbox="true"
          />
          
          <!-- РћСЃРЅРѕРІРЅР°СЏ РёРЅС„РѕСЂРјР°С†РёСЏ -->
          <div class="bg-white rounded-lg p-6 shadow-sm mt-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-4">{{ ad.title || 'Р‘РµР· РЅР°Р·РІР°РЅРёСЏ' }}</h1>
            
            <div class="flex items-center gap-4 mb-4">
              <div class="flex items-center gap-1">
                <MapPinIcon class="w-5 h-5 text-gray-400" />
                <span>{{ ad.city }}{{ ad.district ? ', ' + ad.district : '' }}</span>
              </div>
            </div>
            
            <div v-if="ad.description" class="text-gray-700 mb-4">
              {{ ad.description }}
            </div>
          </div>
          
          <!-- РЈСЃР»СѓРіРё -->
          <div v-if="ad.services && ad.services.length" class="bg-white rounded-lg p-6 shadow-sm mt-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">РЈСЃР»СѓРіРё Рё С†РµРЅС‹</h2>
            
            <div class="space-y-4">
              <div 
                v-for="service in ad.services" 
                :key="service.id"
                class="flex justify-between items-center p-4 border border-gray-200 rounded-lg"
              >
                <div>
                  <h3 class="font-medium">{{ service.name }}</h3>
                  <p class="text-sm text-gray-500">{{ service.duration }} РјРёРЅ</p>
                </div>
                <div class="text-right">
                  <div class="font-bold text-lg">{{ formatPrice(service.price) }} в‚Ѕ</div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- РћС‚Р·С‹РІС‹ -->
          <div v-if="ad.reviews && ad.reviews.length" class="bg-white rounded-lg p-6 shadow-sm mt-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">РћС‚Р·С‹РІС‹</h2>
            
            <div class="space-y-4">
              <div 
                v-for="review in ad.reviews" 
                :key="review.id"
                class="border-b border-gray-200 pb-4 last:border-b-0"
              >
                <div class="flex items-center gap-3 mb-2">
                  <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center">
                    {{ review.client_name?.charAt(0) || 'Рђ' }}
                  </div>
                  <div>
                    <div class="font-medium">{{ review.client_name || 'РђРЅРѕРЅРёРјРЅС‹Р№ РєР»РёРµРЅС‚' }}</div>
                    <div class="flex items-center gap-1">
                      <svg v-for="n in 5" :key="n" 
                        :class="n <= review.rating ? 'text-yellow-400' : 'text-gray-300'"
                        class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"
                      >
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                      </svg>
                    </div>
                  </div>
                </div>
                <p class="text-gray-700">{{ review.comment }}</p>
              </div>
            </div>
          </div>
        </div>

                 <!-- РџСЂР°РІР°СЏ РєРѕР»РѕРЅРєР°: Р‘СЂРѕРЅРёСЂРѕРІР°РЅРёРµ Рё РєРѕРЅС‚Р°РєС‚С‹ (РєР°Рє Сѓ РјР°СЃС‚РµСЂР°) -->
         <div class="space-y-6">
           <!-- Р‘СЂРѕРЅРёСЂРѕРІР°РЅРёРµ -->
           <div class="bg-white rounded-lg p-6 shadow-sm sticky top-6">
             <h2 class="text-xl font-bold text-gray-900 mb-4">Р—Р°РїРёСЃР°С‚СЊСЃСЏ</h2>
             
             <!-- Р¦РµРЅР° -->
             <div class="mb-6">
               <div class="text-3xl font-bold text-gray-900 mb-2">
                 РѕС‚ {{ formatPrice(ad.price_from || 2000) }} в‚Ѕ
               </div>
               <p class="text-gray-600">Р·Р° СЃРµР°РЅСЃ</p>
             </div>
             
             
             
                           <!-- РљРЅРѕРїРєРё -->
              <div class="space-y-3">
                <button 
                  @click="showPhone"
                  class="w-full border border-gray-300 py-3 px-4 rounded-lg hover:bg-gray-50 transition font-medium flex items-center justify-center gap-2"
                >
                  <PhoneIcon class="w-5 h-5" />
                  РџРѕРєР°Р·Р°С‚СЊ С‚РµР»РµС„РѕРЅ
                </button>
              </div>
             
             <!-- Р“СЂР°С„РёРє СЂР°Р±РѕС‚С‹ / РРЅС„РѕСЂРјР°С†РёСЏ -->
             <div class="mt-6 pt-6 border-t border-gray-200">
               <h3 class="font-semibold text-gray-900 mb-3">Р’СЂРµРјСЏ СЂР°Р±РѕС‚С‹</h3>
               <div class="space-y-2">
                 <div v-if="ad.schedule" class="space-y-1">
                   <div 
                     v-for="(hours, day) in ad.schedule" 
                     :key="day"
                     class="flex justify-between text-sm"
                   >
                     <span class="text-gray-600">{{ getDayName(day) }}</span>
                     <span class="font-medium">{{ hours || 'Р’С‹С…РѕРґРЅРѕР№' }}</span>
                   </div>
                 </div>
                 <div v-else class="text-sm text-gray-500">
                   РџРѕ РґРѕРіРѕРІРѕСЂРµРЅРЅРѕСЃС‚Рё
                 </div>
               </div>
             </div>
             
             <!-- Р”РѕРїРѕР»РЅРёС‚РµР»СЊРЅР°СЏ РёРЅС„РѕСЂРјР°С†РёСЏ -->
             <div class="mt-6 pt-6 border-t border-gray-200">
               <h3 class="font-semibold text-gray-900 mb-3">РРЅС„РѕСЂРјР°С†РёСЏ</h3>
               <div class="space-y-2">
                 <div v-if="ad.experience" class="flex justify-between text-sm">
                   <span class="text-gray-600">РћРїС‹С‚</span>
                   <span class="font-medium">{{ ad.experience }}</span>
                 </div>
                 <div v-if="ad.education" class="flex justify-between text-sm">
                   <span class="text-gray-600">РћР±СЂР°Р·РѕРІР°РЅРёРµ</span>
                   <span class="font-medium">{{ ad.education }}</span>
                 </div>
                 <div class="flex justify-between text-sm">
                   <span class="text-gray-600">РЎРѕР·РґР°РЅРѕ</span>
                   <span class="font-medium">{{ formatDate(ad.created_at) }}</span>
                 </div>
                 <div class="flex justify-between text-sm">
                   <span class="text-gray-600">ID</span>
                   <span class="font-medium">{{ ad.id }}</span>
                 </div>
               </div>
             </div>
             
             
           </div>
         </div>
      </div>
    </div>

    <!-- РњРѕРґР°Р»СЊРЅРѕРµ РѕРєРЅРѕ РїРѕРґС‚РІРµСЂР¶РґРµРЅРёСЏ СѓРґР°Р»РµРЅРёСЏ -->
    <ConfirmModal
      :show="showDeleteModal"
      @cancel="handleDeleteCancel"
      @confirm="handleDeleteConfirm"
      title="РЈРґР°Р»РёС‚СЊ С‡РµСЂРЅРѕРІРёРє?"
      message="Р­С‚Рѕ РґРµР№СЃС‚РІРёРµ РЅРµР»СЊР·СЏ РѕС‚РјРµРЅРёС‚СЊ. Черновик Р±СѓРґРµС‚ СѓРґР°Р»РµРЅ РЅР°РІСЃРµРіРґР°."
      confirm-text="РЈРґР°Р»РёС‚СЊ"
      cancel-text="РћС‚РјРµРЅР°"
    />
  </div>
</template>

<script setup lang="ts">
import { route } from 'ziggy-js'

import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { MapPinIcon, PhoneIcon } from '@heroicons/vue/24/outline'
import { ConfirmModal } from '@/src/shared/ui/molecules/Modal'
import PhotoGallery from '@/src/features/gallery/ui/PhotoGallery/PhotoGallery.vue'
// рџЋЇ FSD РРјРїРѕСЂС‚С‹
import Breadcrumbs from '@/src/shared/ui/molecules/Breadcrumbs/Breadcrumbs.vue'
import { useToast } from '@/src/shared/composables/useToast'

// Toast РґР»СЏ Р·Р°РјРµРЅС‹ (window as any).alert()
const toast = useToast()

// РРјРїРѕСЂС‚РёСЂСѓРµРј route РёР· (window as any).route (Ziggy)
const route = (window as any).route || ((name, params) => {
  // Fallback РґР»СЏ СЂРѕСѓС‚РѕРІ
  if (name === 'my-ads.destroy' && params) {
    return `/my-ads/${params}`
  }
  if (name === 'ads.edit' && params) {
    return `/ads/${params}/edit`
  }
  if (name === 'my-ads.index' || name === 'profile.items.draft') {
    return '/profile/items/draft/all'
  }
  return '/'
})

const props = defineProps({
  ad: Object
})

// РЎРѕСЃС‚РѕСЏРЅРёРµ
const showDeleteModal = ref(false)

// РћР±СЂР°Р±РѕС‚С‡РёРє РєР»РёРєР° РїРѕ РєРЅРѕРїРєРµ СЂРµРґР°РєС‚РёСЂРѕРІР°РЅРёСЏ
const handleEditClick = (event) => {
  
  // Р•СЃР»Рё РјРѕРґР°Р»СЊРЅРѕРµ РѕРєРЅРѕ РѕС‚РєСЂС‹С‚Рѕ, Р±Р»РѕРєРёСЂСѓРµРј РїРµСЂРµС…РѕРґ
  if (showDeleteModal.value) {
    event.preventDefault()
    event.stopPropagation()
    return false
  }
  
}

// РћР±СЂР°Р±РѕС‚С‡РёРє РєР»РёРєР° РїРѕ РєРЅРѕРїРєРµ СѓРґР°Р»РµРЅРёСЏ
const handleDeleteClick = (event) => {
  event.stopPropagation()
  event.preventDefault()
  showDeleteModal.value = true
}

// РћР±СЂР°Р±РѕС‚С‡РёРє РѕС‚РјРµРЅС‹ СѓРґР°Р»РµРЅРёСЏ
const handleDeleteCancel = () => {
  showDeleteModal.value = false
}

// РћР±СЂР°Р±РѕС‚С‡РёРє РїРѕРґС‚РІРµСЂР¶РґРµРЅРёСЏ СѓРґР°Р»РµРЅРёСЏ
const handleDeleteConfirm = () => {
  deleteDraft()
}

// РћСЃС‚Р°Р»СЊРЅС‹Рµ РјРµС‚РѕРґС‹
const formatPrice = (price) => {
  if (!price) return '0'
  return new Intl.NumberFormat('ru-RU').format(price)
}

const formatDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('ru-RU', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const getDayName = (dayOfWeek) => {
  const days = [
    'Р’РѕСЃРєСЂРµСЃРµРЅСЊРµ', 'РџРѕРЅРµРґРµР»СЊРЅРёРє', 'Р’С‚РѕСЂРЅРёРє', 'РЎСЂРµРґР°', 
    'Р§РµС‚РІРµСЂРі', 'РџСЏС‚РЅРёС†Р°', 'РЎСѓР±Р±РѕС‚Р°'
  ]
  return days[dayOfWeek]
}

const showPhone = () => {
  if (props.ad.phone) {
    (window as any).location.href = `tel:${props.ad.phone.replace(/\D/g, '')}`
  } else {
    toast.info('РўРµР»РµС„РѕРЅ Р±СѓРґРµС‚ РґРѕСЃС‚СѓРїРµРЅ РїРѕСЃР»Рµ РїСѓР±Р»РёРєР°С†РёРё РѕР±СЉСЏРІР»РµРЅРёСЏ')
  }
}

// РЈРґР°Р»РµРЅРёРµ С‡РµСЂРЅРѕРІРёРєР°
const deleteDraft = () => {
  
  // РќР• Р·Р°РєСЂС‹РІР°РµРј РјРѕРґР°Р»РєСѓ - РѕСЃС‚Р°РІР»СЏРµРј РѕС‚РєСЂС‹С‚РѕР№ РґРѕ Р·Р°РІРµСЂС€РµРЅРёСЏ РѕРїРµСЂР°С†РёРё
  
  // РСЃРїРѕР»СЊР·СѓРµРј СЃРїРµС†РёР°Р»СЊРЅС‹Р№ СЂРѕСѓС‚ РґР»СЏ С‡РµСЂРЅРѕРІРёРєРѕРІ
  router.delete(`/draft/${props.ad.id}`, {
    preserveScroll: false,
    preserveState: false,
    onStart: () => {
    },
    onSuccess: (page) => {
      // Р—Р°РєСЂС‹РІР°РµРј РјРѕРґР°Р»РєСѓ С‚РѕР»СЊРєРѕ РїСЂРё СѓСЃРїРµС…Рµ
      showDeleteModal.value = false
      // РљРѕРЅС‚СЂРѕР»Р»РµСЂ РїРµСЂРµРЅР°РїСЂР°РІР»СЏРµС‚ РІ Р»РёС‡РЅС‹Р№ РєР°Р±РёРЅРµС‚
    },
    onError: (errors) => {
      // РџРѕРєР°Р·С‹РІР°РµРј РѕС€РёР±РєСѓ РїРѕР»СЊР·РѕРІР°С‚РµР»СЋ
      toast.error('РћС€РёР±РєР° СѓРґР°Р»РµРЅРёСЏ: ' + (errors.message || JSON.stringify(errors)))
      // РњРѕРґР°Р»РєР° РѕСЃС‚Р°РµС‚СЃСЏ РѕС‚РєСЂС‹С‚РѕР№ РїСЂРё РѕС€РёР±РєРµ
    },
    onFinish: () => {
    }
  })
}

const breadcrumbItems = [
  { title: 'Р“Р»Р°РІРЅР°СЏ', href: '/' },
  { title: 'РњРѕРё РѕР±СЉСЏРІР»РµРЅРёСЏ', href: '/profile/items/draft/all' },
  { title: props.ad.title || 'Черновик' }
]
</script>

<style scoped>
/* РЈР±РёСЂР°РµРј СЃС‚РёР»Рё РїРѕР»РЅРѕР№ С€РёСЂРёРЅС‹ - РёСЃРїРѕР»СЊР·СѓРµРј СЃС‚Р°РЅРґР°СЂС‚РЅСѓСЋ СЃС‚СЂСѓРєС‚СѓСЂСѓ РєР°Рє РЅР° РіР»Р°РІРЅРѕР№ */

/* РџР»Р°РІРЅР°СЏ Р°РЅРёРјР°С†РёСЏ РґР»СЏ РіР°Р»РµСЂРµРё */
.transition-all {
  transition-property: all;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 300ms;
}
</style>
