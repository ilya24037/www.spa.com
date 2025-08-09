<template>
  <nav class="breadcrumbs" aria-label="Хлебные крошки">
    <ol class="breadcrumbs-list">
      <li 
        v-for="(item, index) in items" 
        :key="index"
        class="breadcrumb-item"
      >
        <a 
          v-if="item.url && index < items.length - 1"
          :href="item.url"
          class="breadcrumb-link"
          @click.prevent="handleClick(item)"
        >
          {{ item.label }}
        </a>
        <span 
          v-else
          class="breadcrumb-text"
          :class="{ 'current': index === items.length - 1 }"
        >
          {{ item.label }}
        </span>
        
        <span 
          v-if="index < items.length - 1"
          class="breadcrumb-separator"
          aria-hidden="true"
        >
          ›
        </span>
      </li>
    </ol>
  </nav>
</template>

<script>
export default {
  name: 'Breadcrumbs',
  props: {
    items: {
      type: Array,
      required: true,
      validator: (value) => {
        return value.every(item => 
          typeof item === 'object' && 
          typeof item.label === 'string' &&
          (item.url === undefined || typeof item.url === 'string')
        )
      }
    }
  },
  emits: ['click'],
  methods: {
    handleClick(item) {
      this.$emit('click', item)
      
      if (item.url) {
        window.location.href = item.url
      }
    }
  }
}
</script>

<style scoped>
.breadcrumbs {
  display: flex;
  align-items: center;
}

.breadcrumbs-list {
  display: flex;
  align-items: center;
  list-style: none;
  margin: 0;
  padding: 0;
  flex-wrap: wrap;
}

.breadcrumb-item {
  display: flex;
  align-items: center;
}

.breadcrumb-link {
  color: #007bff;
  text-decoration: none;
  font-size: 14px;
  transition: color 0.2s;
}

.breadcrumb-link:hover {
  color: #0056b3;
  text-decoration: underline;
}

.breadcrumb-text {
  color: #666;
  font-size: 14px;
}

.breadcrumb-text.current {
  color: #333;
  font-weight: 500;
}

.breadcrumb-separator {
  color: #ccc;
  margin: 0 8px;
  font-size: 12px;
}

/* Адаптивность */
@media (max-width: 768px) {
  .breadcrumbs-list {
    font-size: 13px;
  }
  
  .breadcrumb-separator {
    margin: 0 6px;
  }
}
</style> 