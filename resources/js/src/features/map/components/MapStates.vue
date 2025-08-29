<template>
  <div class="map-states">
    <div v-if="loading" class="map-states__loading">
      <div class="skeleton" :style="{ height: `${height || 400}px` }">
        <div class="skeleton__content">
          <div class="skeleton__spinner"></div>
          <p class="skeleton__text">Загрузка карты...</p>
        </div>
      </div>
    </div>
    
    <div v-else-if="error" class="map-states__error">
      <div class="error" :style="{ height: `${height || 400}px` }">
        <div class="error__content">
          <div class="error__icon">❌</div>
          <h3 class="error__title">Ошибка загрузки карты</h3>
          <p class="error__message">{{ error }}</p>
          <button 
            v-if="showRetry" 
            @click="$emit('retry')" 
            class="error__retry"
          >
            Попробовать снова
          </button>
        </div>
      </div>
    </div>
    
    <slot v-else />
  </div>
</template>

<script setup lang="ts">
interface Props {
  loading?: boolean
  error?: string | null
  height?: number
  showRetry?: boolean
}

withDefaults(defineProps<Props>(), {
  loading: false,
  error: null,
  height: 400,
  showRetry: true
})

defineEmits<{ retry: [] }>()
</script>

<style scoped>
.map-states {
  position: relative;
  width: 100%;
}

.skeleton {
  width: 100%;
  background: #f5f5f5;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
}

.skeleton__content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}

.skeleton__spinner {
  width: 40px;
  height: 40px;
  border: 3px solid #e2e8f0;
  border-top: 3px solid #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

.skeleton__text {
  color: #64748b;
  font-size: 14px;
  margin: 0;
}

.error {
  width: 100%;
  background: #fef2f2;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid #fecaca;
}

.error__content {
  text-align: center;
  padding: 2rem;
}

.error__icon {
  font-size: 2rem;
  margin-bottom: 1rem;
}

.error__title {
  color: #dc2626;
  font-size: 18px;
  font-weight: 600;
  margin: 0 0 0.5rem 0;
}

.error__message {
  color: #7f1d1d;
  font-size: 14px;
  margin: 0 0 1rem 0;
}

.error__retry {
  background: #dc2626;
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  transition: background-color 0.2s;
}

.error__retry:hover {
  background: #b91c1c;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
</style>