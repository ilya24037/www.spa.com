<template>
  <div class="master-info">
    <!-- Заголовок -->
    <div class="info-header">
      <h1 class="master-name">{{ master.display_name }}</h1>
      
      <!-- Бейджи -->
      <div class="badges">
        <span v-if="master.is_premium" class="badge badge-premium">
          ПРЕМИУМ
        </span>
        <span v-if="master.is_verified" class="badge badge-verified">
          ✓ Проверено
        </span>
      </div>
    </div>

    <!-- Рейтинг и отзывы -->
    <div class="rating-section">
      <div class="rating">
        <div class="stars">
          <StarIcon 
            v-for="i in 5" 
            :key="i"
            :class="[
              'w-5 h-5',
              i <= Math.round(master.rating) ? 'text-yellow-400 fill-current' : 'text-gray-300'
            ]"
          />
        </div>
        <span class="rating-text">
          {{ master.rating }} ({{ master.reviews_count }} отзывов)
        </span>
      </div>
      
      <div class="status">
        <span class="status-dot online"></span>
        В сети
      </div>
    </div>

    <!-- Основные данные -->
    <div class="info-grid">
      <div class="info-item">
        <MapPinIcon class="w-5 h-5 text-gray-400" />
        <span>{{ master.city }}{{ master.district ? ', ' + master.district : '' }}</span>
      </div>
      
      <div class="info-item">
        <ClockIcon class="w-5 h-5 text-gray-400" />
        <span>Опыт {{ master.experience_years }} {{ getYearWord(master.experience_years) }}</span>
      </div>
      
      <div class="info-item">
        <EyeIcon class="w-5 h-5 text-gray-400" />
        <span>{{ formatViews(master.views_count) }} просмотров</span>
      </div>
      
      <div v-if="master.metro_station" class="info-item">
        <TramIcon class="w-5 h-5 text-gray-400" />
        <span>Метро {{ master.metro_station }}</span>
      </div>
    </div>

    <!-- Описание -->
    <div v-if="master.bio" class="description">
      <p>{{ master.bio }}</p>
    </div>
  </div>
</template>

<script setup>
import { 
  StarIcon, 
  MapPinIcon, 
  ClockIcon, 
  EyeIcon, 
  TramIcon 
} from '@heroicons/vue/24/outline'

const props = defineProps({
  master: {
    type: Object,
    required: true
  }
})

const getYearWord = (years) => {
  if (years === 1) return 'год'
  if (years >= 2 && years <= 4) return 'года'
  return 'лет'
}

const formatViews = (views) => {
  if (views >= 1000000) {
    return (views / 1000000).toFixed(1) + 'M'
  }
  if (views >= 1000) {
    return (views / 1000).toFixed(1) + 'K'
  }
  return views.toString()
}
</script>

<style scoped>
.master-info {
  @apply bg-white rounded-lg p-6 shadow-sm;
}

.info-header {
  @apply flex items-start justify-between mb-4;
}

.master-name {
  @apply text-2xl font-bold text-gray-900;
}

.badges {
  @apply flex gap-2;
}

.badge {
  @apply px-3 py-1 rounded-full text-xs font-medium;
}

.badge-premium {
  @apply bg-yellow-100 text-yellow-800;
}

.badge-verified {
  @apply bg-green-100 text-green-800;
}

.rating-section {
  @apply flex items-center justify-between mb-6;
}

.rating {
  @apply flex items-center gap-2;
}

.stars {
  @apply flex gap-1;
}

.rating-text {
  @apply text-gray-600;
}

.status {
  @apply flex items-center gap-2 text-green-600 font-medium;
}

.status-dot {
  @apply w-2 h-2 rounded-full;
}

.status-dot.online {
  @apply bg-green-500;
}

.info-grid {
  @apply grid grid-cols-1 md:grid-cols-2 gap-4 mb-6;
}

.info-item {
  @apply flex items-center gap-3 text-gray-600;
}

.description {
  @apply text-gray-700 leading-relaxed;
}
</style> 