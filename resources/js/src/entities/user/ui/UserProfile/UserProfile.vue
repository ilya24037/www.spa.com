<template>
  <div class="user-profile">
    <!-- Loading состояние -->
    <div v-if="loading" class="animate-pulse">
      <div class="flex items-center space-x-4">
        <div class="w-20 h-20 bg-gray-200 rounded-full"></div>
        <div class="flex-1 space-y-2">
          <div class="h-4 bg-gray-200 rounded w-1/2"></div>
          <div class="h-4 bg-gray-200 rounded w-3/4"></div>
        </div>
      </div>
    </div>
    
    <!-- User data with v-if protection -->
    <div v-else-if="user" class="flex items-center space-x-4">
      <UserAvatar
        :src="user.avatar"
        :name="user.name"
        :size="80"
      />
      <div class="flex-1">
        <h2 class="text-xl font-semibold text-gray-900">{{ user.name || 'Пользователь' }}</h2>
        <p v-if="user.email" class="text-gray-600">{{ user.email }}</p>
        <p v-if="user.phone" class="text-gray-600">{{ user.phone }}</p>
      </div>
    </div>
    
    <!-- Empty state -->
    <div v-else class="text-center py-8 text-gray-500">
      Информация о пользователе недоступна
    </div>
  </div>
</template>

<script setup lang="ts">
import UserAvatar from '../UserAvatar/UserAvatar.vue'

interface User {
  id?: string | number
  name?: string
  email?: string
  phone?: string
  avatar?: string
}

interface Props {
  user?: User | null
  loading?: boolean
}

withDefaults(defineProps<Props>(), {
  user: null,
  loading: false
})
</script>