<!--
  Р РµС„Р°РєС‚РѕСЂРёСЂРѕРІР°РЅРЅС‹Р№ РєРѕРјРїРѕРЅРµРЅС‚ РєР°СЂС‚РѕС‡РєРё СѓСЃР»СѓРіРё РІ СЃС‚РёР»Рµ Avito
  Р Р°Р·Р±РёС‚ РЅР° РїРµСЂРµРёСЃРїРѕР»СЊР·СѓРµРјС‹Рµ РїРѕРґРєРѕРјРїРѕРЅРµРЅС‚С‹ РґР»СЏ Р»СѓС‡С€РµР№ maintainability
  РЎ РёР·РѕР±СЂР°Р¶РµРЅРёРµРј РІ СЃС‚РёР»Рµ Ozon
-->
<template>
  <div 
    class="avito-item-snippet hover:shadow-lg transition-shadow" 
    @click="handleContainerClick"
    role="article"
    :aria-label="`РћР±СЉСЏРІР»РµРЅРёРµ: ${props.item.title || props.item.name || props.item.display_name}`"
    data-testid="item-card"
  >
    <div class="item-snippet-content">
      <!-- РР·РѕР±СЂР°Р¶РµРЅРёРµ РІ СЃС‚РёР»Рµ Ozon -->
      <Link 
        :href="itemUrl" 
        class="item-image-container relative cursor-pointer"
        :aria-label="`РџРѕСЃРјРѕС‚СЂРµС‚СЊ РѕР±СЉСЏРІР»РµРЅРёРµ ${props.item.title || props.item.name}`"
        data-testid="item-image-link"
      >
        <ItemImage 
          :item="item"
          :itemUrl="itemUrl"
        />
      </Link>

      <!-- РћСЃРЅРѕРІРЅРѕР№ РєРѕРЅС‚РµРЅС‚ -->
      <Link 
        :href="itemUrl" 
        class="item-content-link cursor-pointer"
        :aria-label="`РћС‚РєСЂС‹С‚СЊ РѕР±СЉСЏРІР»РµРЅРёРµ ${props.item.title || props.item.name}`"
        data-testid="item-content-link"
      >
        <ItemContent 
          :item="item"
          :itemUrl="itemUrl"
        />
      </Link>

      <!-- РЎС‚Р°С‚РёСЃС‚РёРєР° Рё РґРµР№СЃС‚РІРёСЏ (РќР• РєР»РёРєР°Р±РµР»СЊРЅС‹Рµ) -->
      <div class="item-info-section">
        <div class="item-info-top">
          <ItemStats :item="item" />
        </div>
        
        <!-- Р”РµР№СЃС‚РІРёСЏ РЅР° СѓСЂРѕРІРЅРµ РЅРёР·Р° С„РѕС‚Рѕ -->
        <div class="item-actions-bottom">
          <ItemActions 
            :item="item"
            @pay="payItem"
            @promote="promoteItem"
            @edit="editItem"
            @deactivate="deactivateItem"
            @delete="handleDeleteClick"
          />
        </div>
      </div>
    </div>
  </div>

  <!-- РњРѕРґР°Р»СЊРЅРѕРµ РѕРєРЅРѕ РїРѕРґС‚РІРµСЂР¶РґРµРЅРёСЏ СѓРґР°Р»РµРЅРёСЏ -->
  <ConfirmModal
      :show="showDeleteModal"
      title="РЈРґР°Р»РёС‚СЊ РѕР±СЉСЏРІР»РµРЅРёРµ?"
      message="Р­С‚Рѕ РґРµР№СЃС‚РІРёРµ РЅРµР»СЊР·СЏ РѕС‚РјРµРЅРёС‚СЊ. РћР±СЉСЏРІР»РµРЅРёРµ Р±СѓРґРµС‚ СѓРґР°Р»РµРЅРѕ РЅР°РІСЃРµРіРґР°."
      confirmText="РЈРґР°Р»РёС‚СЊ"
      cancelText="РћС‚РјРµРЅР°"
      @confirm="deleteItem"
      @cancel="showDeleteModal = false"
      data-testid="delete-modal"
    />
</template>

