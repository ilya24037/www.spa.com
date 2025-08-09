<!-- Р‘С‹СЃС‚СЂР°СЏ РЅР°РІРёРіР°С†РёСЏ - FSD Shared Molecule -->
<template>
  <nav 
    :class="navigationClasses"
    role="navigation"
    :aria-label="ariaLabel"
  >
    <!-- РћР±С‹С‡РЅС‹Рµ СЃСЃС‹Р»РєРё -->
    <template v-if="!loading">
      <component
        :is="link.external ? 'a' : Link"
        v-for="link in visibleLinks"
        :key="link.href"
        :href="link.href"
        :target="link.external ? '_blank' : undefined"
        :rel="link.external ? 'noopener noreferrer' : undefined"
        :class="linkClasses(link)"
        :aria-current="link.active ? 'page' : undefined"
        @click="handleLinkClick(link)"
      >
        <!-- РРєРѕРЅРєР° (РµСЃР»Рё РµСЃС‚СЊ) -->
        <component
          :is="link.icon"
          v-if="link.icon"
          :class="iconClasses"
          aria-hidden="true"
        />
        
        <!-- РўРµРєСЃС‚ -->
        <span :class="textClasses">
          {{ link.text }}
        </span>
        
        <!-- Р‘РµР№РґР¶ (РµСЃР»Рё РµСЃС‚СЊ) -->
        <span
          v-if="link.badge"
          :class="badgeClasses"
          :aria-label="`${link.badge} РЅРѕРІС‹С…`"
        >
          {{ formatBadge(link.badge) }}
        </span>
        
        <!-- РРєРѕРЅРєР° РІРЅРµС€РЅРµР№ СЃСЃС‹Р»РєРё -->
        <svg
          v-if="link.external && showExternalIcon"
          class="w-3 h-3 ml-1 opacity-60"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
          aria-hidden="true"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
          />
        </svg>
      </component>
      
      <!-- РџРѕРєР°Р·Р°С‚СЊ Р±РѕР»СЊС€Рµ/РјРµРЅСЊС€Рµ (РµСЃР»Рё РµСЃС‚СЊ СЃРєСЂС‹С‚С‹Рµ СЃСЃС‹Р»РєРё) -->
      <button
        v-if="hasMoreLinks"
        type="button"
        :class="moreButtonClasses"
        :aria-expanded="showAll"
        :aria-label="showAll ? 'РЎРєСЂС‹С‚СЊ РґРѕРїРѕР»РЅРёС‚РµР»СЊРЅС‹Рµ СЃСЃС‹Р»РєРё' : 'РџРѕРєР°Р·Р°С‚СЊ РґРѕРїРѕР»РЅРёС‚РµР»СЊРЅС‹Рµ СЃСЃС‹Р»РєРё'"
        @click="toggleShowAll"
      >
        <span>{{ showAll ? 'РЎРєСЂС‹С‚СЊ' : `+${hiddenLinksCount}` }}</span>
        <svg
          :class="moreIconClasses"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
          aria-hidden="true"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            :d="showAll ? 'M5 15l7-7 7 7' : 'M19 9l-7 7-7-7'"
          />
        </svg>
      </button>
    </template>
    
    <!-- Loading СЃРѕСЃС‚РѕСЏРЅРёРµ -->
    <template v-else>
      <div
        v-for="i in skeletonCount"
        :key="`skeleton-${i}`"
        :class="skeletonClasses"
        aria-hidden="true"
      />
    </template>
    
    <!-- РРЅРґРёРєР°С‚РѕСЂ Р°РєС‚РёРІРЅРѕСЃС‚Рё -->
    <div
      v-if="showActivityIndicator && activeLink"
      class="absolute bottom-0 left-0 h-0.5 bg-blue-500 transition-all duration-300 ease-out"
      :style="{ width: activeIndicatorWidth, transform: `translateX(${activeIndicatorOffset}px)` }"
      aria-hidden="true"
    />
  </nav>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { Link } from '@inertiajs/vue3'

// TypeScript РёРЅС‚РµСЂС„РµР№СЃС‹
export interface NavigationLink {
  href: string
  text: string
  active?: boolean
  external?: boolean
  icon?: any // Vue РєРѕРјРїРѕРЅРµРЅС‚ РёРєРѕРЅРєРё
  badge?: number | string
  priority?: number // РџСЂРёРѕСЂРёС‚РµС‚ РѕС‚РѕР±СЂР°Р¶РµРЅРёСЏ
  category?: string
  disabled?: boolean
  visible?: boolean
}

