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

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        // Создаем Pinia store
        const pinia = createPinia()
        pinia.use(piniaPluginPersistedstate)
        
        // Предзагрузка критических компонентов
        preloadCriticalComponents()
        
        // Настройка глобальной обработки ошибок
        window.addEventListener('error', (event) => {
            logger.error('Global error:', event.error)
        })
        
        window.addEventListener('unhandledrejection', (event) => {
            logger.error('Unhandled promise rejection:', event.reason)
        })
        
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(pinia)
            .use(ZiggyVue, Ziggy)
            .use(createYmaps({
                apikey: '23ff8acc-835f-4e99-8b19-d33c5d346e18',
                lang: 'ru_RU'
            }));
            
        // Регистрируем глобальные директивы
        app.directive('hover-lift', hoverLift);
        app.directive('fade-in', fadeIn);
        app.directive('ripple', ripple);
        
        // Настройка глобальной обработки ошибок Vue
        app.config.errorHandler = (err, instance, info) => {
            logger.error('Vue error:', err, info)
        }
        
        // Предзагрузка компонентов маршрута
        preloadRouteComponents()
        
        app.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
