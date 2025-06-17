<template>
  <!-- Mobile: drawer via Teleport -->
  <Teleport to="body" v-if="isMobile">
    <Transition name="drawer">
      <div v-if="showMobile" class="fixed inset-0 z-50 flex">
        <!-- overlay -->
        <div class="flex-1 bg-black/50" @click="closeMobile" />

        <!-- sliding panel -->
        <aside
          class="w-72 bg-white h-full shadow-lg overflow-y-auto p-6 flex-shrink-0"
          @touchstart="onTouchStart"
          @touchmove="onTouchMove"
        >
          <slot />
        </aside>
      </div>
    </Transition>
  </Teleport>

  <!-- Desktop / Tablet ≥1024px -->
  <aside v-else class="w-64 shrink-0 hidden lg:block">
    <div
      class="bg-white rounded-lg shadow-sm sticky overflow-y-auto p-4"
      :style="`top: ${effectiveTop}px`"
    >
      <slot />
    </div>
  </aside>
</template>

<script>
import { ref, onMounted, onBeforeUnmount, watch, inject, defineProps, defineEmits } from 'vue'

const props = defineProps({
  /** Индивидуальный отступ от верха (если нужно переопределить inject) */
  stickyTop: {
    type: Number,
    default: undefined,
  },
  /** Показ выдвижной панели на мобильных */
  showMobile: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits<{
  (e: 'update:showMobile', value: boolean): void
  (e: 'close'): void
}>()

// ────────── SSR‑safe реактивный детектор ширины ──────────
const isMobile = ref(false)

function updateIsMobile() {
  isMobile.value = window.innerWidth < 1024
}

onMounted(() => {
  updateIsMobile()
  window.addEventListener('resize', updateIsMobile)
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', updateIsMobile)
})

// ─────────── Sticky отступ (через provide/inject) ───────────
const injectedTop = inject<number>('stickyTop', 0)
const effectiveTop = props.stickyTop ?? injectedTop

// ─────────── Scroll‑lock для body при открытой панели ───────────
watch(
  () => props.showMobile,
  (open) => {
    if (isMobile.value) {
      document.body.classList.toggle('overflow-hidden', open)
    }
  },
  { immediate: true }
)

function closeMobile() {
  emit('update:showMobile', false)
  emit('close')
}

// ─────────── Swipe‑закрытие на touch устройствах ───────────
let startX: number | null = null
function onTouchStart(e: TouchEvent) {
  startX = e.touches[0].clientX
}
function onTouchMove(e: TouchEvent) {
  if (startX === null) return
  const diff = e.touches[0].clientX - startX
  // если тянем влево > 80px – закрываем
  if (diff < -80) {
    startX = null
    closeMobile()
  }
}
</script>

<style scoped>
/* простая анимация выезда */
.drawer-enter-active,
.drawer-leave-active {
  transition: transform 0.3s ease;
}
.drawer-enter-from,
.drawer-leave-to {
  transform: translateX(-100%);
}
</style>
