<!-- resources/js/src/features/profile-navigation/ui/ProfileTabs/ProfileTabs.vue -->
<template>
  <div :class="CONTAINER_CLASSES">
    <!-- Основные вкладки -->
    <nav :class="TABS_NAVIGATION_CLASSES">
      <button
        v-for="tab in mainTabs"
        :key="tab.key"
        @click="setActiveTab(tab.key)"
        :class="getTabClasses(tab.key)"
      >
        <svg v-if="tab.icon" :class="TAB_ICON_CLASSES" :innerHTML="tab.icon"></svg>
        <span>{{ tab.label }}</span>
        <span v-if="tab.count !== undefined" :class="TAB_COUNT_CLASSES">
          {{ tab.count }}
        </span>
      </button>
    </nav>

    <!-- Дополнительные вкладки (если есть) -->
    <div v-if="hasAdditionalTabs" :class="ADDITIONAL_TABS_CLASSES">
      <button
        v-for="tab in additionalTabs"
        :key="tab.key"
        @click="setActiveTab(tab.key)"
        :class="getAdditionalTabClasses(tab.key)"
      >
        {{ tab.label }}
        <span v-if="tab.count !== undefined" :class="ADDITIONAL_COUNT_CLASSES">
          {{ tab.count }}
        </span>
      </button>
    </div>

    <!-- Мобильный селект -->
    <div :class="MOBILE_SELECT_CLASSES">
      <select
        :value="activeTab"
        @change="setActiveTab($event.target.value)"
        :class="SELECT_CLASSES"
      >
        <optgroup v-if="mainTabs.length" label="Основное">
          <option
            v-for="tab in mainTabs"
            :key="tab.key"
            :value="tab.key"
          >
            {{ tab.label }}
            <span v-if="tab.count !== undefined">({{ tab.count }})</span>
          </option>
        </optgroup>
        
        <optgroup v-if="hasAdditionalTabs" label="Дополнительно">
          <option
            v-for="tab in additionalTabs"
            :key="tab.key"
            :value="tab.key"
          >
            {{ tab.label }}
            <span v-if="tab.count !== undefined">({{ tab.count }})</span>
          </option>
        </optgroup>
      </select>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

// 🎯 Стили согласно дизайн-системе
const CONTAINER_CLASSES = 'space-y-4'
const TABS_NAVIGATION_CLASSES = 'hidden md:flex gap-1 border-b border-gray-200'
const TAB_BASE_CLASSES = 'flex items-center gap-2 px-4 py-3 text-sm font-medium transition-colors border-b-2 border-transparent'
const TAB_ACTIVE_CLASSES = 'text-blue-600 border-blue-600 bg-blue-50'
const TAB_INACTIVE_CLASSES = 'text-gray-600 hover:text-gray-900 hover:border-gray-300'
const TAB_ICON_CLASSES = 'w-4 h-4'
const TAB_COUNT_CLASSES = 'ml-1 px-2 py-0.5 text-xs bg-gray-100 text-gray-600 rounded-full'
const ADDITIONAL_TABS_CLASSES = 'hidden md:flex gap-2 flex-wrap'
const ADDITIONAL_TAB_BASE_CLASSES = 'flex items-center gap-1 px-3 py-1.5 text-sm font-medium rounded-full transition-colors'
const ADDITIONAL_TAB_ACTIVE_CLASSES = 'bg-blue-100 text-blue-700'
const ADDITIONAL_TAB_INACTIVE_CLASSES = 'bg-gray-100 text-gray-700 hover:bg-gray-200'
const ADDITIONAL_COUNT_CLASSES = 'ml-1 px-1.5 py-0.5 text-xs bg-white rounded-full'
const MOBILE_SELECT_CLASSES = 'md:hidden'
const SELECT_CLASSES = 'w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent'

const props = defineProps({
  activeTab: {
    type: String,
    required: true
  },
  tabs: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['tab-change'])

// Вычисляемые свойства
const mainTabs = computed(() => 
  props.tabs.filter(tab => !tab.additional)
)

const additionalTabs = computed(() => 
  props.tabs.filter(tab => tab.additional)
)

const hasAdditionalTabs = computed(() => 
  additionalTabs.value.length > 0
)

// Методы
const getTabClasses = (tabKey) => {
  const isActive = props.activeTab === tabKey
  
  return [
    TAB_BASE_CLASSES,
    isActive ? TAB_ACTIVE_CLASSES : TAB_INACTIVE_CLASSES
  ].join(' ')
}

const getAdditionalTabClasses = (tabKey) => {
  const isActive = props.activeTab === tabKey
  
  return [
    ADDITIONAL_TAB_BASE_CLASSES,
    isActive ? ADDITIONAL_TAB_ACTIVE_CLASSES : ADDITIONAL_TAB_INACTIVE_CLASSES
  ].join(' ')
}

const setActiveTab = (tabKey) => {
  if (tabKey !== props.activeTab) {
    emit('tab-change', tabKey)
  }
}
</script>