interface Props {
  links?: NavigationLink[]
  variant?: 'horizontal' | 'vertical' | 'dropdown'
  size?: 'small' | 'medium' | 'large'
  maxVisible?: number
  showExternalIcon?: boolean
  showActivityIndicator?: boolean
  loading?: boolean
  skeletonCount?: number
  customClass?: string
  linkClass?: string
  activeClass?: string
  ariaLabel?: string
  compact?: boolean
  responsive?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    links: () => [
        { href: '/masters', text: 'Р’СЃРµ РјР°СЃС‚РµСЂР°', priority: 1 },
        { href: '/services/massage', text: 'РњР°СЃСЃР°Р¶', priority: 2 },
        { href: '/services/spa', text: 'РЎРџРђ', priority: 3 },
        { href: '/services/cosmetology', text: 'РљРѕСЃРјРµС‚РѕР»РѕРіРёСЏ', priority: 4 },
        { href: '/services/at-home', text: 'РќР° РґРѕРјСѓ', priority: 5 },
        { href: '/gift-certificates', text: 'РЎРµСЂС‚РёС„РёРєР°С‚С‹', priority: 6 }
    ],
    variant: 'horizontal',
    size: 'medium',
    maxVisible: 6,
    showExternalIcon: true,
    showActivityIndicator: false,
    loading: false,
    skeletonCount: 5,
    customClass: '',
    linkClass: '',
    activeClass: '',
    ariaLabel: 'Р‘С‹СЃС‚СЂР°СЏ РЅР°РІРёРіР°С†РёСЏ',
    compact: false,
    responsive: true
})

// TypeScript С‚РёРїРёР·Р°С†РёСЏ emits
const emit = defineEmits<{
  'link-click': [link: NavigationLink, event: Event]
  'toggle-show-all': [showAll: boolean]
}>()

// Local state
const showAll = ref(false)
const activeLink = ref<NavigationLink | null>(null)
const activeIndicatorWidth = ref('0px')
const activeIndicatorOffset = ref(0)

// Computed
const sortedLinks = computed(() => 
    props.links
        .filter(link => link.visible !== false && !link.disabled)
        .sort((a, b) => (a.priority || 999) - (b.priority || 999))
)

const visibleLinks = computed(() => {
    if (showAll.value || props.maxVisible <= 0) {
        return sortedLinks.value
    }
    return sortedLinks.value.slice(0, props.maxVisible)
})

const hiddenLinksCount = computed(() => 
    Math.max(0, sortedLinks.value.length - props.maxVisible)
)

const hasMoreLinks = computed(() => 
    !showAll.value && hiddenLinksCount.value > 0 && props.maxVisible > 0
)

const navigationClasses = computed(() => [
    'relative flex transition-all duration-200',
    {
    // Variant styles
        'items-center gap-6': props.variant === 'horizontal',
        'flex-col items-start gap-2': props.variant === 'vertical',
        'flex-col': props.variant === 'dropdown',
    
        // Size styles  
        'text-xs gap-3': props.size === 'small',
        'text-sm gap-4': props.size === 'medium',
        'text-base gap-6': props.size === 'large',
    
        // Responsive
        'flex-wrap': props.responsive && props.variant === 'horizontal',
    
        // Compact
        'gap-2': props.compact
    },
    props.customClass
])

const linkClasses = (link: NavigationLink) => [
    'inline-flex items-center transition-all duration-200 relative',
    'focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 rounded-md',
    {
    // Base styles
        'text-gray-500 hover:text-blue-600': !link.active,
        'text-blue-600 font-medium': link.active,
    
        // Size variants
        'px-2 py-1': props.size === 'small',
        'px-3 py-1.5': props.size === 'medium',
        'px-4 py-2': props.size === 'large',
    
        // Compact mode
        'px-1 py-0.5': props.compact,
    
        // Disabled state
        'opacity-50 cursor-not-allowed pointer-events-none': link.disabled
    },
    props.linkClass,
    link.active ? props.activeClass : ''
]

const iconClasses = computed(() => [
    'transition-colors duration-200',
    {
        'w-3 h-3 mr-1': props.size === 'small',
        'w-4 h-4 mr-1.5': props.size === 'medium',
        'w-5 h-5 mr-2': props.size === 'large'
    }
])

