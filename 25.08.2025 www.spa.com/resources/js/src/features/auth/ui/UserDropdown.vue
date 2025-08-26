<template>
  <div 
    class="relative user-dropdown"
    @mouseenter="handleMouseEnter" 
    @mouseleave="handleMouseLeave"
  >
    <!-- Кнопка-ссылка -->
    <Link
      ref="buttonRef"
      href="/profile"
      class="flex items-center gap-2 p-1 rounded-lg transition-colors hover:bg-gray-100 group"
    >
      <!-- Аватар -->
      <div class="relative">
        <div
          class="w-10 h-10 rounded-full flex items-center justify-center text-white font-medium text-lg"
          :style="{ backgroundColor: avatarColor }"
        >
          {{ avatarLetter }}
        </div>
      </div>
      
      <!-- Имя пользователя (скрыто на мобильных) -->
      <span class="hidden lg:block text-sm font-medium text-gray-700 group-hover:text-gray-900">
        {{ userName }}
      </span>
      
      <!-- Стрелка -->
      <svg
        class="w-4 h-4 text-gray-500 transition-transform duration-200"
        :class="{ 'rotate-180': menuVisible }"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
      >
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
      </svg>
    </Link>

    <!-- Выпадающее меню -->
    <Teleport to="body">
      <Transition name="dropdown">
        <div
          v-show="menuVisible"
          ref="menuRef"
          :style="menuPosition"
          @mouseenter="handleMouseEnter" 
          @mouseleave="handleMouseLeave"
          class="dropdown-menu"
        >
          <!-- Шапка профиля -->
          <div class="dropdown-header">
            <Link 
              href="/profile"
              class="profile-link"
            >
              <div
                class="avatar-large"
                :style="{ backgroundColor: avatarColor }"
              >
                {{ avatarLetter }}
              </div>
              <div class="user-info">
                <p class="user-name">{{ fullName }}</p>
                <p v-if="userEmail" class="user-email">{{ userEmail }}</p>
                <p class="profile-link-text">Личный кабинет →</p>
              </div>
            </Link>
          </div>

          <!-- Основные пункты меню -->
          <div class="dropdown-section">
            <Link 
              href="/additem" 
              class="dropdown-item"
            >
              <span class="dropdown-icon">📝</span>
              <span class="dropdown-text">Создать анкету мастера</span>
            </Link>
            
            <Link 
              href="/bookings" 
              class="dropdown-item"
            >
              <span class="dropdown-icon">📅</span>
              <span class="dropdown-text">Мои бронирования</span>
              <span v-if="bookingsCount > 0" class="badge">{{ bookingsCount }}</span>
            </Link>
            
            <Link 
              href="/favorites" 
              class="dropdown-item"
            >
              <span class="dropdown-icon">❤️</span>
              <span class="dropdown-text">Избранное</span>
              <span v-if="favoritesCount > 0" class="badge">{{ favoritesCount }}</span>
            </Link>
            
            <Link 
              href="/compare" 
              class="dropdown-item"
            >
              <span class="dropdown-icon">📊</span>
              <span class="dropdown-text">Сравнение</span>
              <span v-if="compareCount > 0" class="badge">{{ compareCount }}</span>
            </Link>
          </div>

          <div class="dropdown-divider" />

          <!-- Настройки -->
          <div class="dropdown-section">
            <Link 
              href="/profile/edit" 
              class="dropdown-item"
            >
              <span class="dropdown-icon">⚙️</span>
              <span class="dropdown-text">Настройки профиля</span>
            </Link>
            
            <Link 
              href="/notifications" 
              class="dropdown-item"
            >
              <span class="dropdown-icon">🔔</span>
              <span class="dropdown-text">Уведомления</span>
              <span v-if="unreadNotifications > 0" class="badge badge-red">{{ unreadNotifications }}</span>
            </Link>
          </div>

          <div class="dropdown-divider" />

          <!-- Выход -->
          <div class="dropdown-section">
            <button
              @click="handleLogout"
              class="dropdown-item dropdown-item-danger"
            >
              <span class="dropdown-icon">🚪</span>
              <span class="dropdown-text">Выйти</span>
            </button>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onBeforeUnmount, watch, nextTick } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { useAuthStore } from '@/stores/authStore'
import { useFavoritesStore } from '@/stores/favorites'
import { useCompareStore } from '@/src/features/compare/model/compareStore'

// Stores
const authStore = useAuthStore()
const favoritesStore = useFavoritesStore()
const compareStore = useCompareStore()

// User data
const user = computed(() => authStore.user)
const fullName = computed(() => user.value?.name || 'Пользователь')  // ✅ Используем name вместо display_name
const userName = computed(() => fullName.value.split(' ')[0])
const userEmail = computed(() => user.value?.email || '')

// Счётчики
const bookingsCount = computed(() => 0) // TODO: добавить bookings store
const favoritesCount = computed(() => favoritesStore.favorites.length)
const compareCount = computed(() => compareStore.items.length)
const unreadNotifications = computed(() => 0) // TODO: добавить notifications store

