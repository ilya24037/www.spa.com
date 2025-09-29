<template>
  <Link 
    href="/favorites" 
    class="flex items-center gap-1 text-gray-700 hover:text-gray-900 transition"
    :class="{ 'pointer-events-none opacity-50': loading }"
  >
    <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
      <path d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z"/>
    </svg>
    
    <!-- Счётчик избранного -->
    <span 
      v-if="favoritesCount > 0" 
      class="bg-red-500 text-white text-xs px-1.5 py-0.5 rounded-full min-w-[20px] text-center"
    >
      {{ favoritesCount > 99 ? '99+' : favoritesCount }}
    </span>
    
    <!-- Текст кнопки -->
    <span class="text-sm hidden sm:inline">Избранное</span>
  </Link>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import { useFavoritesStore } from '@/stores/favorites'
import { useAuthStore } from '@/stores/authStore'

// Stores
const favoritesStore = useFavoritesStore()
const authStore = useAuthStore()

// Computed
const favoritesCount = computed(() => {
  // Для неавторизованных показываем 0
  if (!authStore.isAuthenticated) {
    return 0
  }
  return favoritesStore.favorites.length
})

const loading = computed(() => favoritesStore.loading)

// Загружаем избранное при монтировании (если авторизован)
if (authStore.isAuthenticated) {
  favoritesStore.loadFavorites()
}
</script>