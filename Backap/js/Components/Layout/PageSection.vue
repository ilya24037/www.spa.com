<template>
  <div class="page-section" :id="sectionId">
    <div v-if="title" class="section-header">
      <h2 class="section-title">{{ title }}</h2>
      <div v-if="subtitle" class="section-subtitle">{{ subtitle }}</div>
    </div>
    
    <div class="section-content">
      <slot />
    </div>
    
    <div v-if="hasActions" class="section-actions">
      <slot name="actions" />
    </div>
  </div>
</template>

<script>
export default {
  name: 'PageSection',
  props: {
    title: {
      type: String,
      default: null
    },
    subtitle: {
      type: String,
      default: null
    },
    id: {
      type: String,
      default: null
    }
  },
  computed: {
    sectionId() {
      return this.id || (this.title ? this.title.toLowerCase().replace(/\s+/g, '-') : null)
    },
    hasActions() {
      return !!this.$slots.actions
    }
  }
}
</script>

<style scoped>
.page-section {
  margin-bottom: 40px;
  padding: 24px 0;
}

.page-section:last-child {
  margin-bottom: 0;
}

.section-header {
  margin-bottom: 24px;
}

.section-title {
  font-size: 20px;
  font-weight: 600;
  color: #333;
  margin: 0 0 8px 0;
}

.section-subtitle {
  font-size: 14px;
  color: #666;
  margin: 0;
}

.section-content {
  /* Контент секции */
}

.section-actions {
  margin-top: 20px;
  display: flex;
  gap: 12px;
  align-items: center;
}

/* Стили для разных размеров секций */
.page-section.section-small {
  margin-bottom: 24px;
  padding: 16px 0;
}

.page-section.section-large {
  margin-bottom: 56px;
  padding: 32px 0;
}

/* Стили для секций с границами */
.page-section.section-bordered {
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  padding: 32px;
  background: white;
}

.page-section.section-bordered .section-header {
  margin-bottom: 28px;
}

/* Адаптивность */
@media (max-width: 768px) {
  .page-section {
    margin-bottom: 32px;
    padding: 20px 0;
  }
  
  .section-title {
    font-size: 18px;
  }
  
  .section-subtitle {
    font-size: 13px;
  }
  
  .page-section.section-bordered {
    padding: 24px;
  }
  
  .section-actions {
    flex-direction: column;
    align-items: stretch;
  }
}
</style> 