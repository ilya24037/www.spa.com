<template>
  <div class="form-controls">
    <button 
      type="button" 
      @click="$emit('expand-all')" 
      class="control-btn"
      :disabled="disabled"
    >
      📂 Развернуть всё
    </button>
    <button 
      type="button" 
      @click="$emit('collapse-all')" 
      class="control-btn"
      :disabled="disabled"
    >
      📁 Свернуть всё
    </button>
    <button 
      type="button" 
      @click="$emit('expand-required')" 
      class="control-btn"
      :disabled="disabled"
    >
      ⭐ Только обязательные
    </button>
  </div>
</template>

<script setup lang="ts">
interface Props {
  disabled?: boolean
}

withDefaults(defineProps<Props>(), {
  disabled: false
})

defineEmits<{
  'expand-all': []
  'collapse-all': []
  'expand-required': []
}>()
</script>

<style scoped>
.form-controls {
  display: flex;
  gap: 12px;
  margin-bottom: 24px;
  flex-wrap: wrap;
}

.control-btn {
  padding: 8px 16px;
  background: white;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  color: #374151;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  gap: 6px;
}

.control-btn:hover:not(:disabled) {
  background: #f9fafb;
  border-color: #9ca3af;
  transform: translateY(-1px);
}

.control-btn:active:not(:disabled) {
  transform: translateY(0);
}

.control-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

@media (max-width: 640px) {
  .form-controls {
    flex-direction: column;
  }
  
  .control-btn {
    justify-content: center;
  }
}
</style>
