import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { createPinia } from 'pinia';

import { ZiggyVue } from 'ziggy-js';
import { Ziggy } from './ziggy';
import { route } from 'ziggy-js';

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

        const app = createApp({ render: () => h(App, props) });

        // Глобальный обработчик ошибок Vue
        app.config.errorHandler = (err, instance, info) => {
            console.error('Глобальная ошибка Vue:', err);
            console.error('Компонент:', instance);
            console.error('Информация:', info);

            // 🔥 ЗАКОММЕНТИРОВАНО - endpoint не существует
            /*
            if (window.axios) {
                window.axios.post('/api/log-error', {
                    error: err.toString(),
                    stack: err.stack,
                    component: instance?.$options.name || 'Unknown',
                    info: info
                }).catch(() => {});
            }
            */

            // Показываем уведомление пользователю
            if (window.toast) {
                window.toast.error('Произошла ошибка. Мы уже работаем над её исправлением.');
            }
        };

        // Обработчик предупреждений Vue (для разработки)
        app.config.warnHandler = (msg, instance, trace) => {
            console.warn('Vue предупреждение:', msg);
        };

        // 🔥 ИСПРАВЛЕННАЯ ДИРЕКТИВА CLICK-OUTSIDE
        app.directive('click-outside', {
            mounted(el, binding) {
                // Проверяем, что el существует
                if (!el) return;
                
                el._clickOutsideHandler = function(event) {
                    // Дополнительная проверка существования элемента
                    if (!el || !document.body.contains(el)) {
                        return;
                    }
                    
                    // Проверяем, что клик был вне элемента
                    if (!(el === event.target || el.contains(event.target))) {
                        // Проверяем, что binding.value это функция
                        if (binding.value && typeof binding.value === 'function') {
                            binding.value(event);
                        }
                    }
                };
                
                // Используем setTimeout для избежания конфликтов при монтировании
                setTimeout(() => {
                    document.addEventListener('click', el._clickOutsideHandler);
                }, 0);
            },
            unmounted(el) {
                // Проверяем существование перед удалением
                if (el && el._clickOutsideHandler) {
                    document.removeEventListener('click', el._clickOutsideHandler);
                    delete el._clickOutsideHandler;
                }
            }
        });

        // Директива для автофокуса
        app.directive('focus', {
            mounted(el) {
                if (el && typeof el.focus === 'function') {
                    el.focus();
                }
            }
        });

        // Директива для отслеживания изменения размера элемента
        app.directive('resize', {
            mounted(el, binding) {
                if (!el || !binding.value) return;
                
                try {
                    const resizeObserver = new ResizeObserver(entries => {
                        if (binding.value && typeof binding.value === 'function') {
                            binding.value(entries[0]);
                        }
                    });
                    resizeObserver.observe(el);
                    el._resizeObserver = resizeObserver;
                } catch (error) {
                    console.warn('ResizeObserver не поддерживается:', error);
                }
            },
            unmounted(el) {
                if (el && el._resizeObserver) {
                    el._resizeObserver.disconnect();
                    delete el._resizeObserver;
                }
            }
        });

        // Директива для копирования в буфер обмена
        app.directive('clipboard', {
            mounted(el, binding) {
                if (!el) return;
                
                el._clickHandler = () => {
                    const text = binding.value || el.textContent;
                    
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(text).then(() => {
                            if (window.toast) {
                                window.toast.success('Скопировано в буфер обмена!');
                            }
                        }).catch(err => {
                            console.error('Ошибка копирования:', err);
                            // Fallback для старых браузеров
                            fallbackCopyTextToClipboard(text);
                        });
                    } else {
                        // Fallback для старых браузеров
                        fallbackCopyTextToClipboard(text);
                    }
                };
                
                el.addEventListener('click', el._clickHandler);
            },
            unmounted(el) {
                if (el && el._clickHandler) {
                    el.removeEventListener('click', el._clickHandler);
                    delete el._clickHandler;
                }
            }
        });

        // Директива для ленивой загрузки изображений
        app.directive('lazy', {
            mounted(el) {
                if (!el || !('IntersectionObserver' in window)) return;
                
                const imageObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            if (img.dataset.src) {
                                img.src = img.dataset.src;
                                img.classList.remove('lazy');
                                observer.unobserve(img);
                            }
                        }
                    });
                });
                
                imageObserver.observe(el);
                el._imageObserver = imageObserver;
            },
            unmounted(el) {
                if (el && el._imageObserver) {
                    el._imageObserver.disconnect();
                    delete el._imageObserver;
                }
            }
        });

        app.use(plugin);
        app.use(ZiggyVue, Ziggy);
        app.use(pinia);

        app.config.globalProperties.route = route;

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
        console.error('Fallback: Ошибка копирования', err);
    }
    
    document.body.removeChild(textArea);
}

// Глобальный обработчик ошибок JavaScript
window.addEventListener('error', (event) => {
    console.error('Глобальная JS ошибка:', event.error);
});

// Обработчик необработанных Promise rejection
window.addEventListener('unhandledrejection', (event) => {
    console.error('Необработанный Promise rejection:', event.reason);
    event.preventDefault();
});