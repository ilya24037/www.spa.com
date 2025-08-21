<!-- Empty state для PhotoUpload -->
<template>
  <div 
    class="empty-state text-center py-12" 
    role="status"
    aria-label="Фотографии не загружены"
  >
    <svg 
      class="mx-auto h-12 w-12 text-gray-400" 
      fill="none" 
      viewBox="0 0 24 24" 
      stroke="currentColor"
      aria-hidden="true"
    >
      <path 
        stroke-linecap="round" 
        stroke-linejoin="round" 
        stroke-width="2" 
        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" 
      />
    </svg>
    <p class="mt-2 text-sm text-gray-600">
      Фотографии еще не загружены
    </p>
    <p class="text-xs text-gray-500 mt-1">
      Нажмите или перетащите фото для загрузки
    </p>
  </div>
</template>

<script setup lang="ts">
// Empty state component with ARIA support
</script>