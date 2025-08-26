<template>
  <div class="dynamic-field-list">
    <!-- Р—Р°РіРѕР»РѕРІРѕРє -->
    <div class="dynamic-field-header">
      <div class="dynamic-field-title-wrapper">
        <h4 v-if="label" class="dynamic-field-label">
          {{ label }}
        </h4>
        <span v-if="showCount" class="dynamic-field-count">({{ itemCount }})</span>
      </div>
      
      <button
        type="button"
        :disabled="disabled || (maxItems && itemCount >= maxItems)"
        class="dynamic-field-add-btn"
        @click="addItem"
      >
        <svg
          class="w-4 h-4"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 4v16m8-8H4"
          />
        </svg>
        {{ addButtonText }}
      </button>
    </div>

    <p v-if="description" class="dynamic-field-description">
      {{ description }}
    </p>

    <!-- РџСѓСЃС‚РѕРµ СЃРѕСЃС‚РѕСЏРЅРёРµ -->
    <div 
      v-if="isEmpty" 
      class="dynamic-field-empty"
    >
      <div class="dynamic-field-empty-icon">
        <svg
          class="w-8 h-8 text-gray-500"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
          />
        </svg>
      </div>
      <p class="dynamic-field-empty-text">
        {{ emptyStateText }}
      </p>
      <button
        type="button"
        :disabled="disabled"
        class="dynamic-field-empty-btn"
        @click="addItem"
      >
        {{ addButtonText }}
      </button>
    </div>

    <!-- РЎРїРёСЃРѕРє СЌР»РµРјРµРЅС‚РѕРІ -->
    <div v-else class="dynamic-field-items">
      <TransitionGroup 
        name="dynamic-field-item" 
        tag="div"
        class="dynamic-field-items-container"
      >
        <div
          v-for="(item, index) in modelValue"
          :key="getItemKey(item, index)"
          class="dynamic-field-item"
        >
          <!-- Р—Р°РіРѕР»РѕРІРѕРє СЌР»РµРјРµРЅС‚Р° -->
          <div class="dynamic-field-item-header">
            <div class="dynamic-field-item-title">
              <span class="dynamic-field-item-number">{{ index + 1 }}.</span>
              <span v-if="getItemTitle(item, index)" class="dynamic-field-item-label">
                {{ getItemTitle(item, index) }}
              </span>
            </div>

            <div class="dynamic-field-item-actions">
              <!-- РљРЅРѕРїРєРё РїРµСЂРµРјРµС‰РµРЅРёСЏ -->
              <button
                v-if="sortable && itemCount > 1"
                type="button"
                :disabled="disabled || index === 0"
                class="dynamic-field-action-btn"
                title="РџРµСЂРµРјРµСЃС‚РёС‚СЊ РІРІРµСЂС…"
                @click="moveItem(index, index - 1)"
              >
                <svg
                  class="w-4 h-4"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M5 15l7-7 7 7"
                  />
                </svg>
              </button>

              <button
                v-if="sortable && itemCount > 1"
                type="button"
                :disabled="disabled || index === itemCount - 1"
                class="dynamic-field-action-btn"
                title="РџРµСЂРµРјРµСЃС‚РёС‚СЊ РІРЅРёР·"
                @click="moveItem(index, index + 1)"
              >
                <svg
                  class="w-4 h-4"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M19 9l-7 7-7-7"
                  />
                </svg>
              </button>

              <!-- РљРЅРѕРїРєР° РґСѓР±Р»РёСЂРѕРІР°РЅРёСЏ -->
              <button
                v-if="allowDuplicate"
                type="button"
                :disabled="disabled || (maxItems && itemCount >= maxItems)"
                class="dynamic-field-action-btn"
                title="Р”СѓР±Р»РёСЂРѕРІР°С‚СЊ"
                @click="duplicateItem(index)"
              >
                <svg
                  class="w-4 h-4"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"
                  />
                </svg>
              </button>

              <!-- РљРЅРѕРїРєР° СѓРґР°Р»РµРЅРёСЏ -->
              <button
                type="button"
                :disabled="disabled || (minItems && itemCount <= minItems)"
                class="dynamic-field-remove-btn"
                :title="removeButtonText"
                @click="removeItem(index)"
              >
                <svg
                  class="w-4 h-4"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                  />
                </svg>
              </button>
            </div>
          </div>

          <!-- РЎРѕРґРµСЂР¶РёРјРѕРµ СЌР»РµРјРµРЅС‚Р° -->
          <div class="dynamic-field-item-content">
            <slot 
              :item="item"
              :index="index"
              :disabled="disabled"
              :readonly="readonly"
              :update-item="(field: string, value: any) => updateItem(index, field, value)"
              :get-item-error="(field: string) => getItemError(index, field)"
            />
          </div>
        </div>
      </TransitionGroup>
    </div>

    <!-- РћРіСЂР°РЅРёС‡РµРЅРёСЏ -->
    <div v-if="showLimits" class="dynamic-field-limits">
      <div v-if="minItems" class="dynamic-field-limit">
        РњРёРЅРёРјСѓРј: {{ minItems }}
      </div>
      <div v-if="maxItems" class="dynamic-field-limit">
        РњР°РєСЃРёРјСѓРј: {{ maxItems }}
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useDynamicField } from '../composables/useForm'
import type { FormErrors } from '../types/form.types'

