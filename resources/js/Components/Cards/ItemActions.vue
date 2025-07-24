<template>
  <div class="item-actions">
    <!-- Единообразный дизайн для всех статусов -->
    <div class="actions-container">
             <!-- Основная кнопка слева (как на Авито) -->
       <button @click="handleMainAction" class="main-action-button">
         {{ mainActionText }}
       </button>
      
      <!-- Троеточие справа (всегда) -->
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
          @delete="$emit('delete', item)"
        />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import ItemActionsDropdown from './ItemActionsDropdown.vue'

const props = defineProps({
  item: {
    type: Object,
    required: true
  }
})

const emit = defineEmits([
  'pay',
  'promote', 
  'edit',
  'deactivate',
  'restore',
  'delete'
])

const showDropdown = ref(false)

// Везде одинаковая кнопка "Редактировать" как в черновиках
const mainActionText = computed(() => {
  return 'Редактировать'
})

// Всегда редактирование как основное действие
const handleMainAction = () => {
  emit('edit', props.item)
}

const toggleDropdown = () => {
  showDropdown.value = !showDropdown.value
}

// Закрытие dropdown при клике вне
const handleClickOutside = (event) => {
  if (!event.target.closest('.dropdown-container')) {
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

/* Компактный дизайн как на Авито */
.actions-container {
  @apply justify-start;
}
</style>