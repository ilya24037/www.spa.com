<template>
  <div class="category-selector">
    <div class="tabs-container">
      <div class="tabs-wrapper">
        <button
          v-for="category in categories"
          :key="category.id"
          @click="selectCategory(category.id)"
          :class="['tab-button', { 'active': selectedCategory === category.id }]"
        >
          <span class="tab-icon">{{ category.icon }}</span>
          <span class="tab-label">{{ category.name }}</span>
          <span v-if="category.adult" class="adult-badge">18+</span>
        </button>
      </div>
    </div>
    
    <div class="category-description">
      <p v-if="currentCategory" class="description-text">
        {{ currentCategory.description }}
      </p>
    </div>
  </div>
</template>

<script>
export default {
  name: 'CategorySelector',
  props: {
    selectedCategory: {
      type: String,
      default: null
    }
  },
  emits: ['category-changed'],
  data() {
    return {
      categories: [
        {
          id: 'erotic',
          name: 'Эротический массаж',
          icon: '🔥',
          description: 'Тантрический, Body-to-body, интимный массаж',
          adult: true
        },
        {
          id: 'strip',
          name: 'Стриптиз',
          icon: '💃',
          description: 'Приватные танцы, шоу-программы',
          adult: true
        },
        {
          id: 'escort',
          name: 'Сопровождение',
          icon: '👥',
          description: 'Сопровождение на мероприятия',
          adult: true
        }
      ]
    }
  },
  computed: {
    currentCategory() {
      return this.categories.find(cat => cat.id === this.selectedCategory)
    }
  },
  methods: {
    selectCategory(categoryId) {
      this.$emit('category-changed', categoryId)
    }
  }
}
</script>

<style scoped>
.category-selector {
  @apply bg-white rounded-lg shadow-sm border border-gray-200 mb-6;
}

.tabs-container {
  @apply border-b border-gray-200;
}

.tabs-wrapper {
  @apply flex flex-wrap;
}

.tab-button {
  @apply relative flex items-center space-x-2 px-4 py-3 text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-50 border-b-2 border-transparent transition-all duration-200;
}

.tab-button.active {
  @apply text-blue-600 border-blue-600 bg-blue-50;
}

.tab-icon {
  @apply text-lg;
}

.tab-label {
  @apply whitespace-nowrap;
}

.adult-badge {
  @apply inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800;
}

.category-description {
  @apply p-4;
}

.description-text {
  @apply text-sm text-gray-600;
}

/* Адаптивность */
@media (max-width: 768px) {
  .tab-button {
    @apply flex-1 justify-center;
  }
  
  .tab-label {
    @apply text-xs;
  }
  
  .tabs-wrapper {
    @apply grid grid-cols-3;
  }
}
</style> 