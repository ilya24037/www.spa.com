module.exports = {
  root: true,
  env: {
    browser: true,
    es2021: true,
    node: true
  },
  extends: [
    'eslint:recommended',
    'plugin:vue/vue3-recommended',
    'plugin:@typescript-eslint/recommended',
    'plugin:vuejs-accessibility/recommended' // Правила доступности для Vue
  ],
  parser: 'vue-eslint-parser',
  parserOptions: {
    ecmaVersion: 'latest',
    parser: '@typescript-eslint/parser',
    sourceType: 'module'
  },
  plugins: [
    'vue',
    '@typescript-eslint',
    'vuejs-accessibility' // Плагин для проверки доступности
  ],
  rules: {
    // Правила доступности
    'vuejs-accessibility/form-control-has-label': 'error',
    'vuejs-accessibility/label-has-for': 'error',
    'vuejs-accessibility/no-autofocus': 'warn',
    'vuejs-accessibility/no-redundant-roles': 'error',
    'vuejs-accessibility/role-has-required-aria-props': 'error',
    'vuejs-accessibility/tabindex-no-positive': 'error',
    'vuejs-accessibility/aria-props': 'error',
    'vuejs-accessibility/aria-role': 'error',
    'vuejs-accessibility/aria-unsupported-elements': 'error',
    'vuejs-accessibility/click-events-have-key-events': 'warn',
    'vuejs-accessibility/mouse-events-have-key-events': 'warn',
    'vuejs-accessibility/no-access-key': 'error',
    'vuejs-accessibility/no-static-element-interactions': 'warn',
    'vuejs-accessibility/heading-has-content': 'error',
    'vuejs-accessibility/anchor-has-content': 'error',
    'vuejs-accessibility/iframe-has-title': 'error',
    'vuejs-accessibility/media-has-caption': 'warn',
    'vuejs-accessibility/no-distracting-elements': 'error',
    
    // Кастомные правила для форм
    'vue/html-indent': ['error', 2],
    'vue/max-attributes-per-line': ['error', {
      singleline: 3,
      multiline: 1
    }],
    'vue/require-default-prop': 'error',
    'vue/require-prop-types': 'error',
    
    // TypeScript правила
    '@typescript-eslint/no-unused-vars': 'warn',
    '@typescript-eslint/no-explicit-any': 'warn',
    
    // Общие правила
    'no-console': process.env.NODE_ENV === 'production' ? 'error' : 'warn',
    'no-debugger': process.env.NODE_ENV === 'production' ? 'error' : 'warn'
  },
  overrides: [
    {
      files: ['*.vue'],
      rules: {
        // Специальные правила для Vue файлов
        'vue/multi-word-component-names': 'off'
      }
    }
  ],
  ignorePatterns: [
    'node_modules/',
    'vendor/',
    'public/',
    'storage/',
    'bootstrap/cache/',
    '*.min.js',
    'resources/js/ziggy.js'
  ]
}