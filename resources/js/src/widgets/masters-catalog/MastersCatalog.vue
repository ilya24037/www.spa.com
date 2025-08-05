<template>
  <div class="masters-catalog">
    <!-- Filters -->
    <div class="mb-6">
      <slot name="filters" />
    </div>
    
    <!-- Loading -->
    <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div v-for="i in 6" :key="i" class="animate-pulse">
        <div class="h-64 bg-gray-200 rounded-lg"></div>
      </div>
    </div>
    
    <!-- Error -->
    <div v-else-if="error" class="text-center py-12">
      <p class="text-red-500 mb-4">{{ error }}</p>
      <button @click="$emit('retry')" class="px-4 py-2 bg-blue-600 text-white rounded-lg">
        Попробовать снова
      </button>
    </div>
    
    <!-- Empty -->
    <div v-else-if="!masters || masters.length === 0" class="text-center py-12">
      <p class="text-gray-500 text-lg mb-4">Мастера не найдены</p>
      <p class="text-gray-400">Попробуйте изменить параметры поиска</p>
    </div>
    
    <!-- Grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <slot v-for="master in masters" :key="master.id" name="master" :master="master">
        <MasterCard :master="master" />
      </slot>
    </div>
    
    <!-- Pagination -->
    <div v-if="showPagination" class="mt-8">
      <slot name="pagination" />
    </div>
  </div>
</template>

<script setup lang="ts">
import MasterCard from '@/src/entities/master/ui/MasterCard/MasterCard.vue'

interface Props {
  masters?: any[]
  loading?: boolean
  error?: string
  showPagination?: boolean
}

withDefaults(defineProps<Props>(), {
  masters: () => [],
  loading: false,
  error: '',
  showPagination: false
})

defineEmits<{
  retry: []
}>()
</script>