<script setup lang="ts">
import { ref, computed, withDefaults, type} from 'vue'
import { router } from '@inertiajs/vue3'
import ItemImage from './ItemImage.vue'
import ItemContent from './ItemContent.vue'
import ItemStats from './ItemStats.vue'
import ItemActions from './ItemActions.vue'
import ConfirmModal from '@/src/shared/ui/organisms/Modal/Modal.vue'
import { Link } from '@inertiajs/vue3'
import { useToast } from '@/src/shared/composables/useToast'
import type { 
  ItemCardProps, 
  ItemCardEmits,
  ClickEvent
} from './ItemCard.types'

// Toast РґР»СЏ Р·Р°РјРµРЅС‹ alert()
const toast = useToast()

// Props
const _props = withDefaults(defineProps<ItemCardProps>(), {})

// Emits  
const _emit = defineEmits<ItemCardEmits>()

// РЎРѕСЃС‚РѕСЏРЅРёРµ РєРѕРјРїРѕРЅРµРЅС‚Р°
const showDeleteModal = ref<boolean>(false)

// Р’С‹С‡РёСЃР»СЏРµРјС‹Рµ СЃРІРѕР№СЃС‚РІР°
const itemUrl = computed((): string => {
  if (props.item.status === 'draft') {
    return `/draft/${props.item.id}`
  }
  return `/ads/${props.item.id}`
})

// РњРµС‚РѕРґС‹ РґРµР№СЃС‚РІРёР№
const payItem = (): void => {
  try {
    router.visit(`/payment/select-plan?item_id=${props.item.id}`)
  } catch (error: unknown) {
    const errorMessage = error instanceof Error ? error.message : 'РќРµРёР·РІРµСЃС‚РЅР°СЏ РѕС€РёР±РєР°'
    toast.error('РћС€РёР±РєР° РѕРїР»Р°С‚С‹: ' + errorMessage)
  }
}

const promoteItem = (): void => {
  try {
    router.visit(`/payment/promotion?item_id=${props.item.id}`)
  } catch (error: unknown) {
    const errorMessage = error instanceof Error ? error.message : 'РќРµРёР·РІРµСЃС‚РЅР°СЏ РѕС€РёР±РєР°'
    toast.error('РћС€РёР±РєР° РїСЂРѕРґРІРёР¶РµРЅРёСЏ: ' + errorMessage)
  }
}

const editItem = (): void => {
  try {
    
    // Р•СЃР»Рё РјРѕРґР°Р»СЊРЅРѕРµ РѕРєРЅРѕ РѕС‚РєСЂС‹С‚Рѕ, РќР• СЂРµРґР°РєС‚РёСЂСѓРµРј
    if (showDeleteModal.value) {
      return
    }
    
    // Р”Р»СЏ РІСЃРµС… РѕР±СЉСЏРІР»РµРЅРёР№ (РІРєР»СЋС‡Р°СЏ С‡РµСЂРЅРѕРІРёРєРё) РёСЃРїРѕР»СЊР·СѓРµРј РѕРґРёРЅ СЂРѕСѓС‚
    router.visit(`/ads/${props.item.id}/edit`)
  } catch (error: unknown) {
    const errorMessage = error instanceof Error ? error.message : 'РќРµРёР·РІРµСЃС‚РЅР°СЏ РѕС€РёР±РєР°'
    toast.error('РћС€РёР±РєР° СЂРµРґР°РєС‚РёСЂРѕРІР°РЅРёСЏ: ' + errorMessage)
  }
}

const deactivateItem = async (): Promise<void> => {
  try {
    // РСЃРїРѕР»СЊР·СѓРµРј РїСЂР°РІРёР»СЊРЅС‹Р№ СЂРѕСѓС‚ С‡РµСЂРµР· router
    await router.post(`/my-ads/${props.item.id}/deactivate`, {}, {
      preserveState: true,
      onSuccess: () => {
        const updatedItem = { ...props.item, status: 'archived' as const }
        emit('item-updated', updatedItem)
        toast.success('РћР±СЉСЏРІР»РµРЅРёРµ РґРµР°РєС‚РёРІРёСЂРѕРІР°РЅРѕ')
      },
      onError: (errors) => {
        const errorMessage = typeof errors === 'string' ? errors : 'РћС€РёР±РєР° РґРµР°РєС‚РёРІР°С†РёРё'
        toast.error(errorMessage)
      }
    })
  } catch (error: unknown) {
    const errorMessage = error instanceof Error ? error.message : 'РќРµРёР·РІРµСЃС‚РЅР°СЏ РѕС€РёР±РєР°'
    toast.error('РћС€РёР±РєР° РґРµР°РєС‚РёРІР°С†РёРё: ' + errorMessage)
  }
}

