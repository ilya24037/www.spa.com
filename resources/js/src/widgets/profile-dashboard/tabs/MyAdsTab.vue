<!-- resources/js/src/widgets/profile-dashboard/tabs/MyAdsTab.vue -->
<template>
  <div :class="CONTAINER_CLASSES">
    <!-- Р¤РёР»СЊС‚СЂС‹ СЃС‚Р°С‚СѓСЃРѕРІ -->
    <div :class="FILTERS_CLASSES">
      <button
        v-for="status in statuses"
        :key="status.key"
        :class="getStatusButtonClasses(status.key)"
        @click="activeStatus = status.key"
      >
        {{ status.label }}
        <span v-if="getStatusCount(status.key) > 0" :class="STATUS_COUNT_CLASSES">
          {{ getStatusCount(status.key) }}
        </span>
      </button>
    </div>

    <!-- РЎРїРёСЃРѕРє РѕР±СЉСЏРІР»РµРЅРёР№ -->
    <div v-if="filteredAds.length > 0" :class="ADS_LIST_CLASSES">
      <AdCard
        v-for="ad in filteredAds"
        :key="ad.id"
        :ad="ad"
        :editable="true"
        @edit="handleEdit(ad)"
        @delete="handleDelete(ad)"
        @toggle-status="handleToggleStatus(ad)"
      />
    </div>

    <!-- РџСѓСЃС‚РѕРµ СЃРѕСЃС‚РѕСЏРЅРёРµ -->
    <div v-else :class="EMPTY_STATE_CLASSES">
      <CollectionIcon :class="EMPTY_ICON_CLASSES" />
      <h3 :class="EMPTY_TITLE_CLASSES">
        РќРµС‚ РѕР±СЉСЏРІР»РµРЅРёР№
      </h3>
      <p :class="EMPTY_DESCRIPTION_CLASSES">
        {{ getEmptyMessage() }}
      </p>
      <button
        :class="CREATE_BUTTON_CLASSES"
        @click="createNewAd"
      >
        РЎРѕР·РґР°С‚СЊ РѕР±СЉСЏРІР»РµРЅРёРµ
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { CollectionIcon } from '@heroicons/vue/outline'
import AdCard from '@/src/entities/ad/ui/AdCard/AdCard.vue'

// рџЋЇ РЎС‚РёР»Рё
const CONTAINER_CLASSES = 'space-y-6'
const FILTERS_CLASSES = 'flex flex-wrap gap-2'
const STATUS_BUTTON_BASE_CLASSES = 'px-4 py-2 rounded-lg font-medium text-sm transition-colors'
const STATUS_BUTTON_ACTIVE_CLASSES = 'bg-blue-600 text-white'
const STATUS_BUTTON_INACTIVE_CLASSES = 'bg-gray-500 text-gray-500 hover:bg-gray-500'
const STATUS_COUNT_CLASSES = 'ml-1 px-2 py-0.5 text-xs bg-white/20 rounded-full'
const ADS_LIST_CLASSES = 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6'
const EMPTY_STATE_CLASSES = 'text-center py-12'
const EMPTY_ICON_CLASSES = 'w-12 h-12 mx-auto text-gray-500 mb-4'
const EMPTY_TITLE_CLASSES = 'text-lg font-medium text-gray-500 mb-2'
const EMPTY_DESCRIPTION_CLASSES = 'text-gray-500 mb-4'
const CREATE_BUTTON_CLASSES = 'px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors'

const props = defineProps({
    ads: {
        type: Array,
        default: () => []
    },
    counts: {
        type: Object,
        default: () => ({})
    }
})

const emit = defineEmits(['refresh'])

// РЎРѕСЃС‚РѕСЏРЅРёРµ
const activeStatus = ref('all')

// РЎС‚Р°С‚СѓСЃС‹
const statuses = [
    { key: 'all', label: 'Р’СЃРµ' },
    { key: 'active', label: 'РђРєС‚РёРІРЅС‹Рµ' },
    { key: 'drafts', label: 'ЧерновикРё' },
    { key: 'waiting', label: 'РќР° РјРѕРґРµСЂР°С†РёРё' },
    { key: 'archived', label: 'РђСЂС…РёРІ' }
]

// Р’С‹С‡РёСЃР»СЏРµРјС‹Рµ СЃРІРѕР№СЃС‚РІР°
const filteredAds = computed(() => {
    if (activeStatus.value === 'all') {
        return props.ads
    }
    return props.ads.filter(ad => ad.status === activeStatus.value)
})

// РњРµС‚РѕРґС‹
const getStatusButtonClasses = (statusKey) => {
    return [
        STATUS_BUTTON_BASE_CLASSES,
        activeStatus.value === statusKey ? STATUS_BUTTON_ACTIVE_CLASSES : STATUS_BUTTON_INACTIVE_CLASSES
    ].join(' ')
}

const getStatusCount = (statusKey) => {
    if (statusKey === 'all') {
        return props.counts.ads || 0
    }
    return props.counts[statusKey] || 0
}

const getEmptyMessage = () => {
    switch (activeStatus.value) {
    case 'active':
        return 'РЈ РІР°СЃ РЅРµС‚ Р°РєС‚РёРІРЅС‹С… РѕР±СЉСЏРІР»РµРЅРёР№'
    case 'drafts':
        return 'РЈ РІР°СЃ РЅРµС‚ С‡РµСЂРЅРѕРІРёРєРѕРІ'
    case 'waiting':
        return 'РќРµС‚ РѕР±СЉСЏРІР»РµРЅРёР№ РЅР° РјРѕРґРµСЂР°С†РёРё'
    case 'archived':
        return 'Р’ Р°СЂС…РёРІРµ РїСѓСЃС‚Рѕ'
    default:
        return 'РЈ РІР°СЃ РїРѕРєР° РЅРµС‚ РѕР±СЉСЏРІР»РµРЅРёР№'
    }
}

const createNewAd = () => {
    router.visit('/ads/create')
}

const handleEdit = (ad) => {
    router.visit(`/ads/${ad.id}/edit`)
}

const handleDelete = async (ad) => {
    if (confirm('РЈРґР°Р»РёС‚СЊ РѕР±СЉСЏРІР»РµРЅРёРµ?')) {
    // API РІС‹Р·РѕРІ РґР»СЏ СѓРґР°Р»РµРЅРёСЏ
        emit('refresh')
    }
}

const handleToggleStatus = async (ad) => {
    // API РІС‹Р·РѕРІ РґР»СЏ РёР·РјРµРЅРµРЅРёСЏ СЃС‚Р°С‚СѓСЃР°
    emit('refresh')
}
</script>

