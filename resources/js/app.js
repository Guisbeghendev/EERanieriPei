import '../css/app.css';
import './bootstrap';
import 'flowbite'; // Adicionada a importação do Flowbite

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
// REMOVIDA: import { ZiggyVue } from '../../vendor/tightenco/ziggy'; // Confirma a remoção

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            // REMOVIDA: .use(ZiggyVue) // Confirma a remoção
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
