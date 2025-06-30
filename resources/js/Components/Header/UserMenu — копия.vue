<!-- resources/js/Components/Header/UserMenu.vue -->
<template>
  <div class="relative">
    <!-- Кнопка профиля -->
    <button
      ref="buttonRef"
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

    <!-- Портал для меню (как на Ozon, Avito) -->
    <Teleport to="body">
      <!-- Оверлей для закрытия по клику -->
      <div 
        v-if="isOpen" 
        @click="closeMenu" 
        class="fixed inset-0" 
        style="z-index: 9998"
      />
      
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
          ref="menuRef"
          :style="menuStyles"
          class="fixed w-72 bg-white rounded-xl shadow-2xl border border-gray-100 overflow-hidden"
          style="z-index: 9999"
        >
          <!-- Шапка профиля (как на Авито) -->
          <div class="p-4 border-b border-gray-100">
            <div class="flex items-center gap-3">
              <div class="w-12 h-12 rounded-full flex items-center justify-center text-white font-semibold text-xl" 
                   :style="{ backgroundColor: avatarColor }">
                {{ avatarLetter }}
              </div>
              <div class="flex-1 min-w-0">
                <p class="font-semibold text-gray-900 truncate">{{ userName }}</p>
                <p v-if="userEmail" class="text-sm text-gray-500">{{ userEmail }}</p>
              </div>
            </div>
            
            <!-- Рейтинг (опционально, как на Авито) -->
            <div v-if="user.rating" class="mt-3 flex items-center gap-2">
              <span class="text-lg font-bold">{{ user.rating }}</span>
              <div class="flex">
                <span v-for="i in 5" :key="i" class="text-yellow-400">⭐</span>
              </div>
              <span class="text-sm text-gray-600">{{ user.reviews_count || 0 }} отзывов</span>
            </div>
          </div>

          <!-- Секции меню (как на больших сайтах) -->
          <!-- Основные действия -->
          <div class="py-2">
            <Link 
              href="/profile" 
              class="flex items-center gap-3 px-4 py-2.5 text-gray-700 hover:bg-gray-50 transition-colors"
              @click="closeMenu"
            >
              <span class="text-gray-400">📋</span>
              <span class="flex-1">Мои объявления</span>
              <span v-if="user.ads_count" class="text-sm text-gray-500">{{ user.ads_count }}</span>
            </Link>
            
            <Link 
              href="/orders" 
              class="flex items-center gap-3 px-4 py-2.5 text-gray-700 hover:bg-gray-50 transition-colors"
              @click="closeMenu"
            >
              <span class="text-gray-400">🛍️</span>
              <span class="flex-1">Заказы</span>
            </Link>
            
            <Link 
              href="/favorites" 
              class="flex items-center gap-3 px-4 py-2.5 text-gray-700 hover:bg-gray-50 transition-colors"
              @click="closeMenu"
            >
              <span class="text-gray-400">❤️</span>
              <span class="flex-1">Избранное</span>
              <span v-if="user.favorites_count" class="text-sm text-gray-500">{{ user.favorites_count }}</span>
            </Link>
          </div>

          <!-- Разделитель -->
          <div class="border-t border-gray-100"></div>

          <!-- Коммуникации -->
          <div class="py-2">
            <Link 
              href="/profile/messenger" 
              class="flex items-center gap-3 px-4 py-2.5 text-gray-700 hover:bg-gray-50 transition-colors"
              @click="closeMenu"
            >
              <span class="text-gray-400">💬</span>
              <span class="flex-1">Сообщения</span>
              <span v-if="user.unread_messages" class="px-2 py-0.5 text-xs bg-red-500 text-white rounded-full">
                {{ user.unread_messages }}
              </span>
            </Link>
            
            <Link 
              href="/profile/notifications" 
              class="flex items-center gap-3 px-4 py-2.5 text-gray-700 hover:bg-gray-50 transition-colors"
              @click="closeMenu"
            >
              <span class="text-gray-400">🔔</span>
              <span class="flex-1">Уведомления</span>
              <span v-if="user.unread_notifications" class="text-sm text-gray-500">
                {{ user.unread_notifications }}
              </span>
            </Link>
          </div>

          <!-- Разделитель -->
          <div class="border-t border-gray-100"></div>

          <!-- Настройки -->
          <div class="py-2">
            <Link 
              href="/profile/settings" 
              class="flex items-center gap-3 px-4 py-2.5 text-gray-700 hover:bg-gray-50 transition-colors"
              @click="closeMenu"
            >
              <span class="text-gray-400">⚙️</span>
              <span class="flex-1">Настройки</span>
            </Link>
          </div>

          <!-- Разделитель -->
          <div class="border-t border-gray-100"></div>

          <!-- Выход -->
          <div class="py-2">
            <Link 
              href="/logout" 
              method="post" 
              as="button"
              class="flex items-center gap-3 px-4 py-2.5 text-red-600 hover:bg-red-50 transition-colors w-full text-left"
              @click="closeMenu"
            >
              <span class="text-red-500">🚪</span>
              <span class="flex-1">Выйти</span>
            </Link>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

