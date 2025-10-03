<!-- MasterProfile - профиль мастера в стиле Avito -->
<template>
  <div class="master-profile min-h-screen bg-gray-50">
    <MasterProfileSkeleton v-if="loading" />

    <div v-else-if="master" class="max-w-7xl mx-auto px-4 py-6">
      <!-- Grid: Sidebar + Content -->
      <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Левая панель (Sidebar) -->
        <div class="lg:col-span-1">
          <ProfileSidebar
            :master="master"
            @show-phone="handleShowPhone"
            @write-message="handleWriteMessage"
            @subscribe="handleSubscribe"
          />
        </div>

        <!-- Правая панель (Контент) -->
        <div class="lg:col-span-3 space-y-6">
          <!-- Табы и поиск -->
          <div class="bg-white rounded-lg shadow-sm p-4">
            <div class="flex items-center justify-between mb-4">
              <!-- Табы -->
              <div class="flex gap-2">
                <button
                  @click="activeTab = 'active'"
                  :class="[
                    'px-4 py-2 rounded-lg font-medium transition-colors',
                    activeTab === 'active'
                      ? 'bg-blue-600 text-white'
                      : 'text-gray-600 hover:bg-gray-100'
                  ]"
                >
                  Активные
                  <sup v-if="activeAdsCount" class="ml-1">{{ activeAdsCount }}</sup>
                </button>
                <button
                  @click="activeTab = 'completed'"
                  :class="[
                    'px-4 py-2 rounded-lg font-medium transition-colors',
                    activeTab === 'completed'
                      ? 'bg-blue-600 text-white'
                      : 'text-gray-600 hover:bg-gray-100'
                  ]"
                >
                  Завершённые
                  <sup v-if="completedAdsCount" class="ml-1">{{ completedAdsCount }}</sup>
                </button>
              </div>

              <!-- Поиск -->
              <div class="flex items-center gap-2">
                <input
                  v-model="searchQuery"
                  type="text"
                  placeholder="Поиск в профиле"
                  class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                <button class="px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800">
                  Найти
                </button>
              </div>
            </div>
          </div>

          <!-- Список объявлений -->
          <div v-if="filteredAds.length > 0" class="space-y-4">
            <ItemCard
              v-for="ad in filteredAds"
              :key="ad.id"
              :item="ad"
            />
          </div>
          <div v-else class="bg-white rounded-lg shadow-sm p-12 text-center">
            <p class="text-gray-500">
              {{ activeTab === 'active' ? 'Нет активных объявлений' : 'Нет завершённых объявлений' }}
            </p>
          </div>

          <!-- Адрес на карте -->
          <div v-if="master.address || master.location" class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-xl font-semibold mb-4 text-gray-900">
              Адрес
            </h2>
            <div class="rounded-lg overflow-hidden bg-gray-200 h-64 flex items-center justify-center mb-4">
              <div class="text-center">
                <div class="text-gray-500 text-lg mb-2">🗺️ Карта</div>
                <div class="text-gray-400 text-sm">
                  {{ master.address || master.location || 'Адрес не указан' }}
                </div>
              </div>
            </div>
            <div class="text-sm text-gray-700 mb-3">
              <strong>{{ master.city || 'Краснодарский край' }}, {{ master.district || 'Сочи' }}</strong>
            </div>
            <div v-if="master.district" class="text-sm text-gray-600 mb-4">
              р-н {{ master.district }}
            </div>
            <button
              @click="handleShowPhone"
              class="w-full px-4 py-3 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium"
            >
              Показать телефон
            </button>
          </div>

          <!-- Отзывы -->
          <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="mb-6">
              <h2 class="text-xl font-semibold mb-4 text-gray-900">
                Отзывы о {{ master.name }}
              </h2>

              <!-- Общий рейтинг -->
              <div class="flex items-start gap-8 mb-6">
                <div class="text-center">
                  <div class="text-5xl font-bold text-gray-900 mb-2">
                    {{ averageRating }}
                  </div>
                  <div class="flex gap-1 mb-2">
                    <span
                      v-for="star in 5"
                      :key="star"
                      class="text-2xl"
                      :class="star <= Math.round(averageRating) ? 'text-yellow-400' : 'text-gray-300'"
                    >
                      ★
                    </span>
                  </div>
                  <div class="text-sm text-gray-500">
                    {{ totalReviews ? 'рейтинга пока нет' : `${totalReviews} отзывов` }}
                  </div>
                </div>

                <!-- График распределения -->
                <div class="flex-1">
                  <RatingChart :ratings="ratingDistribution" />
                </div>
              </div>

              <!-- Информация о рейтинге -->
              <p class="text-sm text-gray-600 mb-4">
                Рейтинг — это среднее арифметическое оценок пользователей.
                <a href="#" class="text-blue-600 hover:underline">Подробнее</a>
              </p>

              <!-- Кнопка написать отзыв -->
              <button
                @click="$emit('write-review')"
                class="px-6 py-3 bg-gray-900 text-white rounded-lg hover:bg-gray-800 transition-colors font-medium"
              >
                Написать отзыв
              </button>
            </div>

            <!-- Список отзывов -->
            <div v-if="reviews && reviews.length > 0" class="space-y-4 mt-6">
              <ReviewCard
                v-for="review in reviews"
                :key="review.id"
                :review="review"
              />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty state -->
    <div v-else class="text-center py-12">
      <p class="text-gray-500">
        Информация о мастере недоступна
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import MasterProfileSkeleton from './MasterProfileSkeleton.vue'
import ProfileSidebar from './ProfileSidebar.vue'
import RatingChart from './RatingChart.vue'
import ItemCard from '@/src/entities/ad/ui/ItemCard/ItemCard.vue'
import ReviewCard from '@/src/entities/review/ui/ReviewCard/ReviewCard.vue'

