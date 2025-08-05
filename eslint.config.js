import js from '@eslint/js'
import pluginVue from 'eslint-plugin-vue'
import typescript from '@typescript-eslint/eslint-plugin'
import typescriptParser from '@typescript-eslint/parser'
import vueParser from 'vue-eslint-parser'

export default [
  js.configs.recommended,
  ...pluginVue.configs['flat/recommended'],
  {
    files: ['**/*.{js,jsx,ts,tsx,vue,cjs,mjs}'],
    ignores: ['**/node_modules/**', '**/vendor/**', '**/public/**', '**/storage/**', '**/Document/**'],
    languageOptions: {
      ecmaVersion: 2022,
      sourceType: 'module',
      globals: {
        window: 'readonly',
        document: 'readonly',
        console: 'readonly',
        process: 'readonly',
        defineProps: 'readonly',
        defineEmits: 'readonly',
        defineExpose: 'readonly',
        withDefaults: 'readonly'
      }
    },
    plugins: {
      '@typescript-eslint': typescript
    },
    rules: {
      // Запрет использования console в production коде
      'no-console': 'error',
      
      // Запрет использования debugger
      'no-debugger': 'error',
      
      // Другие полезные правила
      'no-unused-vars': 'off', // Отключаем для JS, используем TypeScript версию
      '@typescript-eslint/no-unused-vars': ['error', {
        'argsIgnorePattern': '^_',
        'varsIgnorePattern': '^_'
      }],
      
      // Vue правила
      'vue/multi-word-component-names': 'off',
      'vue/no-v-html': 'warn',
      'vue/require-default-prop': 'off',
      'vue/no-unused-components': 'error',
      'vue/no-unused-vars': 'error',
      
      // TypeScript правила
      '@typescript-eslint/explicit-module-boundary-types': 'off',
      '@typescript-eslint/no-explicit-any': 'warn',
      '@typescript-eslint/no-non-null-assertion': 'warn'
    }
  },
  {
    // Специальные правила для Vue файлов
    files: ['**/*.vue'],
    languageOptions: {
      parser: vueParser,
      parserOptions: {
        parser: typescriptParser,
        ecmaVersion: 2022,
        sourceType: 'module',
        ecmaFeatures: {
          jsx: true
        }
      }
    },
    rules: {
      'vue/html-indent': ['error', 2],
      'vue/max-attributes-per-line': ['error', {
        singleline: 3,
        multiline: 1
      }]
    }
  },
  {
    // Правила для CommonJS файлов
    files: ['**/*.cjs'],
    languageOptions: {
      sourceType: 'commonjs',
      globals: {
        require: 'readonly',
        module: 'readonly',
        exports: 'readonly',
        __dirname: 'readonly',
        __filename: 'readonly',
        process: 'readonly'
      }
    },
    rules: {
      '@typescript-eslint/no-var-requires': 'off'
    }
  },
  {
    // Исключения для конфигурационных файлов
    files: ['*.config.js', '*.config.ts', 'scripts/*.js', 'scripts/*.cjs'],
    rules: {
      'no-console': 'off' // Разрешаем console в конфигах и скриптах
    }
  },
  {
    // Исключения для тестов
    files: ['**/*.test.js', '**/*.test.ts', '**/*.spec.js', '**/*.spec.ts'],
    rules: {
      'no-console': 'off',
      '@typescript-eslint/no-explicit-any': 'off'
    }
  }
]