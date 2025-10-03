<!-- ProfileSidebar - левая панель профиля мастера -->
<template>
  <div class="bg-white rounded-lg shadow-sm p-6 sticky top-4">
    <!-- Аватар -->
    <div class="flex flex-col items-center mb-6">
      <img
        :src="master.avatar || '/placeholder-avatar.jpg'"
        :alt="master.name"
        class="w-32 h-32 rounded-full object-cover mb-4"
      >
      <h1 class="text-xl font-bold text-gray-900 text-center mb-2">
        {{ master.name || 'Мастер' }}
      </h1>

      <!-- Первый отзыв -->
      <button
        v-if="!master.reviews_count"
        class="text-sm text-blue-600 hover:text-blue-700"
      >
        Оставить первый отзыв
      </button>
    </div>

    <!-- Статистика -->
    <div class="mb-6 pb-6 border-b border-gray-200">
      <div class="text-sm text-gray-600 mb-2">
        <span class="font-medium">{{ master.followers_count || 0 }}</span> подписчиков.
        <span class="font-medium">{{ master.following_count || 0 }}</span> подписок
      </div>
      <div class="text-sm text-gray-500">
        Зарегистрирован {{ formatRegistrationDate(master.created_at) }}
      </div>
    </div>

    <!-- Верификация -->
    <div class="mb-6 pb-6 border-b border-gray-200 space-y-3">
      <div class="flex items-center gap-2 text-sm">
        <span v-if="master.is_verified" class="text-green-600">✓</span>
        <span v-else class="text-gray-400">○</span>
        <span :class="master.is_verified ? 'text-gray-900' : 'text-gray-500'">
          Документы проверены
        </span>
      </div>
      <div class="flex items-center gap-2 text-sm">
        <span v-if="master.phone_verified" class="text-green-600">✓</span>
        <span v-else class="text-gray-400">○</span>
        <span :class="master.phone_verified ? 'text-gray-900' : 'text-gray-500'">
          Телефон подтверждён
        </span>
      </div>
    </div>

    <!-- Действия -->
    <div class="space-y-3">
      <button
        @click="$emit('show-phone')"
        class="w-full px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium"
      >
        Показать номер
      </button>
      <button
        @click="$emit('write-message')"
        class="w-full px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium"
      >
        Написать
      </button>
      <button
        @click="$emit('subscribe')"
        class="w-full px-4 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium"
      >
        {{ master.is_subscribed ? 'Отписаться' : 'Подписаться' }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Master {
  id?: string | number
  name?: string
  avatar?: string
  reviews_count?: number
  followers_count?: number
  following_count?: number
  created_at?: string
  is_verified?: boolean
  phone_verified?: boolean
  is_subscribed?: boolean
}

interface Props {
  master: Master
}

defineProps<Props>()

defineEmits<{
  'show-phone': []
  'write-message': []
  'subscribe': []
}>()

const formatRegistrationDate = (dateString: string | undefined) => {
  if (!dateString) return 'недавно'

  const date = new Date(dateString)
  const now = new Date()
  const diffTime = Math.abs(now.getTime() - date.getTime())
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))

  if (diffDays === 0) return 'сегодня'
  if (diffDays === 1) return 'вчера'
  if (diffDays < 30) return `${diffDays} дней назад`
  if (diffDays < 365) return `${Math.floor(diffDays / 30)} месяцев назад`

  return date.toLocaleDateString('ru-RU', {
    year: 'numeric',
    month: 'long'
  })
}
</script>
