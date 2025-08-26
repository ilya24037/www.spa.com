<template>
  <header class="page-header">
    <div class="page-header-container">
      <!-- Хлебные крошки -->
      <nav v-if="breadcrumbs && breadcrumbs.length > 0" class="breadcrumbs">
        <template v-for="(item, index) in breadcrumbs" :key="index">
          <Link
            v-if="item.href && index < breadcrumbs.length - 1"
            :href="item.href"
            class="breadcrumb-link"
          >
            {{ item.label }}
          </Link>
          <span
            v-else
            class="breadcrumb-current"
          >
            {{ item.label }}
          </span>
          
          <span
            v-if="index < breadcrumbs.length - 1"
            class="breadcrumb-separator"
          >
            /
          </span>
        </template>
      </nav>
      
      <!-- Заголовок и подзаголовок -->
      <div class="page-header-content">
        <h1 class="page-title">{{ title }}</h1>
        <p v-if="subtitle" class="page-subtitle">{{ subtitle }}</p>
      </div>
      
      <!-- Дополнительные действия -->
      <div v-if="$slots.actions" class="page-actions">
        <slot name="actions" />
      </div>
    </div>
  </header>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3'

interface Breadcrumb {
  label: string
  href?: string
}

interface Props {
  title: string
  subtitle?: string
  breadcrumbs?: Breadcrumb[]
}

defineProps<Props>()
</script>

<style scoped>
.page-header {
  background: white;
  border-bottom: 1px solid #e5e7eb;
  padding: 20px 0;
}

.page-header-container {
  max-width: 1920px;
  margin: 0 auto;
  padding: 0 24px;
}

.breadcrumbs {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 12px;
  font-size: 14px;
}

.breadcrumb-link {
  color: #6b7280;
  text-decoration: none;
  transition: color 0.2s;
}

.breadcrumb-link:hover {
  color: #3b82f6;
}

.breadcrumb-current {
  color: #111827;
}

.breadcrumb-separator {
  color: #d1d5db;
}

.page-header-content {
  display: flex;
  align-items: baseline;
  gap: 16px;
  flex-wrap: wrap;
}

.page-title {
  font-size: 28px;
  font-weight: 700;
  color: #111827;
  margin: 0;
}

.page-subtitle {
  font-size: 16px;
  color: #6b7280;
  margin: 0;
}

.page-actions {
  margin-top: 16px;
}

@media (max-width: 640px) {
  .page-header-container {
    padding: 0 12px;
  }
  
  .page-title {
    font-size: 24px;
  }
  
  .breadcrumbs {
    font-size: 12px;
  }
}
</style>