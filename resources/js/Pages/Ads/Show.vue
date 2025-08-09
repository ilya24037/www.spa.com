<!-- РЎС‚СЂР°РЅРёС†Р° РїСЂРѕСЃРјРѕС‚СЂР° РѕР±СЉСЏРІР»РµРЅРёСЏ -->
<template>
  <Head :title="ad.title" />
  
  <div class="min-h-screen bg-gray-500">
    <div class="max-w-4xl mx-auto py-8 px-4">
      <!-- Р—Р°РіРѕР»РѕРІРѕРє -->
      <div class="bg-white rounded-lg p-6 mb-6">
        <div class="flex items-start justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-500 mb-2">
              {{ ad.title }}
            </h1>
            <p class="text-lg text-gray-500 mb-4">
              {{ ad.specialty }}
            </p>
            <div class="flex items-center gap-4 text-sm text-gray-500">
              <span>РЎРѕР·РґР°РЅРѕ: {{ formatDate(ad.created_at) }}</span>
              <span>РћР±РЅРѕРІР»РµРЅРѕ: {{ formatDate(ad.updated_at) }}</span>
              <span
                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                :class="getStatusClass(ad.status)"
              >
                {{ getStatusText(ad.status) }}
              </span>
            </div>
          </div>
          <div class="flex items-center gap-3">
            <Link 
              :href="`/ads/${ad.id}/edit`"
              class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
            >
              Р РµРґР°РєС‚РёСЂРѕРІР°С‚СЊ
            </Link>
          </div>
        </div>
      </div>

      <!-- РћСЃРЅРѕРІРЅРѕР№ РєРѕРЅС‚РµРЅС‚ -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Р›РµРІР°СЏ РєРѕР»РѕРЅРєР° - РѕСЃРЅРѕРІРЅР°СЏ РёРЅС„РѕСЂРјР°С†РёСЏ -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Р¤РѕС‚РѕРіСЂР°С„РёРё С‡РµСЂРµР· СѓРЅРёРІРµСЂСЃР°Р»СЊРЅСѓСЋ РіР°Р»РµСЂРµСЋ -->
          <div v-if="ad.photos && ad.photos.length > 0" class="bg-white rounded-lg p-6">
            <PhotoGallery 
              :photos="ad.photos"
              mode="grid"
              title="Р¤РѕС‚РѕРіСЂР°С„РёРё"
              :enable-lightbox="true"
            />
          </div>

          <!-- РћРїРёСЃР°РЅРёРµ -->
          <div class="bg-white rounded-lg p-6">
            <h2 class="text-xl font-semibold mb-4">
              РћРїРёСЃР°РЅРёРµ
            </h2>
            <div class="prose max-w-none">
              <p class="text-gray-500 leading-relaxed whitespace-pre-line">
                {{ ad.description || 'РћРїРёСЃР°РЅРёРµ РЅРµ СѓРєР°Р·Р°РЅРѕ' }}
              </p>
            </div>
          </div>

          <!-- Р”РѕРїРѕР»РЅРёС‚РµР»СЊРЅР°СЏ РёРЅС„РѕСЂРјР°С†РёСЏ -->
          <div class="bg-white rounded-lg p-6">
            <h2 class="text-xl font-semibold mb-4">
              Р”РѕРїРѕР»РЅРёС‚РµР»СЊРЅР°СЏ РёРЅС„РѕСЂРјР°С†РёСЏ
            </h2>
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  РћРїС‹С‚ СЂР°Р±РѕС‚С‹
                </dt>
                <dd class="text-gray-500">
                  {{ ad.experience || 'РќРµ СѓРєР°Р·Р°РЅ' }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Р¤РѕСЂРјР°С‚ СЂР°Р±РѕС‚С‹
                </dt>
                <dd class="text-gray-500">
                  {{ ad.work_format || 'РќРµ СѓРєР°Р·Р°РЅ' }}
                </dd>
              </div>
              <div v-if="ad.clients && ad.clients.length > 0">
                <dt class="text-sm font-medium text-gray-500">
                  РљР°С‚РµРіРѕСЂРёРё РєР»РёРµРЅС‚РѕРІ
                </dt>
                <dd class="text-gray-500">
                  {{ ad.clients.join(', ') }}
                </dd>
              </div>
              <div v-if="ad.service_location && ad.service_location.length > 0">
                <dt class="text-sm font-medium text-gray-500">
                  РњРµСЃС‚Р° РѕРєР°Р·Р°РЅРёСЏ СѓСЃР»СѓРі
                </dt>
                <dd class="text-gray-500">
                  {{ getServiceLocationText(ad.service_location) }}
                </dd>
              </div>
            </dl>
          </div>
        </div>

        <!-- РџСЂР°РІР°СЏ РєРѕР»РѕРЅРєР° - РєРѕРЅС‚Р°РєС‚РЅР°СЏ РёРЅС„РѕСЂРјР°С†РёСЏ Рё С†РµРЅР° -->
        <div class="space-y-6">
          <!-- Р¦РµРЅР° -->
          <div class="bg-white rounded-lg p-6">
            <h2 class="text-xl font-semibold mb-4">
              РЎС‚РѕРёРјРѕСЃС‚СЊ
            </h2>
            <div class="text-3xl font-bold text-gray-500 mb-2">
              {{ formatPrice(ad.price) }}
            </div>
            <p class="text-gray-500">
              {{ getPriceUnitText(ad.price_unit) }}
            </p>
            <div v-if="ad.discount" class="mt-2">
              <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                РЎРєРёРґРєР° {{ ad.discount }}%
              </span>
            </div>
          </div>

          <!-- РљРѕРЅС‚Р°РєС‚С‹ -->
          <div class="bg-white rounded-lg p-6">
            <h2 class="text-xl font-semibold mb-4">
              РљРѕРЅС‚Р°РєС‚РЅР°СЏ РёРЅС„РѕСЂРјР°С†РёСЏ
            </h2>
            <div class="space-y-3">
              <div v-if="ad.phone">
                <dt class="text-sm font-medium text-gray-500">
                  РўРµР»РµС„РѕРЅ
                </dt>
                <dd class="text-gray-500">
                  {{ ad.phone }}
                </dd>
              </div>
              <div v-if="ad.contact_method">
                <dt class="text-sm font-medium text-gray-500">
                  РЎРїРѕСЃРѕР± СЃРІСЏР·Рё
                </dt>
                <dd class="text-gray-500">
                  {{ getContactMethodText(ad.contact_method) }}
                </dd>
              </div>
              <div v-if="ad.address">
                <dt class="text-sm font-medium text-gray-500">
                  РђРґСЂРµСЃ
                </dt>
                <dd class="text-gray-500">
                  {{ ad.address }}
                </dd>
              </div>
            </div>
          </div>

          <!-- РЎС‚Р°С‚РёСЃС‚РёРєР° -->
          <div class="bg-white rounded-lg p-6">
            <h2 class="text-xl font-semibold mb-4">
              РЎС‚Р°С‚РёСЃС‚РёРєР°
            </h2>
            <dl class="space-y-2">
              <div class="flex justify-between">
                <dt class="text-gray-500">
                  РџСЂРѕСЃРјРѕС‚СЂС‹
                </dt>
                <dd class="text-gray-500">
                  {{ ad.views_count || 0 }}
                </dd>
              </div>
              <div class="flex justify-between">
                <dt class="text-gray-500">
                  Р’ РёР·Р±СЂР°РЅРЅРѕРј
                </dt>
                <dd class="text-gray-500">
                  {{ ad.favorites_count || 0 }}
                </dd>
              </div>
              <div class="flex justify-between">
                <dt class="text-gray-500">
                  РџРѕРєР°Р·С‹ РєРѕРЅС‚Р°РєС‚РѕРІ
                </dt>
                <dd class="text-gray-500">
                  {{ ad.contacts_shown || 0 }}
                </dd>
              </div>
            </dl>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import PhotoGallery from '@/src/features/gallery/ui/PhotoGallery/PhotoGallery.vue'

const _props = defineProps({
    ad: {
        type: Object,
        required: true
    },
    isOwner: {
        type: Boolean,
        default: false
    }
})

// РЈС‚РёР»РёС‚С‹ С„РѕСЂРјР°С‚РёСЂРѕРІР°РЅРёСЏ
const formatDate = (dateString: any) => {
    if (!dateString) return ''
    return new Date(dateString).toLocaleDateString('ru-RU', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    })
}

