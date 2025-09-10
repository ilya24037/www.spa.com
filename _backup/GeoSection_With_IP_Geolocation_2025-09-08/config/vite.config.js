import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { resolve } from 'path';
import { globSync } from 'glob';

export default defineConfig({
    define: {
        // Передаем переменные окружения в клиентский код
        'import.meta.env.VITE_YANDEX_MAPS_API_KEY': JSON.stringify(process.env.YANDEX_MAPS_API_KEY || '23ff8acc-835f-4e99-8b19-d33c5d346e18')
    },
    plugins: [
        laravel({
            input: [
                'resources/js/app.js',
                ...globSync('resources/js/Pages/**/*.vue')
            ],
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
            '@/ymaps': resolve(__dirname, 'resources/js/src/features/map/ymaps-components'),
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
        port: 5180,
        strictPort: false, // Если порт занят, использовать следующий доступный
        hmr: {
            host: 'localhost',
        },
    },
    // Оптимизация сборки по стандартам Wildberries
    build: {
        target: 'es2020',
        sourcemap: false, // Отключаем source maps в production
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: true,
                drop_debugger: true,
                pure_funcs: ['console.log', 'console.info', 'console.debug'],
            }
        },
        rollupOptions: {
            output: {
                // Стратегия разделения как у Wildberries
                manualChunks: (id) => {
                    // Vendor библиотеки
                    if (id.includes('node_modules')) {
                        if (id.includes('vue') || id.includes('@inertiajs')) {
                            return 'vendor-vue'
                        }
                        if (id.includes('pinia')) {
                            return 'vendor-state'
                        }
                        if (id.includes('axios') || id.includes('lodash')) {
                            return 'vendor-utils'
                        }
                        if (id.includes('@heroicons') || id.includes('lucide')) {
                            return 'vendor-icons'
                        }
                        if (id.includes('@floating-ui') || id.includes('@vueuse')) {
                            return 'vendor-ui'
                        }
                        return 'vendor-misc'
                    }
                    
                    // UI компоненты отдельно
                    if (id.includes('/src/shared/ui/')) {
                        if (id.includes('atoms')) return 'ui-atoms'
                        if (id.includes('molecules')) return 'ui-molecules'
                        if (id.includes('organisms')) return 'ui-organisms'
                        return 'ui-shared'
                    }
                    
                    // Виджеты отдельными чанками
                    if (id.includes('/src/widgets/masters-catalog')) return 'widget-catalog'
                    if (id.includes('/src/widgets/booking-calendar')) return 'widget-booking'
                    if (id.includes('/src/widgets/profile-dashboard')) return 'widget-dashboard'
                    if (id.includes('/src/widgets/')) return 'widgets-misc'
                    
                    // Фичи отдельно
                    if (id.includes('/src/features/')) return 'features'
                    
                    // Сущности отдельно
                    if (id.includes('/src/entities/')) return 'entities'
                    
                    // Страницы отдельно
                    if (id.includes('/Pages/')) return 'pages'
                },
                // Оптимизация имен файлов
                entryFileNames: 'js/[name]-[hash].js',
                chunkFileNames: 'js/[name]-[hash].js',
                assetFileNames: (assetInfo) => {
                    const extType = assetInfo.name.split('.').pop()
                    if (/png|jpe?g|svg|gif|tiff|bmp|ico/i.test(extType)) {
                        return 'images/[name]-[hash][extname]'
                    }
                    if (/css/i.test(extType)) {
                        return 'css/[name]-[hash][extname]'
                    }
                    return 'assets/[name]-[hash][extname]'
                }
            }
        },
        // Улучшенное сжатие
        chunkSizeWarningLimit: 500,
        cssCodeSplit: true,
        assetsInlineLimit: 4096, // Инлайн ассеты < 4KB
    },
    
    // Настройки CSS и Sass
    css: {
        preprocessorOptions: {
            scss: {
                // Используем новый современный API вместо legacy
                api: 'modern-compiler',
                // Отключаем предупреждения о deprecated функциях
                silenceDeprecations: ['legacy-js-api']
            }
        }
    },
    
    // Оптимизация зависимостей
    optimizeDeps: {
        include: [
            'vue',
            '@inertiajs/vue3',
            'pinia',
            'axios',
            'lodash'
        ],
        exclude: [
            // Исключаем крупные библиотеки из предварительной сборки
            '@floating-ui/dom',
            'vue3-google-map'
        ]
    }
});
