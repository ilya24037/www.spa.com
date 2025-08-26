<template>
  <div class="form-progress">
    <div class="progress-bar">
      <div 
        class="progress-fill" 
        :style="{ width: progress + '%' }"
        :class="{
          'progress-fill--low': progress < 30,
          'progress-fill--medium': progress >= 30 && progress < 70,
          'progress-fill--high': progress >= 70
        }"
      ></div>
    </div>
    <span class="progress-text">
      {{ label || `Заполнено ${progress}%` }}
    </span>
  </div>
</template>

<script setup lang="ts">
interface Props {
  progress: number
  label?: string
}

withDefaults(defineProps<Props>(), {
  progress: 0,
  label: ''
})
</script>

<style scoped>
.form-progress {
  margin-bottom: 24px;
  padding: 16px 20px;
  background: rgba(249, 250, 251, 0.8);
  border-radius: 12px;
  border: 1px solid #e5e7eb;
}

.progress-bar {
  width: 100%;
  height: 8px;
  background: #f3f4f6;
  border-radius: 4px;
  overflow: hidden;
  margin-bottom: 8px;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #3b82f6, #1d4ed8);
  border-radius: 4px;
  transition: width 0.3s ease, background 0.3s ease;
}

.progress-fill--low {
  background: linear-gradient(90deg, #ef4444, #dc2626);
}

.progress-fill--medium {
  background: linear-gradient(90deg, #f59e0b, #d97706);
}

.progress-fill--high {
  background: linear-gradient(90deg, #10b981, #059669);
}

.progress-text {
  font-size: 14px;
  font-weight: 500;
  color: #6b7280;
  display: block;
  text-align: center;
}
</style>
