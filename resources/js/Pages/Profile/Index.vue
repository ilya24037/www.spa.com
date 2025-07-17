<template>
  <AppLayout>
    <Head title="Профиль" />
    
    <div class="py-6 lg:py-8">
      <div class="max-w-7xl mx-auto">
        <div class="flex gap-6">
          <!-- Боковая панель -->
          <div class="w-64 flex-shrink-0">
            <div class="bg-white rounded-lg shadow-sm p-6">
              <!-- Информация о пользователе -->
              <div class="text-center mb-6">
                <div 
                  class="w-20 h-20 rounded-full mx-auto flex items-center justify-center text-white font-bold text-2xl mb-4"
                  :style="{ backgroundColor: avatarColor }"
                >
                  {{ userInitial }}
                </div>
                <h2 class="text-xl font-semibold text-gray-900">{{ user.name }}</h2>
                <div class="flex items-center justify-center mt-2">
                  <div class="flex">
                    <svg v-for="i in 5" :key="i" class="w-4 h-4" :class="i <= userStats.rating ? 'text-yellow-400' : 'text-gray-300'" fill="currentColor" viewBox="0 0 20 20">
                      <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                    </svg>
                  </div>
                  <span class="ml-2 text-sm text-gray-600">{{ userStats.reviewsCount }} отзывов</span>
                </div>
              </div>

              <!-- Навигация -->
              <nav class="space-y-1">
                <Link 
                  href="/profile"
                  class="flex items-center px-3 py-2 text-sm font-medium text-blue-600 bg-blue-50 rounded-lg"
                >
                  Профиль
                </Link>
                
                <Link 
                  href="/profile/items/active/all"
                  class="flex items-center justify-between px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-lg"
                >
                  <span>Мои объявления</span>
                  <span v-if="userStats.totalAds > 0" class="bg-gray-100 text-gray-900 px-2 py-1 rounded-full text-xs">
                    {{ userStats.totalAds }}
                  </span>
                </Link>
                
                <Link 
                  href="/bookings"
                  class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-lg"
                >
                  Бронирования
                </Link>
                
                <Link 
                  href="/favorites"
                  class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-lg"
                >
                  Избранное
                </Link>
                
                <Link 
                  href="/profile/edit"
                  class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded-lg"
                >
                  Настройки профиля
                </Link>
              </nav>
            </div>
          </div>

          <!-- Основной контент -->
          <div class="flex-1">
            <div class="bg-white rounded-lg shadow-sm p-6">
              <h1 class="text-2xl font-bold text-gray-900 mb-6">Профиль</h1>
              
              <!-- Статистика объявлений -->
              <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-8">
                <div class="bg-blue-50 p-4 rounded-lg">
                  <div class="text-2xl font-bold text-blue-600">{{ counts.active }}</div>
                  <div class="text-sm text-blue-800">Активные</div>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg">
                  <div class="text-2xl font-bold text-gray-600">{{ counts.draft }}</div>
                  <div class="text-sm text-gray-800">Черновики</div>
                </div>
                <div class="bg-orange-50 p-4 rounded-lg">
                  <div class="text-2xl font-bold text-orange-600">{{ counts.inactive }}</div>
                  <div class="text-sm text-orange-800">Неактивные</div>
                </div>
                <div class="bg-red-50 p-4 rounded-lg">
                  <div class="text-2xl font-bold text-red-600">{{ counts.old }}</div>
                  <div class="text-sm text-red-800">Старые</div>
                </div>
                <div class="bg-purple-50 p-4 rounded-lg">
                  <div class="text-2xl font-bold text-purple-600">{{ counts.archived }}</div>
                  <div class="text-sm text-purple-800">Архив</div>
                </div>
              </div>

              <!-- Промо блок -->
              <div class="bg-gradient-to-r from-green-400 to-blue-500 rounded-lg p-6 text-white mb-6">
                <div class="flex items-center justify-between">
                  <div>
                    <h3 class="text-lg font-semibold mb-2">Скидка на продвижение</h3>
                    <p class="text-green-100">Действует ограниченное время</p>
                  </div>
                  <div class="text-right">
                    <div class="text-3xl font-bold bg-white/20 rounded-lg px-4 py-2">70%</div>
                  </div>
                </div>
              </div>

              <!-- Быстрые действия -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <Link 
                  href="/additem"
                  class="flex items-center p-4 border-2 border-dashed border-gray-300 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-colors group"
                >
                  <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4 group-hover:bg-blue-200">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                  </div>
                  <div>
                    <h3 class="font-medium text-gray-900 group-hover:text-blue-900">Разместить объявление</h3>
                    <p class="text-sm text-gray-500">Создайте новое объявление</p>
                  </div>
                </Link>

                <Link 
                  href="/profile/items/active/all"
                  class="flex items-center p-4 border border-gray-200 rounded-lg hover:border-gray-300 hover:bg-gray-50 transition-colors group"
                >
                  <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center mr-4 group-hover:bg-gray-200">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                  </div>
                  <div>
                    <h3 class="font-medium text-gray-900 group-hover:text-gray-700">Управлять объявлениями</h3>
                    <p class="text-sm text-gray-500">Просмотр и редактирование</p>
                  </div>
                </Link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

// Props
const props = defineProps({
  user: {
    type: Object,
    required: true
  },
  counts: {
    type: Object,
    required: true
  },
  userStats: {
    type: Object,
    required: true
  }
})

// Computed
const userInitial = computed(() => {
  return props.user.name?.charAt(0).toUpperCase() || 'П'
})

const avatarColor = computed(() => {
  const colors = ['#e91e63', '#9c27b0', '#673ab7', '#3f51b5', '#2196f3', '#00bcd4', '#009688', '#4caf50', '#ff9800', '#ff5722']
  const charCode = props.user.name?.charCodeAt(0) || 85
  return colors[charCode % colors.length]
})
</script> 