interface Master {
  id?: string | number
  name?: string
  avatar?: string
  description?: string
  rating?: number
  reviews_count?: number
  followers_count?: number
  following_count?: number
  created_at?: string
  is_verified?: boolean
  phone_verified?: boolean
  is_subscribed?: boolean
  address?: string
  location?: string
  city?: string
  district?: string
  ads?: any[]
  reviews?: any[]
  rating_distribution?: Array<{ stars: number; count: number }>
}

interface Props {
  master?: Master | null
  loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  master: null,
  loading: false
})

defineEmits<{
  'write-review': []
}>()

const page = usePage()
const activeTab = ref<'active' | 'completed'>('active')
const searchQuery = ref('')

// Computed properties
const activeAds = computed(() => {
  return props.master?.ads?.filter(ad => ad.status === 'active') || []
})

const completedAds = computed(() => {
  return props.master?.ads?.filter(ad => ad.status === 'completed') || []
})

const activeAdsCount = computed(() => activeAds.value.length)
const completedAdsCount = computed(() => completedAds.value.length)

const filteredAds = computed(() => {
  const ads = activeTab.value === 'active' ? activeAds.value : completedAds.value

  if (!searchQuery.value.trim()) return ads

  const query = searchQuery.value.toLowerCase()
  return ads.filter(ad =>
    ad.title?.toLowerCase().includes(query) ||
    ad.description?.toLowerCase().includes(query)
  )
})

const reviews = computed(() => props.master?.reviews || [])
const totalReviews = computed(() => reviews.value.length)

const averageRating = computed(() => {
  if (!props.master?.rating) return 0
  return Number(props.master.rating.toFixed(1))
})

const ratingDistribution = computed(() => {
  return props.master?.rating_distribution || [
    { stars: 5, count: 0 },
    { stars: 4, count: 0 },
    { stars: 3, count: 0 },
    { stars: 2, count: 0 },
    { stars: 1, count: 0 }
  ]
})

// Handlers
const handleShowPhone = () => {
  alert(`Телефон: ${props.master?.phone || 'не указан'}`)
}

const handleWriteMessage = () => {
  window.location.href = `/messages/new?master=${props.master?.id}`
}

const handleSubscribe = () => {
  // TODO: implement subscription
  console.log('Subscribe to master:', props.master?.id)
}
</script>
