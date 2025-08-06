<template>
  <section 
    class="form-section"
    :class="{
      'form-section--collapsed': isCollapsed,
      'form-section--disabled': disabled,
      'form-section--readonly': readonly
    }"
  >
    <!-- Р—Р°РіРѕР»РѕРІРѕРє СЃРµРєС†РёРё -->
    <header class="form-section-header">
      <div class="form-section-title-wrapper">
        <h3 class="form-section-title">
          {{ title }}
          <span v-if="required" class="form-section-required" aria-label="РѕР±СЏР·Р°С‚РµР»СЊРЅРѕ">*</span>
        </h3>
        
        <button
          v-if="collapsible"
          type="button"
          class="form-section-toggle"
          :aria-expanded="!isCollapsed"
          :aria-label="isCollapsed ? 'Р Р°Р·РІРµСЂРЅСѓС‚СЊ СЃРµРєС†РёСЋ' : 'РЎРІРµСЂРЅСѓС‚СЊ СЃРµРєС†РёСЋ'"
          @click="toggleCollapse"
        >
          <svg 
            class="form-section-toggle-icon"
            :class="{ 'rotate-180': !isCollapsed }"
            fill="none" 
            stroke="currentColor" 
            viewBox="0 0 24 24"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </button>
      </div>
      
      <p v-if="description" class="form-section-description">
        {{ description }}
      </p>
    </header>

    <!-- РљРѕРЅС‚РµРЅС‚ СЃРµРєС†РёРё -->
    <Transition
      name="form-section-content"
      @enter="onEnter"
      @leave="onLeave"
    >
      <div 
        v-show="!isCollapsed"
        class="form-section-content"
        :aria-hidden="isCollapsed"
      >
        <div class="form-section-fields">
          <slot 
            :is-collapsed="isCollapsed"
            :disabled="disabled"
            :readonly="readonly"
            :update-field="updateField"
            :get-field-error="getFieldError"
          />
        </div>
        
        <!-- Р”РѕРїРѕР»РЅРёС‚РµР»СЊРЅС‹Р№ РєРѕРЅС‚РµРЅС‚ РІРЅРёР·Сѓ СЃРµРєС†РёРё -->
        <div v-if="$slots.footer" class="form-section-footer">
          <slot 
            name="footer"
            :is-collapsed="isCollapsed"
            :disabled="disabled"
            :readonly="readonly"
          />
        </div>
      </div>
    </Transition>
  </section>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import type { FormData, FormErrors } from './types/form.types'

interface Props {
  title: string
  description?: string
  modelValue?: FormData
  errors?: FormErrors
  collapsible?: boolean
  collapsed?: boolean
  required?: boolean
  disabled?: boolean
  readonly?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: () => ({}),
  errors: () => ({}),
  collapsible: false,
  collapsed: false,
  required: false,
  disabled: false,
  readonly: false
})

const emit = defineEmits<{
  'update:modelValue': [value: FormData]
  'update:collapsed': [collapsed: boolean]
  'field-change': [fieldName: string, value: any]
  'toggle': [collapsed: boolean]
}>()

// РЎРѕСЃС‚РѕСЏРЅРёРµ
const isCollapsed = ref(props.collapsed)

// РњРµС‚РѕРґС‹
const toggleCollapse = () => {
  isCollapsed.value = !isCollapsed.value
  emit('update:collapsed', isCollapsed.value)
  emit('toggle', isCollapsed.value)
}

const updateField = (fieldName: string, value: any) => {
  const newData = { ...props.modelValue, [fieldName]: value }
  emit('update:modelValue', newData)
  emit('field-change', fieldName, value)
}

const getFieldError = (fieldName: string): string | undefined => {
  const error = props.errors[fieldName]
  if (Array.isArray(error)) {
    return error[0]
  }
  return error
}

// РђРЅРёРјР°С†РёРё
const onEnter = (el: Element) => {
  const htmlEl = el as HTMLElement
  htmlEl.style.height = '0'
  htmlEl.offsetHeight // force reflow
  htmlEl.style.height = htmlEl.scrollHeight + 'px'
}

const onLeave = (el: Element) => {
  const htmlEl = el as HTMLElement
  htmlEl.style.height = htmlEl.scrollHeight + 'px'
  htmlEl.offsetHeight // force reflow
  htmlEl.style.height = '0'
}

// РћС‚СЃР»РµР¶РёРІР°РЅРёРµ РІРЅРµС€РЅРёС… РёР·РјРµРЅРµРЅРёР№ collapsed
watch(() => props.collapsed, (newValue) => {
  isCollapsed.value = newValue
})
</script>

<style scoped>
.form-section {
  @apply border border-gray-200 rounded-lg bg-white shadow-sm overflow-hidden;
}

.form-section--disabled {
  @apply opacity-50 pointer-events-none;
}

.form-section--readonly {
  @apply bg-gray-50;
}

.form-section-header {
  @apply p-4 border-b border-gray-200 bg-gray-50;
}

.form-section--collapsed .form-section-header {
  @apply border-b-0;
}

.form-section-title-wrapper {
  @apply flex items-center justify-between;
}

.form-section-title {
  @apply text-base font-semibold text-gray-900 flex items-center gap-1;
}

.form-section-required {
  @apply text-red-500;
}

.form-section-toggle {
  @apply p-1 text-gray-500 hover:text-gray-700 rounded transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1;
}

.form-section-toggle-icon {
  @apply w-5 h-5 transition-transform duration-200;
}

.form-section-description {
  @apply mt-2 text-sm text-gray-600 leading-relaxed;
}

.form-section-content {
  @apply overflow-hidden;
}

.form-section-fields {
  @apply p-4 space-y-4;
}

.form-section-footer {
  @apply px-4 pb-4 pt-0 border-t border-gray-100;
}

/* РђРЅРёРјР°С†РёРё */
.form-section-content-enter-active,
.form-section-content-leave-active {
  transition: height 0.3s ease;
}

.form-section-content-enter-from,
.form-section-content-leave-to {
  height: 0;
}

/* РђРґР°РїС‚РёРІРЅРѕСЃС‚СЊ */
@media (max-width: 640px) {
  .form-section-header {
    @apply p-3;
  }
  
  .form-section-fields {
    @apply p-3;
  }
  
  .form-section-title {
    @apply text-sm;
  }
  
  .form-section-description {
    @apply text-xs;
  }
}

/* Р’С‹СЃРѕРєРёР№ РєРѕРЅС‚СЂР°СЃС‚ */
@media (prefers-contrast: high) {
  .form-section {
    @apply border-2 border-gray-800;
  }
  
  .form-section-header {
    @apply border-b-2 border-gray-800;
  }
}

/* РЈРјРµРЅСЊС€РµРЅРЅР°СЏ Р°РЅРёРјР°С†РёСЏ */
@media (prefers-reduced-motion: reduce) {
  .form-section-content-enter-active,
  .form-section-content-leave-active {
    transition: none;
  }
  
  .form-section-toggle-icon {
    transition: none;
  }
}
</style>

