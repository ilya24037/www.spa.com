<!-- 
  Пример использования адаптивной сетки
  Демонстрирует все возможности системы сеток SPA Platform
-->
<template>
  <div class="adaptive-grid-example">
    <div class="container-adaptive">
      
      <!-- Заголовок -->
      <header class="text-center mb-8">
        <h1 class="heading-page">Адаптивная Сетка SPA Platform</h1>
        <p class="text-body max-w-2xl mx-auto">
          Система сеток по образцу больших маркетплейсов: Ozon, Wildberries, Avito
        </p>
      </header>

      <!-- Информация об устройстве -->
      <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <h3 class="font-medium text-blue-900 mb-2">Информация об устройстве</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
          <div>Тип: <strong>{{ deviceType }}</strong></div>
          <div>Ширина: <strong>{{ screenWidth }}px</strong></div>
          <div>Колонок: <strong>{{ effectiveColumns }}</strong></div>
          <div>Вид: <strong>{{ currentView }}</strong></div>
        </div>
      </div>

      <!-- Управление сеткой -->
      <GridControls
        :displayed-count="displayedItems.length"
        :total-count="totalItems.length"
        items-label="услуг"
        :current-view="currentView"
        :current-density="currentDensity"
        :current-sort="currentSort"
        :current-columns="currentColumns"
        @view-change="setView"
        @density-change="setDensity"
        @sort-change="setSort"
        @columns-change="setColumns"
      />

      <!-- Основная сетка -->
      <div 
        :class="gridClasses"
        :style="gridStyles"
        class="animate-fade-in"
      >
        <!-- Карточка услуги -->
        <div
          v-for="item in sortedItems"
          :key="item.id"
          :class="[
            'adaptive-card service-card',
            { 'adaptive-card--compact': currentDensity === 'compact' }
          ]"
        >
          <!-- Изображение -->
          <img
            :src="item.image"
            :alt="item.title"
            class="adaptive-card__image"
            loading="lazy"
          />
          
          <!-- Промо бейдж -->
          <div
            v-if="item.isPromoted"
            class="absolute top-3 left-3 bg-yellow-500 text-white text-xs px-2 py-1 rounded-full font-semibold"
          >
            ⭐ Топ
          </div>
          
          <!-- Скидка -->
          <div
            v-if="item.discount"
            class="absolute top-3 right-3 bg-red-500 text-white text-xs px-2 py-1 rounded-full font-bold"
          >
            -{{ item.discount }}%
          </div>
          
          <!-- Контент -->
          <div class="adaptive-card__content">
            <h3 class="adaptive-card__title">{{ item.title }}</h3>
            <p class="adaptive-card__description">{{ item.description }}</p>
            
            <!-- Мастер -->
            <div class="flex items-center gap-2 mb-3">
              <img
                :src="item.masterAvatar"
                :alt="item.masterName"
                class="w-6 h-6 rounded-full"
              />
              <span class="text-sm text-blue-600 font-medium">{{ item.masterName }}</span>
            </div>
            
            <!-- Метаинформация -->
            <div class="adaptive-card__meta">
              <div class="flex flex-col">
                <!-- Цена -->
                <div class="flex items-center gap-2">
                  <span class="service-card__price">{{ formatPrice(item.price) }}</span>
                  <span
                    v-if="item.oldPrice"
                    class="text-sm text-gray-400 line-through"
                  >
                    {{ formatPrice(item.oldPrice) }}
                  </span>
                </div>
                
                <!-- Длительность -->
                <div class="service-card__duration">
                  {{ item.duration }} мин
                </div>
              </div>
              
              <!-- Рейтинг -->
              <div class="service-card__rating">
                <span class="stars">★★★★★</span>
                <span class="count">{{ item.rating }} ({{ item.reviewsCount }})</span>
              </div>
            </div>
            
            <!-- Кнопка -->
            <button class="button button--primary button--small w-full mt-4">
              Записаться
            </button>
          </div>
        </div>
      </div>

      <!-- Секция мастеров -->
      <section class="mt-16">
        <h2 class="heading-section">Сетка Мастеров</h2>
        <div class="master-grid">
          <div
            v-for="master in mockMasters.slice(0, 6)"
            :key="master.id"
            class="master-card"
          >
            <img
              :src="master.avatar"
              :alt="master.name"
              class="master-card__avatar"
            />
            <h3 class="master-card__name">{{ master.name }}</h3>
            <p class="master-card__specialization">{{ master.specialization }}</p>
            <div class="master-card__stats">
              <div class="master-card__stat">
                <span class="number">{{ master.rating }}</span>
                <span class="label">Рейтинг</span>
              </div>
              <div class="master-card__stat">
                <span class="number">{{ master.experience }}</span>
                <span class="label">Лет опыта</span>
              </div>
              <div class="master-card__stat">
                <span class="number">{{ master.clientsCount }}</span>
                <span class="label">Клиентов</span>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Компактная сетка -->
      <section class="mt-16">
        <h2 class="heading-section">Компактная Сетка (как Wildberries)</h2>
        <div class="compact-grid">
          <div
            v-for="item in mockItems.slice(0, 12)"
            :key="`compact-${item.id}`"
            class="adaptive-card adaptive-card--compact"
          >
            <img
              :src="item.image"
              :alt="item.title"
              class="adaptive-card__image"
              style="height: 150px;"
            />
            <div class="adaptive-card__content p-2">
              <h4 class="text-sm font-medium mb-1 line-clamp-2">{{ item.title }}</h4>
              <div class="text-lg font-bold text-blue-600">{{ formatPrice(item.price) }}</div>
            </div>
          </div>
        </div>
      </section>

    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * Пример адаптивной сетки для SPA Platform
 */

