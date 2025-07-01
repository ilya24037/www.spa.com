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
        
        // 🔥 ДИРЕКТИВА CLICK-OUTSIDE - закрытие элементов по клику вне
        app.directive('click-outside', {
            mounted(el, binding) {
                el.clickOutsideEvent = function(event) {
                    // Проверяем, что клик был вне элемента
                    if (!(el === event.target || el.contains(event.target))) {
                        binding.value(event);
                    }
                };
                // Слушаем клики по всему документу
                document.body.addEventListener('click', el.clickOutsideEvent);
            },
            unmounted(el) {
                // Удаляем слушатель при размонтировании
                document.body.removeEventListener('click', el.clickOutsideEvent);
            }
        });
        
        // 🔥 ДОПОЛНИТЕЛЬНЫЕ ПОЛЕЗНЫЕ ДИРЕКТИВЫ
        
        // Директива для автофокуса
        app.directive('focus', {
            mounted(el) {
                el.focus();
            }
        });
        
        // Директива для отслеживания изменения размера элемента
        app.directive('resize', {
            mounted(el, binding) {
                const resizeObserver = new ResizeObserver(entries => {
                    binding.value(entries[0]);
                });
                resizeObserver.observe(el);
                el._resizeObserver = resizeObserver;
            },
            unmounted(el) {
                if (el._resizeObserver) {
                    el._resizeObserver.disconnect();
                    delete el._resizeObserver;
                }
            }
        });
        
        // Директива для копирования в буфер обмена
        app.directive('clipboard', {
            mounted(el, binding) {
                el.clickHandler = () => {
                    const text = binding.value || el.textContent;
                    navigator.clipboard.writeText(text).then(() => {
                        if (window.toast) {
                            window.toast.success('Скопировано в буфер обмена!');
                        }
                    }).catch(err => {
                        console.error('Ошибка копирования:', err);
                    });
                };
                el.addEventListener('click', el.clickHandler);
            },
            unmounted(el) {
                el.removeEventListener('click', el.clickHandler);
            }
        });
        
        // Директива для ленивой загрузки изображений
        app.directive('lazy', {
            mounted(el) {
                const imageObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            img.src = img.dataset.src;
                            img.classList.remove('lazy');
                            observer.unobserve(img);
                        }
                    });
                });
                imageObserver.observe(el);
            }
        });
        
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