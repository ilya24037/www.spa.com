<!-- Р›РѕРіРѕС‚РёРї РїСЂРёР»РѕР¶РµРЅРёСЏ - FSD Р°С‚РѕРј -->
<template>
  <Link 
    :href="href" 
    :class="logoClasses"
    :aria-label="ariaLabel"
  >
    <!-- SVG Р»РѕРіРѕС‚РёРї РёР»Рё С‚РµРєСЃС‚ -->
    <div class="flex items-center space-x-2">
      <!-- РРєРѕРЅРєР° Р»РѕРіРѕС‚РёРїР° -->
      <div 
        v-if="showIcon"
        class="flex-shrink-0 w-8 h-8 bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg flex items-center justify-center"
      >
        <svg 
          class="w-5 h-5 text-white" 
          fill="none" 
          stroke="currentColor" 
          viewBox="0 0 24 24"
          aria-hidden="true"
        >
          <path 
            stroke-linecap="round" 
            stroke-linejoin="round" 
            stroke-width="2" 
            d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" 
          />
        </svg>
      </div>

      <!-- РўРµРєСЃС‚ Р»РѕРіРѕС‚РёРїР° -->
      <span 
        v-if="showText"
        :class="textClasses"
      >
        {{ logoText }}
      </span>
    </div>
  </Link>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'

// TypeScript РёРЅС‚РµСЂС„РµР№СЃС‹
interface AppLogoProps {
  href?: string
  logoText?: string
  variant?: 'default' | 'compact' | 'icon-only' | 'text-only'
  size?: 'small' | 'medium' | 'large'
  showIcon?: boolean
  showText?: boolean
  customClass?: string
}

const props = withDefaults(defineProps<AppLogoProps>(), {
    href: '/',
    logoText: 'MASSAGIST',
    variant: 'default',
    size: 'medium',
    showIcon: true,
    showText: true,
    customClass: ''
})

// Computed properties
const logoClasses = computed(() => [
    // Р‘Р°Р·РѕРІС‹Рµ СЃС‚РёР»Рё
    'inline-flex items-center transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 rounded-lg',
  
    // Р Р°Р·РјРµСЂС‹
    {
        'text-lg': props.size === 'small',
        'text-xl': props.size === 'medium', 
        'text-2xl': props.size === 'large'
    },
  
    // Р¦РІРµС‚Р° РїРѕ СѓРјРѕР»С‡Р°РЅРёСЋ
    'text-blue-600 hover:text-blue-700',
  
    // РљР°СЃС‚РѕРјРЅС‹Р№ РєР»Р°СЃСЃ
    props.customClass
])

const textClasses = computed(() => [
    'font-bold select-none',
    {
        'text-sm': props.size === 'small',
        'text-base': props.size === 'medium',
        'text-lg': props.size === 'large'
    }
])

const ariaLabel = computed(() => 
    `${props.logoText} - РїРµСЂРµР№С‚Рё РЅР° РіР»Р°РІРЅСѓСЋ СЃС‚СЂР°РЅРёС†Сѓ`
)

// Р’С‹С‡РёСЃР»СЏРµРј РїРѕРєР°Р·С‹РІР°С‚СЊ Р»Рё РёРєРѕРЅРєСѓ Рё С‚РµРєСЃС‚ РЅР° РѕСЃРЅРѕРІРµ РІР°СЂРёР°РЅС‚Р°
const showIcon = computed(() => {
    if (props.variant === 'text-only') return false
    return props.showIcon
})

const showText = computed(() => {
    if (props.variant === 'icon-only') return false
    return props.showText
})
</script>

<style scoped>
/* РђРЅРёРјР°С†РёРё РґР»СЏ hover СЃРѕСЃС‚РѕСЏРЅРёР№ */
.logo-link {
  transition: transform 0.2s ease;
}

.logo-link:hover {
  transform: translateY(-1px);
}

/* Р РµСЃРїРѕРЅСЃРёРІРЅРѕСЃС‚СЊ РґР»СЏ РѕС‡РµРЅСЊ РјР°Р»РµРЅСЊРєРёС… СЌРєСЂР°РЅРѕРІ */
@media (max-width: 480px) {
  .logo-text-responsive {
    display: none;
  }
}

/* Р”РѕСЃС‚СѓРїРЅРѕСЃС‚СЊ */
@media (prefers-reduced-motion: reduce) {
  .logo-link {
    transition: none;
  }
}

/* РџРѕРґРґРµСЂР¶РєР° С‚РµРјРЅРѕР№ С‚РµРјС‹ */
@media (prefers-color-scheme: dark) {
  .text-blue-600 {
    color: theme('colors.blue.400');
  }
  
  .hover\:text-blue-700:hover {
    color: theme('colors.blue.300');
  }
}
</style>
