<template>
  <div class="dropdown-container" ref="dropdown">
    <button 
      type="button" 
      class="dropdown-button-inline"
      @click="$emit('toggle')"
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
    
    <div v-if="showDropdown" class="dropdown-menu">
      <a v-if="$attrs.onPay" href="#" class="dropdown-item" @click.prevent="$emit('pay')">
        Оплатить размещение
      </a>
      <a v-if="$attrs.onPromote" href="#" class="dropdown-item" @click.prevent="$emit('promote')">
        Поднять просмотры
      </a>
      <a v-if="$attrs.onEdit" href="#" class="dropdown-item" @click.prevent="$emit('edit')">
        Редактировать
      </a>
      <a v-if="$attrs.onRestore" href="#" class="dropdown-item" @click.prevent="$emit('restore')">
        Восстановить
      </a>
      <a v-if="$attrs.onDeactivate" href="#" class="dropdown-item" @click.prevent="$emit('deactivate')">
        {{ $attrs.onRestore ? 'Архивировать' : 'Снять с публикации' }}
      </a>
      <a v-if="$attrs.onDelete" href="#" class="dropdown-item danger-item" @click.stop.prevent="$emit('delete')">
        Удалить
      </a>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  showDropdown: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits([
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
  @apply p-2 text-gray-400 hover:text-gray-600 transition-colors;
}

.dropdown-button-wrapper {
  @apply flex items-center justify-center;
}

.dropdown-icon {
  @apply w-5 h-5;
}

.dropdown-menu {
  @apply absolute right-0 top-full mt-1 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50;
}

.dropdown-item {
  @apply block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors;
}

.danger-item {
  @apply text-red-600 hover:bg-red-50;
}
</style>