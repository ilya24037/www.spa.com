<template>
  <div class="progress-bar-wrapper">
    <!-- Заголовок и процент -->
    <div class="flex justify-between items-center mb-3">
      <h3 class="text-sm font-medium text-gray-700">
        {{ title }}
      </h3>
      <span class="text-sm font-bold" :class="percentageClass">
        {{ percentage }}%
      </span>
    </div>
    
    <!-- Прогресс-бар -->
    <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
      <div 
        class="h-full rounded-full transition-all duration-500 ease-out"
        :class="barClass"
        :style="{ width: `${percentage}%` }"
      >
        <!-- Анимированный блик -->
        <div 
          v-if="percentage > 0"
          class="h-full w-full relative overflow-hidden"
        >
          <div 
            class="absolute top-0 left-0 h-full w-6 bg-white opacity-30 skew-x-12 animate-shimmer"
            style="animation-duration: 2s; animation-iteration-count: infinite;"
          ></div>
        </div>
      </div>
    </div>
    
    <!-- Подсказки по разделам -->
    <div v-if="showSections" class="mt-4 grid grid-cols-2 md:grid-cols-3 gap-2">
      <div 
        v-for="section in sections"
        :key="section.name"
        class="flex items-center gap-2 p-2 rounded-lg text-xs"
        :class="section.completed ? 'bg-green-50 text-green-700' : 'bg-gray-50 text-gray-500'"
      >
        <div 
          class="w-3 h-3 rounded-full flex items-center justify-center"
          :class="section.completed ? 'bg-green-500' : 'bg-gray-300'"
        >
          <svg 
            v-if="section.completed"
            class="w-2 h-2 text-white" 
            fill="currentColor" 
            viewBox="0 0 20 20"
          >
            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
          </svg>
        </div>
        <span>{{ section.name }}</span>
      </div>
    </div>
    
    <!-- Мотивационное сообщение -->
    <div v-if="motivationMessage" class="mt-3 p-3 rounded-lg" :class="motivationClass">
      <div class="flex items-center gap-2">
        <span class="text-lg">{{ motivationIcon }}</span>
        <span class="text-sm font-medium">{{ motivationMessage }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  percentage: {
    type: Number,
    required: true,
    validator: (value) => value >= 0 && value <= 100
  },
  title: {
    type: String,
    default: 'Прогресс заполнения'
  },
  showSections: {
    type: Boolean,
    default: true
  },
  sections: {
    type: Array,
    default: () => []
  }
})

// Цветовые классы в зависимости от процента
const percentageClass = computed(() => {
  if (props.percentage < 30) return 'text-red-600'
  if (props.percentage < 70) return 'text-yellow-600'
  return 'text-green-600'
})

const barClass = computed(() => {
  if (props.percentage < 30) return 'bg-gradient-to-r from-red-500 to-red-600'
  if (props.percentage < 70) return 'bg-gradient-to-r from-yellow-500 to-yellow-600'
  return 'bg-gradient-to-r from-green-500 to-green-600'
})

// Мотивационные сообщения
const motivationData = computed(() => {
  if (props.percentage === 0) {
    return {
      message: 'Начните заполнение анкеты',
      icon: '🚀',
      class: 'bg-blue-50 text-blue-700'
    }
  }
  
  if (props.percentage < 25) {
    return {
      message: 'Отличное начало! Продолжайте',
      icon: '💪',
      class: 'bg-blue-50 text-blue-700'
    }
  }
  
  if (props.percentage < 50) {
    return {
      message: 'Четверть пути пройдена!',
      icon: '⭐',
      class: 'bg-yellow-50 text-yellow-700'
    }
  }
  
  if (props.percentage < 75) {
    return {
      message: 'Половина готова! Не останавливайтесь',
      icon: '🔥',
      class: 'bg-orange-50 text-orange-700'
    }
  }
  
  if (props.percentage < 90) {
    return {
      message: 'Почти готово! Осталось совсем немного',
      icon: '🎯',
      class: 'bg-green-50 text-green-700'
    }
  }
  
  if (props.percentage < 100) {
    return {
      message: 'Последний рывок к финишу!',
      icon: '🏁',
      class: 'bg-green-50 text-green-700'
    }
  }
  
  return {
    message: 'Поздравляем! Анкета готова к публикации',
    icon: '🎉',
    class: 'bg-green-50 text-green-700'
  }
})

const motivationMessage = computed(() => motivationData.value.message)
const motivationIcon = computed(() => motivationData.value.icon)
const motivationClass = computed(() => motivationData.value.class)
</script>

<style scoped>
@keyframes shimmer {
  0% {
    transform: translateX(-100%) skewX(12deg);
  }
  100% {
    transform: translateX(400%) skewX(12deg);
  }
}

.animate-shimmer {
  animation: shimmer 2s infinite;
}
</style>