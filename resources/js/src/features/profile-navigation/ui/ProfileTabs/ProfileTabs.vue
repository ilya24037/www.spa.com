<template>
  <div class="border-b border-gray-500">
    <nav class="flex space-x-8" aria-label="Навигация по профилю">
      <button
        v-for="tab in tabs"
        :key="tab.key"
        :class="[
          'py-2 px-1 border-b-2 font-medium text-sm transition-colors',
          activeTab === tab.key
            ? 'border-blue-500 text-blue-600'
            : 'border-transparent text-gray-500 hover:text-gray-500 hover:border-gray-500'
        ]"
        :aria-current="activeTab === tab.key ? 'page' : undefined"
        @click="setActiveTab(tab.key)"
      >
        <div class="flex items-center space-x-2">
          <!-- Icon -->
          <component
            :is="getIconComponent(tab.icon)"
            v-if="tab.icon"
            class="w-4 h-4"
          />
          
          <!-- Label -->
          <span>{{ tab.label }}</span>
          
          <!-- Count Badge -->
          <span
            v-if="tab.count && tab.count > 0"
            class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-red-600 rounded-full"
          >
            {{ tab.count }}
          </span>
        </div>
      </button>
    </nav>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useProfileNavigationStore, type TabKey } from '../../model/navigation.store'

// Icons
import {
    ClockIcon,
    CheckCircleIcon,
    ArchiveBoxIcon,
    DocumentIcon,
    HeartIcon,
    CogIcon
} from '@heroicons/vue/24/outline'

// Store
const navigationStore = useProfileNavigationStore()

// Computed
const activeTab = computed(() => navigationStore.activeTab)
const tabs = computed(() => navigationStore.tabs)

// Methods
const setActiveTab = (tabKey: TabKey) => {
    navigationStore.setActiveTab(tabKey)
}

const getIconComponent = (iconName: string) => {
    const iconMap = {
        'clock': ClockIcon,
        'check-circle': CheckCircleIcon,
        'archive': ArchiveBoxIcon,
        'document': DocumentIcon,
        'heart': HeartIcon,
        'cog': CogIcon
    }
  
    return iconMap[iconName as keyof typeof iconMap] || DocumentIcon
}
</script>