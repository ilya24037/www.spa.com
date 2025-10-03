<!-- RatingChart - график распределения оценок -->
<template>
  <div class="space-y-2">
    <div
      v-for="rating in ratings"
      :key="rating.stars"
      class="flex items-center gap-3"
    >
      <!-- Звёзды -->
      <div class="flex gap-0.5 w-24">
        <span
          v-for="star in 5"
          :key="star"
          class="text-sm"
          :class="star <= rating.stars ? 'text-yellow-400' : 'text-gray-300'"
        >
          ★
        </span>
      </div>

      <!-- Прогресс бар -->
      <div class="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
        <div
          class="h-full bg-yellow-400 transition-all duration-300"
          :style="{ width: getPercentage(rating.count) + '%' }"
        />
      </div>

      <!-- Количество -->
      <div class="w-8 text-sm text-gray-600 text-right">
        {{ rating.count }}
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface RatingDistribution {
  stars: number
  count: number
}

interface Props {
  ratings?: RatingDistribution[]
}

const props = withDefaults(defineProps<Props>(), {
  ratings: () => [
    { stars: 5, count: 0 },
    { stars: 4, count: 0 },
    { stars: 3, count: 0 },
    { stars: 2, count: 0 },
    { stars: 1, count: 0 }
  ]
})

const totalReviews = computed(() => {
  return props.ratings.reduce((sum, r) => sum + r.count, 0)
})

const getPercentage = (count: number): number => {
  if (totalReviews.value === 0) return 0
  return Math.round((count / totalReviews.value) * 100)
}
</script>
