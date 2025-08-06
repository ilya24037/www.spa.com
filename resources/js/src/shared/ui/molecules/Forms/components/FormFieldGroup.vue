<template>
  <div 
    class="form-field-group"
    :class="{
      'form-field-group--row': layout === 'row',
      'form-field-group--column': layout === 'column',
      'form-field-group--responsive': responsive
    }"
  >
    <!-- Заголовок группы -->
    <div v-if="label || description" class="form-field-group-header">
      <h4 v-if="label" class="form-field-group-label">{{ label }}</h4>
      <p v-if="description" class="form-field-group-description">{{ description }}</p>
    </div>

    <!-- Поля группы -->
    <div class="form-field-group-content">
      <slot 
        :disabled="disabled"
        :readonly="readonly"
        :layout="layout"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  label?: string
  description?: string
  layout?: 'row' | 'column'
  responsive?: boolean
  disabled?: boolean
  readonly?: boolean
}

withDefaults(defineProps<Props>(), {
  layout: 'column',
  responsive: true,
  disabled: false,
  readonly: false
})
</script>

<style scoped>
.form-field-group {
  @apply space-y-3;
}

.form-field-group-header {
  @apply space-y-1;
}

.form-field-group-label {
  @apply text-sm font-medium text-gray-900;
}

.form-field-group-description {
  @apply text-xs text-gray-600;
}

.form-field-group-content {
  @apply space-y-3;
}

/* Row layout */
.form-field-group--row .form-field-group-content {
  @apply flex gap-4 space-y-0;
}

.form-field-group--row.form-field-group--responsive .form-field-group-content {
  @apply flex-col space-y-3 sm:flex-row sm:space-y-0 sm:gap-4;
}

/* Column layout (default) */
.form-field-group--column .form-field-group-content {
  @apply space-y-3;
}

/* Адаптивность */
@media (max-width: 640px) {
  .form-field-group--row .form-field-group-content {
    @apply flex-col gap-3 space-y-0;
  }
}
</style>