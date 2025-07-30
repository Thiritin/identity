import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import PrimeVue from 'primevue/config';

const appName = window.document.getElementsByTagName('title')[0]?.innerText || 'Laravel'

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => {
        // Strip the domain prefix (User/) from page names
        const pageName = name.replace(/^User\//, '');
        return resolvePageComponent(`../pages/${pageName}.vue`, import.meta.glob('../pages/**/*.vue'));
    },
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(PrimeVue, {
                unstyled: true
            })
            .mount(el)
    },
    progress: {
        color: '#4B5563',
    },
})
