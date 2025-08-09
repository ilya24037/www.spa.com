<template>
  <div class="section-stub">
    <h2 class="form-group-title">Где вы оказываете услуги</h2>
    <div class="stub-content">
      <p>🚧 Секция "Локация" в разработке</p>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  serviceLocation: { type: Array, default: () => [] },
  errors: { type: Object, default: () => ({}) }
})
const emit = defineEmits(['update:serviceLocation'])
</script>

<style scoped>
.section-stub { background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; padding: 20px; }
.form-group-title { font-size: 18px; font-weight: 600; color: #333; margin-bottom: 16px; }
.stub-content { color: #856404; font-style: italic; }
</style>
