<template>
  <div class="back-button-container">
    <button 
      type="button" 
      class="back-button"
      @click="handleClick"
      :disabled="disabled"
    >
      <svg viewBox="0 0 24 24" class="back-icon">
        <path d="m11 7.41-5.3 5.3a1 1 0 0 1-1.4-1.42l7-7a1 1 0 0 1 1.4 0l7 7a1 1 0 0 1-1.4 1.42L13 7.4V19a1 1 0 1 1-2 0V7.41Z"/>
      </svg>
    </button>
  </div>
</template>

<script>
export default {
  name: 'BackButton',
  props: {
    disabled: {
      type: Boolean,
      default: false
    },
    to: {
      type: String,
      default: null
    }
  },
  emits: ['click'],
  methods: {
    handleClick() {
      if (this.disabled) return
      
      this.$emit('click')
      
      // Простой подход как на Avito
      if (this.to) {
        window.location.href = this.to
      } else {
        // Простая проверка истории
        if (window.history.length > 1) {
          window.history.back()
        } else {
          // Fallback на главную
          window.location.href = '/'
        }
      }
    }
  }
}
</script>

<style scoped>
.back-button-container {
  margin-bottom: 20px;
}

.back-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  background: white;
  border: 1px solid #ddd;
  border-radius: 50%;
  cursor: pointer;
  transition: all 0.2s;
}

.back-button:hover:not(:disabled) {
  background: #f5f5f5;
}

.back-button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.back-icon {
  width: 20px;
  height: 20px;
  fill: #333;
  transform: rotate(-90deg);
}
</style> 