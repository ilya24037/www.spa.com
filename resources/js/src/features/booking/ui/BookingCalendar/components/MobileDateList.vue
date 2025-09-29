<template>
  <div 
    v-if="showMobileList"
    class="mobile-date-list"
    :class="{ 'mobile-date-list--compact': compact }"
  >
    <!-- Р—Р°РіРѕР»РѕРІРѕРє -->
    <div class="mobile-date-list-header">
      <h3 class="mobile-date-list-title">
        {{ title }}
      </h3>
      <div v-if="availableDates.length > maxVisibleDates" class="mobile-date-list-count">
        Показано {{ Math.min(maxVisibleDates, availableDates.length) }} из {{ availableDates.length }}
      </div>
    </div>

    <!-- РЎРїРёСЃРѕРє РґР°С‚ -->
    <div class="mobile-date-list-content">
      <div
        v-if="availableDates.length === 0"
        class="mobile-date-list-empty"
      >
        <div class="mobile-date-list-empty-icon">
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
              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
            />
          </svg>
        </div>
        <p class="mobile-date-list-empty-text">
          {{ emptyStateText }}
        </p>
        <button
          v-if="onRefresh"
          class="mobile-date-list-refresh"
          @click="onRefresh"
        >
          РћР±РЅРѕРІРёС‚СЊ
        </button>
      </div>

      <div
        v-else
        class="mobile-date-list-items"
        role="list"
        :aria-label="title"
      >
        <button
          v-for="dateItem in visibleDates"
          :key="dateItem.dateString"
          type="button"
          class="mobile-date-item"
          :class="{
            'mobile-date-item--selected': dateItem.isSelected,
            'mobile-date-item--disabled': disabled
          }"
          :disabled="disabled"
          role="listitem"
          :aria-selected="dateItem.isSelected"
          :aria-label="`Р’С‹Р±СЂР°С‚СЊ ${dateItem.displayText}, ${dateItem.availableSlots}`"
          @click="handleDateSelect(dateItem)"
        >
          <div class="mobile-date-item-content">
            <!-- РћСЃРЅРѕРІРЅР°СЏ РёРЅС„РѕСЂРјР°С†РёСЏ -->
            <div class="mobile-date-item-main">
              <div class="mobile-date-item-date">
                {{ dateItem.displayText }}
              </div>
              <div class="mobile-date-item-slots">
                {{ dateItem.availableSlots }}
              </div>
            </div>

            <!-- РРЅРґРёРєР°С‚РѕСЂ СЃС‚Р°С‚СѓСЃР° -->
            <div class="mobile-date-item-status">
              <div 
                class="mobile-date-item-indicator"
                :class="getStatusClasses(dateItem.dateString)"
              />
            </div>

            <!-- Р§РµРєР±РѕРєСЃ РІС‹Р±РѕСЂР° -->
            <div class="mobile-date-item-check">
              <svg 
                v-if="dateItem.isSelected"
                class="w-5 h-5 text-blue-600" 
                fill="currentColor" 
                viewBox="0 0 20 20"
                aria-hidden="true"
              >
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
              </svg>
            </div>
          </div>

          <!-- Р”РѕРїРѕР»РЅРёС‚РµР»СЊРЅР°СЏ РёРЅС„РѕСЂРјР°С†РёСЏ -->
          <div v-if="showAdditionalInfo && getAdditionalInfo(dateItem)" class="mobile-date-item-extra">
            {{ getAdditionalInfo(dateItem) }}
          </div>
        </button>
      </div>

      <!-- Кнопка "Показать больше" -->
      <button
        v-if="availableDates.length > maxVisibleDates && !showAll"
        class="mobile-date-list-show-more"
        @click="showAll = true"
      >
        Показать еще {{ availableDates.length - maxVisibleDates }} дат
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import type { AvailableDateItem, DateAvailabilityStatus } from '../../../model/calendar.types'

interface Props {
  availableDates: AvailableDateItem[]
  selectedDate?: string | null
  title?: string
  emptyStateText?: string
  showMobileList?: boolean
  maxVisibleDates?: number
  compact?: boolean
  disabled?: boolean
  showAdditionalInfo?: boolean
  getStatusForDate?: (dateString: string) => DateAvailabilityStatus
  getAdditionalInfoForDate?: (dateString: string) => string
  onRefresh?: () => void
}

const props = withDefaults(defineProps<Props>(), {
    title: 'Р‘Р»РёР¶Р°Р№С€РёРµ РґРѕСЃС‚СѓРїРЅС‹Рµ РґР°С‚С‹',
    emptyStateText: 'РќРµС‚ РґРѕСЃС‚СѓРїРЅС‹С… РґР°С‚ РґР»СЏ Р±СЂРѕРЅРёСЂРѕРІР°РЅРёСЏ',
    showMobileList: true,
    maxVisibleDates: 5,
    compact: false,
    disabled: false,
    showAdditionalInfo: false,
    selectedDate: null
})

const emit = defineEmits<{
  dateSelect: [dateString: string]
  refresh: []
}>()

// РЎРѕСЃС‚РѕСЏРЅРёРµ
const showAll = ref(false)

// Р’С‹С‡РёСЃР»СЏРµРјС‹Рµ СЃРІРѕР№СЃС‚РІР°
const visibleDates = computed(() => {
    if (showAll.value || props.availableDates.length <= props.maxVisibleDates) {
        return props.availableDates
    }
    return props.availableDates.slice(0, props.maxVisibleDates)
})

// РћР±СЂР°Р±РѕС‚С‡РёРєРё
const handleDateSelect = (dateItem: AvailableDateItem) => {
    if (props.disabled) return
    emit('dateSelect', dateItem.dateString)
}

