<!-- resources/js/Components/Header/UserMenu.vue -->
<template>
  <div class="relative">
    <!-- Кнопка-триггер с аватаркой -->
    <button
      @click="toggleMenu"
      class="flex items-center gap-2 p-1 rounded-lg hover:bg-gray-100 transition-colors group"
      :aria-expanded="isOpen"
      aria-haspopup="menu"
    >
      <!-- Аватарка с буквой -->
      <div class="relative">
        <div 
          class="w-10 h-10 rounded-full flex items-center justify-center text-white font-medium text-lg"
          :style="{ backgroundColor: avatarColor }"
        >
          {{ avatarLetter }}
        </div>
        
        <!-- Индикатор онлайн (опционально) -->
        <div 
          v-if="showOnlineStatus"
          class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 rounded-full border-2 border-white"
        ></div>
      </div>

      <!-- Имя пользователя (на десктопе) -->
      <span class="hidden lg:block text-sm font-medium text-gray-700 group-hover:text-gray-900">
        {{ userName }}
      </span>

      <!-- Стрелка -->
      <svg 
        class="w-4 h-4 text-gray-500 transition-transform duration-200"
        :class="{ 'rotate-180': isOpen }"
        fill="none" 
        stroke="currentColor" 
        viewBox="0 0 24 24"
      >
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
      </svg>
    </button>

    <!-- Выпадающее меню -->
    <Transition
      enter-active-class="transition ease-out duration-100"
      enter-from-class="transform opacity-0 scale-95"
      enter-to-class="transform opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="transform opacity-100 scale-100"
      leave-to-class="transform opacity-0 scale-95"
    >
      <div
        v-if="isOpen"
        class="absolute right-0 mt-2 w-72 bg-white rounded-xl shadow-lg ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 z-50"
        role="menu"
      >
        <!-- Шапка профиля -->
        <div class="p-4">
          <div class="flex items-center gap-3">
            <!-- Аватар в меню -->
            <div 
              class="w-12 h-12 rounded-full flex items-center justify-center text-white font-medium text-xl"
              :style="{ backgroundColor: avatarColor }"
            >
              {{ avatarLetter }}
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-semibold text-gray-900 truncate">
                {{ userName }}
              </p>
              <p v-if="userEmail" class="text-xs text-gray-500">
                {{ userEmail }}
              </p>
            </div>
          </div>
        </div>

        <!-- Основные ссылки -->
        <nav class="py-2" role="none">
          <Link
            v-for="item in menuItems"
            :key="item.href"
            :href="item.href"
            class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors"
            role="menuitem"
            @click="closeMenu"
          >
            <span class="w-5 h-5 text-gray-400">{{ item.emoji }}</span>
            <span class="flex-1">{{ item.label }}</span>
            <span 
              v-if="item.badge"
              class="text-xs px-2 py-0.5 rounded-full"
              :class="item.badgeClass || 'bg-gray-100 text-gray-600'"
            >
              {{ item.badge }}
            </span>
          </Link>
        </nav>

        <!-- Настройки и выход -->
        <div class="py-2">
          <Link
            href="/profile/settings"
            class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors"
            @click="closeMenu"
          >
            <span class="w-5 h-5 text-gray-400">⚙️</span>
            <span>Настройки</span>
          </Link>
          
          <Link
            href="/logout"
            method="post"
            as="button"
            class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors"
            @click="closeMenu"
          >
            <span class="w-5 h-5 text-red-500">🚪</span>
            <span>Выйти</span>
          </Link>
        </div>
      </div>
    </Transition>

    <!-- Оверлей для закрытия -->
    <div
      v-if="isOpen"
      @click="closeMenu"
      class="fixed inset-0 z-40"
      aria-hidden="true"
    ></div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

// Props
const props = defineProps({
  showOnlineStatus: {
    type: Boolean,
    default: false
  },
  showWallet: {
    type: Boolean,
    default: true
  },
  showProfileSwitcher: {
    type: Boolean,
    default: false
  }
})

// Данные из Inertia
const page = usePage()

// Состояние
const isOpen = ref(false)

// Безопасное получение данных пользователя
const user = computed(() => page.props.auth?.user || {})
const userName = computed(() => user.value?.display_name || user.value?.name || 'Пользователь')
const userEmail = computed(() => user.value?.email || '')

// Цвета для аватаров
const avatarColors = [
  '#F87171', '#FB923C', '#FBBF24', '#A3E635', 
  '#4ADE80', '#2DD4BF', '#22D3EE', '#60A5FA',
  '#818CF8', '#A78BFA', '#E879F9', '#F472B6'
]

// Вычисляемые свойства для аватара
const avatarLetter = computed(() => {
  const name = userName.value
  return name ? name.charAt(0).toUpperCase() : '?'
})

const avatarColor = computed(() => {
  const name = userName.value
  if (!name) return avatarColors[0]
  const charCode = name.charCodeAt(0)
  const index = charCode % avatarColors.length
  return avatarColors[index]
})

// Пункты меню с эмодзи вместо иконок
const menuItems = computed(() => [
  { 
    href: '/profile', 
    label: 'Мои объявления', 
    emoji: '📋',
    badge: user.value?.ads_count || null
  },
  { 
    href: '/orders', 
    label: 'Заказы', 
    emoji: '🛍️'
  },
  { 
    href: '/profile/reviews', 
    label: 'Мои отзывы', 
    emoji: '⭐'
  },
  { 
    href: '/favorites', 
    label: 'Избранное', 
    emoji: '❤️',
    badge: user.value?.favorites_count || null
  },
  { 
    href: '/profile/messenger', 
    label: 'Сообщения', 
    emoji: '💬',
    badge: user.value?.unread_messages || null
  },
  { 
    href: '/profile/notifications', 
    label: 'Уведомления', 
    emoji: '🔔',
    badge: user.value?.unread_notifications || null,
    badgeClass: user.value?.unread_notifications ? 'bg-red-100 text-red-600' : null
  }
])

// Методы
const toggleMenu = () => {
  isOpen.value = !isOpen.value
}

const closeMenu = () => {
  isOpen.value = false
}

// Закрытие по Escape
const handleEscape = (e) => {
  if (e.key === 'Escape' && isOpen.value) {
    closeMenu()
  }
}

onMounted(() => {
  document.addEventListener('keydown', handleEscape)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleEscape)
})
</script>