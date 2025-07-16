<template>
  <div class="form-actions">
    <button 
      type="submit" 
      class="submit-button"
      :disabled="isSubmitting"
      @click="$emit('submit')"
    >
      {{ isSubmitting ? submitLoadingText : submitText }}
    </button>
    
    <button 
      type="button" 
      class="draft-button"
      :disabled="isSaving"
      @click="$emit('save-and-exit')"
    >
      {{ isSaving ? 'Сохранение...' : 'Сохранить и выйти' }}
    </button>
    
    <div v-if="showAutosaveStatus" class="autosave-status">
      <div v-if="isSaving" class="autosave-saving">
        <span class="spinner"></span>
        Сохранение...
      </div>
      <div v-else-if="lastSaved" class="autosave-saved">
        Сохранено {{ formatTime(lastSaved) }}
      </div>
      <div v-else-if="hasUnsavedChanges" class="autosave-unsaved">
        Есть несохраненные изменения
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'FormActions',
  props: {
    isSubmitting: {
      type: Boolean,
      default: false
    },
    isSaving: {
      type: Boolean,
      default: false
    },
    hasUnsavedChanges: {
      type: Boolean,
      default: false
    },
    lastSaved: {
      type: Date,
      default: null
    },
    submitText: {
      type: String,
      default: 'Сохранить изменения'
    },
    submitLoadingText: {
      type: String,
      default: 'Сохранение...'
    },
    showAutosaveStatus: {
      type: Boolean,
      default: true
    }
  },
  emits: ['submit', 'save-and-exit'],
  methods: {
    formatTime(date) {
      if (!date) return ''
      
      const now = new Date()
      const diff = now - date
      
      if (diff < 60000) { // менее минуты
        return 'только что'
      } else if (diff < 3600000) { // менее часа
        const minutes = Math.floor(diff / 60000)
        return `${minutes} мин. назад`
      } else {
        return date.toLocaleTimeString('ru-RU', { 
          hour: '2-digit', 
          minute: '2-digit' 
        })
      }
    }
  }
}
</script>

<style scoped>
.form-actions {
  display: flex;
  gap: 12px;
  align-items: center;
  margin-top: 32px;
  padding-top: 24px;
  border-top: 1px solid #eee;
}

.submit-button {
  flex: 1;
  padding: 12px 24px;
  background: #0066cc;
  color: white;
  border: none;
  border-radius: 4px;
  font-size: 16px;
  font-weight: 500;
  cursor: pointer;
  transition: background 0.2s;
}

.submit-button:hover:not(:disabled) {
  background: #0052a3;
}

.submit-button:disabled {
  background: #ccc;
  cursor: not-allowed;
}

.draft-button {
  padding: 12px 24px;
  background: white;
  color: #0066cc;
  border: 1px solid #0066cc;
  border-radius: 4px;
  font-size: 16px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.draft-button:hover:not(:disabled) {
  background: #f0f8ff;
}

.draft-button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.autosave-status {
  margin-left: auto;
  font-size: 14px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.autosave-saving {
  color: #666;
  display: flex;
  align-items: center;
  gap: 8px;
}

.autosave-saved {
  color: #28a745;
}

.autosave-unsaved {
  color: #ffc107;
}

.spinner {
  width: 12px;
  height: 12px;
  border: 2px solid #e0e0e0;
  border-top: 2px solid #666;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

@media (max-width: 768px) {
  .form-actions {
    flex-direction: column;
    align-items: stretch;
  }
  
  .autosave-status {
    margin-left: 0;
    justify-content: center;
  }
}
</style> 