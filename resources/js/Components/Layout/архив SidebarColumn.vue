<!-- resources/js/Components/Layout/SidebarWrapper.vue -->
<template>
  <aside 
    v-if="!isMobile || showMobile"
    :class="[
      'shrink-0 transition-all duration-300',
      isMobile ? 'fixed inset-y-0 left-0 z-40 w-72' : 'w-64',
      !isMobile && 'hidden lg:block'
    ]"
  >
    <div 
      :class="[
        'bg-white rounded-lg shadow-sm h-full overflow-y-auto',
        !isMobile && 'sticky p-4',
        isMobile && 'p-6'
      ]"
      :style="!isMobile ? `top: ${stickyTop}px` : ''"
    >
      <slot />
    </div>
  </aside>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  stickyTop: {
    type: Number,
    default: 110
  },
  showMobile: {
    type: Boolean,
    default: false
  }
})

const isMobile = computed(() => window.innerWidth < 1024)
</script>