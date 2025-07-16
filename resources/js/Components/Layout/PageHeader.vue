<template>
  <div class="page-header">
    <h1 class="page-title">{{ title }}</h1>
    <p v-if="subtitle" class="page-subtitle">{{ subtitle }}</p>
    <div v-if="$slots.default" class="page-actions">
      <slot />
    </div>
  </div>
</template>

<script>
export default {
  name: 'PageHeader',
  props: {
    title: {
      type: String,
      required: true
    },
    subtitle: {
      type: String,
      default: ''
    }
  }
}
</script>

<style scoped>
.page-header {
  margin-bottom: 40px;
  text-align: center;
  padding: 24px 0;
}

.page-title {
  font-size: 32px;
  font-weight: 700;
  color: #333;
  margin: 0 0 12px 0;
  line-height: 1.2;
}

.page-subtitle {
  font-size: 16px;
  color: #666;
  margin: 0 0 32px 0;
  line-height: 1.5;
  max-width: 500px;
  margin-left: auto;
  margin-right: auto;
}

.page-actions {
  display: flex;
  justify-content: center;
  gap: 12px;
  flex-wrap: wrap;
}

/* Адаптивность */
@media (max-width: 768px) {
  .page-header {
    margin-bottom: 32px;
    padding: 20px 0;
  }
  
  .page-title {
    font-size: 26px;
  }
  
  .page-subtitle {
    font-size: 14px;
    margin-bottom: 24px;
  }
  
  .page-actions {
    flex-direction: column;
    align-items: center;
  }
}
</style> 