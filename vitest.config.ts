import { defineConfig } from 'vitest/config'
import vue from '@vitejs/plugin-vue'
import { fileURLToPath } from 'node:url'

export default defineConfig({
  plugins: [vue()],
  test: {
    globals: true,
    environment: 'happy-dom',
    setupFiles: ['./tests/setup.ts'],
    css: {
      // Обработка CSS файлов в тестах
      modules: {
        classNameStrategy: 'non-scoped'
      }
    },
    deps: {
      // Обработка CSS импортов в тестах
      external: [/\.css$/]
    },
    coverage: {
      provider: 'v8',
      reporter: ['text', 'json', 'html'],
      exclude: [
        'node_modules',
        'tests',
        '*.config.ts',
        '**/*.d.ts',
        '**/*.test.ts',
        '**/*.spec.ts'
      ],
      thresholds: {
        statements: 80,
        branches: 80,
        functions: 80,
        lines: 80
      }
    }
  },
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
      '@/src': fileURLToPath(new URL('./resources/js/src', import.meta.url)),
      '@/shared': fileURLToPath(new URL('./resources/js/src/shared', import.meta.url)),
      '@/entities': fileURLToPath(new URL('./resources/js/src/entities', import.meta.url)),
      '@/features': fileURLToPath(new URL('./resources/js/src/features', import.meta.url)),
      '@/widgets': fileURLToPath(new URL('./resources/js/src/widgets', import.meta.url)),
      '@/pages': fileURLToPath(new URL('./resources/js/src/pages', import.meta.url)),
      '@/types': fileURLToPath(new URL('./resources/js/types', import.meta.url)),
      '@/ymaps': fileURLToPath(new URL('./resources/js/src/features/map/ymaps-components', import.meta.url))
    }
  }
})