const restoreItem = async (): Promise<void> => {
  try {
    // РСЃРїРѕР»СЊР·СѓРµРј РїСЂР°РІРёР»СЊРЅС‹Р№ СЂРѕСѓС‚ С‡РµСЂРµР· router
    await router.post(`/my-ads/${props.item.id}/restore`, {}, {
      preserveState: true,
      onSuccess: () => {
        const updatedItem = { ...props.item, status: 'active' as const }
        emit('item-updated', updatedItem)
        toast.success('РћР±СЉСЏРІР»РµРЅРёРµ РІРѕСЃСЃС‚Р°РЅРѕРІР»РµРЅРѕ')
      },
      onError: (errors) => {
        const errorMessage = typeof errors === 'string' ? errors : 'РћС€РёР±РєР° РІРѕСЃСЃС‚Р°РЅРѕРІР»РµРЅРёСЏ'
        toast.error(errorMessage)
      }
    })
  } catch (error: unknown) {
    const errorMessage = error instanceof Error ? error.message : 'РќРµРёР·РІРµСЃС‚РЅР°СЏ РѕС€РёР±РєР°'
    toast.error('РћС€РёР±РєР° РІРѕСЃСЃС‚Р°РЅРѕРІР»РµРЅРёСЏ: ' + errorMessage)
  }
}

const handleContainerClick = (event: ClickEvent): void => {
}

const handleDeleteClick = (event: ClickEvent): void => {
  
  // Р‘РµР·РѕРїР°СЃРЅРѕ РѕСЃС‚Р°РЅР°РІР»РёРІР°РµРј РІСЃРїР»С‹С‚РёРµ СЃРѕР±С‹С‚РёСЏ С‡С‚РѕР±С‹ РЅРµ СЃСЂР°Р±РѕС‚Р°Р» Link
  if (event && typeof event.stopPropagation === 'function') {
    event.stopPropagation()
  }
  if (event && typeof event.preventDefault === 'function') {
    event.preventDefault()
  }
  
  showDeleteModal.value = true
}

const deleteItem = async (): Promise<void> => {
  try {
    
    // Р’С‹Р±РёСЂР°РµРј РїСЂР°РІРёР»СЊРЅС‹Р№ СЂРѕСѓС‚ РІ Р·Р°РІРёСЃРёРјРѕСЃС‚Рё РѕС‚ С‚РёРїР° РѕР±СЉСЏРІР»РµРЅРёСЏ
    const deleteUrl = props.item.status === 'draft' 
      ? `/draft/${props.item.id}` 
      : `/my-ads/${props.item.id}`
    
    
    // РСЃРїРѕР»СЊР·СѓРµРј РїСЂР°РІРёР»СЊРЅС‹Р№ СЂРѕСѓС‚ С‡РµСЂРµР· router РґР»СЏ СѓРґР°Р»РµРЅРёСЏ
    await router.delete(deleteUrl, {
      preserveScroll: false,
      preserveState: false,
      onStart: () => {
      },
      onSuccess: (_page) => {
        
        // Р­РјРёС‚РёРј СЃРѕР±С‹С‚РёРµ РґР»СЏ РѕР±РЅРѕРІР»РµРЅРёСЏ СЃРїРёСЃРєР°
        emit('item-deleted', props.item.id)
        showDeleteModal.value = false
        toast.success('РћР±СЉСЏРІР»РµРЅРёРµ СѓРґР°Р»РµРЅРѕ')
      },
      onError: (errors) => {
        
        const errorMessage = typeof errors === 'object' && errors !== null && 'message' in errors
          ? String(errors.message)
          : 'РћС€РёР±РєР° СѓРґР°Р»РµРЅРёСЏ РѕР±СЉСЏРІР»РµРЅРёСЏ'
        
        toast.error(errorMessage)
        showDeleteModal.value = false
      },
      onFinish: () => {
      }
    })
  } catch (error: unknown) {
    const errorMessage = error instanceof Error ? error.message : 'РќРµРёР·РІРµСЃС‚РЅР°СЏ РѕС€РёР±РєР°'
    toast.error('РћС€РёР±РєР° СѓРґР°Р»РµРЅРёСЏ: ' + errorMessage)
    showDeleteModal.value = false
  }
}
</script>

