<!-- AdDetail Widget - Детальная информация об объявлении -->
<template>
  <div class="ad-detail">
    <!-- Loading с детальным skeleton -->
    <AdDetailSkeleton v-if="loading" />

    <!-- Detail -->
    <div v-else-if="ad">
      <div class="min-h-screen">
        <div class="py-6 lg:py-8">
          <!-- Заголовок -->
          <div class="bg-white rounded-lg p-6 mb-6 shadow-sm">
            <div class="flex items-start justify-between">
              <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">
                  {{ adData.title }}
                </h1>
                <!-- specialty скрыто - поле теперь необязательное -->
                <div class="flex items-center gap-4 text-sm text-gray-500">
                  <span>Создано: {{ formatDate(adData.created_at) }}</span>
                  <span>Обновлено: {{ formatDate(adData.updated_at) }}</span>
                  <span
                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                    :class="getStatusClass(adData.status)"
                  >
                    {{ getStatusText(adData.status) }}
                  </span>
                </div>
              </div>
              <div class="flex items-center gap-3">
                <Link
                  :href="`/ads/${adData.id}/edit`"
                  class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
                >
                  Редактировать
                </Link>
              </div>
            </div>
          </div>

          <!-- Основной контент -->
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Левая колонка - основная информация -->
            <div class="lg:col-span-2 space-y-6">
              <!-- Фотографии через универсальную галерею -->
              <div v-if="normalizedPhotos.length > 0" class="bg-white rounded-lg p-6 shadow-sm">
                <PhotoGallery
                  :photos="normalizedPhotos"
                  layout="vertical"
                  title="Фотографии"
                  :show-thumbnails="true"
                />
              </div>

              <!-- Описание -->
              <div class="bg-white rounded-lg p-6 shadow-sm">
                <h2 class="text-xl font-semibold mb-4 text-gray-900">
                  Описание
                </h2>
                <div class="prose max-w-none">
                  <p class="text-gray-600 leading-relaxed whitespace-pre-line">
                    {{ adData.description || 'Описание не указано' }}
                  </p>
                </div>
              </div>

              <!-- Услуги -->
              <div v-if="normalizedServices.length > 0" class="bg-white rounded-lg p-6 shadow-sm">
                <h2 class="text-xl font-semibold mb-4 text-gray-900">
                  Предоставляемые услуги
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                  <div
                    v-for="(service, idx) in normalizedServices"
                    :key="idx"
                    class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                  >
                    <span class="text-gray-700 capitalize">{{ service.name }}</span>
                    <span v-if="service.price" class="text-gray-900 font-semibold">
                      {{ formatPrice(service.price) }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Прайс-лист -->
              <div v-if="normalizedPrices.length > 0" class="bg-white rounded-lg p-6 shadow-sm">
                <h2 class="text-xl font-semibold mb-4 text-gray-900">
                  Прайс-лист
                </h2>
                <div class="space-y-2">
                  <div
                    v-for="(price, idx) in normalizedPrices"
                    :key="idx"
                    class="flex items-center justify-between p-3 border-b border-gray-200 last:border-b-0"
                  >
                    <span class="text-gray-700 capitalize">{{ price.name }}</span>
                    <span class="text-gray-900 font-semibold">
                      {{ formatPrice(price.value) }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Дополнительная информация -->
              <div class="bg-white rounded-lg p-6 shadow-sm">
                <h2 class="text-xl font-semibold mb-4 text-gray-900">
                  Дополнительная информация
                </h2>
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <dt class="text-sm font-medium text-gray-500">
                      Опыт работы
                    </dt>
                    <dd class="text-gray-600">
                      {{ adData.experience || 'Не указан' }}
                    </dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500">
                      Формат работы
                    </dt>
                    <dd class="text-gray-600">
                      {{ adData.work_format || 'Не указан' }}
                    </dd>
                  </div>
                  <div v-if="adData.clients && adData.clients.length > 0">
                    <dt class="text-sm font-medium text-gray-500">
                      Категории клиентов
                    </dt>
                    <dd class="text-gray-600">
                      {{ adData.clients.join(', ') }}
                    </dd>
                  </div>
                  <div v-if="adData.service_location && adData.service_location.length > 0">
                    <dt class="text-sm font-medium text-gray-500">
                      Места оказания услуг
                    </dt>
                    <dd class="text-gray-600">
                      {{ getServiceLocationText(adData.service_location) }}
                    </dd>
                  </div>
                </dl>
              </div>

              <!-- Местоположение на карте -->
              <div v-if="adLocation" class="bg-white rounded-lg p-6 shadow-sm">
                <h2 class="text-xl font-semibold mb-4 text-gray-900">
                  Местоположение услуги
                </h2>
                <div class="rounded-lg overflow-hidden bg-gray-200 h-75 flex items-center justify-center">
                  <div class="text-center">
                    <div class="text-gray-500 text-lg mb-2">
                      🗺️ Карта временно недоступна
                    </div>
                    <div class="text-gray-400 text-sm">
                      YandexMapNative удален из проекта
                    </div>
                    <div class="text-gray-400 text-xs mt-2">
                      Координаты: {{ adLocation.join(', ') }}
                    </div>
                  </div>
                </div>
                <p class="text-sm text-gray-600 mt-3">
                  {{ adData.address || 'Адрес не указан' }}
                </p>
              </div>
            </div>

            <!-- Правая колонка - контактная информация и цена -->
            <div class="space-y-6">
              <!-- Цена -->
              <div class="bg-white rounded-lg p-6 shadow-sm">
                <h2 class="text-xl font-semibold mb-4 text-gray-900">
                  Стоимость
                </h2>
                <div class="text-3xl font-bold text-gray-900 mb-2">
                  {{ formatPrice(adData.price) }}
                </div>
                <p class="text-gray-600">
                  {{ getPriceUnitText(adData.price_unit) }}
                </p>
                <div v-if="adData.discount" class="mt-2">
                  <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                    Скидка {{ adData.discount }}%
                  </span>
                </div>
              </div>

              <!-- Продавец -->
              <div v-if="adData.user" class="bg-white rounded-lg p-6 shadow-sm">
                <!-- Кликабельная версия (если есть профиль мастера) -->
                <Link
                  v-if="adData.user"
                  :href="`/users/${masterSlug}`"
                  class="block mb-4"
                >
                  <div class="flex items-center justify-between cursor-pointer hover:bg-gray-50 p-2 rounded-lg transition-colors">
                    <!-- Левая часть: имя и дата -->
                    <div>
                      <h3 class="text-blue-600 font-semibold hover:underline">
                        {{ userName }}
                      </h3>
                      <p class="text-sm text-gray-500">
                        На платформе с {{ formatRegistrationYear }}
                      </p>
                    </div>

                    <!-- Правая часть: аватар или инициал -->
                    <div v-if="adData.user.avatar">
                      <img
                        :src="adData.user.avatar"
                        :alt="userName"
                        class="w-12 h-12 rounded-full object-cover"
                      >
                    </div>
                    <div
                      v-else
                      class="w-12 h-12 rounded-full flex items-center justify-center text-white font-medium text-lg"
                      :style="{ backgroundColor: avatarColor }"
                    >
                      {{ userInitial }}
                    </div>
                  </div>
                </Link>

                <!-- Некликабельная версия (если нет профиля мастера) -->
                <div v-else class="block mb-4">
                  <div class="flex items-center justify-between p-2">
                    <!-- Левая часть: имя и дата -->
                    <div>
                      <h3 class="text-gray-900 font-semibold">
                        {{ userName }}
                      </h3>
                      <p class="text-sm text-gray-500">
                        На платформе с {{ formatRegistrationYear }}
                      </p>
                    </div>

                    <!-- Правая часть: аватар или инициал -->
                    <div v-if="adData.user.avatar">
                      <img
                        :src="adData.user.avatar"
                        :alt="userName"
                        class="w-12 h-12 rounded-full object-cover"
                      >
                    </div>
                    <div
                      v-else
                      class="w-12 h-12 rounded-full flex items-center justify-center text-white font-medium text-lg"
                      :style="{ backgroundColor: avatarColor }"
                    >
                      {{ userInitial }}
                    </div>
                  </div>
                </div>

                <button
                  class="w-full px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium"
                >
                  Подписаться на продавца
                </button>
              </div>

              <!-- Контакты -->
              <div class="bg-white rounded-lg p-6 shadow-sm">
                <h2 class="text-xl font-semibold mb-4 text-gray-900">
                  Контактная информация
                </h2>
                <div class="space-y-3">
                  <div v-if="adData.phone">
                    <dt class="text-sm font-medium text-gray-500">
                      Телефон
                    </dt>
                    <dd class="text-gray-600">
                      {{ adData.phone }}
                    </dd>
                  </div>
                  <div v-if="adData.contact_method">
                    <dt class="text-sm font-medium text-gray-500">
                      Способ связи
                    </dt>
                    <dd class="text-gray-600">
                      {{ getContactMethodText(adData.contact_method) }}
                    </dd>
                  </div>
                  <div v-if="adData.address">
                    <dt class="text-sm font-medium text-gray-500">
                      Адрес
                    </dt>
                    <dd class="text-gray-600">
                      {{ adData.address }}
                    </dd>
                  </div>
                </div>
              </div>

              <!-- Статистика -->
              <div class="bg-white rounded-lg p-6 shadow-sm">
                <h2 class="text-xl font-semibold mb-4 text-gray-900">
                  Статистика
                </h2>
                <dl class="space-y-2">
                  <div class="flex justify-between">
                    <dt class="text-gray-500">
                      Просмотры
                    </dt>
                    <dd class="text-gray-600">
                      {{ adData.views_count || 0 }}
                    </dd>
                  </div>
                  <div class="flex justify-between">
                    <dt class="text-gray-500">
                      В избранном
                    </dt>
                    <dd class="text-gray-600">
                      {{ adData.favorites_count || 0 }}
                    </dd>
                  </div>
                  <div class="flex justify-between">
                    <dt class="text-gray-500">
                      Показы контактов
                    </dt>
                    <dd class="text-gray-600">
                      {{ adData.contacts_shown || 0 }}
                    </dd>
                  </div>
                </dl>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty -->
    <div v-else class="text-center py-12">
      <p class="text-gray-500">
        Информация об объявлении недоступна
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import PhotoGallery from '@/src/features/gallery/ui/PhotoGallery/PhotoGallery.vue'
import AdDetailSkeleton from './AdDetailSkeleton.vue'

interface User {
  id?: number
  name?: string
  avatar?: string | null
  created_at?: string
  profile?: {
    id?: number
    slug?: string
  }
}

interface Ad {
  id?: string | number
  title?: string
  name?: string
  avatar?: string
  description?: string
  rating?: number
  reviewsCount?: number
  reviews_count?: number
  photos?: any[]
  services?: any
  prices?: any
  created_at?: string
  updated_at?: string
  status?: string
  experience?: string
  work_format?: string
  clients?: string[]
  service_location?: string[]
  coordinates?: { lat: number; lng: number }
  lat?: number
  lng?: number
  address?: string
  price?: number | string
  price_unit?: string
  discount?: number
  phone?: string
  contact_method?: string
  views_count?: number
  favorites_count?: number
  contacts_shown?: number
  user?: User
  [key: string]: any
}

interface Props {
  ad?: Ad | null
  loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    ad: null,
    loading: false
})

// 🔧 FIX: Unwrap data if nested
const adData = computed(() => {
    if (!props.ad) return {} as Ad
    const data = (props.ad as any).data || props.ad

    // DEBUG: Проверяем данные пользователя (slug теперь в user, не в masterProfile)
    // console.log('AdDetail:', {
    //     hasUser: !!data.user,
    //     userName: data.user?.name,
    //     userSlug: data.user?.slug,
    //     userId: data.user?.id,
    // })

    return data
})

// Computed свойства
const adLocation = computed(() => {
    // Пытаемся получить координаты из объявления
    if (adData.value.coordinates) {
        return [adData.value.coordinates.lat, adData.value.coordinates.lng]
    }

    // Альтернативный формат координат
    if (adData.value.lat && adData.value.lng) {
        return [adData.value.lat, adData.value.lng]
    }

    // Если координат нет, карта не отображается
    return null
})

// Master profile ID and slug for navigation
// Slug теперь находится в user, не в masterProfile
const masterSlug = computed(() => adData.value.user?.slug || `user-${adData.value.user?.id || 'unknown'}`)

// Format registration year
const formatRegistrationYear = computed(() => {
    const date = adData.value.user?.created_at
    if (!date) return 'недавно'
    return new Date(date).getFullYear()
})

// Avatar logic (from Dashboard.vue)
const userName = computed(() => adData.value.user?.name || 'Мастер')
const userInitial = computed(() => userName.value.charAt(0).toUpperCase())
const avatarColor = computed(() => {
    const colors = ['#ef4444', '#f97316', '#eab308', '#22c55e', '#06b6d4', '#3b82f6', '#8b5cf6', '#ec4899']
    const index = userName.value.charCodeAt(0) % colors.length
    return colors[index]
})

// Normalize photos format for PhotoGallery component
const normalizedPhotos = computed(() => {
    if (!adData.value.photos || !Array.isArray(adData.value.photos)) {
        return []
    }

    const result = adData.value.photos.map((photo: any) => {
        // If photo is already an object with url property, return as is
        if (typeof photo === 'object' && photo.url) {
            return photo
        }

        // If photo is a string (URL), convert to object format
        if (typeof photo === 'string') {
            return {
                url: photo,
                preview: photo,
                alt: adData.value.title
            }
        }

        return null
    }).filter(Boolean)

    return result
})

// Normalize services for display
const normalizedServices = computed(() => {
    if (!adData.value.services) {
        return []
    }

    const services: any[] = []
    // services is a nested object structure
    Object.entries(adData.value.services).forEach(([_category, items]) => {
        if (typeof items === 'object') {
            Object.entries(items as any).forEach(([serviceKey, serviceData]: [string, any]) => {
                if (serviceData && serviceData.enabled) {
                    services.push({
                        name: serviceKey.replace(/_/g, ' '),
                        price: serviceData.price || null,
                        comment: serviceData.price_comment || null
                    })
                }
            })
        }
    })

    return services
})

// Normalize prices for display
const normalizedPrices = computed(() => {
    if (!adData.value.prices) {
        return []
    }

    const prices: any[] = []
    Object.entries(adData.value.prices).forEach(([key, value]) => {
        if (value && value !== '0' && value !== 0) {
            prices.push({
                name: key.replace(/_/g, ' '),
                value: value
            })
        }
    })

    return prices
})

// Утилиты форматирования
const formatDate = (dateString: any) => {
    if (!dateString) return ''
    return new Date(dateString).toLocaleDateString('ru-RU', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    })
}

