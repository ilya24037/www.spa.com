<!-- resources/js/Components/Masters/MasterHeader/index.vue -->
<template>
  <div class="master-header-widget">
    <!-- Заголовок -->
    <div class="mb-4">
      <div class="flex items-start gap-3 mb-3">
        <h1 class="text-2xl font-bold text-gray-900">
          {{ master.name }}
        </h1>
        <!-- Бейджи -->
        <div class="flex items-center gap-2 flex-shrink-0">
          <span 
            v-if="master.is_premium" 
            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gradient-to-r from-amber-400 to-amber-500 text-white shadow-sm"
          >
            ⭐ ПРЕМИУМ
          </span>
          <span 
            v-if="master.is_verified" 
            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-500 text-white shadow-sm"
          >
            ✓ Проверен
          </span>
        </div>
      </div>
      
      <!-- Основные данные -->
      <div class="flex items-center gap-4 text-sm text-gray-600">
        <span>{{ master.age }} лет</span>
        <span>•</span>
        <span>Опыт {{ master.experience_years }} {{ experienceYearsText }}</span>
        <span>•</span>
        <span>ID: {{ master.id }}</span>
      </div>
    </div>
    
    <!-- Рейтинг и статистика -->
    <QuickStats 
      :rating="master.rating"
      :reviews-count="master.reviews_count"
      :is-available="master.is_available_now"
      :views-count="master.views_count"
      @show-reviews="$emit('show-reviews')"
    />
    
    <!-- Описание -->
    <div class="prose prose-sm max-w-none mt-6">
      <p>{{ master.description || 'Профессиональный массажист с большим опытом работы.' }}</p>
    </div>
    
    <!-- Локация -->
    <div class="mt-4 space-y-2 text-sm">
      <div v-if="master.metro_station" class="flex items-center gap-2 text-gray-600">
        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
        <span>Метро {{ master.metro_station }}</span>
      </div>
      <div class="flex items-center gap-2 text-gray-600">
        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
        </svg>
        <span>{{ master.district }}, {{ master.city }}</span>
      </div>
      
      <!-- Выезд -->
      <div v-if="master.work_on_site" class="flex items-center gap-2 text-green-600">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span>Работает с выездом</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import QuickStats from './QuickStats.vue'

const props = defineProps({
  master: {
    type: Object,
    required: true
  }
})

defineEmits(['show-reviews'])

// Склонение слов
const experienceYearsText = computed(() => {
  const years = props.master.experience_years || 0
  const lastDigit = years % 10
  const lastTwoDigits = years % 100
  
  if (lastTwoDigits >= 11 && lastTwoDigits <= 14) return 'лет'
  if (lastDigit === 1) return 'год'
  if (lastDigit >= 2 && lastDigit <= 4) return 'года'
  return 'лет'
})
</script>