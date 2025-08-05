// fix-remaining-errors.cjs - Исправление оставшихся TypeScript ошибок
const fs = require('fs');
const path = require('path');

console.log('🔧 ИСПРАВЛЕНИЕ ОСТАВШИХСЯ TYPESCRIPT ОШИБОК\n');
console.log('='.repeat(60));

// 1. Создаем недостающие composables
const ensureComposables = [
  {
    path: 'resources/js/src/shared/composables/useToast.ts',
    content: `// useToast.ts - Toast composable
import { reactive } from 'vue'

export interface ToastOptions {
  message: string
  type?: 'success' | 'error' | 'warning' | 'info'
  duration?: number
}

interface ToastState {
  toasts: ToastOptions[]
}

const state = reactive<ToastState>({
  toasts: []
})

export function useToast() {
  const show = (options: ToastOptions) => {
    state.toasts.push(options)
    setTimeout(() => {
      const index = state.toasts.indexOf(options)
      if (index > -1) {
        state.toasts.splice(index, 1)
      }
    }, options.duration || 3000)
  }

  const success = (message: string) => show({ message, type: 'success' })
  const error = (message: string) => show({ message, type: 'error' })
  const warning = (message: string) => show({ message, type: 'warning' })
  const info = (message: string) => show({ message, type: 'info' })

  return {
    show,
    success,
    error,
    warning,
    info,
    toasts: state.toasts
  }
}
`
  },
  {
    path: 'resources/js/src/shared/composables/usePageLoading.ts',
    content: `// usePageLoading.ts - Page loading composable
import { ref } from 'vue'

export function usePageLoading() {
  const isLoading = ref(false)
  const error = ref<Error | null>(null)

  const startLoading = () => {
    isLoading.value = true
    error.value = null
  }

  const stopLoading = () => {
    isLoading.value = false
  }

  const setError = (err: Error) => {
    error.value = err
    isLoading.value = false
  }

  return {
    isLoading,
    error,
    startLoading,
    stopLoading,
    setError
  }
}
`
  }
];

// 2. Список исправлений для readonly
const readonlyFixes = [
  'resources/js/src/shared/ui/molecules/Toast/useToast.ts',
  'resources/js/src/shared/ui/organisms/Cards/useCard.ts'
];

// 3. Исправления Ref типов
const refFixes = [
  'resources/js/src/shared/ui/organisms/PageLoader/PageLoader.types.ts'
];

// 4. Создание недостающих stores
const ensureStores = [
  {
    path: 'resources/js/src/entities/booking/model/bookingStore.ts',
    content: `import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useBookingStore = defineStore('booking', () => {
  const bookings = ref([])
  const loading = ref(false)
  
  return {
    bookings,
    loading
  }
})
`
  }
];

// Применяем исправления
let fixedCount = 0;

// Создаем недостающие composables
ensureComposables.forEach(({ path: filePath, content }) => {
  if (!fs.existsSync(filePath)) {
    const dir = path.dirname(filePath);
    if (!fs.existsSync(dir)) {
      fs.mkdirSync(dir, { recursive: true });
    }
    fs.writeFileSync(filePath, content);
    console.log(`   ✅ Создан composable: ${filePath}`);
    fixedCount++;
  }
});

// Создаем недостающие stores
ensureStores.forEach(({ path: filePath, content }) => {
  if (!fs.existsSync(filePath)) {
    const dir = path.dirname(filePath);
    if (!fs.existsSync(dir)) {
      fs.mkdirSync(dir, { recursive: true });
    }
    fs.writeFileSync(filePath, content);
    console.log(`   ✅ Создан store: ${filePath}`);
    fixedCount++;
  }
});

// Исправляем readonly проблемы
readonlyFixes.forEach(filePath => {
  if (fs.existsSync(filePath)) {
    let content = fs.readFileSync(filePath, 'utf-8');
    if (content.includes('readonly') && !content.includes('Readonly')) {
      content = content.replace(/readonly/g, 'Readonly');
      fs.writeFileSync(filePath, content);
      console.log(`   ✅ Исправлен readonly: ${filePath}`);
      fixedCount++;
    }
  }
});

// Исправляем Ref проблемы
refFixes.forEach(filePath => {
  if (fs.existsSync(filePath)) {
    let content = fs.readFileSync(filePath, 'utf-8');
    if (content.includes(': Ref<') && !content.includes('import("vue").Ref')) {
      content = content.replace(/: Ref</g, ': import("vue").Ref<');
      fs.writeFileSync(filePath, content);
      console.log(`   ✅ Исправлен Ref: ${filePath}`);
      fixedCount++;
    }
  }
});

// Удаляем неиспользуемые импорты onMounted
const fixUnusedImports = [
  'resources/js/Components/UI/Forms/TextInput.vue',
  'resources/js/src/shared/ui/molecules/ErrorBoundary/ErrorBoundary.vue',
  'resources/js/src/shared/ui/molecules/ErrorState/ErrorState.vue'
];

fixUnusedImports.forEach(filePath => {
  if (fs.existsSync(filePath)) {
    let content = fs.readFileSync(filePath, 'utf-8');
    let changed = false;
    
    // Удаляем onMounted если не используется
    if (content.includes('onMounted') && !content.includes('onMounted(')) {
      content = content.replace(/,?\s*onMounted/g, '');
      changed = true;
    }
    
    // Удаляем другие неиспользуемые импорты
    if (content.includes('beforeEach') && !content.includes('beforeEach(')) {
      content = content.replace(/,?\s*beforeEach/g, '');
      changed = true;
    }
    
    if (changed) {
      fs.writeFileSync(filePath, content);
      console.log(`   ✅ Удалены неиспользуемые импорты: ${filePath}`);
      fixedCount++;
    }
  }
});

console.log('\n' + '='.repeat(60));
console.log(`✅ ИСПРАВЛЕНО: ${fixedCount} файлов`);
console.log('='.repeat(60));
console.log('\n🎯 СЛЕДУЮЩИЙ ШАГ: Запустить проверку TypeScript');