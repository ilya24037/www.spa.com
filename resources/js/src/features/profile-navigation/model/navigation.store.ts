import { defineStore } from 'pinia'
import { computed } from 'vue'

export type TabKey = 'waiting' | 'active' | 'completed' | 'drafts' | 'favorites' | 'settings'

export interface Tab {
  key: TabKey
  label: string
  count?: number
  icon?: string
}

export const useProfileNavigationStore = defineStore('profile-navigation', () => {
  // State
  const activeTab = ref<TabKey>('waiting')
  const tabs = ref<Tab[]>([
    { key: 'waiting', label: 'Ждут действий', count: 0, icon: 'clock' },
    { key: 'active', label: 'Активные', count: 0, icon: 'check-circle' },
    { key: 'completed', label: 'Завершенные', count: 0, icon: 'archive' },
    { key: 'drafts', label: 'Черновики', count: 0, icon: 'document' },
    { key: 'favorites', label: 'Избранное', count: 0, icon: 'heart' },
    { key: 'settings', label: 'Настройки', count: 0, icon: 'cog' }
  ])
  
  // Getters
  const currentTab = computed(() => 
    tabs.value.find(tab => tab.key === activeTab.value)
  )
  
  const tabsWithCounts = computed(() => 
    tabs.value.filter(tab => tab.count !== undefined && tab.count > 0)
  )
  
  // Actions
  const setActiveTab = (tabKey: TabKey) => {
    activeTab.value = tabKey
  }
  
  const updateTabCount = (tabKey: TabKey, count: number) => {
    const tab = tabs.value.find(t => t.key === tabKey)
    if (tab) {
      tab.count = count
    }
  }
  
  const updateAllCounts = (counts: Record<TabKey, number>) => {
    Object.entries(counts).forEach(([key, count]) => {
      updateTabCount(key as TabKey, count)
    })
  }
  
  return {
    // State
    activeTab,
    tabs,
    
    // Getters
    currentTab,
    tabsWithCounts,
    
    // Actions
    setActiveTab,
    updateTabCount,
    updateAllCounts
  }
})