<!-- resources/js/src/shared/layouts/ProfileLayout/ProfileSidebar.vue -->
<template>
  <div class="space-y-6">
    <!-- Профильная карточка -->
    <div class="bg-white rounded-lg shadow-sm p-6">
      <div class="flex items-center gap-4">
        <img
          :src="user.avatar || '/images/default-avatar.svg'"
          :alt="user.name"
          class="w-16 h-16 rounded-full object-cover"
        >
        <div>
          <h3 class="font-semibold text-gray-500">
            {{ user.name }}
          </h3>
          <p class="text-sm text-gray-500">
            {{ user.email }}
          </p>
          <div v-if="user.is_master" class="flex items-center gap-1 mt-1 px-2 py-0.5 bg-green-100 text-green-700 text-xs font-medium rounded-full">
            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
            Мастер
          </div>
        </div>
      </div>
    </div>

    <!-- Статистика -->
    <div class="bg-white rounded-lg shadow-sm p-6">
      <h4 class="font-semibold text-gray-500 mb-4">
        Статистика
      </h4>
      <div class="grid grid-cols-2 gap-4">
        <div class="text-center">
          <div class="text-2xl font-bold text-blue-600">
            {{ counts.ads || 0 }}
          </div>
          <div class="text-sm text-gray-500 mt-1">
            Объявлений
          </div>
        </div>
                
        <div v-if="user.is_master" class="text-center">
          <div class="text-2xl font-bold text-blue-600">
            {{ counts.bookings || 0 }}
          </div>
          <div class="text-sm text-gray-500 mt-1">
            Записей
          </div>
        </div>
                
        <div v-if="user.is_master" class="text-center">
          <div class="text-2xl font-bold text-blue-600">
            {{ counts.reviews || 0 }}
          </div>
          <div class="text-sm text-gray-500 mt-1">
            Отзывов
          </div>
        </div>
                
        <div class="text-center">
          <div class="text-2xl font-bold text-blue-600">
            {{ counts.favorites || 0 }}
          </div>
          <div class="text-sm text-gray-500 mt-1">
            Избранных
          </div>
        </div>
      </div>
    </div>

    <!-- Навигация -->
    <div class="bg-white rounded-lg shadow-sm">
      <nav class="p-6">
        <button
          v-for="tab in tabs"
          :key="tab.key"
          :class="getTabClasses(tab.key)"
          class="w-full flex items-center justify-between px-3 py-2 text-sm rounded-md transition-colors mb-1"
          @click="handleTabClick(tab.key)"
        >
          <span>{{ tab.label }}</span>
          <span v-if="tab.count !== undefined" class="text-xs bg-gray-500 text-gray-500 px-2 py-0.5 rounded-full">
            {{ tab.count }}
          </span>
        </button>
      </nav>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

// Props
const props = defineProps({
    user: {
        type: Object,
        required: true
    },
    counts: {
        type: Object,
        default: () => ({})
    },
    activeTab: {
        type: String,
        default: 'ads'
    }
})

// Events
const emit = defineEmits(['tab-change'])

// Вкладки
const tabs = computed(() => [
    { key: 'ads', label: 'Мои объявления', count: props.counts.ads },
    { key: 'bookings', label: 'Заказы', count: props.counts.bookings },
    { key: 'reviews', label: 'Отзывы', count: props.counts.reviews },
    { key: 'favorites', label: 'Избранное', count: props.counts.favorites },
    { key: 'messages', label: 'Сообщения' },
    { key: 'settings', label: 'Настройки' }
])

// Методы
const handleTabClick = (tab) => {
    emit('tab-change', tab)
}

const getTabClasses = (tabKey) => {
    return props.activeTab === tabKey
        ? 'bg-blue-50 text-blue-700 font-medium'
        : 'text-gray-500 hover:bg-gray-500'
}
</script>


