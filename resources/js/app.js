import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { createPinia } from 'pinia';

import { ZiggyVue } from 'ziggy-js';
import { Ziggy } from './ziggy';
import { route } from 'ziggy-js';

// Делаем route доступным глобально
window.route = route;

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
            
            // Определяем, является ли страница страницей авторизации
            // Теперь страницы авторизации показывают навигацию, так как используют модальные окна
            const isAuthPage = false; // Отключаем скрытие навигации для авторизации
            
            // Сохраняем информацию о типе страницы в глобальной переменной
            window.isAuthPage = isAuthPage;
            
            // Применяем базовый AppLayout
            page.default.layout = AppLayout.default;
        }

        return page;
    },
    setup({ el, App, props, plugin }) {
        const pinia = createPinia();
        
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(pinia)
            .use(ZiggyVue, Ziggy)
            .mount(el);
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