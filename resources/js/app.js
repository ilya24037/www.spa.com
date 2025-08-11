import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { createPinia } from 'pinia';
import piniaPluginPersistedstate from 'pinia-plugin-persistedstate';

// Импорт системы ленивой загрузки
import { preloadCriticalComponents, preloadRouteComponents } from './utils/lazyLoadingOptimized';

import { ZiggyVue } from 'ziggy-js';
import { Ziggy } from './ziggy';
import { route } from 'ziggy-js';
import { logger } from '@/src/shared/utils/logger';

// Делаем route доступным глобально
window.route = route;

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: async (name) => {
        const page = await resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue')
        );

        // Применяем AppLayout только к страницам, которые его уже не имеют
        // Исключаем TestEncoding и страницы с явным AppLayout
        const pagesWithoutLayout = ['Home', 'TestEncoding'];

        if (pagesWithoutLayout.includes(name.split('/').pop())) {
            // Импортируем единый FSD-лайаут динамически
            const MainLayout = (await import('@/src/shared/layouts/MainLayout/MainLayout.vue')).default;
            page.default.layout = MainLayout;
        }
        
        return page;
    },
    setup({ el, App, props, plugin }) {
        const pinia = createPinia();
        pinia.use(piniaPluginPersistedstate);
        
        // Запускаем предзагрузку критических компонентов
        preloadCriticalComponents();
        
        // Предзагрузка компонентов на основе текущего маршрута
        if (props.initialPage?.component) {
            const routeName = props.initialPage.url || '';
            preloadRouteComponents(routeName);
        }
        
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(pinia)
            .use(ZiggyVue, Ziggy);
            
        // Глобальные обработчики производительности
        app.config.performance = true;
        
        // Обработчик ошибок для ленивых компонентов
        app.config.errorHandler = (err, instance, info) => {
            logger.error('Vue error:', err, info);
            
            // Отправка ошибок в систему мониторинга
            if (typeof window !== 'undefined' && window.performance) {
                // Component error logging removed for production
            }
        };
        
        return app.mount(el);
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
});

// Обработчик необработанных Promise rejection
window.addEventListener('unhandledrejection', (event) => {
    event.preventDefault();
});