import { computed } from 'vue'
import { useAdaptiveGrid } from '@/src/shared/composables/useAdaptiveGrid'
import GridControls from '@/src/shared/ui/molecules/GridControls/GridControls.vue'

// === MOCK DATA ===

const mockItems = [
  {
    id: 1,
    title: 'Расслабляющий массаж всего тела',
    description: 'Профессиональный массаж для снятия стресса и напряжения. Используются натуральные масла.',
    price: 3500,
    oldPrice: 4000,
    discount: 12,
    duration: 60,
    image: 'https://picsum.photos/300/200?random=1',
    masterName: 'Анна Петрова',
    masterAvatar: 'https://picsum.photos/50/50?random=101',
    rating: 4.8,
    reviewsCount: 127,
    isPromoted: true,
    category: 'massage'
  },
  {
    id: 2,
    title: 'Антицеллюлитный массаж',
    description: 'Эффективная процедура для коррекции фигуры и борьбы с целлюлитом.',
    price: 4200,
    duration: 90,
    image: 'https://picsum.photos/300/200?random=2',
    masterName: 'Елена Сидорова',
    masterAvatar: 'https://picsum.photos/50/50?random=102',
    rating: 4.9,
    reviewsCount: 89,
    isPromoted: false,
    category: 'massage'
  },
  {
    id: 3,
    title: 'Лимфодренажный массаж лица',
    description: 'Деликатная процедура для улучшения цвета лица и снятия отечности.',
    price: 2800,
    duration: 45,
    image: 'https://picsum.photos/300/200?random=3',
    masterName: 'Мария Иванова',
    masterAvatar: 'https://picsum.photos/50/50?random=103',
    rating: 4.7,
    reviewsCount: 156,
    isPromoted: true,
    category: 'beauty'
  },
  {
    id: 4,
    title: 'Спортивный массаж',
    description: 'Специализированный массаж для спортсменов и активных людей.',
    price: 3800,
    duration: 75,
    image: 'https://picsum.photos/300/200?random=4',
    masterName: 'Дмитрий Волков',
    masterAvatar: 'https://picsum.photos/50/50?random=104',
    rating: 4.6,
    reviewsCount: 73,
    isPromoted: false,
    category: 'sports'
  },
  // Добавляем еще элементов для демонстрации
  ...Array.from({ length: 16 }, (_, i) => ({
    id: i + 5,
    title: `Массаж ${i + 5}`,
    description: `Описание массажа ${i + 5}. Профессиональная процедура для вашего здоровья.`,
    price: Math.floor(Math.random() * 3000) + 2000,
    duration: [30, 45, 60, 90][Math.floor(Math.random() * 4)],
    image: `https://picsum.photos/300/200?random=${i + 5}`,
    masterName: `Мастер ${i + 5}`,
    masterAvatar: `https://picsum.photos/50/50?random=${i + 105}`,
    rating: Math.round((Math.random() * 2 + 3) * 10) / 10,
    reviewsCount: Math.floor(Math.random() * 200) + 10,
    isPromoted: Math.random() > 0.8,
    category: ['massage', 'beauty', 'sports', 'spa'][Math.floor(Math.random() * 4)]
  }))
]

const mockMasters = Array.from({ length: 12 }, (_, i) => ({
  id: i + 1,
  name: `Мастер ${i + 1}`,
  specialization: ['Массажист', 'Косметолог', 'Остеопат', 'Мануальный терапевт'][Math.floor(Math.random() * 4)],
  avatar: `https://picsum.photos/100/100?random=${i + 200}`,
  rating: Math.round((Math.random() * 2 + 3) * 10) / 10,
  experience: Math.floor(Math.random() * 15) + 2,
  clientsCount: Math.floor(Math.random() * 500) + 50
}))

// === АДАПТИВНАЯ СЕТКА ===

const {
  currentView,
  currentDensity,
  currentSort,
  currentColumns,
  deviceType,
  screenWidth,
  effectiveColumns,
  gridClasses,
  gridStyles,
  setView,
  setDensity,
  setSort,
  setColumns,
  applySorting
} = useAdaptiveGrid({
  defaultView: 'grid',
  defaultDensity: 'comfortable',
  saveToLocalStorage: true,
  storageKey: 'spa-grid-demo'
})

// === COMPUTED ===

const totalItems = computed(() => mockItems)
const displayedItems = computed(() => mockItems)
const sortedItems = computed(() => applySorting(displayedItems.value))

// === UTILS ===

function formatPrice(price: number): string {
  return `${price.toLocaleString('ru-RU')} ₽`
}
</script>

<style scoped>
.adaptive-grid-example {
  min-height: 100vh;
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
  padding: 2rem 0;
}

/* Анимации */
.animate-fade-in {
  animation: fadeIn 0.5s ease-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Ограничение строк */
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>