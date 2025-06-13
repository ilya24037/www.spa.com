<template>
  <div class="sidebar-column bg-white rounded-lg shadow-sm p-6 sticky top-4">
    <slot />
  </div>
</template>

<style scoped>
.sidebar-column {
  min-height: fit-content;
}

/* Скроллбар для длинного контента */
.sidebar-column::-webkit-scrollbar {
  width: 4px;
}

.sidebar-column::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 2px;
}

.sidebar-column::-webkit-scrollbar-thumb {
  background: #888;
  border-radius: 2px;
}

.sidebar-column::-webkit-scrollbar-thumb:hover {
  background: #555;
}
</style>