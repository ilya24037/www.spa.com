<!-- Р’С‹РїР°РґР°СЋС‰РµРµ РјРµРЅСЋ РїРѕР»СЊР·РѕРІР°С‚РµР»СЏ -->
<template>
  <div class="relative" ref="dropdownRef">
    <!-- РљРЅРѕРїРєР° РїРѕР»СЊР·РѕРІР°С‚РµР»СЏ -->
    <button
      @click="toggleDropdown"
      :class="userButtonClasses"
      :aria-expanded="isOpen"
      :aria-haspopup="true"
      :aria-label="userButtonAriaLabel"
    >
      <!-- РђРІР°С‚Р°СЂ -->
      <div class="relative">
        <img
          v-if="user.avatar"
          :src="user.avatar"
          :alt="user.name"
          class="w-8 h-8 rounded-full object-cover"
        >
        <div
          v-else
          class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-medium text-sm"
        >
          {{ userInitials }}
        </div>
        
        <!-- РРЅРґРёРєР°С‚РѕСЂ РѕРЅР»Р°Р№РЅ СЃС‚Р°С‚СѓСЃР° -->
        <div
          v-if="showOnlineStatus"
          :class="onlineStatusClasses"
          :title="onlineStatusTitle"
        ></div>
      </div>

      <!-- РРјСЏ РїРѕР»СЊР·РѕРІР°С‚РµР»СЏ (СЃРєСЂС‹С‚Рѕ РЅР° РјРѕР±РёР»СЊРЅС‹С…) -->
      <span class="hidden lg:block ml-2 text-sm font-medium text-gray-700 max-w-32 truncate">
        {{ user.name }}
      </span>

      <!-- РЎС‚СЂРµР»РєР° РІРЅРёР· -->
      <svg
        class="hidden lg:block ml-1 w-4 h-4 text-gray-500 transition-transform duration-200"
        :class="{ 'rotate-180': isOpen }"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
        aria-hidden="true"
      >
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
      </svg>
    </button>

    <!-- Р’С‹РїР°РґР°СЋС‰РµРµ РјРµРЅСЋ -->
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
        :class="dropdownClasses"
        role="menu"
        :aria-labelledby="dropdownId"
      >
        <!-- Р—Р°РіРѕР»РѕРІРѕРє РјРµРЅСЋ СЃ РёРЅС„РѕСЂРјР°С†РёРµР№ Рѕ РїРѕР»СЊР·РѕРІР°С‚РµР»Рµ -->
        <div class="px-4 py-3 border-b border-gray-100">
          <div class="flex items-center space-x-3">
            <img
              v-if="user.avatar"
              :src="user.avatar"
              :alt="user.name"
              class="w-10 h-10 rounded-full object-cover"
            >
            <div
              v-else
              class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-medium"
            >
              {{ userInitials }}
            </div>
            
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900 truncate">
                {{ user.name }}
              </p>
              <p class="text-sm text-gray-500 truncate">
                {{ user.email }}
              </p>
              <p v-if="user.role !== 'user'" class="text-xs text-blue-600 font-medium">
                {{ roleDisplayName }}
              </p>
            </div>
          </div>
        </div>

        <!-- РћСЃРЅРѕРІРЅС‹Рµ РїСѓРЅРєС‚С‹ РјРµРЅСЋ -->
        <div class="py-1">
          <Link
            v-for="item in menuItems"
            :key="item.key"
            :href="item.href"
            :class="menuItemClasses"
            role="menuitem"
            @click="closeDropdown"
          >
            <component
              :is="item.icon"
              class="w-5 h-5 text-gray-400 group-hover:text-gray-500"
              aria-hidden="true"
            />
            <span class="ml-3">{{ item.label }}</span>
            <span
              v-if="item.badge"
              class="ml-auto inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800"
            >
              {{ item.badge }}
            </span>
          </Link>
        </div>

        <!-- Р‘Р°Р»Р°РЅСЃ -->
        <div v-if="showBalance" class="px-4 py-3 border-t border-gray-100">
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-600">Р‘Р°Р»Р°РЅСЃ</span>
            <span class="text-sm font-medium text-gray-900">
              {{ formatBalance(user.balance) }} в‚Ѕ
            </span>
          </div>
          <Link
            href="/wallet"
            class="mt-2 w-full bg-green-50 hover:bg-green-100 text-green-700 text-sm px-3 py-1 rounded transition-colors flex items-center justify-center"
            @click="closeDropdown"
          >
            РџРѕРїРѕР»РЅРёС‚СЊ
          </Link>
        </div>

        <!-- РџРµСЂРµРєР»СЋС‡Р°С‚РµР»СЊ РїСЂРѕС„РёР»СЏ (РґР»СЏ РјР°СЃС‚РµСЂРѕРІ) -->
        <div v-if="showProfileSwitcher && user.role === 'master'" class="px-4 py-3 border-t border-gray-100">
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-600">Р РµР¶РёРј РїСЂРѕСЃРјРѕС‚СЂР°</span>
            <button
              @click="toggleProfileMode"
              :class="profileSwitchClasses"
              :aria-label="profileSwitchAriaLabel"
            >
              <span class="sr-only">{{ profileSwitchAriaLabel }}</span>
              <span :class="profileSwitchIndicatorClasses"></span>
            </button>
          </div>
          <p class="text-xs text-gray-500 mt-1">
            {{ profileModeDescription }}
          </p>
        </div>

        <!-- РљРЅРѕРїРєР° РІС‹С…РѕРґР° -->
        <div class="border-t border-gray-100 py-1">
          <button
            @click="handleLogout"
            :class="logoutButtonClasses"
            role="menuitem"
            :disabled="isLoggingOut"
          >
            <svg
              v-if="!isLoggingOut"
              class="w-5 h-5 text-gray-400 group-hover:text-red-500"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
              aria-hidden="true"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
            <div v-else class="w-5 h-5 border-2 border-gray-300 border-t-transparent rounded-full animate-spin"></div>
            <span class="ml-3">Р’С‹Р№С‚Рё</span>
          </button>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Link } from '@inertiajs/vue3'
