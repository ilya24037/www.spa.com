<template>
  <div class="item-actions">
    <!-- Для объявлений ждущих оплаты -->
    <div v-if="item.status === 'waiting_payment'" class="waiting-payment-actions">
      <button @click="$emit('pay', item)" class="action-button secondary-button-flex">
        <span class="button-wrapper">
          <span class="button-text">Оплатить размещение</span>
        </span>
      </button>
      
      <ItemActionsDropdown 
        :showDropdown="showDropdown"
        @toggle="toggleDropdown"
        @pay="$emit('pay', item)"
        @deactivate="$emit('deactivate', item)"
        @edit="$emit('edit', item)"
        @delete="$emit('delete', item)"
      />
    </div>

    <!-- Для активных объявлений -->
    <template v-else-if="item.status === 'active'">
      <div class="waiting-payment-actions">
        <button @click="$emit('promote', item)" class="action-button secondary-button-flex">
          <span class="button-wrapper">
            <span class="button-text">Поднять просмотры</span>
          </span>
        </button>
        
        <ItemActionsDropdown 
          :showDropdown="showDropdown"
          @toggle="toggleDropdown"
          @promote="$emit('promote', item)"
          @edit="$emit('edit', item)"
          @deactivate="$emit('deactivate', item)"
          @delete="$emit('delete', item)"
        />
      </div>
    </template>
    
    <!-- Для черновиков -->
    <template v-else-if="item.status === 'draft'">
      <div class="waiting-payment-actions">
        <button @click="$emit('edit', item)" class="action-button secondary-button-flex">
          <span class="button-wrapper">
            <span class="button-text">Редактировать</span>
          </span>
        </button>
        
        <ItemActionsDropdown 
          :showDropdown="showDropdown"
          @toggle="toggleDropdown"
          @edit="$emit('edit', item)"
          @delete="$emit('delete', item)"
        />
      </div>
    </template>

    <!-- Для архивных объявлений -->
    <template v-else-if="item.status === 'archived'">
      <div class="waiting-payment-actions">
        <button @click="$emit('restore', item)" class="action-button secondary-button-flex">
          <span class="button-wrapper">
            <span class="button-text">Восстановить</span>
          </span>
        </button>
        
        <ItemActionsDropdown 
          :showDropdown="showDropdown"
          @toggle="toggleDropdown"
          @restore="$emit('restore', item)"
          @delete="$emit('delete', item)"
        />
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
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

.waiting-payment-actions {
  @apply flex gap-2;
}

.action-button {
  @apply flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors;
}

.secondary-button-flex {
  @apply bg-gray-100 text-gray-800 hover:bg-gray-200;
}

.button-wrapper {
  @apply flex items-center justify-center;
}

.button-text {
  @apply text-sm font-medium;
}
</style>