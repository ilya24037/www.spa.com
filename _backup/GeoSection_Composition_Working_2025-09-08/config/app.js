import '../css/app.css';
import './src/shared/styles/variables.css'; // CSS переменные для дизайн-системы
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { createPinia } from 'pinia';
import piniaPluginPersistedstate from 'pinia-plugin-persistedstate';
import { hoverLift, fadeIn, ripple } from '@/src/shared/directives';

// Импорт системы ленивой загрузки
import { preloadCriticalComponents, preloadRouteComponents } from './utils/lazyLoadingOptimized';

import { ZiggyVue } from 'ziggy-js';
import { Ziggy } from './ziggy';
import { route } from 'ziggy-js';
import { logger } from '@/src/shared/utils/logger';

// Импорт vue-yandex-maps
import { createYmaps } from 'vue-yandex-maps';


// Делаем route доступным глобально
window.route = route;

console.log('🚀 [APP] Начало инициализации приложения');
console.log('🔧 [APP] Imports загружены успешно');

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

console.log('⚙️ [APP] Создание Inertia App...');

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: async (name) => {
        console.log(`📄 [APP] Загружаю страницу: ${name}`);
        
        try {
            const page = await resolvePageComponent(
                `./Pages/${name}.vue`,
                import.meta.glob('./Pages/**/*.vue')
            );
            
            console.log(`✅ [APP] Страница ${name} загружена успешно`);

            // Применяем AppLayout только к страницам, которые его уже не имеют
            // Исключаем TestEncoding и страницы с явным AppLayout
            const pagesWithLayout = ['Home', 'Dashboard', 'TestEncoding'];

            if (pagesWithLayout.includes(name.split('/').pop())) {
                console.log(`🎨 [APP] Применяю MainLayout для ${name}`);
                // Импортируем единый FSD-лайаут динамически
                const MainLayout = (await import('@/src/shared/layouts/MainLayout/MainLayout.vue')).default;
                page.default.layout = MainLayout;
            }
            
            return page;
        } catch (error) {
            console.error(`❌ [APP] Ошибка загрузки страницы ${name}:`, error);
            throw error;
        }
    },
    setup({ el, App, props, plugin }) {
        console.log('🔧 [APP] Начало setup функции');
        console.log('🏷️ [APP] Props:', props);
        console.log('🎯 [APP] Element:', el);
        
        const pinia = createPinia();
        pinia.use(piniaPluginPersistedstate);
        console.log('🗂️ [APP] Pinia инициализирован');
        
        // Глобальная инициализация Yandex Maps API для предотвращения конфликтов Web Workers
        if (!window.__YANDEX_MAPS_INITIALIZED) {
            window.__YANDEX_MAPS_INITIALIZED = true;
            // Предотвращаем множественные инициализации векторного движка
            window.__YANDEX_MAPS_SINGLETON = true;
            console.log('🗺️ [APP] Yandex Maps глобально инициализирован');
        }
        
        try {
            // Запускаем предзагрузку критических компонентов
            preloadCriticalComponents();
            console.log('⚡ [APP] Критические компоненты предзагружены');
            
            // Предзагрузка компонентов на основе текущего маршрута
            if (props.initialPage?.component) {
                const routeName = props.initialPage.url || '';
                preloadRouteComponents(routeName);
                console.log(`🚀 [APP] Компоненты для ${routeName} предзагружены`);
            }
        } catch (error) {
            console.error('⚠️ [APP] Ошибка предзагрузки компонентов:', error);
        }
        
        console.log('🎭 [APP] Создание Vue приложения...');
        
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(pinia)
            .use(ZiggyVue, Ziggy)
            
        console.log('🔌 [APP] Основные плагины подключены');
            
        try {
            // Глобальная инициализация vue-yandex-maps с корректными параметрами
            app.use(createYmaps({
                apikey: '23ff8acc-835f-4e99-8b19-d33c5d346e18',
                lang: 'ru_RU',
                version: '3.0'
                // Убираем неподдерживаемые параметры initializeOnMount и singleInstance
                // Глобальная инициализация будет работать через window.__YANDEX_MAPS_SINGLETON
            }));
            console.log('🗺️ [APP] Vue-Yandex-Maps подключен');
        } catch (error) {
            console.error('❌ [APP] Ошибка подключения Yandex Maps:', error);
        }
            
        try {
            // Регистрируем глобальные директивы
            app.directive('hover-lift', hoverLift);
            app.directive('fade-in', fadeIn);
            app.directive('ripple', ripple);
            console.log('📐 [APP] Директивы зарегистрированы');
        } catch (error) {
            console.error('❌ [APP] Ошибка регистрации директив:', error);
        }
            
        // Глобальные обработчики производительности
        app.config.performance = true;
        
        // Регистрация Service Worker для кеширования
        // ВРЕМЕННО ОТКЛЮЧЕНО для диагностики конфликта с Yandex Maps Web Workers
        if (false && 'serviceWorker' in navigator && import.meta.env.PROD) {
            navigator.serviceWorker.register('/service-worker.js')
                .then(registration => {
                    // Service Worker registered successfully
                })
                .catch(error => {
                    // Service Worker registration failed
                })
        }
        
        // Обработчик ошибок для ленивых компонентов
        app.config.errorHandler = (err, instance, info) => {
            console.error('❌ [VUE] Ошибка компонента:', err, info);
            console.error('🔍 [VUE] Instance:', instance);
            
            try {
                logger.error('Vue error:', err, info);
            } catch (loggerError) {
                console.error('❌ [LOGGER] Ошибка логирования:', loggerError);
            }
            
            // Отправка ошибок в систему мониторинга
            if (typeof window !== 'undefined' && window.performance) {
                // Component error logging removed for production
            }
        };
        
        console.log('🎯 [APP] Монтирование приложения...');
        
        try {
            const mountedApp = app.mount(el);
            console.log('✅ [APP] Приложение успешно смонтировано!');
            return mountedApp;
        } catch (error) {
            console.error('❌ [APP] КРИТИЧЕСКАЯ ОШИБКА монтирования:', error);
            console.error('🔍 [APP] Element для монтирования:', el);
            throw error;
        }
    },
    progress: {
        color: '#4B5563',
    },
});

// Функция для копирования в старых браузерах
function fallbackCopyTextToClipboard(text) {
    const textArea = document.createElement("textarea");
    textArea.value = text;
    textArea.style.top = "0";
    textArea.style.left = "0";
    textArea.style.position = "fixed";
    
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    try {
        const successful = document.execCommand('copy');
        if (successful && window.toast) {
            window.toast.success('Скопировано в буфер обмена!');
        }
    } catch (err) {
    }
    
    document.body.removeChild(textArea);
}

// Глобальный обработчик ошибок JavaScript
window.addEventListener('error', (event) => {
    console.error('❌ [GLOBAL] JavaScript ошибка:', event.error);
    console.error('🔍 [GLOBAL] Файл:', event.filename);
    console.error('🔍 [GLOBAL] Строка:', event.lineno, 'Колонка:', event.colno);
    console.error('🔍 [GLOBAL] Message:', event.message);
});

// Обработчик необработанных Promise rejection
window.addEventListener('unhandledrejection', (event) => {
    console.error('❌ [PROMISE] Необработанное отклонение Promise:', event.reason);
    console.error('🔍 [PROMISE] Event:', event);
    event.preventDefault();
});