// Avatar
const colors = [
  '#F87171', '#FB923C', '#FBBF24', '#A3E635', 
  '#4ADE80', '#2DD4BF', '#22D3EE', '#60A5FA', 
  '#818CF8', '#A78BFA', '#E879F9', '#F472B6'
]
const avatarLetter = computed(() => userName.value.charAt(0).toUpperCase() || '?')
const avatarColor = computed(() => {
  const charCode = userName.value.charCodeAt(0) || 0
  return colors[charCode % colors.length]
})

// Refs
const buttonRef = ref<any>(null)
const menuRef = ref<HTMLElement | null>(null)

// Menu state
const menuVisible = ref(false)
const menuPosition = ref({ top: '0px', left: '0px' })
let openTimer: number | null = null  // ✅ Используем number вместо NodeJS.Timeout
let closeTimer: number | null = null  // ✅ Используем number вместо NodeJS.Timeout

// Calculate menu position
const updateMenuPosition = async () => {
  await nextTick()
  
  if (!buttonRef.value || !menuRef.value) return
  
  const button = buttonRef.value.$el || buttonRef.value
  const rect = button.getBoundingClientRect()
  const menuWidth = 288 // 18rem = 288px
  
  let top = rect.bottom + 4
  let left = rect.right - menuWidth
  
  // Check screen boundaries
  if (left < 16) {
    left = 16
  }
  
  const menuHeight = menuRef.value?.offsetHeight || 400
  if (top + menuHeight > window.innerHeight - 16) {
    top = rect.top - menuHeight - 4
  }
  
  menuPosition.value = {
    top: `${top}px`,
    left: `${left}px`
  }
}

// Mouse handlers with delay
const handleMouseEnter = () => {
  if (closeTimer) {
    clearTimeout(closeTimer)
    closeTimer = null
  }
  
  if (!menuVisible.value && !openTimer) {
    openTimer = setTimeout(() => {
      menuVisible.value = true
      openTimer = null
    }, 100)
  }
}

const handleMouseLeave = () => {
  if (openTimer) {
    clearTimeout(openTimer)
    openTimer = null
  }
  
  if (menuVisible.value && !closeTimer) {
    closeTimer = setTimeout(() => {
      menuVisible.value = false
      closeTimer = null
    }, 300)
  }
}

// Logout
const handleLogout = () => {
  router.post('/logout', {}, {
    onSuccess: () => {
      // ✅ Используем правильный метод logout из store
      authStore.logout()
    }
  })
}

// Update position on menu visibility change
watch(menuVisible, (visible) => {
  if (visible) {
    updateMenuPosition()
  }
})

// Update position on scroll/resize
const handleWindowChange = () => {
  if (menuVisible.value) {
    updateMenuPosition()
  }
}

// Event listeners
if (typeof window !== 'undefined') {
  window.addEventListener('scroll', handleWindowChange, true)
  window.addEventListener('resize', handleWindowChange)
}

// Cleanup
onBeforeUnmount(() => {
  if (openTimer) clearTimeout(openTimer)
  if (closeTimer) clearTimeout(closeTimer)
  if (typeof window !== 'undefined') {
    window.removeEventListener('scroll', handleWindowChange, true)
    window.removeEventListener('resize', handleWindowChange)
  }
})
</script>

<style scoped>
/* Анимация выпадающего меню */
.dropdown-enter-active,
.dropdown-leave-active {
  transition: all 0.2s ease;
}

.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

/* Основной контейнер */
.user-dropdown {
  @apply relative;
}

/* Выпадающее меню */
.dropdown-menu {
  @apply fixed w-72 bg-white rounded-xl shadow-2xl border border-gray-100 overflow-hidden;
  z-index: 9999;
}

/* Шапка профиля */
.dropdown-header {
  @apply p-4 border-b border-gray-100;
}

.profile-link {
  @apply flex items-center gap-3 -m-2 p-2 rounded-lg transition-colors hover:bg-gray-50;
}

.avatar-large {
  @apply w-12 h-12 rounded-full flex items-center justify-center text-white font-semibold text-xl;
}

.user-info {
  @apply flex-1 min-w-0;
}

.user-name {
  @apply font-semibold text-gray-900 truncate;
}

.user-email {
  @apply text-sm text-gray-500;
}

.profile-link-text {
  @apply text-sm text-blue-600 hover:text-blue-700;
}

/* Секции меню */
.dropdown-section {
  @apply py-2;
}

.dropdown-divider {
  @apply border-t border-gray-100;
}

/* Пункты меню */
.dropdown-item {
  @apply flex items-center gap-3 px-4 py-2.5 text-gray-700 hover:bg-gray-50 transition-colors w-full text-left;
}

.dropdown-item-danger {
  @apply text-red-600 hover:bg-red-50;
}

.dropdown-icon {
  @apply text-gray-400 flex-shrink-0;
}

.dropdown-text {
  @apply flex-1;
}

/* Бейджи */
.badge {
  @apply bg-gray-200 text-gray-700 text-xs px-2 py-0.5 rounded-full;
}

.badge-red {
  @apply bg-red-500 text-white;
}
</style>