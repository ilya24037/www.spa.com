<template>
  <div class="section-stub">
    <h2 class="form-group-title">Особенности</h2>
    <div class="stub-content">
      <p>🚧 Секция в разработке</p>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  features?: string[]
  additionalFeatures?: string
  errors?: Record<string, string[]>
}

const props = withDefaults(defineProps<Props>(), {
  features: () => [],
  additionalFeatures: '',
  errors: () => ({})
})

const emit = defineEmits<{
  'update:features': [value: string[]]
  'update:additionalFeatures': [value: string]
}>()
</script>

<style scoped>
.section-stub {
  background: #fff3cd;
  border: 1px solid #ffeaa7;
  border-radius: 8px;
  padding: 20px;
}

.form-group-title {
  font-size: 18px;
  font-weight: 600;
  color: #333;
  margin-bottom: 16px;
}

.stub-content {
  color: #856404;
  font-style: italic;
}
</style>