import {
  UserIcon,
  Cog6ToothIcon,
  HeartIcon,
  ClipboardDocumentListIcon,
  ChatBubbleLeftRightIcon,
  StarIcon,
  BellIcon
} from '@heroicons/vue/24/outline'

// TypeScript РёРЅС‚РµСЂС„РµР№СЃС‹
interface User {
  id: number
  name: string
  email: string
  avatar?: string
  balance: number
  role: 'user' | 'master' | 'admin'
  isOnline?: boolean
}

interface MenuItem {
  key: string
  label: string
  href: string
  icon: any
  badge?: number | string
}

interface Props {
  user: User
  showOnlineStatus?: boolean
  showBalance?: boolean
  showProfileSwitcher?: boolean
  isLoggingOut?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  showOnlineStatus: true,
  showBalance: true,
  showProfileSwitcher: true,
  isLoggingOut: false
})

// TypeScript С‚РёРїРёР·Р°С†РёСЏ emits
const emit = defineEmits<{
  'logout': []
  'profile-click': []
  'profile-mode-changed': [mode: 'user' | 'master']
}>()

// Refs
const dropdownRef = ref<HTMLElement>()
const isOpen = ref(false)
const profileMode = ref<'user' | 'master'>('master')

// Computed
const dropdownId = computed(() => `user-dropdown-${props.user.id}`)

const userInitials = computed(() => {
  const names = props.user.name.split(' ')
  return names.map(name => name.charAt(0)).join('').toUpperCase().slice(0, 2)
})

const roleDisplayName = computed(() => {
  const roleNames = {
    master: 'РњР°СЃС‚РµСЂ',
    admin: 'РђРґРјРёРЅРёСЃС‚СЂР°С‚РѕСЂ',
    user: 'РџРѕР»СЊР·РѕРІР°С‚РµР»СЊ'
  }
  return roleNames[props.user.role]
})

const menuItems = computed<MenuItem[]>(() => {
  const baseItems: MenuItem[] = [
    {
      key: 'profile',
      label: 'РњРѕР№ РїСЂРѕС„РёР»СЊ',
      href: '/profile',
      icon: UserIcon
    },
    {
      key: 'bookings',
      label: 'РњРѕРё Р±СЂРѕРЅРёСЂРѕРІР°РЅРёСЏ',
      href: '/profile/bookings',
      icon: ClipboardDocumentListIcon
    },
    {
      key: 'favorites',
      label: 'РР·Р±СЂР°РЅРЅРѕРµ',
      href: '/profile/favorites',
      icon: HeartIcon
    },
    {
      key: 'messages',
      label: 'РЎРѕРѕР±С‰РµРЅРёСЏ',
      href: '/profile/messages',
      icon: ChatBubbleLeftRightIcon,
      badge: 3 // TODO: РїРѕР»СѓС‡Р°С‚СЊ РёР· store
    },
    {
      key: 'notifications',
      label: 'РЈРІРµРґРѕРјР»РµРЅРёСЏ',
      href: '/profile/notifications',
      icon: BellIcon
    },
    {
      key: 'settings',
      label: 'РќР°СЃС‚СЂРѕР№РєРё',
      href: '/profile/settings',
      icon: Cog6ToothIcon
    }
  ]

  // Р”РѕР±Р°РІРёС‚СЊ РїСѓРЅРєС‚С‹ РґР»СЏ РјР°СЃС‚РµСЂРѕРІ
  if (props.user.role === 'master') {
    baseItems.splice(2, 0, {
      key: 'master-dashboard',
      label: 'РџР°РЅРµР»СЊ РјР°СЃС‚РµСЂР°',
      href: '/master/dashboard',
      icon: StarIcon
    })
  }

  return baseItems
})

