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
        
        // Устанавливаем layout по умолчанию для страниц без layout
        if (!page.default.layout) {
            const AppLayout = await import('./Layouts/AppLayout.vue');
            page.default.layout = AppLayout.default;
        }
        
        return page;
    },
    setup({ el, App, props, plugin }) {
        const pinia = createPinia();
        
        const app = createApp({ render: () => h(App, props) });
        
        app.use(plugin);
        app.use(ZiggyVue);
        app.use(pinia);
        
        // Делаем route доступным глобально
        app.config.globalProperties.route = route;
        
        return app.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});