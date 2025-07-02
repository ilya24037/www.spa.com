<!-- resources/js/Components/Profile/ProfileSidebar.vue -->
<template>
  <!-- Универсальная обёртка: sticky + drawer -->
  <SidebarWrapper
    :sticky-top="80"
    v-model:show-mobile="showMobile"
  >
    <!-- Блок пользователя -->
    <div class="text-center mb-6">
      <img
        :src="user.avatar || placeholderAvatar"
        alt="avatar"
        class="mx-auto w-20 h-20 rounded-full object-cover mb-3 shadow"
      />
      <h3 class="font-semibold text-gray-900 mb-1 truncate max-w-[10rem] mx-auto">
        {{ user.name }}
      </h3>
      <div v-if="user.rating" class="text-sm text-yellow-500 flex items-center justify-center gap-1">
        <StarIcon class="w-4 h-4" />
        <span>{{ user.rating.toFixed(1) }}</span>
      </div>
    </div>

    <!-- Меню навигации ЛК -->
    <nav class="flex flex-col gap-2 text-sm">
      <SidebarLink
        v-for="link in links"
        :key="link.href"
        :href="link.href"
        :icon="link.icon"
        :active="route().current(link.active)"
      >
        {{ link.label }}
      </SidebarLink>
    </nav>

    <!-- Слот: позволяет расширять меню -->
    <slot />
  </SidebarWrapper>
</template>

<script setup>
import { ref, computed } from 'vue'
import { StarIcon } from 'lucide-vue-next'
import SidebarWrapper from '@/Components/Layout/SidebarWrapper.vue'
import SidebarLink from '@/Components/Profile/SidebarLink.vue'

/**
 * Props
 */
const props = defineProps({
  user: {
    type: Object,
    required: true
  },
  /**
   * Управляет отображением drawer на мобильных.
   * Используется как v-model:show-mobile на родительской странице.
   */
  showMobile: {
    type: Boolean,
    default: false
  }
})

/**
 * stub-аватар, если у пользователя нет аватара
 */
const placeholderAvatar = new URL('/images/avatar-placeholder.png', import.meta.url).href

/**
 * Ссылки меню
 * active: имя маршрута, по которому проверяем активность через Ziggy route().current()
 */
const links = computed(() => [
  { label: 'Мой профиль',      href: route('profile.show'),      active: 'profile.show',      icon: 'User' },
  { label: 'Мои объявления',   href: route('items.index'),      active: 'items.*',          icon: 'Package' },
  { label: 'Сообщения',        href: route('chat.index'),       active: 'chat.*',           icon: 'MessageCircle' },
  { label: 'Заказы',           href: route('orders.index'),     active: 'orders.*',         icon: 'ClipboardList' },
  { label: 'Избранное',        href: route('favorites.index'),  active: 'favorites.*',      icon: 'Heart' },
  { label: 'Настройки',        href: route('settings.index'),   active: 'settings.*',       icon: 'Settings' },
])
</script>
