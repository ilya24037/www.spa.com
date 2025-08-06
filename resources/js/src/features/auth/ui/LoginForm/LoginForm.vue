<template>
  <form @submit.prevent="handleSubmit" class="space-y-4">
    <h2 class="text-2xl font-bold text-center mb-6">
      Р’С…РѕРґ
    </h2>
    
    <!-- Error message -->
    <div v-if="error" class="p-3 bg-red-50 border border-red-200 rounded-lg text-red-600 text-sm">
      {{ error }}
    </div>
    
    <!-- Form fields -->
    <div class="space-y-4">
      <slot />
    </div>
    
    <!-- Submit button -->
    <button
      type="submit"
      :disabled="loading"
      class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
    >
      {{ loading ? 'Р—Р°РіСЂСѓР·РєР°...' : 'Р’РѕР№С‚Рё' }}
    </button>
  </form>
</template>

<script setup lang="ts">
interface Props {
  loading?: boolean
  error?: string
}

withDefaults(defineProps<Props>(), {
  loading: false,
  error: ''
})

const emit = defineEmits<{
  submit: []
}>()

const handleSubmit = () => {
  emit('submit')
}
</script>
