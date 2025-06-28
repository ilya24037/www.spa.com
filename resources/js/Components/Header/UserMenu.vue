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
        {{ user.display_name || user.name }}
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
        @click="closeMenu"
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
                {{ user.display_name || user.name }}
              </p>
              <div class="flex items-center gap-2 mt-1">
                <!-- Рейтинг -->
                <div v-if="user.rating" class="flex items-center gap-1">
                  <span class="text-sm font-bold text-gray-900">{{ formatRating(user.rating) }}</span>
                  <div class="flex">
                    <svg 
                      v-for="i in 5" 
                      :key="i"
                      class="w-3 h-3"
                      :class="i <= Math.round(user.rating) ? 'text-yellow-400' : 'text-gray-300'"
                      fill="currentColor"
                      viewBox="0 0 20 20"
                    >
                      <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                    </svg>
                  </div>
                  <span class="text-xs text-gray-500">{{ user.reviews_count || 0 }} отзывов</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Основные ссылки -->
        <nav class="py-2" role="none">
          <Link
            v-for="item in mainMenuItems"
            :key="item.href"
            :href="item.href"
            class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors"
            role="menuitem"
          >
            <component 
              :is="item.icon" 
              class="w-5 h-5 text-gray-400"
            />
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

        <!-- Кошелёк -->
        <div v-if="showWallet" class="py-2">
          <Link
            href="/account"
            class="flex items-center justify-between px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors"
          >
            <span class="flex items-center gap-3">
              <WalletIcon class="w-5 h-5 text-gray-400" />
              <span>Кошелёк</span>
            </span>
            <span class="font-semibold">{{ formatCurrency(user.balance || 0) }}</span>
          </Link>
        </div>

        <!-- Профили (если включена мультиаккаунтность) -->
        <div v-if="showProfileSwitcher && profiles.length > 1" class="py-2">
          <div class="px-4 py-2 text-xs font-medium text-gray-500 uppercase">
            Мои профили
          </div>
          <div class="flex gap-2 px-4 py-2">
            <button
              v-for="profile in profiles"
              :key="profile.id"
              @click="switchProfile(profile)"
              class="relative group"
              :title="profile.name"
            >
              <div 
                class="w-10 h-10 rounded-full flex items-center justify-center text-white font-medium ring-2 transition"
                :class="profile.id === user.id ? 'ring-blue-500' : 'ring-gray-200 group-hover:ring-gray-300'"
                :style="{ backgroundColor: getAvatarColor(profile.name) }"
              >
                {{ getAvatarLetter(profile.name) }}
              </div>
              <div 
                v-if="profile.id === user.id"
                class="absolute -bottom-1 -right-1 w-4 h-4 bg-blue-500 rounded-full flex items-center justify-center"
              >
                <svg class="w-2.5 h-2.5 text-white" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
              </div>
            </button>
            
            <!-- Кнопка добавления профиля -->
            <button
              @click="addProfile"
              class="w-10 h-10 rounded-full border-2 border-dashed border-gray-300 hover:border-gray-400 flex items-center justify-center transition"
            >
              <PlusIcon class="w-5 h-5 text-gray-400" />
            </button>
          </div>
        </div>

        <!-- Настройки и выход -->
        <div class="py-2">
          <Link
            href="/profile/settings"
            class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors"
          >
            <CogIcon class="w-5 h-5 text-gray-400" />
            <span>Настройки</span>
          </Link>
          
          <Link
            href="/logout"
            method="post"
            as="button"
            class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors"
          >
            <LogoutIcon class="w-5 h-5 text-red-500" />
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
import { 
  UserIcon, 
  ShoppingBagIcon, 
  HeartIcon, 
  BellIcon, 
  ChatBubbleLeftIcon,
  WalletIcon,
  CogIcon,
  LogoutIcon,
  PlusIcon,
  StarIcon,
  TrophyIcon,
  CarIcon
} from '@heroicons/vue/24/outline'

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
const user = computed(() => page.props.auth.user)

// Состояние
const isOpen = ref(false)

// Цвета для аватаров (как на Avito)
const avatarColors = [
  '#F87171', // red-400
  '#FB923C', // orange-400
  '#FBBF24', // amber-400
  '#A3E635', // lime-400
  '#4ADE80', // green-400
  '#2DD4BF', // teal-400
  '#22D3EE', // cyan-400
  '#60A5FA', // blue-400
  '#818CF8', // indigo-400
  '#A78BFA', // violet-400
  '#E879F9', // fuchsia-400
  '#F472B6', // pink-400
]

// Вычисляемые свойства для аватара
const avatarLetter = computed(() => getAvatarLetter(user.value?.name))
const avatarColor = computed(() => getAvatarColor(user.value?.name))

// Профили для переключения
const profiles = ref([
  { id: user.value?.id, name: user.value?.name }
])

// Пункты меню
const mainMenuItems = computed(() => [
  { 
    href: '/profile', 
    label: 'Мои объявления', 
    icon: ShoppingBagIcon,
    badge: user.value?.ads_count || null
  },
  { 
    href: '/orders', 
    label: 'Заказы', 
    icon: ShoppingBagIcon 
  },
  { 
    href: '/profile/reviews', 
    label: 'Мои отзывы', 
    icon: StarIcon 
  },
  { 
    href: '/favorites', 
    label: 'Избранное', 
    icon: HeartIcon,
    badge: user.value?.favorites_count || null
  },
  { 
    href: '/profile/rewards', 
    label: 'Портал призов', 
    icon: TrophyIcon,
    badge: 'Новое',
    badgeClass: 'bg-blue-100 text-blue-600'
  },
  {
    href: '/garage',
    label: 'Гараж',
    icon: CarIcon,
    badge: 'Новое', 
    badgeClass: 'bg-blue-100 text-blue-600'
  },
  { 
    href: '/profile/messenger', 
    label: 'Сообщения', 
    icon: ChatBubbleLeftIcon,
    badge: user.value?.unread_messages || null
  },
  { 
    href: '/profile/notifications', 
    label: 'Уведомления', 
    icon: BellIcon,
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

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(amount)
}

const formatRating = (rating) => {
  return rating ? rating.toFixed(1).replace('.', ',') : '0,0'
}

const getAvatarLetter = (name) => {
  if (!name) return '?'
  return name.charAt(0).toUpperCase()
}

const getAvatarColor = (name) => {
  if (!name) return avatarColors[0]
  // Генерируем стабильный индекс цвета на основе имени
  const charCode = name.charCodeAt(0)
  const index = charCode % avatarColors.length
  return avatarColors[index]
}

const switchProfile = (profile) => {
  console.log('Switching to profile:', profile)
  closeMenu()
}

const addProfile = () => {
  console.log('Add new profile')
  closeMenu()
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