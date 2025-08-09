<template>
  <aside
    :class="sidebarClasses"
    role="navigation"
    :aria-label="ariaLabel"
  >
    <!-- Mobile toggle -->
    <button
      class="lg:hidden fixed top-4 left-4 z-50 p-2 bg-white rounded-lg shadow-md"
      :aria-label="isOpen ? 'Р—Р°РєСЂС‹С‚СЊ РјРµРЅСЋ' : 'РћС‚РєСЂС‹С‚СЊ РјРµРЅСЋ'"
      @click="toggleSidebar"
    >
      <svg
        class="w-6 h-6"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          v-if="!isOpen"
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2" 
          d="M4 6h16M4 12h16M4 18h16"
        />
        <path
          v-else
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2" 
          d="M6 18L18 6M6 6l12 12"
        />
      </svg>
    </button>
    
    <!-- Overlay for mobile -->
    <div
      v-if="isOpen"
      class="lg:hidden fixed inset-0 bg-black bg-opacity-50 z-40"
      @click="closeSidebar"
    />
    
    <!-- Sidebar content -->
    <nav
      :class="navClasses"
    >
      <slot />
    </nav>
  </aside>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'

interface Props {
  ariaLabel?: string
  defaultOpen?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    ariaLabel: 'Р‘РѕРєРѕРІРѕРµ РјРµРЅСЋ',
    defaultOpen: false
})

const isOpen = ref(props.defaultOpen)

const toggleSidebar = () => {
    isOpen.value = !isOpen.value
}

const closeSidebar = () => {
    isOpen.value = false
}

// РњРѕР±РёР»СЊРЅР°СЏ Р°РґР°РїС‚РёРІРЅРѕСЃС‚СЊ - Р§Р•Рљ-Р›РРЎРў CLAUDE.md вњ…
const sidebarClasses = computed(() => [
    'relative'
])

const navClasses = computed(() => [
    'fixed lg:sticky top-0 left-0 h-full lg:h-auto',
    'w-64 bg-white shadow-lg lg:shadow-none',
    'transform transition-transform duration-300 z-50',
    'overflow-y-auto',
    isOpen.value ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
])

// Р­РєСЃРїРѕСЂС‚ РјРµС‚РѕРґРѕРІ РґР»СЏ РІРЅРµС€РЅРµРіРѕ СѓРїСЂР°РІР»РµРЅРёСЏ
defineExpose({
    toggleSidebar,
    closeSidebar
})
</script>
