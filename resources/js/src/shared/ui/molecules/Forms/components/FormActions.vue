<template>
  <div class="mt-8 px-6 py-6 bg-gray-50/80 rounded-xl border border-gray-200">
    <div class="flex gap-4 justify-end items-center sm:flex-row flex-col-reverse">
      <!-- Кнопки для активных объявлений (ТОЧНАЯ КОПИЯ ЧЕРНОВИКОВ) -->
      <template v-if="isActiveAd">
        <button
          type="button"
          @click="$emit('cancel')"
          class="px-6 py-3 rounded-lg text-base font-semibold cursor-pointer transition-all duration-200 flex items-center gap-2 min-w-[140px] justify-center bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 hover:border-gray-400 disabled:opacity-60 disabled:cursor-not-allowed disabled:transform-none sm:w-auto w-full"
          :disabled="savingDraft"
        >
          Отменить и выйти
        </button>
        
        <button
          type="button"
          @click="() => { console.log('🔵 Кнопка СОХРАНИТЬ ИЗМЕНЕНИЯ нажата!'); $emit('save-draft') }"
          class="px-6 py-3 rounded-lg text-base font-semibold cursor-pointer transition-all duration-200 flex items-center gap-2 min-w-[140px] justify-center bg-gradient-to-br from-blue-500 to-blue-700 text-white border-0 hover:from-blue-600 hover:to-blue-800 hover:-translate-y-px disabled:opacity-60 disabled:cursor-not-allowed disabled:transform-none sm:w-auto w-full"
          :disabled="savingDraft"
        >
          <span v-if="savingDraft" class="w-4 h-4 border-2 border-transparent border-t-current rounded-full animate-spin"></span>
          {{ savingDraft ? 'Сохранение...' : 'Сохранить изменения' }}
        </button>
      </template>
      
      <!-- Обычные кнопки для создания и черновиков -->
      <template v-else>
        <button
          type="button"
          @click="() => { console.log('🔵 Кнопка СОХРАНИТЬ ЧЕРНОВИК нажата!'); $emit('save-draft') }"
          class="px-6 py-3 rounded-lg text-base font-semibold cursor-pointer transition-all duration-200 flex items-center gap-2 min-w-[140px] justify-center bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 hover:border-gray-400 disabled:opacity-60 disabled:cursor-not-allowed disabled:transform-none sm:w-auto w-full"
          :disabled="savingDraft"
        >
          <span v-if="savingDraft" class="w-4 h-4 border-2 border-transparent border-t-current rounded-full animate-spin"></span>
          {{ savingDraft ? 'Сохранение...' : 'Сохранить черновик' }}
        </button>
        
        <button
          type="submit"
          @click="$emit('submit')"
          class="px-6 py-3 rounded-lg text-base font-semibold cursor-pointer transition-all duration-200 flex items-center gap-2 min-w-[140px] justify-center bg-gradient-to-br from-blue-500 to-blue-700 text-white border-0 hover:from-blue-600 hover:to-blue-800 hover:-translate-y-px disabled:opacity-60 disabled:cursor-not-allowed disabled:transform-none sm:w-auto w-full"
          :disabled="submitting || !canSubmit"
        >
          <span v-if="submitting" class="w-4 h-4 border-2 border-transparent border-t-current rounded-full animate-spin"></span>
          {{ submitting ? 'Публикация...' : submitLabel }}
        </button>
      </template>
    </div>
    
    <div v-if="showProgress" class="mt-4 text-center">
      <small class="text-gray-500 text-sm">
        {{ progressHint }}
      </small>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  submitLabel?: string
  canSubmit?: boolean
  submitting?: boolean
  savingDraft?: boolean
  showProgress?: boolean
  progressHint?: string
  isActiveAd?: boolean
}

withDefaults(defineProps<Props>(), {
  submitLabel: 'Опубликовать',
  canSubmit: true,
  submitting: false,
  savingDraft: false,
  showProgress: false,
  progressHint: '',
  isActiveAd: false
})

defineEmits<{
  'submit': []
  'save-draft': []
  'cancel': []
}>()
</script>

<!-- Все стили мигрированы на Tailwind CSS с градиентами и анимациями -->
