<template>
  <!-- РњРѕРґР°Р»СЊРЅРѕРµ РѕРєРЅРѕ РіР°Р»РµСЂРµРё -->
  <Teleport to="body">
    <Transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-if="isOpen" class="fixed inset-0 z-50 overflow-hidden">
        <!-- Р—Р°С‚РµРјРЅС‘РЅРЅС‹Р№ С„РѕРЅ -->
        <div 
          class="absolute inset-0 bg-black/80 backdrop-blur-sm" 
          @click="close"
        />

        <!-- РљРѕРЅС‚РµР№РЅРµСЂ РіР°Р»РµСЂРµРё -->
        <div class="relative h-full flex items-center justify-center p-4">
          <!-- РљРЅРѕРїРєР° Р·Р°РєСЂС‹С‚РёСЏ -->
          <button
            class="absolute top-4 right-4 z-10 p-2 rounded-full bg-white/10 hover:bg-white/20 transition-colors"
            aria-label="Р—Р°РєСЂС‹С‚СЊ РіР°Р»РµСЂРµСЋ"
            @click="close"
          >
            <svg
              class="w-6 h-6 text-white"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
          </button>

          <!-- РћСЃРЅРѕРІРЅРѕР№ РєРѕРЅС‚РµРЅС‚ -->
          <div class="relative max-w-6xl w-full mx-auto flex gap-4">
            <!-- РњРёРЅРёР°С‚СЋСЂС‹ (desktop) -->
            <div class="hidden lg:flex flex-col gap-2 max-h-[600px] overflow-y-auto scrollbar-hide">
              <button
                v-for="(image, index) in images"
                :key="index"
                class="relative w-20 h-20 rounded-lg overflow-hidden transition-all"
                :class="[
                  currentIndex === index 
                    ? 'ring-2 ring-purple-500 scale-105' 
                    : 'opacity-70 hover:opacity-100'
                ]"
                @click="currentIndex = index"
              >
                <img 
                  :src="image" 
                  :alt="`Р¤РѕС‚Рѕ ${index + 1}`"
                  class="w-full h-full object-cover"
                  loading="lazy"
                >
              </button>
            </div>

            <!-- РћСЃРЅРѕРІРЅРѕРµ РёР·РѕР±СЂР°Р¶РµРЅРёРµ -->
            <div class="relative flex-1">
              <!-- РР·РѕР±СЂР°Р¶РµРЅРёРµ СЃ РїРѕРґРґРµСЂР¶РєРѕР№ touch-Р¶РµСЃС‚РѕРІ -->
              <div 
                class="relative bg-white rounded-lg overflow-hidden"
                @touchstart="handleTouchStart"
                @touchmove="handleTouchMove"
                @touchend="handleTouchEnd"
              >
                <img 
                  :src="images[currentIndex]" 
                  :alt="`Р¤РѕС‚Рѕ ${currentIndex + 1}`"
                  class="w-full h-auto max-h-[80vh] object-contain"
                >

                <!-- РЎС‡С‘С‚С‡РёРє РёР·РѕР±СЂР°Р¶РµРЅРёР№ -->
                <div class="absolute top-4 left-4 px-3 py-1 bg-black/50 text-white rounded-full text-sm">
                  {{ currentIndex + 1 }} / {{ images.length }}
                </div>
              </div>

              <!-- РќР°РІРёРіР°С†РёСЏ СЃС‚СЂРµР»РєР°РјРё -->
              <button
                v-if="currentIndex > 0"
                class="absolute left-2 top-1/2 -translate-y-1/2 p-2 rounded-full bg-white/10 hover:bg-white/20 transition-colors"
                aria-label="РџСЂРµРґС‹РґСѓС‰РµРµ С„РѕС‚Рѕ"
                @click="previousImage"
              >
                <svg
                  class="w-6 h-6 text-white"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M15 19l-7-7 7-7"
                  />
                </svg>
              </button>

              <button
                v-if="currentIndex < images.length - 1"
                class="absolute right-2 top-1/2 -translate-y-1/2 p-2 rounded-full bg-white/10 hover:bg-white/20 transition-colors"
                aria-label="РЎР»РµРґСѓСЋС‰РµРµ С„РѕС‚Рѕ"
                @click="nextImage"
              >
                <svg
                  class="w-6 h-6 text-white"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M9 5l7 7-7 7"
                  />
                </svg>
              </button>

              <!-- РњРёРЅРёР°С‚СЋСЂС‹ (mobile) -->
              <div class="lg:hidden flex gap-2 mt-4 overflow-x-auto scrollbar-hide justify-center">
                <button
                  v-for="(image, index) in images"
                  :key="index"
                  class="relative w-16 h-16 rounded-lg overflow-hidden flex-shrink-0 transition-all"
                  :class="[
                    currentIndex === index 
                      ? 'ring-2 ring-purple-500 scale-105' 
                      : 'opacity-70'
                  ]"
                  @click="currentIndex = index"
                >
                  <img 
                    :src="image" 
                    :alt="`Р¤РѕС‚Рѕ ${index + 1}`"
                    class="w-full h-full object-cover"
                    loading="lazy"
                  >
                </button>
              </div>
            </div>
          </div>

          <!-- Р”РѕРїРѕР»РЅРёС‚РµР»СЊРЅС‹Рµ РґРµР№СЃС‚РІРёСЏ -->
          <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2">
            <button
              class="px-4 py-2 bg-white/10 hover:bg-white/20 text-white rounded-lg transition-colors text-sm flex items-center gap-2"
              @click="downloadImage"
            >
              <svg
                class="w-4 h-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                />
              </svg>
              РЎРєР°С‡Р°С‚СЊ
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'

