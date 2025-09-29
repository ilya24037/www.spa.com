<!-- resources/js/Components/Profile/ProfileNavigation.vue -->
<template>
  <nav class="flex-1">
    <div class="py-2">
      <!-- РњРѕРё РѕР±СЉСЏРІР»РµРЅРёСЏ (РѕСЃРЅРѕРІРЅР°СЏ СЃРµРєС†РёСЏ) -->
      <div class="px-4">
        <Link 
          href="/profile/items/inactive/all"
          :class="menuItemClass(isAdsRoute)"
        >
          <span>РњРѕРё РѕР±СЉСЏРІР»РµРЅРёСЏ</span>
          <span v-if="totalAds > 0" class="text-xs bg-gray-500 text-gray-500 px-2 py-0.5 rounded-full">
            {{ totalAds }}
          </span>
        </Link>
      </div>
            
      <!-- РћСЃС‚Р°Р»СЊРЅС‹Рµ РїСѓРЅРєС‚С‹ РјРµРЅСЋ -->
      <div class="px-4 mt-2 space-y-1">
        <Link 
          v-for="item in menuItems" 
          :key="item.href"
          :href="item.href"
          :class="menuItemClass(isCurrentRoute(item.href))"
        >
          <span>{{ item.label }}</span>
          <span v-if="item.count > 0" class="text-xs bg-gray-500 text-gray-500 px-2 py-0.5 rounded-full">
            {{ item.count }}
          </span>
        </Link>
      </div>
    </div>
  </nav>
</template>

<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'

// Props
const props = defineProps({
    counts: {
        type: Object,
        default: () => ({})
    }
})

// РўРµРєСѓС‰Р°СЏ СЃС‚СЂР°РЅРёС†Р°
const page = usePage()

// РџСѓРЅРєС‚С‹ РјРµРЅСЋ
const menuItems = [
    { href: '/bookings', label: 'Р—Р°РєР°Р·С‹', count: 0 },
    { href: '/reviews', label: 'РњРѕРё РѕС‚Р·С‹РІС‹', count: 0 },
    { href: '/favorites', label: 'РР·Р±СЂР°РЅРЅРѕРµ', count: props.counts?.favorites || 0 },
    { href: '/messages', label: 'РЎРѕРѕР±С‰РµРЅРёСЏ', count: 0 },
    { href: '/notifications', label: 'РЈРІРµРґРѕРјР»РµРЅРёСЏ', count: 0 },
    { href: '/wallet', label: 'РљРѕС€РµР»С‘Рє', count: 0 },
    { href: '/profile/addresses', label: 'РђРґСЂРµСЃР°', count: 0 },
    { href: '/profile/edit', label: 'РЈРїСЂР°РІР»РµРЅРёРµ РїСЂРѕС„РёР»РµРј', count: 0 },
    { href: '/profile/security', label: 'Р—Р°С‰РёС‚Р° РїСЂРѕС„РёР»СЏ', count: 0 },
    { href: '/settings', label: 'РќР°СЃС‚СЂРѕР№РєРё', count: 0 },
    { href: '/services', label: 'РџР»Р°С‚РЅС‹Рµ СѓСЃР»СѓРіРё', count: 0 }
]

// РџСЂРѕРІРµСЂРєР° С‚РµРєСѓС‰РµРіРѕ СЂРѕСѓС‚Р° РґР»СЏ РѕР±СЉСЏРІР»РµРЅРёР№
const isAdsRoute = computed(() => {
    const currentRoute = page.url
    return currentRoute.includes('/profile/items/')
})

// РћР±С‰РµРµ РєРѕР»РёС‡РµСЃС‚РІРѕ РѕР±СЉСЏРІР»РµРЅРёР№
const totalAds = computed(() => {
    const counts = props.counts || {}
    return (counts.active || 0) + (counts.draft || 0) + (counts.waiting_payment || 0) + (counts.old || 0) + (counts.archive || 0)
})

// РџСЂРѕРІРµСЂРєР° Р°РєС‚РёРІРЅРѕРіРѕ СЂРѕСѓС‚Р°
const isCurrentRoute = (href) => {
    // РЈР±РёСЂР°РµРј РІРµРґСѓС‰РёР№ СЃР»СЌС€ РґР»СЏ СЃСЂР°РІРЅРµРЅРёСЏ
    const routePath = href.replace(/^\//, '')
    const currentPath = page.url.replace(/^\//, '')
    
    // РўРѕС‡РЅРѕРµ СЃРѕРІРїР°РґРµРЅРёРµ РёР»Рё РЅР°С‡Р°Р»Рѕ РїСѓС‚Рё
    return currentPath === routePath || currentPath.startsWith(routePath + '/')
}

// РљР»Р°СЃСЃ РґР»СЏ РїСѓРЅРєС‚РѕРІ РјРµРЅСЋ (РєРѕРїРёСЂСѓРµРј РёР· Dashboard)
const menuItemClass = (isActive) => [
    'flex items-center justify-between px-3 py-2 text-sm rounded-md transition-colors',
    isActive 
        ? 'bg-blue-50 text-blue-700 font-medium' 
        : 'text-gray-500 hover:bg-gray-500'
]
</script>