// РџРѕР»СѓС‡РµРЅРёРµ CSS РєР»Р°СЃСЃРѕРІ СЃС‚Р°С‚СѓСЃР°
const getStatusClasses = (dateString: string): string => {
    if (!props.getStatusForDate) return 'mobile-date-item-indicator--available'
  
    const status = props.getStatusForDate(dateString)
    return `mobile-date-item-indicator--${status}`
}

// РџРѕР»СѓС‡РµРЅРёРµ РґРѕРїРѕР»РЅРёС‚РµР»СЊРЅРѕР№ РёРЅС„РѕСЂРјР°С†РёРё
const getAdditionalInfo = (dateItem: AvailableDateItem): string => {
    if (!props.getAdditionalInfoForDate) return ''
    return props.getAdditionalInfoForDate(dateItem.dateString)
}
</script>

<style scoped>
.mobile-date-list {
  @apply mt-4 md:hidden;
}

.mobile-date-list--compact {
  @apply mt-2;
}

.mobile-date-list-header {
  @apply mb-3;
}

.mobile-date-list-title {
  @apply text-sm font-medium text-gray-500 mb-1;
}

.mobile-date-list-count {
  @apply text-xs text-gray-500;
}

.mobile-date-list-content {
  @apply space-y-2;
}

/* РџСѓСЃС‚РѕРµ СЃРѕСЃС‚РѕСЏРЅРёРµ */
.mobile-date-list-empty {
  @apply text-center py-8 px-4;
}

.mobile-date-list-empty-icon {
  @apply flex justify-center mb-3;
}

.mobile-date-list-empty-text {
  @apply text-sm text-gray-500 mb-4;
}

.mobile-date-list-refresh {
  @apply px-4 py-2 text-sm font-medium text-blue-600 bg-blue-50 border border-blue-200 rounded-md hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1;
}

/* РЎРїРёСЃРѕРє РґР°С‚ */
.mobile-date-list-items {
  @apply space-y-2;
}

/* Р­Р»РµРјРµРЅС‚ РґР°С‚С‹ */
.mobile-date-item {
  @apply w-full p-3 text-left border rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1;
}

.mobile-date-item:not(.mobile-date-item--disabled):hover {
  @apply border-blue-300 bg-blue-50;
}

.mobile-date-item--selected {
  @apply border-blue-600 bg-blue-50 ring-1 ring-blue-600;
}

.mobile-date-item--disabled {
  @apply opacity-50 cursor-not-allowed;
}

.mobile-date-item-content {
  @apply flex items-center justify-between;
}

.mobile-date-item-main {
  @apply flex-1 min-w-0;
}

.mobile-date-item-date {
  @apply font-medium text-gray-500;
}

.mobile-date-item-slots {
  @apply text-sm text-gray-500 mt-1;
}

.mobile-date-item-status {
  @apply flex items-center mx-3;
}

.mobile-date-item-indicator {
  @apply w-3 h-3 rounded-full;
}

.mobile-date-item-indicator--available {
  @apply bg-green-500;
}

.mobile-date-item-indicator--busy {
  @apply bg-yellow-500;
}

.mobile-date-item-indicator--full {
  @apply bg-red-500;
}

.mobile-date-item-indicator--unavailable {
  @apply bg-gray-500;
}

.mobile-date-item-check {
  @apply flex items-center;
}

.mobile-date-item-extra {
  @apply mt-2 pt-2 border-t border-gray-500 text-xs text-gray-500;
}

    /* Кнопка "Показать больше" */
.mobile-date-list-show-more {
  @apply w-full py-3 text-sm font-medium text-blue-600 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1;
}

/* РљРѕРјРїР°РєС‚РЅС‹Р№ СЂРµР¶РёРј */
.mobile-date-list--compact .mobile-date-item {
  @apply p-2;
}

.mobile-date-list--compact .mobile-date-item-date {
  @apply text-sm;
}

.mobile-date-list--compact .mobile-date-item-slots {
  @apply text-xs;
}

/* РђРЅРёРјР°С†РёРё */
.mobile-date-item {
  transition: all 0.2s ease;
}

.mobile-date-item:active:not(.mobile-date-item--disabled) {
  transform: scale(0.98);
}

/* Р’С‹СЃРѕРєРёР№ РєРѕРЅС‚СЂР°СЃС‚ */
@media (prefers-contrast: high) {
  .mobile-date-item {
    @apply border-2 border-gray-500;
  }
  
  .mobile-date-item--selected {
    @apply border-2 border-blue-800;
  }
  
  .mobile-date-item-indicator {
    @apply ring-2 ring-gray-500;
  }
}

/* РЈРјРµРЅСЊС€РµРЅРЅР°СЏ Р°РЅРёРјР°С†РёСЏ */
@media (prefers-reduced-motion: reduce) {
  .mobile-date-item {
    transition: none;
  }
  
  .mobile-date-item:active:not(.mobile-date-item--disabled) {
    transform: none;
  }
}

/* РўРµРјРЅР°СЏ С‚РµРјР° */
@media (prefers-color-scheme: dark) {
  .mobile-date-list-title {
    @apply text-gray-500;
  }
  
  .mobile-date-item {
    @apply border-gray-500 bg-gray-500;
  }
  
  .mobile-date-item:not(.mobile-date-item--disabled):hover {
    @apply border-blue-400 bg-gray-500;
  }
  
  .mobile-date-item--selected {
    @apply border-blue-400 bg-gray-500;
  }
  
  .mobile-date-item-date {
    @apply text-gray-500;
  }
  
  .mobile-date-item-slots {
    @apply text-gray-500;
  }
}
</style>

