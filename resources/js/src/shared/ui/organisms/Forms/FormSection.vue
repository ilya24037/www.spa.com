<template>
  <div class="form-section">
    <div class="form-section-card">
      <!-- Р—Р°РіРѕР»РѕРІРѕРє СЃРµРєС†РёРё -->
      <div v-if="title || hint" class="form-section-header">
        <h2 v-if="title" class="form-section-title">
          {{ title }}
          <span v-if="required" class="required-marker">*</span>
        </h2>
        <div v-if="hint" class="form-section-hint">
          {{ hint }}
        </div>
      </div>

      <!-- РЎРѕРґРµСЂР¶РёРјРѕРµ СЃРµРєС†РёРё -->
      <div class="form-section-content">
        <slot />
      </div>

      <!-- РћС€РёР±РєРё СЃРµРєС†РёРё -->
      <div v-if="hasErrors" class="form-section-errors">
        <div v-for="error in sectionErrors" :key="error" class="error-message">
          {{ error }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  title: {
    type: String,
    default: ''
  },
  hint: {
    type: String,
    default: ''
  },
  required: {
    type: Boolean,
    default: false
  },
  errors: {
    type: Object,
    default: () => ({})
  },
  errorKeys: {
    type: Array,
    default: () => []
  }
})

// Р’С‹С‡РёСЃР»СЏРµРј РѕС€РёР±РєРё РґР»СЏ РґР°РЅРЅРѕР№ СЃРµРєС†РёРё
const sectionErrors = computed(() => {
  if (!props.errorKeys.length) return []
  
  const errors = []
  props.errorKeys.forEach(key => {
    if (props.errors[key]) {
      errors.push(props.errors[key])
    }
  })
  return errors
})

const hasErrors = computed(() => sectionErrors.value.length > 0)
</script>

<style scoped>
.form-section {
  margin-bottom: 24px;
}

.form-section-card {
  background: white;
  border-radius: 8px;
  border: 1px solid #e5e5e5;
  padding: 24px;
  transition: box-shadow 0.2s ease;
}

.form-section-card:hover {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.form-section-header {
  margin-bottom: 20px;
}

.form-section-title {
  font-size: 20px;
  font-weight: 600;
  color: #1a1a1a;
  margin: 0 0 8px 0;
  line-height: 1.3;
}

.required-marker {
  color: #ff4d4f;
  margin-left: 4px;
}

.form-section-hint {
  font-size: 16px;
  color: #8c8c8c;
  line-height: 1.4;
}

.form-section-content {
  /* РљРѕРЅС‚РµРЅС‚ СЃРµРєС†РёРё */
}

.form-section-errors {
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid #ffccc7;
}

.error-message {
  color: #ff4d4f;
  font-size: 14px;
  line-height: 1.4;
  margin-bottom: 4px;
}

.error-message:last-child {
  margin-bottom: 0;
}
</style>