interface Props {
  modelValue: any[]
  label?: string
  description?: string
  errors?: FormErrors
  itemTemplate: () => any
  minItems?: number
  maxItems?: number
  disabled?: boolean
  readonly?: boolean
  addButtonText?: string
  removeButtonText?: string
  emptyStateText?: string
  sortable?: boolean
  allowDuplicate?: boolean
  showCount?: boolean
  showLimits?: boolean
  getItemKey?: (item: any, index: number) => string | number
  getItemTitle?: (item: any, index: number) => string
}

const props = withDefaults(defineProps<Props>(), {
    errors: () => ({}),
    disabled: false,
    readonly: false,
    addButtonText: 'Р”РѕР±Р°РІРёС‚СЊ',
    removeButtonText: 'РЈРґР°Р»РёС‚СЊ',
    emptyStateText: 'РќРµС‚ СЌР»РµРјРµРЅС‚РѕРІ. Р”РѕР±Р°РІСЊС‚Рµ РїРµСЂРІС‹Р№ СЌР»РµРјРµРЅС‚.',
    sortable: false,
    allowDuplicate: false,
    showCount: true,
    showLimits: false,
    getItemKey: (item: any, index: number) => index,
    getItemTitle: () => ''
})

const emit = defineEmits<{
  'update:modelValue': [value: any[]]
  'item-add': [item: any, index: number]
  'item-remove': [item: any, index: number]
  'item-move': [fromIndex: number, toIndex: number]
  'item-duplicate': [item: any, index: number]
  'item-change': [index: number, field: string, value: any]
}>()

// РСЃРїРѕР»СЊР·СѓРµРј composable РґР»СЏ РґРёРЅР°РјРёС‡РµСЃРєРёС… РїРѕР»РµР№
const modelValueRef = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
})

const {
    addItem,
    removeItem,
    moveItem,
    duplicateItem,
    isEmpty,
    itemCount
} = useDynamicField(modelValueRef, props.itemTemplate)

// РњРµС‚РѕРґС‹
const updateItem = (index: number, field: string, value: any) => {
    const newItems = [...props.modelValue]
    newItems[index] = { ...newItems[index], [field]: value }
    emit('update:modelValue', newItems)
    emit('item-change', index, field, value)
}

const getItemError = (index: number, field: string): string | undefined => {
    const errorKey = `${index}.${field}`
    const error = props.errors[errorKey]
    if (Array.isArray(error)) {
        return error[0]
    }
    return error
}

// РџРµСЂРµРѕРїСЂРµРґРµР»СЏРµРј РјРµС‚РѕРґС‹ РґР»СЏ РґРѕР±Р°РІР»РµРЅРёСЏ СЃРѕР±С‹С‚РёР№
const handleAddItem = () => {
    const newItemIndex = props.modelValue.length
    addItem()
    emit('item-add', props.modelValue[newItemIndex], newItemIndex)
}

const handleRemoveItem = (index: number) => {
    const item = props.modelValue[index]
    removeItem(index)
    emit('item-remove', item, index)
}

const handleMoveItem = (fromIndex: number, toIndex: number) => {
    moveItem(fromIndex, toIndex)
    emit('item-move', fromIndex, toIndex)
}

