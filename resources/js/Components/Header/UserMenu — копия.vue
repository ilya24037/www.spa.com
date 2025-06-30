<!-- resources/js/Components/Header/UserMenu.vue -->
<template>
  <div class="relative" ref="wrapper">
    <!-- trigger button -->
    <button
      ref="btn"
      @click="toggleMenu"
      class="flex items-center gap-2 p-1 rounded-lg hover:bg-gray-100 transition-colors group"
      :aria-expanded="isOpen"
      aria-haspopup="menu"
    >
      <div class="relative">
        <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-medium text-lg" :style="{ backgroundColor: avatarColor }">
          {{ avatarLetter }}
        </div>
        <div v-if="showOnlineStatus" class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 rounded-full border-2 border-white" />
      </div>
      <span class="hidden lg:block text-sm font-medium text-gray-700 group-hover:text-gray-900">{{ userName }}</span>
      <svg class="w-4 h-4 text-gray-500 transition-transform duration-200" :class="{ 'rotate-180': isOpen }" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>

    <!-- ================= Popover ================= -->
    <teleport to="#overlay-root">
      <Transition
        enter-active-class="transition ease-out duration-100"
        enter-from-class="transform opacity-0 scale-95"
        enter-to-class="transform opacity-100 scale-100"
        leave-active-class="transition ease-in duration-75"
        leave-from-class="transform opacity-100 scale-100"
        leave-to-class="transform opacity-0 scale-95"
      >
        <div v-if="isOpen" ref="menu" :style="menuStyles" class="fixed z-[60] w-72 rounded-xl overflow-hidden bg-white shadow-lg ring-1 ring-black/5 divide-y divide-gray-100" role="menu">
          <!-- profile header -->
          <div class="p-4 flex items-center gap-3">
            <div class="w-12 h-12 rounded-full flex items-center justify-center text-white font-medium text-xl" :style="{ backgroundColor: avatarColor }">{{ avatarLetter }}</div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-semibold text-gray-900 truncate">{{ userName }}</p>
              <p v-if="userEmail" class="text-xs text-gray-500">{{ userEmail }}</p>
            </div>
          </div>

          <!-- sections -->
          <template v-for="(section, sIdx) in sections" :key="sIdx">
            <nav class="py-2" role="none">
              <Link
                v-for="item in section"
                :key="item.href"
                :href="item.href"
                :method="item.method || 'get'"
                :as="item.as || 'a'"
                class="flex items-center gap-3 px-4 py-2.5 text-sm transition-colors"
                :class="item.danger ? 'text-red-600 hover:bg-red-50' : 'text-gray-700 hover:bg-gray-50'"
                role="menuitem"
                @click="closeMenu"
              >
                <span class="w-5 h-5" :class="item.danger ? 'text-red-500' : 'text-gray-400'">{{ item.emoji }}</span>
                <span class="flex-1">{{ item.label }}</span>
                <span v-if="item.badge" class="text-xs px-2 py-0.5 rounded-full" :class="item.badgeClass || 'bg-gray-100 text-gray-600'">{{ item.badge }}</span>
                <span v-if="item.isNew" class="ml-1 text-xs text-red-500">Новое</span>
              </Link>
            </nav>
            <!-- разделитель между секциями -->
            <div v-if="sIdx < sections.length - 1" class="h-px bg-gray-100" />
          </template>
        </div>
      </Transition>
    </teleport>

    <!-- overlay -->
    <div v-if="isOpen" @click="closeMenu" class="fixed inset-0 z-40" aria-hidden="true" />
  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { computePosition, offset, flip, shift, autoUpdate } from '@floating-ui/dom'

/* props */
const props = defineProps({ showOnlineStatus: { type: Boolean, default: false } })

/* user */
const page = usePage()
const user = computed(() => page.props.auth?.user || {})
const userName = computed(() => user.value.display_name || user.value.name || 'Пользователь')
const userEmail = computed(() => user.value.email || '')

/* avatar */
const colors = ['#F87171','#FB923C','#FBBF24','#A3E635','#4ADE80','#2DD4BF','#22D3EE','#60A5FA','#818CF8','#A78BFA','#E879F9','#F472B6']
const avatarLetter = computed(() => userName.value.charAt(0).toUpperCase() || '?')
const avatarColor  = computed(() => colors[(userName.value.charCodeAt(0)||0)%colors.length])

/* sections (Avito‑style) */
const sections = computed(() => [
  // блок 1 — основные действия
  [
    { href:'/profile', label:'Мои объявления', emoji:'📋', badge:user.value.ads_count || null },
    { href:'/orders', label:'Заказы', emoji:'🛍️' },
    { href:'/profile/reviews', label:'Мои отзывы', emoji:'⭐' },
    { href:'/favorites', label:'Избранное', emoji:'❤️', badge:user.value.favorites_count || null },
  ],
  // блок 2 — сообщения / уведомления
  [
    { href:'/profile/messenger', label:'Сообщения', emoji:'💬', badge:user.value.unread_messages || null },
    { href:'/profile/notifications', label:'Уведомления', emoji:'🔔', badge:user.value.unread_notifications || null, badgeClass:user.value.unread_notifications?'bg-red-100 text-red-600':null },
  ],
  // блок 3 — настройки
  [
    { href:'/profile/settings', label:'Настройки', emoji:'⚙️' },
  ],
  // блок 4 — выход
  [
    { href:'/logout', method:'post', as:'button', label:'Выйти', emoji:'🚪', danger:true },
  ],
])

/* menu state & positioning */
const isOpen = ref(false)
const btn = ref(null)
const menu = ref(null)
const menuStyles = ref({ left: '0px', top: '0px' })
let cleanup = () => {}
const toggleMenu = () => { isOpen.value = !isOpen.value }
const closeMenu  = () => { isOpen.value = false }

watch(isOpen, async (open) => {
  if (open && btn.value) {
    await nextTick()
    
    cleanup = autoUpdate(btn.value, menu.value, () => {
      // Получаем позицию кнопки
      const btnRect = btn.value.getBoundingClientRect()
      
      computePosition(btn.value, menu.value, { 
        strategy: 'fixed',
        placement: 'bottom-end',
        middleware: [
          offset(8),
          flip(),
          shift({ 
            padding: 8,
            crossAxis: false
          })
        ] 
      })
        .then(({x, y, placement}) => {
          // Для правильного выравнивания по правому краю
          const menuWidth = 288 // w-72 = 18rem = 288px
          
          // Вычисляем позицию так, чтобы правый край меню совпадал с правым краем кнопки
          const rightEdgeX = btnRect.right - menuWidth
          
          menuStyles.value = { 
            left: `${rightEdgeX}px`, 
            top: `${y}px` 
          }
        })
    })
  } else { 
    cleanup() 
  }
})

/* close on outside / esc */
const outside = (e) => !menu.value?.contains(e.target) && !btn.value?.contains(e.target) && closeMenu()
const onEsc   = (e) => e.key==='Escape' && closeMenu()

onMounted(() => {
  document.addEventListener('mousedown', outside)
  document.addEventListener('keydown', onEsc)
})

onUnmounted(() => {
  document.removeEventListener('mousedown', outside)
  document.removeEventListener('keydown', onEsc)
  cleanup()
})
</script>