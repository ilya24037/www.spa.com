<!-- PageLoader.vue -->
<template>
  <div 
    :class="containerClasses"
    :data-testid="`page-loader-${type}`"
    role="status"
    :aria-label="ariaLabel"
    :aria-live="showProgress ? 'polite' : 'off'"
  >
    <!-- Overlay РґР»СЏ РїРѕР»РЅРѕСЌРєСЂР°РЅРЅРѕРіРѕ СЂРµР¶РёРјР° -->
    <div 
      v-if="overlay && fullScreen"
      class="fixed inset-0 bg-white bg-opacity-90 backdrop-blur-sm z-50"
      data-testid="page-loader-overlay"
    />

    <!-- РћСЃРЅРѕРІРЅРѕР№ РєРѕРЅС‚РµР№РЅРµСЂ -->
    <div :class="contentClasses">
      <!-- РЎРїРёРЅРЅРµСЂ -->
      <div 
        v-if="showSpinner"
        class="flex justify-center mb-6"
        data-testid="page-loader-spinner"
      >
        <LoadingSpinner 
          :size="spinnerSize"
          :color="spinnerColor"
          class="animate-spin"
        />
      </div>

      <!-- РџСЂРѕРіСЂРµСЃСЃ Р±Р°СЂ -->
      <div 
        v-if="showProgress && progress > 0"
        class="w-full max-w-md mx-auto mb-6"
        data-testid="page-loader-progress"
        role="progressbar"
        :aria-valuenow="progress"
        aria-valuemin="0"
        aria-valuemax="100"
      >
        <div class="bg-gray-500 rounded-full h-2 overflow-hidden">
          <div 
            class="bg-blue-600 h-2 rounded-full transition-all duration-300 ease-out"
            :style="{ width: `${progress}%` }"
          />
        </div>
        <div class="text-center mt-2 text-sm text-gray-500">
          {{ progress }}%
        </div>
      </div>

      <!-- РЎРѕРѕР±С‰РµРЅРёРµ -->
      <div 
        v-if="message"
        class="text-center mb-8"
        data-testid="page-loader-message"
      >
        <p class="text-gray-500 text-lg">
          {{ message }}
        </p>
      </div>

      <!-- РЎРєРµР»РµС‚РѕРЅС‹ -->
      <div 
        v-if="showSkeletons"
        class="space-y-4"
        data-testid="page-loader-skeletons"
      >
        <component
          :is="skeletonComponent"
          v-for="index in skeletonCount"
          :key="`skeleton-${index}`"
          :class="skeletonClasses"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import LoadingSpinner from '../../atoms/LoadingSpinner/LoadingSpinner.vue'
import SkeletonCard from './skeletons/SkeletonCard.vue'
import SkeletonList from './skeletons/SkeletonList.vue'
import SkeletonProfile from './skeletons/SkeletonProfile.vue'
import SkeletonForm from './skeletons/SkeletonForm.vue'
import SkeletonContent from './skeletons/SkeletonContent.vue'
import SkeletonStats from './skeletons/SkeletonStats.vue'
import type {
    PageLoaderProps,
    PageLoaderEmits,
    PageTypeConfigs
} from './PageLoader.types'

// Props СЃ TypeScript С‚РёРїРёР·Р°С†РёРµР№
const _props = withDefaults(defineProps<PageLoaderProps>(), {
    type: 'default',
    message: '',
    showSkeletons: true,
    skeletonCount: 6,
    showProgress: false,
    progress: 0,
    duration: 0,
    fullScreen: false,
    overlay: false,
    className: ''
})

// Emits
defineEmits<PageLoaderEmits>()

// РљРѕРЅС„РёРіСѓСЂР°С†РёРё РґР»СЏ СЂР°Р·РЅС‹С… С‚РёРїРѕРІ СЃС‚СЂР°РЅРёС†
const pageConfigs: PageTypeConfigs = {
    catalog: {
        showSkeletons: true,
        skeletonCount: 6,
        showSpinner: true,
        showProgress: false,
        message: 'Р—Р°РіСЂСѓР¶Р°РµРј РјР°СЃС‚РµСЂРѕРІ...',
        fullScreen: false,
        overlay: false,
        animation: 'fade'
    },
    profile: {
        showSkeletons: true,
        skeletonCount: 1,
        showSpinner: true,
        showProgress: false,
        message: 'Р—Р°РіСЂСѓР¶Р°РµРј РїСЂРѕС„РёР»СЊ...',
        fullScreen: false,
        overlay: false,
        animation: 'fade'
    },
    dashboard: {
        showSkeletons: true,
        skeletonCount: 4,
        showSpinner: true,
        showProgress: false,
        message: 'Р—Р°РіСЂСѓР¶Р°РµРј РґР°РЅРЅС‹Рµ...',
        fullScreen: false,
        overlay: false,
        animation: 'fade'
    },
    form: {
        showSkeletons: true,
        skeletonCount: 1,
        showSpinner: true,
        showProgress: false,
        message: 'РџРѕРґРіРѕС‚Р°РІР»РёРІР°РµРј С„РѕСЂРјСѓ...',
        fullScreen: false,
        overlay: false,
        animation: 'slide'
    },
    content: {
        showSkeletons: true,
        skeletonCount: 3,
        showSpinner: true,
        showProgress: false,
        message: 'Р—Р°РіСЂСѓР¶Р°РµРј РєРѕРЅС‚РµРЅС‚...',
        fullScreen: false,
        overlay: false,
        animation: 'fade'
    },
    minimal: {
        showSkeletons: false,
        skeletonCount: 0,
        showSpinner: true,
        showProgress: false,
        message: '',
        fullScreen: false,
        overlay: false,
        animation: 'none'
    },
    default: {
        showSkeletons: true,
        skeletonCount: 3,
        showSpinner: true,
        showProgress: false,
        message: 'Загрузка...',
        fullScreen: false,
        overlay: false,
        animation: 'fade'
    }
}

