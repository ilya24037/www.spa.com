<template>
  <div class="map-controls">
    <!-- Поиск -->
    <div v-if="showSearch" class="control-group">
      <input
        v-model="searchQuery"
        type="text"
        placeholder="Поиск адреса..."
        class="search-input"
        @keyup.enter="handleSearch"
      />
      <button
        @click="handleSearch"
        :disabled="!searchQuery.trim()"
        class="search-btn"
        title="Найти"
      >
        🔍
      </button>
    </div>

    <!-- Контролы масштабирования -->
    <div class="control-group">
      <button
        @click="$emit('zoom-in')"
        :disabled="!canZoomIn"
        class="control-btn"
        title="Приблизить"
      >
        +
      </button>
      <button
        @click="$emit('zoom-out')"
        :disabled="!canZoomOut"
        class="control-btn"
        title="Отдалить"
      >
        −
      </button>
    </div>

    <!-- Геолокация -->
    <button
      v-if="showGeolocation"
      @click="$emit('geolocation-click')"
      class="control-btn geolocation-btn"
      :class="{ 'geolocation-btn--active': locationActive }"
      title="Моя локация"
    >
      📍
    </button>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

interface Props {
  showGeolocation?: boolean
  showSearch?: boolean
  canZoomIn?: boolean
  canZoomOut?: boolean
  locationActive?: boolean
}

withDefaults(defineProps<Props>(), {
  showGeolocation: false,
  showSearch: false,
  canZoomIn: true,
  canZoomOut: true,
  locationActive: false
})

const emit = defineEmits<{
  'geolocation-click': []
  'zoom-in': []
  'zoom-out': []
  'search': [query: string]
}>()

const searchQuery = ref('')

function handleSearch() {
  if (searchQuery.value.trim()) {
    emit('search', searchQuery.value.trim())
  }
}
</script>

<style scoped>
.map-controls {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.control-group {
  display: flex;
  gap: 2px;
  background: white;
  border-radius: 4px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.search-input {
  padding: 8px 12px;
  border: none;
  outline: none;
  font-size: 14px;
  min-width: 200px;
}

.search-btn {
  padding: 8px 12px;
  border: none;
  background: #3b82f6;
  color: white;
  cursor: pointer;
  font-size: 14px;
}

.search-btn:hover:not(:disabled) {
  background: #2563eb;
}

.search-btn:disabled {
  background: #d1d5db;
  cursor: not-allowed;
}

.control-btn {
  padding: 8px 12px;
  border: none;
  background: white;
  cursor: pointer;
  font-size: 16px;
  font-weight: bold;
  min-width: 32px;
  transition: background-color 0.2s;
}

.control-btn:hover:not(:disabled) {
  background: #f3f4f6;
}

.control-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.control-btn:not(:last-child) {
  border-right: 1px solid #e5e7eb;
}

.geolocation-btn {
  background: white;
  border-radius: 4px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.geolocation-btn--active {
  background: #3b82f6;
  color: white;
}
</style>