<style scoped>
.avito-item-snippet {
  @apply bg-white border border-gray-200 mb-4 relative;
  border-radius: 16px; /* РљР°Рє РЅР° Ozon */
  padding: 0; /* РЈР±РёСЂР°РµРј padding РєР°Рє РЅР° Ozon */
  height: fit-content; /* РЎС‚СЂРѕРіРѕ РїРѕ РєРѕРЅС‚РµРЅС‚Сѓ */
  overflow: visible; /* РР·РјРµРЅСЏРµРј РЅР° visible РґР»СЏ dropdown */
}

.item-snippet-content {
  @apply flex;
  align-items: flex-start;
  height: 256px; /* Р¤РѕС‚Рѕ + СЂР°РІРЅРѕРјРµСЂРЅС‹Рµ РѕС‚СЃС‚СѓРїС‹ 12px */
  padding: 12px; /* Р Р°РІРЅРѕРјРµСЂРЅС‹Рµ РѕС‚СЃС‚СѓРїС‹ 12px СЃРѕ РІСЃРµС… СЃС‚РѕСЂРѕРЅ */
  gap: 12px; /* РЈРјРµРЅСЊС€Р°РµРј gap */
}

.item-image-container {
  @apply relative;
  @apply overflow-hidden; /* РљР°Рє РЅР° Ozon - РѕР±СЂРµР·Р°РµРј */
  width: 160px; /* РЁРёСЂРёРЅР° РєР°Рє РЅР° Ozon */
  height: 232px; /* РўРѕС‡РЅРѕ РїРѕ СЂР°Р·РјРµСЂСѓ Р±РµР· Р»РёС€РЅРёС… РѕС‚СЃС‚СѓРїРѕРІ */
  border-radius: 12px; /* РЎРєСЂСѓРіР»РµРЅРёРµ С„РѕС‚Рѕ */
  flex-shrink: 0;
  display: block; /* Р”Р»СЏ РєРѕСЂСЂРµРєС‚РЅРѕРіРѕ РѕС‚РѕР±СЂР°Р¶РµРЅРёСЏ Link */
}

.item-content-link {
  display: block; /* Р”Р»СЏ РєРѕСЂСЂРµРєС‚РЅРѕРіРѕ РѕС‚РѕР±СЂР°Р¶РµРЅРёСЏ Link */
  flex: 1; /* Р—Р°РЅРёРјР°РµС‚ РѕСЃС‚Р°РІС€РµРµСЃСЏ РјРµСЃС‚Рѕ */
}

.item-info-section {
  @apply flex flex-col justify-between flex-shrink-0;
  width: 200px; /* РћРїС‚РёРјР°Р»СЊРЅР°СЏ С€РёСЂРёРЅР° РїСЂР°РІРѕР№ РєРѕР»РѕРЅРєРё */
  height: 232px; /* Р’С‹СЃРѕС‚Р° РєРѕРЅС‚РµРЅС‚Р° СЃ РѕС‚СЃС‚СѓРїР°РјРё 12px */
}

/* РљРЅРѕРїРєРё СЃ Р±РѕР»СЊС€РёРј РѕС‚СЃС‚СѓРїРѕРј СЃРЅРёР·Сѓ */
.item-actions-bottom {
  margin-bottom: 30px; /* Р•С‰Рµ Р±РѕР»СЊС€РёР№ РѕС‚СЃС‚СѓРї СЃРЅРёР·Сѓ */
  position: relative; /* Р”Р»СЏ РєРѕСЂСЂРµРєС‚РЅРѕРіРѕ РїРѕР·РёС†РёРѕРЅРёСЂРѕРІР°РЅРёСЏ dropdown */
}

/* Responsive */
@media (max-width: 768px) {
  .item-snippet-content {
    @apply flex-col gap-3;
  }
  
  .item-info-section {
    @apply w-full;
  }
}
</style>


