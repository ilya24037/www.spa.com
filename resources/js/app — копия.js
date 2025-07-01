import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { createPinia } from 'pinia';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: async (name) => {
        const page = await resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        );
        
        if (!page.default.layout) {
            const AppLayout = await import('./Layouts/AppLayout.vue');
            page.default.layout = AppLayout.default;
        }
        
        return page;
    },
    setup({ el, App, props, plugin }) {
        const pinia = createPinia();
        
        const app = createApp({ render: () => h(App, props) });
        
        // Глобальный обработчик ошибок Vue
        app.config.errorHandler = (err, instance, info) => {
            console.error('Глобальная ошибка Vue:', err);
            console.error('Компонент:', instance);
            console.error('Информация:', info);
            
            // Отправка на сервер (опционально)
            if (window.axios) {
                window.axios.post('/api/log-error', {
                    error: err.toString(),
                    stack: err.stack,
                    component: instance?.$options.name || 'Unknown',
                    info: info
                }).catch(() => {
                    // Игнорируем ошибки логирования
                });
            }
            
            // Показываем уведомление пользователю
            if (window.toast) {
                window.toast.error('Произошла ошибка. Мы уже работаем над её исправлением.');
            }
        };
        
        // Обработчик предупреждений Vue (для разработки)
        app.config.warnHandler = (msg, instance, trace) => {
            console.warn('Vue предупреждение:', msg);
        };
        
        app.use(plugin);
        app.use(ZiggyVue);
        app.use(pinia);
        
        app.config.globalProperties.route = route;
        
        return app.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// Глобальный обработчик ошибок JavaScript
window.addEventListener('error', (event) => {
    console.error('Глобальная JS ошибка:', event.error);
    // Можно отправить на сервер
});

// Обработчик необработанных Promise rejection
window.addEventListener('unhandledrejection', (event) => {
    console.error('Необработанный Promise rejection:', event.reason);
    event.preventDefault(); // Предотвращаем вывод в консоль по умолчанию
});