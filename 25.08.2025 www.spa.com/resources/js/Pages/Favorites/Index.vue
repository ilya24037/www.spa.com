<!-- Страница избранного (/favorites) -->
<template>
  <Head title="Избранное" />
    
  <!-- Обертка с правильными отступами как в Dashboard -->
  <div class="py-6 lg:py-8">
    <!-- Loading состояние -->
    <PageLoader 
      v-if="pageLoader?.isLoading.value"
      type="catalog"
      :message="pageLoader?.message.value"
      :show-progress="false"
      :skeleton-count="3"
    />
        
    <!-- Основной контент -->
    <template v-else>
      <!-- Основной контент с гэпом между блоками -->
      <div class="flex gap-6">
        <!-- Боковая панель -->
        <ProfileSidebar 
          :counts="counts"
          :user-stats="userStats"
        />
                
        <!-- Основной контент -->
        <main class="flex-1">
          <ContentCard title="Избранные мастера">
            <div v-if="favorites?.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              <MasterCard 
                v-for="master in favorites"
                :key="master?.id"
                :master="master as any"
              />
            </div>
                    
            <div v-else class="text-center py-12">
              <svg
                class="mx-auto h-12 w-12 text-gray-500 mb-4"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"
                />
              </svg>
              <p class="text-gray-500 text-lg mb-4">
                У вас пока нет избранных мастеров
              </p>
              <Link 
                href="/" 
                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
              >
                Найти мастеров
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

// 🎯 FSD Импорты
import ProfileSidebar from '@/src/shared/ui/organisms/ProfileSidebar/ProfileSidebar.vue'
import ContentCard from '@/src/shared/ui/organisms/ContentCard/ContentCard.vue'
import MasterCard from '@/src/entities/master/ui/MasterCard/MasterCard.vue'
import PageLoader from '@/src/shared/ui/organisms/PageLoader/PageLoader.vue'
import { usePageLoading } from '@/src/shared/composables/usePageLoading'

// Типизация данных
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
  favorites: number
  reviews: number
}

// Props
interface Props {
  favorites?: Master[]
  counts?: Counts
  userStats?: UserStats
}

const props = withDefaults(defineProps<Props>(), {
  favorites: () => [],
  counts: () => ({
    ads: 0,
    bookings: 0,
    favorites: 0,
    reviews: 0
  }),
  userStats: () => ({
    views: 0,
    calls: 0,
    bookings: 0,
    revenue: 0
  })
})

// Page loader для состояния загрузки
const pageLoader = usePageLoading({
  type: 'catalog',
  autoStart: false,
  timeout: 8000,
  onStart: () => {
    logger.info('Favorites page loading started')
  },
  onComplete: () => {
    logger.info('Favorites page loading completed')
  },
  onError: (error) => {
    logger.error('Favorites page loading error:', error)
  }
})

// Логика загрузки при монтировании
onMounted(() => {
  // Проверяем наличие данных
  if (!props.favorites || props.favorites.length === 0) {
    pageLoader.completeLoading()
    return
  }

  // Имитируем загрузку для UX
  pageLoader.startLoading()
  
  setTimeout(() => {
    pageLoader.setProgress(50, 'Загружаем избранных мастеров...')
  }, 200)
  
  setTimeout(() => {
    pageLoader.setProgress(100, 'Готово!')
    pageLoader.completeLoading()
  }, 500)
})
</script>

<style scoped>
/* Стили для страницы избранного */
.favorites-grid {
  display: grid;
  gap: 1.5rem;
}

@media (min-width: 768px) {
  .favorites-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (min-width: 1024px) {
  .favorites-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

/* Анимации для карточек */
.master-card {
  transition: all 0.2s ease-in-out;
}

.master-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
}

/* Стили для пустого состояния */
.empty-state {
  text-align: center;
  padding: 3rem 1rem;
}

.empty-state svg {
  margin: 0 auto 1rem;
  color: #6b7280;
}

.empty-state p {
  color: #6b7280;
  font-size: 1.125rem;
  margin-bottom: 1rem;
}

/* Кнопка поиска мастеров */
.find-masters-btn {
  display: inline-flex;
  align-items: center;
  padding: 0.5rem 1rem;
  background-color: #2563eb;
  color: white;
  border-radius: 0.5rem;
  font-weight: 500;
  transition: all 0.2s ease-in-out;
}

.find-masters-btn:hover {
  background-color: #1d4ed8;
  transform: translateY(-1px);
}
</style>