// РџСЂРѕРїСЃС‹
const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false
    },
    images: {
        type: Array,
        required: true,
        validator: (value) => value.length > 0
    },
    initialIndex: {
        type: Number,
        default: 0
    }
})

// Р­РјРёС‚С‹
const emit = defineEmits(['update:modelValue', 'close'])

// РЎРѕСЃС‚РѕСЏРЅРёРµ
const currentIndex = ref(props.initialIndex)
const touchStartX = ref(0)
const touchEndX = ref(0)

// Р’С‹С‡РёСЃР»СЏРµРјС‹Рµ СЃРІРѕР№СЃС‚РІР°
const isOpen = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
})

// РњРµС‚РѕРґС‹ РЅР°РІРёРіР°С†РёРё
const nextImage = () => {
    if (currentIndex.value < props.images.length - 1) {
        currentIndex.value++
    }
}

const previousImage = () => {
    if (currentIndex.value > 0) {
        currentIndex.value--
    }
}

const close = () => {
    emit('close')
    isOpen.value = false
}

// РЎРєР°С‡РёРІР°РЅРёРµ РёР·РѕР±СЂР°Р¶РµРЅРёСЏ
const downloadImage = async () => {
    const imageUrl = props.images[currentIndex.value]
    try {
        const response = await fetch(imageUrl)
        const blob = await response.blob()
        const url = window.URL.createObjectURL(blob)
        const link = document.createElement('a')
        link.href = url
        link.download = `image-${currentIndex.value + 1}.jpg`
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        window.URL.revokeObjectURL(url)
    } catch (error) {
    }
}

// Touch СЃРѕР±С‹С‚РёСЏ РґР»СЏ СЃРІР°Р№РїРѕРІ РЅР° РјРѕР±РёР»СЊРЅС‹С…
const handleTouchStart = (e) => {
    touchStartX.value = e.touches[0].clientX
}

const handleTouchMove = (e) => {
    touchEndX.value = e.touches[0].clientX
}

const handleTouchEnd = () => {
    const swipeThreshold = 50
    const diff = touchStartX.value - touchEndX.value

    if (Math.abs(diff) > swipeThreshold) {
        if (diff > 0) {
            nextImage()
        } else {
            previousImage()
        }
    }
}

// РћР±СЂР°Р±РѕС‚РєР° РєР»Р°РІРёР°С‚СѓСЂС‹
const handleKeydown = (e) => {
    if (!isOpen.value) return
    
    switch(e.key) {
    case 'ArrowLeft':
        previousImage()
        break
    case 'ArrowRight':
        nextImage()
        break
    case 'Escape':
        close()
        break
    }
}

// Р‘Р»РѕРєРёСЂРѕРІРєР° СЃРєСЂРѕР»Р»Р° body РїСЂРё РѕС‚РєСЂС‹С‚РѕР№ РіР°Р»РµСЂРµРµ
watch(isOpen, (newValue) => {
    if (newValue) {
        document.body.style.overflow = 'hidden'
    } else {
        document.body.style.overflow = ''
    }
})

// Lifecycle
onMounted(() => {
    document.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeydown)
    document.body.style.overflow = ''
})
</script>

<style scoped>
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
</style>

