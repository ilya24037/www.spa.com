<template>
  <div class="dropdown-container" ref="dropdown">
    <button 
      type="button" 
      class="dropdown-button-inline"
      @click.stop="$emit('toggle')"
      aria-haspopup="true"
      :aria-expanded="showDropdown"
    >
      <span class="dropdown-button-wrapper">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" class="dropdown-icon">
          <circle cx="4" cy="10" r="1.5" fill="currentColor"/>
          <circle cx="10" cy="10" r="1.5" fill="currentColor"/>
          <circle cx="16" cy="10" r="1.5" fill="currentColor"/>
        </svg>
      </span>
    </button>
    
    <div v-if="showDropdown" class="dropdown-menu" @click.stop>
      <a v-if="showPay" href="#" class="dropdown-item" @click.stop.prevent="$emit('pay')">
        РћРїР»Р°С‚РёС‚СЊ СЂР°Р·РјРµС‰РµРЅРёРµ
      </a>
      <a v-if="showPromote" href="#" class="dropdown-item" @click.stop.prevent="$emit('promote')">
        РџРѕРґРЅСЏС‚СЊ РїСЂРѕСЃРјРѕС‚СЂС‹
      </a>
      <a v-if="showEdit" href="#" class="dropdown-item" @click.stop.prevent="$emit('edit')">
        Р РµРґР°РєС‚РёСЂРѕРІР°С‚СЊ
      </a>
      <a v-if="showRestore" href="#" class="dropdown-item" @click.stop.prevent="$emit('restore')">
        Р’РѕСЃСЃС‚Р°РЅРѕРІРёС‚СЊ
      </a>
             <a v-if="showDeactivate" href="#" class="dropdown-item" @click.stop.prevent="$emit('deactivate')">
         {{ showRestore ? 'РђСЂС…РёРІРёСЂРѕРІР°С‚СЊ' : 'РЈР¶Рµ РЅРµ Р°РєС‚СѓР°Р»СЊРЅРѕ' }}
       </a>
      <a v-if="showDelete" href="#" class="dropdown-item danger-item" @click.stop.prevent="$emit('delete')">
        РЈРґР°Р»РёС‚СЊ
      </a>
    </div>
  </div>
</template>

<script setup lang="ts">
const _props = defineProps({
  showDropdown: {
    type: Boolean,
    default: false
  },
  showPay: {
    type: Boolean,
    default: false
  },
  showPromote: {
    type: Boolean,
    default: false
  },
  showEdit: {
    type: Boolean,
    default: false
  },
  showRestore: {
    type: Boolean,
    default: false
  },
  showDeactivate: {
    type: Boolean,
    default: false
  },
  showDelete: {
    type: Boolean,
    default: false
  }
})

const _emit = defineEmits([
  'toggle',
  'pay',
  'promote', 
  'edit',
  'deactivate',
  'restore',
  'delete'
])
</script>

<style scoped>
.dropdown-container {
  @apply relative;
}

.dropdown-button-inline {
  @apply p-2 bg-gray-100 text-gray-800 hover:bg-gray-200 rounded-lg transition-colors;
}

.dropdown-button-wrapper {
  @apply flex items-center justify-center;
}

.dropdown-icon {
  @apply w-5 h-5;
}

.dropdown-menu {
  @apply absolute right-0 top-full mt-1 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1;
  z-index: 9999;
}

.dropdown-item {
  @apply block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors;
}

.danger-item {
  @apply text-red-600 hover:bg-red-50;
}
</style>
