<template>
  <div class="form-actions">
    <div class="form-actions-main">
      <button
        type="button"
        @click="$emit('save-draft')"
        class="btn btn-secondary"
        :disabled="savingDraft"
      >
        <span v-if="savingDraft" class="loading-spinner"></span>
        {{ savingDraft ? 'Сохранение...' : 'Сохранить черновик' }}
      </button>
      
      <button
        type="submit"
        @click="$emit('submit')"
        class="btn btn-primary"
        :disabled="submitting || !canSubmit"
      >
        <span v-if="submitting" class="loading-spinner"></span>
        {{ submitting ? 'Публикация...' : submitLabel }}
      </button>
    </div>
    
    <div v-if="showProgress" class="form-actions-progress">
      <small class="progress-hint">
        {{ progressHint }}
      </small>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  submitLabel?: string
  canSubmit?: boolean
  submitting?: boolean
  savingDraft?: boolean
  showProgress?: boolean
  progressHint?: string
}

withDefaults(defineProps<Props>(), {
  submitLabel: 'Опубликовать',
  canSubmit: true,
  submitting: false,
  savingDraft: false,
  showProgress: false,
  progressHint: ''
})

defineEmits<{
  'submit': []
  'save-draft': []
}>()
</script>

<style scoped>
.form-actions {
  margin-top: 32px;
  padding: 24px;
  background: rgba(249, 250, 251, 0.8);
  border-radius: 12px;
  border: 1px solid #e5e7eb;
}

.form-actions-main {
  display: flex;
  gap: 16px;
  justify-content: flex-end;
  align-items: center;
}

.btn {
  padding: 12px 24px;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  gap: 8px;
  min-width: 140px;
  justify-content: center;
}

.btn-secondary {
  background: white;
  color: #374151;
  border: 1px solid #d1d5db;
}

.btn-secondary:hover:not(:disabled) {
  background: #f9fafb;
  border-color: #9ca3af;
}

.btn-primary {
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
  color: white;
  border: none;
}

.btn-primary:hover:not(:disabled) {
  background: linear-gradient(135deg, #2563eb, #1e40af);
  transform: translateY(-1px);
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

.loading-spinner {
  width: 16px;
  height: 16px;
  border: 2px solid transparent;
  border-top: 2px solid currentColor;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

.form-actions-progress {
  margin-top: 16px;
  text-align: center;
}

.progress-hint {
  color: #6b7280;
  font-size: 14px;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

@media (max-width: 640px) {
  .form-actions-main {
    flex-direction: column-reverse;
  }
  
  .btn {
    width: 100%;
  }
}
</style>
