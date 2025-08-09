<!-- resources/js/Components/Profile/UserProfile.vue -->
<template>
  <div class="p-6 border-b">
    <div class="flex items-center space-x-3">
      <!-- РђРІР°С‚Р°СЂ РїРѕР»СЊР·РѕРІР°С‚РµР»СЏ -->
      <div 
        class="w-12 h-12 rounded-full flex items-center justify-center text-white font-medium text-lg"
        :style="{ backgroundColor: avatarColor }"
      >
        {{ userInitial }}
      </div>
            
      <!-- РРЅС„РѕСЂРјР°С†РёСЏ Рѕ РїРѕР»СЊР·РѕРІР°С‚РµР»Рµ -->
      <div>
        <div class="font-medium text-gray-500">
          {{ userName }}
        </div>
        <div class="text-sm text-gray-500">
          в… {{ userStats?.rating || 4.2 }} вЂў {{ userStats?.reviews_count || userStats?.reviewsCount || 5 }} РѕС‚Р·С‹РІРѕРІ
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

// Props
const props = defineProps({
    userStats: {
        type: Object,
        default: () => ({})
    }
})

// РџРѕР»СѓС‡РµРЅРёРµ РґР°РЅРЅС‹С… РїРѕР»СЊР·РѕРІР°С‚РµР»СЏ
const page = usePage()
const user = computed(() => page.props.auth?.user || {})

// Р’С‹С‡РёСЃР»СЏРµРјС‹Рµ СЃРІРѕР№СЃС‚РІР° РґР»СЏ РїРѕР»СЊР·РѕРІР°С‚РµР»СЏ
const userName = computed(() => user.value.name || 'РџРѕР»СЊР·РѕРІР°С‚РµР»СЊ')
const userInitial = computed(() => userName.value.charAt(0).toUpperCase())

// Р¦РІРµС‚ Р°РІР°С‚Р°СЂР° (РёСЃРїРѕР»СЊР·СѓРµРј С‚Сѓ Р¶Рµ Р»РѕРіРёРєСѓ С‡С‚Рѕ РІ Dashboard Рё UserMenu)
const colors = ['#ef4444', '#f97316', '#eab308', '#22c55e', '#06b6d4', '#3b82f6', '#8b5cf6', '#ec4899']
const avatarColor = computed(() => {
    const index = userName.value.charCodeAt(0) % colors.length
    return colors[index]
})
</script>