// Данные пользователя
const page = usePage()
const user = computed(() => page.props.auth?.user || {})
const userName = computed(() => {
  const fullName = user.value.display_name || user.value.name || 'Пользователь'
  return fullName.split(' ')[0] // Берём только первое слово (имя)
})
const userEmail = computed(() => user.value.email || '')

// Цвет аватарки
const colors = ['#F87171','#FB923C','#FBBF24','#A3E635','#4ADE80','#2DD4BF','#22D3EE','#60A5FA','#818CF8','#A78BFA','#E879F9','#F472B6']
const avatarLetter = computed(() => userName.value.charAt(0).toUpperCase() || '?')
const avatarColor = computed(() => colors[(userName.value.charCodeAt(0)||0)%colors.length])

// Состояние меню
const isOpen = ref(false)
const buttonRef = ref(null)
const menuRef = ref(null)
const menuStyles = ref({})

// Функция позиционирования (как на больших сайтах)
const updatePosition = () => {
  if (!buttonRef.value || !menuRef.value) return
  
  const button = buttonRef.value.getBoundingClientRect()
  const menu = menuRef.value
  const menuWidth = 288 // w-72 = 18rem = 288px
  const menuHeight = menu.offsetHeight
  
  // Вычисляем позицию
  let top = button.bottom + 8
  let left = button.left
  
  // Проверка выхода за границы viewport
  if (left < 8) {
    left = 8
  }
  
  if (left + menuWidth > window.innerWidth - 8) {
    left = window.innerWidth - menuWidth - 8
  }
  
  // Если не помещается снизу, показываем сверху
  if (top + menuHeight > window.innerHeight - 8) {
    top = button.top - menuHeight - 8
  }
  
  menuStyles.value = {
    top: `${top}px`,
    left: `${left}px`,
    // Добавляем transform для GPU ускорения (как на больших сайтах)
    transform: 'translate3d(0, 0, 0)',
    willChange: 'transform'
  }
}

// Переключение меню
const toggleMenu = () => {
  isOpen.value = !isOpen.value
}

const closeMenu = () => {
  isOpen.value = false
}

// Обновление позиции при открытии
watch(isOpen, async (newValue) => {
  if (newValue) {
    await nextTick()
    updatePosition()
  }
})

// Закрытие по Escape
const handleEscape = (e) => {
  if (e.key === 'Escape' && isOpen.value) {
    closeMenu()
  }
}

// Обновление позиции при скролле/ресайзе (как на больших сайтах)
const handleScroll = () => {
  if (isOpen.value) {
    updatePosition()
  }
}

// Жизненный цикл
onMounted(() => {
  document.addEventListener('keydown', handleEscape)
  window.addEventListener('scroll', handleScroll, true)
  window.addEventListener('resize', handleScroll)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleEscape)
  window.removeEventListener('scroll', handleScroll, true)
  window.removeEventListener('resize', handleScroll)
})
</script>