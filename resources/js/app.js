import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { Link, Head } from '@inertiajs/vue3';
import { applyTheme } from './theme';

const appName = import.meta.env.VITE_APP_NAME || 'Unikosa';

createInertiaApp({
    title: (title) => title ? `${title} - ${appName}` : appName,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        // Apply admin theme settings (accent, font, light/dark) before mount,
        // then keep them in sync as shared settings change across visits.
        applyTheme(props.initialPage.props.settings);
        router.on('navigate', (event) => applyTheme(event.detail.page.props.settings));

        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue, Ziggy)
            .component('Link', Link)
            .component('Head', Head)
            .mount(el);
    },
    progress: {
        color: '#F59E0B',
    },
});
