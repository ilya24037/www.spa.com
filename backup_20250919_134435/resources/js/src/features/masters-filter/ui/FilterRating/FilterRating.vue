<!-- Фильтр по рейтингу -->
<template>
  <div class="space-y-3">
    <!-- Радио-кнопки для выбора минимального рейтинга -->
    <div class="space-y-2">
      <label
        v-for="option in ratingOptions"
        :key="option.value"
        class="flex items-center cursor-pointer hover:bg-gray-50 p-2 rounded-lg transition-colors"
      >
        <input
          type="radio"
          :value="option.value"
          :checked="rating === option.value"
          class="mr-3 text-blue-600 focus:ring-blue-500"
          @change="$emit('update:rating', option.value)"
        >
        <div class="flex items-center gap-2">
          <!-- Звезды -->
          <div class="flex gap-0.5">
            <svg
              v-for="i in 5"
              :key="i"
              class="w-4 h-4"
              :class="i <= option.value ? 'text-yellow-400' : 'text-gray-300'"
              fill="currentColor"
              viewBox="0 0 20 20"
            >
              <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
            </svg>
          </div>
          <span class="text-sm text-gray-600">{{ option.label }}</span>
        </div>
      </label>
    </div>

    <!-- Кнопка сброса -->
    <button
      v-if="rating !== null"
      class="text-sm text-blue-600 hover:text-blue-700 font-medium"
      @click="$emit('update:rating', null)"
    >
      Сбросить рейтинг
    </button>
  </div>
</template>

<script setup lang="ts">
interface Props {
  rating?: number | null
}

const props = withDefaults(defineProps<Props>(), {
  rating: null
})

defineEmits(['update:rating'])

// Опции рейтинга
const ratingOptions = [
  { value: 5, label: 'Только 5 звезд' },
  { value: 4, label: '4 звезды и выше' },
  { value: 3, label: '3 звезды и выше' },
  { value: 2, label: '2 звезды и выше' },
  { value: 1, label: '1 звезда и выше' }
]
</script>