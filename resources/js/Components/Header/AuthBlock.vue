<template>
  <div class="flex items-center gap-2">
    <!-- Авторизованный пользователь -->
    <div v-if="user" class="flex items-center gap-2">
      <!-- Профиль -->
      <Link 
        href="/profile"
        class="flex flex-col items-center px-3 py-2 rounded-lg hover:bg-gray-100 transition-colors group"
      >
        <div class="relative">
          <div v-if="user.avatar" class="w-8 h-8 rounded-full overflow-hidden">
            <img :src="user.avatar" :alt="user.name" class="w-full h-full object-cover">
          </div>
          <div v-else class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-semibold">
            {{ user.name.charAt(0).toUpperCase() }}
          </div>
          <!-- Уведомления -->
          <span 
            v-if="notificationsCount > 0"
            class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 text-white text-xs rounded-full flex items-center justify-center"
          >
            {{ notificationsCount }}
          </span>
        </div>
        <span class="text-xs text-gray-600 mt-1">{{ user.name.split(' ')[0] }}</span>
      </Link>
    </div>

    <!-- Неавторизованный пользователь -->
    <div v-else class="flex items-center gap-2">
      <Link 
        href="/login"
        class="px-4 py-2 text-sm font-medium text-gray-700 hover:text-blue-600 transition-colors"
      >
        Войти
      </Link>
      <Link 
        href="/register"
        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors"
      >
        Регистрация
      </Link>
    </div>

    <!-- Кнопка разместить объявление -->
    <Link 
      href="/masters/create"
      class="ml-2 px-5 py-2.5 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition-colors flex items-center gap-2"
    >
      <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
      </svg>
      <span class="hidden lg:inline">Разместить объявление</span>
      <span class="lg:hidden">Подать</span>
    </Link>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

const page = usePage()
const user = computed(() => page.props.auth?.user)
const notificationsCount = computed(() => page.props.notificationsCount || 0)
</script>