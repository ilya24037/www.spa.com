import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { resolve } from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
            // Поддержка TypeScript в Vue файлах
            script: {
                defineModel: true,
                propsDestructure: true
            }
        }),
    ],
    resolve: {
        alias: {
            '@': resolve(__dirname, 'resources/js'),
            '@/src': resolve(__dirname, 'resources/js/src'),
            '@/shared': resolve(__dirname, 'resources/js/src/shared'),
            '@/entities': resolve(__dirname, 'resources/js/src/entities'),
            '@/features': resolve(__dirname, 'resources/js/src/features'),
            '@/widgets': resolve(__dirname, 'resources/js/src/widgets'),
            '@/pages': resolve(__dirname, 'resources/js/src/pages'),
            '@/types': resolve(__dirname, 'resources/js/types'),
        },
        // Расширения файлов для импорта
        extensions: ['.mjs', '.js', '.ts', '.jsx', '.tsx', '.json', '.vue']
    },
    // Оптимизация для TypeScript
    esbuild: {
        target: 'es2020',
        jsxFactory: 'h',
        jsxFragment: 'Fragment'
    },
    // Настройки сервера разработки
    server: {
        hmr: {
            host: 'localhost',
        },
    },
    // Оптимизация сборки
    build: {
        target: 'es2020',
        rollupOptions: {
            output: {
                manualChunks: {
                    'vendor-vue': ['vue', '@inertiajs/vue3'],
                    'vendor-utils': ['axios', 'lodash'],
                }
            }
        }
    }
});
