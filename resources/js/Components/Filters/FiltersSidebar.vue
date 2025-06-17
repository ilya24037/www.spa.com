<template>
  <!-- Общая боковая обёртка -->
  <SidebarWrapper :sticky-top="110" :show-mobile="showMobile">
    <!-- Заголовок и кнопка сброса -->
    <div class="flex items-center justify-between mb-4">
      <h3 class="font-semibold text-lg">Фильтры</h3>
      <button
        v-if="hasActiveFilters"
        @click="$emit('reset')"
        class="text-sm text-blue-600 hover:underline"
      >
        Сбросить
      </button>
    </div>

    <!-- Содержимое снаружи компонента -->
    <slot />

    <!-- Кнопка «Показать N объявлений» (mobile‑only) -->
    <button
      v-if="isMobile"
      @click="$emit('apply')"
      class="mt-6 w-full h-11 rounded-lg bg-blue-600 text-white font-medium"
    >
      Показать {{ resultsCount }} объявлений
    </button>
  </SidebarWrapper>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import SidebarWrapper from '@/Components/Layout/SidebarWrapper.vue'

/* ---------- props ---------- */
const props = defineProps({
  /** Кол‑во объявлений после фильтрации */
  resultsCount: {
    type: Number,
    required: true,
  },
  /** Есть ли активные фильтры */
  hasActiveFilters: {
    type: Boolean,
    default: false,
  },
  /** Показ drawer на мобилке (контролируется родителем) */
  showMobile: {
    type: Boolean,
    default: false,
  },
})

/* ---------- helpers ---------- */
const isMobile = ref(false)

const onResize = () => {
  isMobile.value = window.innerWidth < 1024
}

onMounted(() => {
  onResize()
  window.addEventListener('resize', onResize)
})
</script>

<style scoped>
/* Анимация draw‑in/draw‑out уже описана в SidebarWrapper → reuse */
</style>