const formatPrice = (price: any) => {
    if (!price) return 'Р¦РµРЅР° РЅРµ СѓРєР°Р·Р°РЅР°'
    return new Intl.NumberFormat('ru-RU').format(price) + ' в‚Ѕ'
}

const getStatusClass = (status: any) => {
    const classes = {
        'active': 'bg-green-100 text-green-800',
        'draft': 'bg-yellow-100 text-yellow-800',
        'waiting_payment': 'bg-orange-100 text-orange-800',
        'archived': 'bg-gray-500 text-gray-500',
        'expired': 'bg-red-100 text-red-800'
    }
    return (classes as any)[status] || 'bg-gray-500 text-gray-500'
}

const getStatusText = (status: any) => {
    const texts = {
        'active': 'РђРєС‚РёРІРЅРѕ',
        'draft': 'Черновик',
        'waiting_payment': 'Р–РґРµС‚ РѕРїР»Р°С‚С‹',
        'archived': 'Р’ Р°СЂС…РёРІРµ',
        'expired': 'РСЃС‚РµРєР»Рѕ'
    }
    return (texts as any)[status] || status
}

const getPriceUnitText = (unit: any) => {
    const units = {
        'service': 'Р·Р° СѓСЃР»СѓРіСѓ',
        'hour': 'Р·Р° С‡Р°СЃ',
        'session': 'Р·Р° СЃРµР°РЅСЃ',
        'day': 'Р·Р° РґРµРЅСЊ',
        'month': 'Р·Р° РјРµСЃСЏС†'
    }
    return (units as any)[unit] || unit
}

const getContactMethodText = (method: any) => {
    const methods = {
        'any': 'Р›СЋР±РѕР№ СЃРїРѕСЃРѕР±',
        'calls': 'РўРѕР»СЊРєРѕ Р·РІРѕРЅРєРё',
        'messages': 'РўРѕР»СЊРєРѕ СЃРѕРѕР±С‰РµРЅРёСЏ'
    }
    return (methods as any)[method] || method
}

const getServiceLocationText = (locations: any) => {
    if (!Array.isArray(locations)) return ''
  
    const locationTexts = {
        'my_place': 'РЈ РјР°СЃС‚РµСЂР°',
        'client_home': 'Р’С‹РµР·Рґ Рє РєР»РёРµРЅС‚Сѓ',
        'salon': 'Р’ СЃР°Р»РѕРЅРµ'
    }
  
    return locations.map(loc => (locationTexts as any)[loc] || loc).join(', ')
}
</script>
