<!-- resources/js/Components/Header/UserMenu.vue -->
<template>
  <div 
    class="relative"
    @mouseenter="handleMouseEnter" 
    @mouseleave="handleMouseLeave"
  >
    <!-- Кнопка-ссылка -->
    <Link
      ref="buttonRef"
      href="/dashboard"
      class="flex items-center gap-2 p-1 rounded-lg transition-colors hover:bg-gray-100 group"
    >
      <div class="relative">
        <div
          class="w-10 h-10 rounded-full flex items-center justify-center text-white font-medium text-lg"
          :style="{ backgroundColor: avatarColor }"
        >
          {{ avatarLetter }}
        </div>
      </div>
      <span class="hidden lg:block text-sm font-medium text-gray-700 group-hover:text-gray-900">
        {{ userName }}
      </span>
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

    <!-- Меню через Teleport -->
    <Teleport to="body">
      <div
        v-show="menuVisible"
        ref="menuRef"
        :style="menuPosition"
        @mouseenter="handleMouseEnter" 
        @mouseleave="handleMouseLeave"
        class="fixed w-72 bg-white rounded-xl shadow-2xl border border-gray-100 overflow-hidden"
        style="z-index: 9999"
      >
        <!-- Шапка профиля -->
        <div class="p-4 border-b border-gray-100">
          <Link 
            href="/dashboard"
            class="flex items-center gap-3 hover:bg-gray-50 -m-2 p-2 rounded-lg transition-colors"
          >
            <div
              class="w-12 h-12 rounded-full flex items-center justify-center text-white font-semibold text-xl"
              :style="{ backgroundColor: avatarColor }"
            >
              {{ avatarLetter }}
            </div>
            <div class="flex-1 min-w-0">
              <p class="font-semibold text-gray-900 truncate">{{ userName }}</p>
              <p v-if="userEmail" class="text-sm text-gray-500">{{ userEmail }}</p>
              <p class="text-sm text-blue-600 hover:text-blue-700">Личный кабинет →</p>
            </div>
          </Link>
        </div>

        <!-- Пункты меню -->
        <div class="py-2">
          <Link 
            href="/additem" 
            class="flex items-center gap-3 px-4 py-2.5 text-gray-700 hover:bg-gray-50 transition-colors block"
          >
            <span class="text-gray-400">📝</span>
            <span class="flex-1">Создать анкету мастера</span>
          </Link>
          
          <Link 
            href="/bookings" 
            class="flex items-center gap-3 px-4 py-2.5 text-gray-700 hover:bg-gray-50 transition-colors block"
          >
            <span class="text-gray-400">📅</span>
            <span class="flex-1">Мои бронирования</span>
          </Link>
          
          <Link 
            href="/favorites" 
            class="flex items-center gap-3 px-4 py-2.5 text-gray-700 hover:bg-gray-50 transition-colors block"
          >
            <span class="text-gray-400">❤️</span>
            <span class="flex-1">Избранное</span>
          </Link>
        </div>

        <div class="border-t border-gray-100" />

        <!-- Настройки -->
        <div class="py-2">
          <Link 
            href="/profile" 
            class="flex items-center gap-3 px-4 py-2.5 text-gray-700 hover:bg-gray-50 transition-colors block"
          >
            <span class="text-gray-400">⚙️</span>
            <span class="flex-1">Настройки профиля</span>
          </Link>
        </div>

        <div class="border-t border-gray-100" />

        <!-- Выход -->
        <div class="py-2">
          <Link 
            href="/logout" 
            method="post" 
            as="button"
            class="flex items-center gap-3 px-4 py-2.5 text-red-600 hover:bg-red-50 transition-colors w-full text-left"
          >
            <span class="text-red-500">🚪</span>
            <span class="flex-1">Выйти</span>
          </Link>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onBeforeUnmount, watch, nextTick } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

// Данные пользователя
const page = usePage()
const user = computed(() => page.props.auth?.user || {})
const userName = computed(() => {
  const fullName = user.value.display_name || user.value.name || 'Пользователь'
  return fullName.split(' ')[0]
})
const userEmail = computed(() => user.value.email || '')

// Цвет аватарки
const colors = ['#F87171','#FB923C','#FBBF24','#A3E635','#4ADE80','#2DD4BF','#22D3EE','#60A5FA','#818CF8','#A78BFA','#E879F9','#F472B6']
const avatarLetter = computed(() => userName.value.charAt(0).toUpperCase() || '?')
const avatarColor = computed(() => colors[(userName.value.charCodeAt(0)||0)%colors.length])

// Refs
const buttonRef = ref(null)
const menuRef = ref(null)

// Состояние меню
const menuVisible = ref(false)
const menuPosition = ref({ top: '0px', left: '0px' })
let openTimer = null
let closeTimer = null

// Вычисление позиции меню
const updateMenuPosition = async () => {
  await nextTick()
  
  if (!buttonRef.value || !menuRef.value) return
  
  const button = buttonRef.value.$el || buttonRef.value
  const rect = button.getBoundingClientRect()
  const menuWidth = 288 // 18rem = 288px
  
  // Позиционируем меню относительно правого края кнопки
  let top = rect.bottom + 4
  let left = rect.right - menuWidth // Выравниваем по правому краю
  
  // Проверка границ экрана
  if (left < 16) {
    left = 16 // Минимальный отступ слева
  }
  
  // Если меню выходит за нижний край экрана
  const menuHeight = menuRef.value?.offsetHeight || 400
  if (top + menuHeight > window.innerHeight - 16) {
    top = rect.top - menuHeight - 4 // Показываем сверху
  }
  
  menuPosition.value = {
    top: `${top}px`,
    left: `${left}px`
  }
}

// Обработчики с задержкой
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

// Обновляем позицию при показе меню
watch(menuVisible, (visible) => {
  if (visible) {
    updateMenuPosition()
  }
})

// Обновляем позицию при скролле/ресайзе
const handleWindowChange = () => {
  if (menuVisible.value) {
    updateMenuPosition()
  }
}

// Подписываемся на события
if (typeof window !== 'undefined') {
  window.addEventListener('scroll', handleWindowChange, true)
  window.addEventListener('resize', handleWindowChange)
}

// Очистка
onBeforeUnmount(() => {
  if (openTimer) clearTimeout(openTimer)
  if (closeTimer) clearTimeout(closeTimer)
  if (typeof window !== 'undefined') {
    window.removeEventListener('scroll', handleWindowChange, true)
    window.removeEventListener('resize', handleWindowChange)
  }
})
</script>