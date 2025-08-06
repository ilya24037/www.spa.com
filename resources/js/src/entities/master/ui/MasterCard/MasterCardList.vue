<!-- resources/js/src/entities/master/ui/MasterCard/MasterCardList.vue -->
<template>
  <div>
    <!-- Р РµР¶РёРј СЃРµС‚РєРё -->
    <div 
      v-if="viewMode === 'grid'"
      :class="GRID_CLASSES"
    >
      <MasterCard
        v-for="master in masters"
        :key="master.id"
        :master="master"
      />
    </div>

    <!-- Р РµР¶РёРј СЃРїРёСЃРєР° -->
    <div 
      v-else
      :class="LIST_CLASSES"
    >
      <MasterCardListItem
        v-for="master in masters"
        :key="master.id"
        :master="master"
      />
    </div>

    <!-- РџСѓСЃС‚РѕРµ СЃРѕСЃС‚РѕСЏРЅРёРµ -->
    <div 
      v-if="masters.length === 0"
      :class="EMPTY_STATE_CLASSES"
    >
      <svg :class="EMPTY_ICON_CLASSES" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <p :class="EMPTY_TEXT_CLASSES">РњР°СЃС‚РµСЂР° РЅРµ РЅР°Р№РґРµРЅС‹</p>
      <p :class="EMPTY_SUBTITLE_CLASSES">РџРѕРїСЂРѕР±СѓР№С‚Рµ РёР·РјРµРЅРёС‚СЊ РїР°СЂР°РјРµС‚СЂС‹ РїРѕРёСЃРєР°</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import MasterCard from './MasterCard.vue'
import MasterCardListItem from './MasterCardListItem.vue'

// рџЋЇ TypeScript РёРЅС‚РµСЂС„РµР№СЃС‹
interface Master {
  id: number
  name: string
  [key: string]: any // Р”Р»СЏ РґРѕРїРѕР»РЅРёС‚РµР»СЊРЅС‹С… СЃРІРѕР№СЃС‚РІ РјР°СЃС‚РµСЂР°
}

interface MasterCardListProps {
  masters?: Master[]
  viewMode?: 'grid' | 'list'
}

const props = withDefaults(defineProps<MasterCardListProps>(), {
  masters: () => [],
  viewMode: 'grid'
});

// рџЋЇ РЎС‚РёР»Рё СЃРѕРіР»Р°СЃРЅРѕ РґРёР·Р°Р№РЅ-СЃРёСЃС‚РµРјРµ
const GRID_CLASSES = 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4'
const LIST_CLASSES = 'space-y-4'
const EMPTY_STATE_CLASSES = 'text-center py-12'
const EMPTY_ICON_CLASSES = 'w-16 h-16 mx-auto text-gray-400 mb-4'
const EMPTY_TEXT_CLASSES = 'text-gray-500'
const EMPTY_SUBTITLE_CLASSES = 'text-sm text-gray-400 mt-2'
</script>