const formatPrice = (price: any) => {
    if (!price || price === '0' || price === 0) return 'Цена не указана'
    // Convert string to number if needed
    const priceNum = typeof price === 'string' ? parseInt(price, 10) : price
    if (isNaN(priceNum)) return 'Цена не указана'
    return new Intl.NumberFormat('ru-RU').format(priceNum) + ' ₽'
}

const getStatusClass = (status: any) => {
    const classes = {
        'active': 'bg-green-100 text-green-800',
        'draft': 'bg-yellow-100 text-yellow-800',
        'waiting_payment': 'bg-orange-100 text-orange-800',
        'archived': 'bg-gray-100 text-gray-800',
        'expired': 'bg-red-100 text-red-800'
    }
    return (classes as any)[status] || 'bg-gray-100 text-gray-800'
}

const getStatusText = (status: any) => {
    const texts = {
        'active': 'Активно',
        'draft': 'Черновик',
        'waiting_payment': 'Ждет оплаты',
        'archived': 'В архиве',
        'expired': 'Истекло'
    }
    return (texts as any)[status] || status
}

const getPriceUnitText = (unit: any) => {
    const units = {
        'service': 'за услугу',
        'hour': 'за час',
        'session': 'за сеанс',
        'day': 'за день',
        'month': 'за месяц'
    }
    return (units as any)[unit] || unit
}

const getContactMethodText = (method: any) => {
    const methods = {
        'any': 'Любой способ',
        'calls': 'Только звонки',
        'messages': 'Только сообщения'
    }
    return (methods as any)[method] || method
}

const getServiceLocationText = (locations: any) => {
    if (!Array.isArray(locations)) return ''

    const locationTexts = {
        'my_place': 'У мастера',
        'client_home': 'Выезд к клиенту',
        'salon': 'В салоне'
    }

    return locations.map(loc => (locationTexts as any)[loc] || loc).join(', ')
}
</script>