const textClasses = computed(() => [
    'transition-colors duration-200',
    {
        'hidden sm:inline': props.responsive && props.compact
    }
])

const badgeClasses = computed(() => [
    'ml-1.5 px-1.5 py-0.5 bg-red-500 text-white text-xs font-bold rounded-full',
    'min-w-[1.25rem] h-5 flex items-center justify-center leading-none'
])

const moreButtonClasses = computed(() => [
    'inline-flex items-center gap-1 text-gray-500 hover:text-blue-600 transition-colors duration-200',
    'focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 rounded-md',
    {
        'px-2 py-1 text-xs': props.size === 'small',
        'px-3 py-1.5 text-sm': props.size === 'medium', 
        'px-4 py-2 text-base': props.size === 'large'
    }
])

const moreIconClasses = computed(() => [
    'w-3 h-3 transition-transform duration-200',
    {
        'rotate-180': showAll.value
    }
])

const skeletonClasses = computed(() => [
    'bg-gray-500 rounded animate-pulse',
    {
        'h-4 w-16': props.size === 'small',
        'h-5 w-20': props.size === 'medium',
        'h-6 w-24': props.size === 'large'
    }
])

// Methods
const handleLinkClick = (link: NavigationLink, event?: Event): void => {
    if (link.disabled) {
        event?.preventDefault()
        return
    }
  
    activeLink.value = link
    emit('link-click', link, event!)
}

const toggleShowAll = (): void => {
    showAll.value = !showAll.value
    emit('toggle-show-all', showAll.value)
}

const formatBadge = (badge: number | string): string => {
    if (typeof badge === 'number') {
        return badge > 99 ? '99+' : badge.toString()
    }
    return badge
}

const updateActiveIndicator = async (): Promise<void> => {
    if (!props.showActivityIndicator || !activeLink.value) return
  
    await nextTick()
    // Р—РґРµСЃСЊ РјРѕР¶РЅРѕ РґРѕР±Р°РІРёС‚СЊ Р»РѕРіРёРєСѓ СЂР°СЃС‡РµС‚Р° РїРѕР·РёС†РёРё Р°РєС‚РёРІРЅРѕРіРѕ РёРЅРґРёРєР°С‚РѕСЂР°
}

// Lifecycle
onMounted(() => {
    // РќР°Р№С‚Рё Р°РєС‚РёРІРЅСѓСЋ СЃСЃС‹Р»РєСѓ РїСЂРё РјРѕРЅС‚РёСЂРѕРІР°РЅРёРё
    const currentActive = props.links.find(link => link.active)
    if (currentActive) {
        activeLink.value = currentActive
        updateActiveIndicator()
    }
})

onUnmounted(() => {
    // Cleanup РµСЃР»Рё РЅРµРѕР±С…РѕРґРёРјРѕ
})
</script>

<style scoped>
/* РђРЅРёРјР°С†РёРё */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Hover СЌС„С„РµРєС‚С‹ */
@media (hover: hover) {
  .navigation-link:hover {
    transform: translateY(-1px);
  }
}

/* Accessibility improvements */
@media (prefers-reduced-motion: reduce) {
  * {
    animation: none !important;
    transition: none !important;
  }
}

/* High contrast mode */
@media (prefers-contrast: high) {
  .text-blue-600 {
    color: #0066cc;
  }
  
  .bg-red-500 {
    background-color: #cc0000;
  }
}

/* Dark mode РїРѕРґРґРµСЂР¶РєР° */
@media (prefers-color-scheme: dark) {
  .text-gray-500 {
    color: theme('colors.gray.200');
  }
  
  .text-gray-500 {
    color: theme('colors.gray.300');
  }
  
  .hover\:text-blue-600:hover {
    color: theme('colors.blue.400');
  }
  
  .bg-gray-500 {
    background-color: theme('colors.gray.700');
  }
}

/* Focus styles РґР»СЏ РєР»Р°РІРёР°С‚СѓСЂРЅРѕР№ РЅР°РІРёРіР°С†РёРё */
.focus\:ring-2:focus {
  box-shadow: 0 0 0 2px theme('colors.blue.500');
}

/* Mobile optimizations */
@media (max-width: 640px) {
  .gap-6 {
    gap: 1rem;
  }
  
  .text-sm {
    font-size: 0.75rem;
  }
}

/* Tablet optimizations */
@media (min-width: 641px) and (max-width: 1024px) {
  .gap-6 {
    gap: 1.25rem;
  }
}
</style>

