import js from '@eslint/js';
import vue from 'eslint-plugin-vue';
import typescript from '@typescript-eslint/eslint-plugin';
import typescriptParser from '@typescript-eslint/parser';

export default [
    {
        ignores: [
            'node_modules/**',
            'vendor/**',
            'public/**',
            'storage/**',
            'dist/**',
            'build/**',
            '*.min.js',
            'resources/js/ziggy.js',
            'Document/**',
            'Backap/**'
        ]
    },
    
    // Базовая конфигурация JavaScript
    js.configs.recommended,
    
    // TypeScript файлы
    {
        files: ['**/*.ts', '**/*.tsx'],
        languageOptions: {
            parser: typescriptParser,
            parserOptions: {
                ecmaVersion: 'latest',
                sourceType: 'module',
                project: './tsconfig.json'
            }
        },
        plugins: {
            '@typescript-eslint': typescript
        },
        rules: {
            '@typescript-eslint/no-unused-vars': ['error', { 
                argsIgnorePattern: '^_',
                varsIgnorePattern: '^_' 
            }],
            '@typescript-eslint/no-explicit-any': 'warn',
            '@typescript-eslint/explicit-function-return-type': 'off',
            '@typescript-eslint/no-non-null-assertion': 'warn'
        }
    },
    
    // Vue файлы
    {
        files: ['**/*.vue'],
        languageOptions: {
            parser: vue.parser,
            parserOptions: {
                parser: typescriptParser,
                ecmaVersion: 'latest',
                sourceType: 'module'
            }
        },
        plugins: {
            vue
        },
        rules: {
            ...vue.configs['vue3-recommended'].rules,
            'vue/multi-word-component-names': 'off',
            'vue/no-v-html': 'warn'
        }
    },
    
    // Общие правила для всех файлов
    {
        files: ['**/*.js', '**/*.ts', '**/*.vue'],
        rules: {
            'no-console': process.env.NODE_ENV === 'production' ? 'error' : 'warn',
            'no-debugger': process.env.NODE_ENV === 'production' ? 'error' : 'warn',
            'no-unused-vars': 'off', // Отключаем в пользу TypeScript правила
            'semi': ['error', 'always'],
            'quotes': ['error', 'single', { avoidEscape: true }],
            'indent': ['error', 4, { SwitchCase: 1 }],
            'comma-dangle': ['error', 'always-multiline'],
            'arrow-parens': ['error', 'always'],
            'object-curly-spacing': ['error', 'always'],
            'array-bracket-spacing': ['error', 'never'],
            'space-before-blocks': 'error',
            'space-infix-ops': 'error',
            'eqeqeq': ['error', 'always'],
            'no-multiple-empty-lines': ['error', { max: 1 }],
            'eol-last': ['error', 'always']
        }
    }
];