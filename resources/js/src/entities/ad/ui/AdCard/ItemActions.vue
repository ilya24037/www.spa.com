<template>
  <div class="item-actions" @click.stop>
    <!-- Р•РґРёРЅРѕРѕР±СЂР°Р·РЅС‹Р№ РґРёР·Р°Р№РЅ РґР»СЏ РІСЃРµС… СЃС‚Р°С‚СѓСЃРѕРІ -->
    <div class="actions-container">
             <!-- РћСЃРЅРѕРІРЅР°СЏ РєРЅРѕРїРєР° СЃР»РµРІР° (РєР°Рє РЅР° РђРІРёС‚Рѕ) -->
       <button @click="handleMainAction" class="main-action-button">
         {{ mainActionText }}
       </button>
      
      <!-- РўСЂРѕРµС‚РѕС‡РёРµ СЃРїСЂР°РІР° (РІСЃРµРіРґР°) -->
                           <ItemActionsDropdown 
          :showDropdown="showDropdown"
          :showPay="item.status === 'waiting_payment'"
          :showPromote="item.status === 'active'"
          :showEdit="true"
          :showRestore="item.status === 'archived'"
          :showDeactivate="['waiting_payment', 'active'].includes(item.status)"
          :showDelete="true"
          @toggle="toggleDropdown"
          @pay="$emit('pay', item)"
          @promote="$emit('promote', item)"
          @edit="$emit('edit', item)"
          @restore="$emit('restore', item)"
          @deactivate="$emit('deactivate', item)"
          @delete="handleDelete"
        />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import ItemActionsDropdown from './ItemActionsDropdown.vue'

const _props = defineProps({
  item: {
    type: Object,
    required: true
  }
})

const _emit = defineEmits([
  'pay',
  'promote', 
  'edit',
  'deactivate',
  'restore',
  'delete'
])

const showDropdown = ref(false)

// Р’РµР·РґРµ РѕРґРёРЅР°РєРѕРІР°СЏ РєРЅРѕРїРєР° "Р РµРґР°РєС‚РёСЂРѕРІР°С‚СЊ" РєР°Рє РІ С‡РµСЂРЅРѕРІРёРєР°С…
const mainActionText = computed(() => {
  return 'Р РµРґР°РєС‚РёСЂРѕРІР°С‚СЊ'
})

// Р’СЃРµРіРґР° СЂРµРґР°РєС‚РёСЂРѕРІР°РЅРёРµ РєР°Рє РѕСЃРЅРѕРІРЅРѕРµ РґРµР№СЃС‚РІРёРµ
const handleMainAction = (event: Event) => {
  
  // Р‘РµР·РѕРїР°СЃРЅРѕ РѕСЃС‚Р°РЅР°РІР»РёРІР°РµРј РІСЃРїР»С‹С‚РёРµ СЃРѕР±С‹С‚РёСЏ
  if (event && typeof event.stopPropagation === 'function') {
    event.stopPropagation()
  }
  if (event && typeof event.preventDefault === 'function') {
    event.preventDefault()
  }
  
  // РќРµР±РѕР»СЊС€Р°СЏ Р·Р°РґРµСЂР¶РєР° С‡С‚РѕР±С‹ РјРѕРґР°Р»СЊРЅС‹Рµ РѕРєРЅР° СѓСЃРїРµР»Рё РѕС‚РєСЂС‹С‚СЊСЃСЏ
  setTimeout(() => {
    emit('edit', props.item)
  }, 200)
}

const toggleDropdown = () => {
  showDropdown.value = !showDropdown.value
}

const handleDelete = (event: any) => {
  // Р—Р°РєСЂС‹РІР°РµРј dropdown
  showDropdown.value = false
  // Р­РјРёС‚РёРј СЃРѕР±С‹С‚РёРµ delete
  emit('delete', props.item)
}

// Р—Р°РєСЂС‹С‚РёРµ dropdown РїСЂРё РєР»РёРєРµ РІРЅРµ
const handleClickOutside = (event: Event) => {
  if (!(event.target as Element)?.closest('.dropdown-container')) {
    showDropdown.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
</script>

<style scoped>
.item-actions {
  @apply mt-4;
}

.actions-container {
  @apply flex items-center gap-2;
}

.main-action-button {
  @apply px-4 py-2 bg-gray-100 text-gray-800 hover:bg-gray-200 rounded-lg transition-colors text-sm font-medium;
}

/* РљРѕРјРїР°РєС‚РЅС‹Р№ РґРёР·Р°Р№РЅ РєР°Рє РЅР° РђРІРёС‚Рѕ */
.actions-container {
  @apply justify-start;
}
</style>

