<!-- resources/js/src/features/masters-filter/ui/FilterCategory/FilterCategory.vue -->
<template>
  <div :class="CONTAINER_CLASSES">
    <h4 :class="TITLE_CLASSES">Вид массажа</h4>
    
    <div :class="CATEGORIES_CONTAINER_CLASSES">
      <label
        v-for="category in categories"
        :key="category.id"
        :class="CATEGORY_ITEM_CLASSES"
      >
        <input
          type="checkbox"
          :value="category.id"
          :checked="isSelected(category.id)"
          @change="toggleCategory(category.id)"
          :class="CHECKBOX_CLASSES"
        >
        <div :class="CATEGORY_INFO_CLASSES">
          <span :class="CATEGORY_NAME_CLASSES">{{ category.name }}</span>
          <span v-if="category.masters_count" :class="CATEGORY_COUNT_CLASSES">
            {{ category.masters_count }}
          </span>
        </div>
      </label>
    </div>

    <!-- Показать выбранные -->
    <div v-if="hasSelected" :class="SELECTED_CONTAINER_CLASSES">
      <div :class="SELECTED_HEADER_CLASSES">
        <span :class="SELECTED_TITLE_CLASSES">Выбрано ({{ selectedCount }})</span>
        <button
          @click="clearSelection"
          :class="CLEAR_BUTTON_CLASSES"
        >
          Очистить
        </button>
      </div>
      <div :class="SELECTED_TAGS_CLASSES">
        <span
          v-for="categoryId in selected"
          :key="categoryId"
          :class="SELECTED_TAG_CLASSES"
        >
          {{ getCategoryName(categoryId) }}
          <button
            @click="removeCategory(categoryId)"
            :class="REMOVE_TAG_BUTTON_CLASSES"
          >
            ✕
          </button>
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

// 🎯 Стили согласно дизайн-системе
const CONTAINER_CLASSES = 'space-y-3'
const TITLE_CLASSES = 'font-medium text-gray-900'
const CATEGORIES_CONTAINER_CLASSES = 'space-y-2 max-h-48 overflow-y-auto'
const CATEGORY_ITEM_CLASSES = 'flex items-center cursor-pointer hover:bg-gray-50 p-2 rounded-lg transition-colors'
const CHECKBOX_CLASSES = 'mr-3 rounded text-blue-600 focus:ring-blue-500 flex-shrink-0'
const CATEGORY_INFO_CLASSES = 'flex items-center justify-between w-full'
const CATEGORY_NAME_CLASSES = 'text-sm text-gray-700'
const CATEGORY_COUNT_CLASSES = 'text-xs text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full'
const SELECTED_CONTAINER_CLASSES = 'mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg'
const SELECTED_HEADER_CLASSES = 'flex items-center justify-between mb-2'
const SELECTED_TITLE_CLASSES = 'text-sm font-medium text-blue-800'
const CLEAR_BUTTON_CLASSES = 'text-xs text-blue-600 hover:text-blue-800 font-medium'
const SELECTED_TAGS_CLASSES = 'flex flex-wrap gap-1'
const SELECTED_TAG_CLASSES = 'inline-flex items-center gap-1 px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full'
const REMOVE_TAG_BUTTON_CLASSES = 'text-blue-600 hover:text-blue-800 font-medium'

const props = defineProps({
  selected: {
    type: Array,
    default: () => []
  },
  categories: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['update'])

// Вычисляемые свойства
const hasSelected = computed(() => props.selected.length > 0)

const selectedCount = computed(() => props.selected.length)

// Методы
const isSelected = (categoryId) => {
  return props.selected.includes(categoryId)
}

const getCategoryName = (categoryId) => {
  const category = props.categories.find(cat => cat.id === categoryId)
  return category?.name || categoryId
}

const toggleCategory = (categoryId) => {
  const newSelected = [...props.selected]
  const index = newSelected.indexOf(categoryId)
  
  if (index >= 0) {
    newSelected.splice(index, 1)
  } else {
    newSelected.push(categoryId)
  }
  
  emit('update', newSelected)
}

const removeCategory = (categoryId) => {
  const newSelected = props.selected.filter(id => id !== categoryId)
  emit('update', newSelected)
}

const clearSelection = () => {
  emit('update', [])
}
</script>