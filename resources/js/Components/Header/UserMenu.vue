<!-- resources/js/Components/Header/UserMenu.vue -->
<template>
  <div class="relative">
    <!-- Кнопка аватарки -->
    <button
      @click="toggleMenu"
      class="flex items-center gap-2 p-1 rounded-lg hover:bg-gray-100 transition-colors group"
    >
      <div class="relative">
        <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-medium text-lg" 
             :style="{ backgroundColor: avatarColor }">
          {{ avatarLetter }}
        </div>
      </div>
      <span class="hidden lg:block text-sm font-medium text-gray-700 group-hover:text-gray-900">
        {{ userName }}
      </span>
      <svg class="w-4 h-4 text-gray-500 transition-transform duration-200" 
           :class="{ 'rotate-180': isOpen }" 
           viewBox="0 0 24 24" fill="none" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
      </svg>
    </button>

    <!-- Выпадающее меню БЕЗ teleport (как на Avito) -->
    <div 
      v-if="isOpen" 
      class="absolute right-0 top-full mt-2 py-2 w-72 bg-white rounded-xl shadow-xl border border-gray-100"
      style="z-index: 999999 !important"
    >
      <!-- Рейтинг пользователя (если нужен) -->
      <div class="px-4 pb-3 border-b border-gray-100">
        <div class="flex items-center gap-3">
          <div class="text-2xl font-bold">{{ user.rating || '5.0' }}</div>
          <div class="flex items-center gap-1">
            <span class="text-yellow-400">⭐</span>
            <span class="text-yellow-400">⭐</span>
            <span class="text-yellow-400">⭐</span>
            <span class="text-yellow-400">⭐</span>
            <span class="text-yellow-400">⭐</span>
          </div>
          <span class="text-sm text-gray-600">{{ user.reviews_count || 0 }} отзывов</span>
        </div>
      </div>

      <!-- Блок 1: Основные действия -->
      <div class="py-2 border-b border-gray-100">
        <Link href="/profile" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
          Мои объявления
        </Link>
        <Link href="/orders" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
          Заказы
        </Link>
        <Link href="/profile/reviews" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
          Мои отзывы
        </Link>
        <Link href="/favorites" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
          Избранное
        </Link>
      </div>

      <!-- Блок 2: Сообщения -->
      <div class="py-2 border-b border-gray-100">
        <Link href="/profile/messenger" class="flex items-center justify-between px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
          <span>Сообщения</span>
          <span v-if="user.unread_messages" class="text-xs bg-red-500 text-white px-2 py-0.5 rounded-full">
            {{ user.unread_messages }}
          </span>
        </Link>
        <Link href="/profile/notifications" class="flex items-center justify-between px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
          <span>Уведомления</span>
          <span v-if="user.unread_notifications" class="text-xs text-gray-500">
            {{ user.unread_notifications }}
          </span>
        </Link>
      </div>

      <!-- Блок 3: Кошелек (если нужен) -->
      <div class="py-2 border-b border-gray-100">
        <Link href="/account" class="flex items-center justify-between px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
          <span>Кошелёк</span>
          <span class="font-medium">{{ user.balance || 0 }} ₽</span>
        </Link>
        <Link href="/paid-services" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
          Платные услуги
        </Link>
      </div>

      <!-- Блок 4: Настройки -->
      <div class="py-2 border-b border-gray-100">
        <Link href="/profile/settings" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
          Настройки
        </Link>
        <Link href="/profile/safety" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
          Защита профиля
        </Link>
      </div>

      <!-- Блок 5: Выход -->
      <div class="py-2">
        <Link 
          href="/logout" 
          method="post" 
          as="button"
          class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50"
        >
          Выйти
        </Link>
      </div>
    </div>

    <!-- Клик вне меню для закрытия -->
    <div 
      v-if="isOpen" 
      @click="closeMenu" 
      class="fixed inset-0" 
      style="z-index: 999998 !important"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

// Получаем данные пользователя
const page = usePage()
const user = computed(() => page.props.auth?.user || {})
const userName = computed(() => user.value.display_name || user.value.name || 'Пользователь')

// Цвет аватарки
const colors = ['#F87171','#FB923C','#FBBF24','#A3E635','#4ADE80','#2DD4BF','#22D3EE','#60A5FA','#818CF8','#A78BFA','#E879F9','#F472B6']
const avatarLetter = computed(() => userName.value.charAt(0).toUpperCase() || '?')
const avatarColor = computed(() => colors[(userName.value.charCodeAt(0)||0)%colors.length])

// Состояние меню
const isOpen = ref(false)
const toggleMenu = () => { isOpen.value = !isOpen.value }
const closeMenu = () => { isOpen.value = false }

// Закрытие по Escape
const onEsc = (e) => {
  if (e.key === 'Escape' && isOpen.value) {
    closeMenu()
  }
}

// Закрытие при клике на ссылку
const onLinkClick = () => {
  closeMenu()
}

onMounted(() => {
  document.addEventListener('keydown', onEsc)
  // Закрываем меню при клике на любую ссылку внутри
  document.addEventListener('click', (e) => {
    if (e.target.closest('a') && e.target.closest('.relative')) {
      closeMenu()
    }
  })
})

onUnmounted(() => {
  document.removeEventListener('keydown', onEsc)
})
</script>

<style scoped>
/* Убедимся, что меню всегда сверху */
[style*="z-index: 999999"] {
  z-index: 999999 !important;
}
</style>