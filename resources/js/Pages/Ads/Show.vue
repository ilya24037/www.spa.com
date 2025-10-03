<!-- Страница просмотра объявления -->
<template>
  <Head :title="ad.title" />
  
  <div class="min-h-screen">
    <div class="max-w-4xl mx-auto py-8 px-4">
      <!-- Заголовок -->
      <div class="bg-white rounded-lg p-6 mb-6 shadow-sm">
        <div class="flex items-start justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
              {{ ad.value.title }}
            </h1>
            <!-- specialty скрыто - поле теперь необязательное -->
            <div class="flex items-center gap-4 text-sm text-gray-500">
              <span>Создано: {{ formatDate(ad.created_at) }}</span>
              <span>Обновлено: {{ formatDate(ad.updated_at) }}</span>
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
              mode="grid"
              title="Фотографии"
              :enable-lightbox="true"
            />
          </div>

          <!-- Описание -->
          <div class="bg-white rounded-lg p-6 shadow-sm">
            <h2 class="text-xl font-semibold mb-4 text-gray-900">
              Описание
            </h2>
            <div class="prose max-w-none">
              <p class="text-gray-600 leading-relaxed whitespace-pre-line">
                {{ ad.value.description || 'Описание не указано' }}
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
                  {{ ad.value.experience || 'Не указан' }}
                </dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">
                  Формат работы
                </dt>
                <dd class="text-gray-600">
                  {{ ad.value.work_format || 'Не указан' }}
                </dd>
              </div>
              <div v-if="ad.value.clients && ad.clients.length > 0">
                <dt class="text-sm font-medium text-gray-500">
                  Категории клиентов
                </dt>
                <dd class="text-gray-600">
                  {{ ad.value.clients.join(', ') }}
                </dd>
              </div>
              <div v-if="ad.value.service_location && ad.service_location.length > 0">
                <dt class="text-sm font-medium text-gray-500">
                  Места оказания услуг
                </dt>
                <dd class="text-gray-600">
                  {{ getServiceLocationText(ad.service_location) }}
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
                <div class="text-gray-500 text-lg mb-2">🗺️ Карта временно недоступна</div>
                <div class="text-gray-400 text-sm">YandexMapNative удален из проекта</div>
                <div class="text-gray-400 text-xs mt-2">Координаты: {{ adLocation.join(', ') }}</div>
              </div>
            </div>
            <p class="text-sm text-gray-600 mt-3">
              {{ ad.value.address || 'Адрес не указан' }}
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
              {{ formatPrice(ad.price) }}
            </div>
            <p class="text-gray-600">
              {{ getPriceUnitText(ad.price_unit) }}
            </p>
            <div v-if="ad.value.discount" class="mt-2">
              <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                Скидка {{ ad.value.discount }}%
              </span>
            </div>
          </div>

          <!-- Контакты -->
          <div class="bg-white rounded-lg p-6 shadow-sm">
            <h2 class="text-xl font-semibold mb-4 text-gray-900">
              Контактная информация
            </h2>
            <div class="space-y-3">
              <div v-if="ad.value.phone">
                <dt class="text-sm font-medium text-gray-500">
                  Телефон
                </dt>
                <dd class="text-gray-600">
                  {{ ad.value.phone }}
                </dd>
              </div>
              <div v-if="ad.value.contact_method">
                <dt class="text-sm font-medium text-gray-500">
                  Способ связи
                </dt>
                <dd class="text-gray-600">
                  {{ getContactMethodText(ad.contact_method) }}
                </dd>
              </div>
              <div v-if="ad.value.address">
                <dt class="text-sm font-medium text-gray-500">
                  Адрес
                </dt>
                <dd class="text-gray-600">
                  {{ ad.value.address }}
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
                  {{ ad.value.views_count || 0 }}
                </dd>
              </div>
              <div class="flex justify-between">
                <dt class="text-gray-500">
                  В избранном
                </dt>
                <dd class="text-gray-600">
                  {{ ad.value.favorites_count || 0 }}
                </dd>
              </div>
              <div class="flex justify-between">
                <dt class="text-gray-500">
                  Показы контактов
                </dt>
                <dd class="text-gray-600">
                  {{ ad.value.contacts_shown || 0 }}
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
import { computed } from 'vue'
import PhotoGallery from '@/src/features/gallery/ui/PhotoGallery/PhotoGallery.vue'
// import YandexMapNative from '@/src/features/map/components/YandexMapNative.vue' // УДАЛЕН

const props = defineProps({
    ad: {
        type: Object,
        required: true
    },
    isOwner: {
        type: Boolean,
        default: false
    }
})

// 🔧 FIX: Unwrap data if nested
const ad = computed(() => props.ad.data || props.ad)

// Computed свойства
const adLocation = computed(() => {
    // Пытаемся получить координаты из объявления
    if (ad.value.coordinates) {
        return [ad.value.coordinates.lat, ad.value.coordinates.lng]
    }

    // Альтернативный формат координат
    if (ad.value.lat && ad.value.lng) {
        return [ad.value.lat, ad.value.lng]
    }

    // Если координат нет, карта не отображается
    return null
})

// Normalize photos format for PhotoGallery component
const normalizedPhotos = computed(() => {
    if (!ad.value.photos || !Array.isArray(ad.value.photos)) {
        return []
    }

    const result = ad.value.photos.map(photo => {
        // If photo is already an object with url property, return as is
        if (typeof photo === 'object' && photo.url) {
            return photo
        }

        // If photo is a string (URL), convert to object format
        if (typeof photo === 'string') {
            return {
                url: photo,
                preview: photo,
                alt: ad.value.title
            }
        }

        return null
    }).filter(Boolean)

    return result
})

// Normalize services for display
const normalizedServices = computed(() => {
    if (!ad.value.services) {
        return []
    }

    const services = []
    // services is a nested object structure
    Object.entries(ad.value.services).forEach(([category, items]) => {
        if (typeof items === 'object') {
            Object.entries(items).forEach(([serviceKey, serviceData]) => {
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
    if (!ad.value.prices) {
        return []
    }

    const prices = []
    Object.entries(ad.value.prices).forEach(([key, value]) => {
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
