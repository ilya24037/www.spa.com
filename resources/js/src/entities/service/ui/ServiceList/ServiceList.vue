<template>
  <div class="servicelist">
    <!-- Loading state -->
    <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-for="i in 6" :key="i" class="animate-pulse">
        <div class="h-32 bg-gray-500 rounded-lg" />
      </div>
    </div>
    
    <!-- Error state -->
    <div v-else-if="error" class="text-center py-8">
      <p class="text-red-500 mb-4">
        {{ error }}
      </p>
      <PrimaryButton @click="$emit('retry')">
        Попробовать снова
      </PrimaryButton>
    </div>
    
    <!-- Empty state -->
    <div v-else-if="!items || items.length === 0" class="text-center py-12">
      <p class="text-gray-500 mb-4">
        {{ emptyMessage }}
      </p>
      <slot name="empty" />
    </div>
    
    <!-- List -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <slot
        v-for="item in items"
        :key="item.id"
        name="item"
        :item="item"
      >
        <div class="p-4 bg-white rounded-lg shadow">
          {{ item }}
        </div>
      </slot>
    </div>
    
    <!-- Pagination -->
    <div v-if="showPagination && totalPages > 1" class="mt-8 flex justify-center">
      <slot name="pagination" />
    </div>
  </div>
</template>

<script setup lang="ts">
import PrimaryButton from '@/src/shared/ui/atoms/PrimaryButton/PrimaryButton.vue'
interface Props {
  items?: any[]
  loading?: boolean
  error?: string
  emptyMessage?: string
  showPagination?: boolean
  totalPages?: number
}

withDefaults(defineProps<Props>(), {
    items: () => [],
    loading: false,
    error: '',
    emptyMessage: 'Нет данных для отображения',
    showPagination: false,
    totalPages: 1
})

defineEmits<{
  retry: []
}>()
</script>