const handleDuplicateItem = (index: number) => {
    const item = props.modelValue[index]
    duplicateItem(index)
    emit('item-duplicate', item, index)
}
</script>

<style scoped>
.dynamic-field-list {
  @apply space-y-4;
}

.dynamic-field-header {
  @apply flex items-center justify-between;
}

.dynamic-field-title-wrapper {
  @apply flex items-center gap-2;
}

.dynamic-field-label {
  @apply text-sm font-medium text-gray-500;
}

.dynamic-field-count {
  @apply text-xs text-gray-500 bg-gray-500 px-2 py-1 rounded;
}

.dynamic-field-add-btn {
  @apply inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-blue-600 bg-blue-50 border border-blue-200 rounded-md hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 disabled:opacity-50 disabled:cursor-not-allowed;
}

.dynamic-field-description {
  @apply text-sm text-gray-500;
}

/* РџСѓСЃС‚РѕРµ СЃРѕСЃС‚РѕСЏРЅРёРµ */
.dynamic-field-empty {
  @apply text-center py-8 px-4 bg-gray-500 border border-gray-500 border-dashed rounded-lg;
}

.dynamic-field-empty-icon {
  @apply flex justify-center mb-3;
}

.dynamic-field-empty-text {
  @apply text-sm text-gray-500 mb-4;
}

.dynamic-field-empty-btn {
  @apply inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-blue-600 bg-white border border-blue-200 rounded-md hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1;
}

/* РЎРїРёСЃРѕРє СЌР»РµРјРµРЅС‚РѕРІ */
.dynamic-field-items-container {
  @apply space-y-4;
}

.dynamic-field-item {
  @apply border border-gray-500 rounded-lg bg-white shadow-sm;
}

.dynamic-field-item-header {
  @apply flex items-center justify-between px-4 py-3 bg-gray-500 border-b border-gray-500;
}

.dynamic-field-item-title {
  @apply flex items-center gap-2;
}

.dynamic-field-item-number {
  @apply text-sm font-medium text-gray-500;
}

.dynamic-field-item-label {
  @apply text-sm font-medium text-gray-500;
}

.dynamic-field-item-actions {
  @apply flex items-center gap-1;
}

.dynamic-field-action-btn {
  @apply p-1 text-gray-500 hover:text-gray-500 rounded transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 disabled:opacity-50 disabled:cursor-not-allowed;
}

.dynamic-field-remove-btn {
  @apply p-1 text-red-400 hover:text-red-600 rounded transition-colors focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-1 disabled:opacity-50 disabled:cursor-not-allowed;
}

.dynamic-field-item-content {
  @apply p-4 space-y-3;
}

/* РћРіСЂР°РЅРёС‡РµРЅРёСЏ */
.dynamic-field-limits {
  @apply flex gap-4 text-xs text-gray-500;
}

/* РђРЅРёРјР°С†РёРё */
.dynamic-field-item-enter-active,
.dynamic-field-item-leave-active {
  transition: all 0.3s ease;
}

.dynamic-field-item-enter-from {
  opacity: 0;
  transform: translateX(-20px);
}

.dynamic-field-item-leave-to {
  opacity: 0;
  transform: translateX(20px);
}

.dynamic-field-item-move {
  transition: transform 0.3s ease;
}

/* РђРґР°РїС‚РёРІРЅРѕСЃС‚СЊ */
@media (max-width: 640px) {
  .dynamic-field-header {
    @apply flex-col gap-3 items-stretch;
  }
  
  .dynamic-field-item-actions {
    @apply gap-0.5;
  }
  
  .dynamic-field-action-btn,
  .dynamic-field-remove-btn {
    @apply p-0.5;
  }
  
  .dynamic-field-action-btn svg,
  .dynamic-field-remove-btn svg {
    @apply w-3 h-3;
  }
}

/* РЈРјРµРЅСЊС€РµРЅРЅР°СЏ Р°РЅРёРјР°С†РёСЏ */
@media (prefers-reduced-motion: reduce) {
  .dynamic-field-item-enter-active,
  .dynamic-field-item-leave-active,
  .dynamic-field-item-move {
    transition: none;
  }
}
</style>

