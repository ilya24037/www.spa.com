<template>
  <div class="map-states">
    <MapSkeleton v-if="isLoading" v-bind="skeletonProps" />
    <MapErrorState v-else-if="error" v-bind="errorProps" @retry="$emit('retry')" />
    <MapEmptyState v-else-if="isEmpty" v-bind="emptyProps">
      <template v-if="$slots['empty-action']" #action>
        <slot name="empty-action" />
      </template>
    </MapEmptyState>
    <slot v-else />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import MapSkeleton from '../MapSkeleton/MapSkeleton.vue'
import MapErrorState from '../MapErrorState/MapErrorState.vue'
import MapEmptyState from '../MapEmptyState/MapEmptyState.vue'

interface Props {
  isLoading?: boolean
  error?: string | null
  errorDetails?: string | null
  isEmpty?: boolean
  height?: number
  showGeolocation?: boolean
  showRetry?: boolean
  loadingText?: string
  errorTitle?: string
  emptyTitle?: string
  emptyMessage?: string
}

const props = withDefaults(defineProps<Props>(), {
  isLoading: false,
  error: null,
  errorDetails: null,
  isEmpty: false,
  height: 400,
  showGeolocation: false,
  showRetry: true,
  loadingText: 'Загрузка карты...',
  errorTitle: 'Ошибка загрузки карты',
  emptyTitle: 'Нет данных',
  emptyMessage: 'Нет маркеров для отображения'
})

defineEmits<{ retry: [] }>()

const skeletonProps = computed(() => ({
  height: props.height,
  showGeolocation: props.showGeolocation,
  loadingText: props.loadingText
}))

const errorProps = computed(() => ({
  height: props.height,
  title: props.errorTitle,
  message: props.error || undefined,
  details: props.errorDetails || undefined,
  showRetry: props.showRetry
}))

const emptyProps = computed(() => ({
  height: props.height,
  title: props.emptyTitle,
  message: props.emptyMessage
}))
</script>

<style scoped>
.map-states {
  @apply relative w-full;
}

.map-states__content {
  @apply relative w-full;
}

/* State modifiers */
.map-states--loading,
.map-states--error,
.map-states--empty {
  @apply min-h-[200px];
}
</style>