// РџРѕР»СѓС‡Р°РµРј РєРѕРЅС„РёРіСѓСЂР°С†РёСЋ РґР»СЏ С‚РµРєСѓС‰РµРіРѕ С‚РёРїР°
const config = computed(() => pageConfigs[_props?.type])

// Р’С‹С‡РёСЃР»СЏРµРјС‹Рµ СЃРІРѕР№СЃС‚РІР°
const showSpinner = computed<boolean>(() => config?.value.showSpinner)
const actualMessage = computed<string>(() => _props?.message || config?.value.message)
const _actualSkeletonCount = computed<number>(() => _props?.skeletonCount || config?.value.skeletonCount)
const _actualShowSkeletons = computed<boolean>(() => _props?.showSkeletons && config?.value.showSkeletons)

const ariaLabel = computed<string>(() => {
    if (_props?.showProgress && _props?.progress > 0) {
        return `Загрузка: ${_props?.progress}%`
    }
            return actualMessage?.value || 'Загрузка страницы'
})

const containerClasses = computed<string>(() => {
    const baseClasses = [
        'page-loader',
        `page-loader--${_props?.type}`,
        `page-loader--animation-${config?.value.animation}`
    ]

    if (_props?.fullScreen || config?.value.fullScreen) {
        baseClasses?.push('fixed', 'inset-0', 'z-40', 'flex', 'items-center', 'justify-center', 'bg-white')
    } else {
        baseClasses?.push('w-full', 'py-12')
    }

    if (_props?.className) {
        baseClasses?.push(_props?.className)
    }

    return baseClasses?.join(' ')
})

const contentClasses = computed<string>(() => {
    const baseClasses = ['page-loader__content']

    if (_props?.fullScreen || config?.value.fullScreen) {
        baseClasses?.push('text-center', 'max-w-md', 'mx-auto', 'px-6')
    } else {
        baseClasses?.push('container', 'mx-auto', 'px-4')
    }

    return baseClasses?.join(' ')
})

const spinnerSize = computed<string>(() => {
    switch (_props?.type) {
    case 'minimal':
        return 'sm'
    case 'catalog':
    case 'dashboard':
        return 'lg'
    default:
        return 'md'
    }
})

const spinnerColor = computed<string>(() => {
    switch (_props?.type) {
    case 'profile':
        return 'purple'
    case 'dashboard':
        return 'blue'
    case 'form':
        return 'green'
    default:
        return 'indigo'
    }
})

const skeletonComponent = computed(() => {
    switch (_props?.type) {
    case 'catalog':
        return SkeletonCard
    case 'profile':
        return SkeletonProfile
    case 'dashboard':
        return SkeletonStats
    case 'form':
        return SkeletonForm
    case 'content':
        return SkeletonContent
    default:
        return SkeletonList
    }
})

const skeletonClasses = computed<string>(() => {
    const baseClasses = ['animate-pulse']

    switch (_props?.type) {
    case 'catalog':
        baseClasses?.push('mb-4')
        break
    case 'dashboard':
        baseClasses?.push('mb-6')
        break
    default:
        baseClasses?.push('mb-3')
    }

    return baseClasses?.join(' ')
})
</script>

<style scoped>
/* РђРЅРёРјР°С†РёРё РґР»СЏ СЂР°Р·РЅС‹С… С‚РёРїРѕРІ */
.page-loader--animation-fade {
  @apply opacity-0 animate-fade-in;
}

.page-loader--animation-slide {
  @apply transform translate-y-4 animate-slide-up;
}

.page-loader--animation-scale {
  @apply transform scale-95 animate-scale-up;
}

.page-loader--animation-pulse {
  @apply animate-pulse;
}

/* РљР°СЃС‚РѕРјРЅС‹Рµ Р°РЅРёРјР°С†РёРё */
@keyframes fade-in {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

@keyframes slide-up {
  from {
    opacity: 0;
    transform: translateY(1rem);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes scale-up {
  from {
    opacity: 0;
    transform: scale(0?.95);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

.animate-fade-in {
  animation: fade-in 0?.3s ease-out;
}

.animate-slide-up {
  animation: slide-up 0?.4s ease-out;
}

.animate-scale-up {
  animation: scale-up 0?.3s ease-out;
}

/* РЎС‚РёР»Рё РґР»СЏ СЂР°Р·РЅС‹С… С‚РёРїРѕРІ */
.page-loader--catalog {
  @apply bg-gray-500;
}

.page-loader--profile {
  @apply bg-gradient-to-br from-purple-50 to-indigo-50;
}

.page-loader--dashboard {
  @apply bg-gradient-to-br from-blue-50 to-cyan-50;
}

.page-loader--form {
  @apply bg-white;
}

.page-loader--content {
  @apply bg-white;
}

.page-loader--minimal {
  @apply bg-transparent;
}
</style>