const userButtonClasses = computed(() => [
  'flex items-center p-1 rounded-lg transition-colors duration-200',
  'hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2',
  'max-w-xs'
])

const dropdownClasses = computed(() => [
  'absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 z-50',
  'focus:outline-none'
])

const menuItemClasses = computed(() => [
  'flex items-center w-full px-4 py-2 text-sm text-gray-700 transition-colors duration-150',
  'hover:bg-gray-100 group'
])

const logoutButtonClasses = computed(() => [
  'flex items-center w-full px-4 py-2 text-sm text-gray-700 transition-colors duration-150',
  'hover:bg-red-50 hover:text-red-600 group disabled:opacity-50 disabled:cursor-not-allowed'
])

const onlineStatusClasses = computed(() => [
  'absolute -bottom-0 -right-0 block h-2.5 w-2.5 rounded-full ring-2 ring-white',
  {
    'bg-green-400': props.user.isOnline,
    'bg-gray-300': !props.user.isOnline
  }
])

const onlineStatusTitle = computed(() =>
  props.user.isOnline ? 'Р’ СЃРµС‚Рё' : 'РќРµ РІ СЃРµС‚Рё'
)

const profileSwitchClasses = computed(() => [
  'relative inline-flex h-6 w-11 items-center rounded-full transition-colors',
  'focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2',
  {
    'bg-blue-600': profileMode.value === 'master',
    'bg-gray-200': profileMode.value === 'user'
  }
])

const profileSwitchIndicatorClasses = computed(() => [
  'inline-block h-4 w-4 transform rounded-full bg-white transition duration-200',
  {
    'translate-x-6': profileMode.value === 'master',
    'translate-x-1': profileMode.value === 'user'
  }
])

const profileSwitchAriaLabel = computed(() =>
  `РџРµСЂРµРєР»СЋС‡РёС‚СЊ СЂРµР¶РёРј РЅР° ${profileMode.value === 'master' ? 'РєР»РёРµРЅС‚Р°' : 'РјР°СЃС‚РµСЂР°'}`
)

const profileModeDescription = computed(() =>
  profileMode.value === 'master' 
    ? 'Р’РёРґРёС‚Рµ СЃР°Р№С‚ РєР°Рє РјР°СЃС‚РµСЂ' 
    : 'Р’РёРґРёС‚Рµ СЃР°Р№С‚ РєР°Рє РєР»РёРµРЅС‚'
)

const userButtonAriaLabel = computed(() =>
  `РњРµРЅСЋ РїРѕР»СЊР·РѕРІР°С‚РµР»СЏ ${props.user.name}`
)

// Methods
const toggleDropdown = (): void => {
  isOpen.value = !isOpen.value
}

const closeDropdown = (): void => {
  isOpen.value = false
}

const handleLogout = (): void => {
  closeDropdown()
  emit('logout')
}

const toggleProfileMode = (): void => {
  profileMode.value = profileMode.value === 'master' ? 'user' : 'master'
  emit('profile-mode-changed', profileMode.value)
}

const formatBalance = (balance: number): string => {
  return new Intl.NumberFormat('ru-RU').format(balance)
}

// Р—Р°РєСЂС‹С‚РёРµ РїСЂРё РєР»РёРєРµ РІРЅРµ РєРѕРјРїРѕРЅРµРЅС‚Р°
const handleClickOutside = (event: MouseEvent): void => {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target as Node)) {
    closeDropdown()
  }
}

// Р—Р°РєСЂС‹С‚РёРµ РїСЂРё РЅР°Р¶Р°С‚РёРё Escape
const handleEscapeKey = (event: KeyboardEvent): void => {
  if (event.key === 'Escape' && isOpen.value) {
    closeDropdown()
  }
}

// Lifecycle
onMounted(() => {
  document.addEventListener('click', handleClickOutside)
  document.addEventListener('keydown', handleEscapeKey)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
  document.removeEventListener('keydown', handleEscapeKey)
})
</script>

<style scoped>
/* РђРЅРёРјР°С†РёРё РґР»СЏ РїРµСЂРµРєР»СЋС‡Р°С‚РµР»СЏ */
.toggle-transition {
  transition: transform 0.2s ease;
}

/* РЎРѕСЃС‚РѕСЏРЅРёСЏ Р·Р°РіСЂСѓР·РєРё */
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* Accessibility improvements */
@media (prefers-reduced-motion: reduce) {
  * {
    transition: none !important;
  }
}

/* Dark mode РїРѕРґРґРµСЂР¶РєР° */
@media (prefers-color-scheme: dark) {
  .bg-white {
    background-color: theme('colors.gray.800');
  }
  
  .text-gray-700 {
    color: theme('colors.gray.200');
  }
  
  .border-gray-100 {
    border-color: theme('colors.gray.600');
  }
}
</style>

