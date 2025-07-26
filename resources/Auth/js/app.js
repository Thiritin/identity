import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'

const appName = window.document.getElementsByTagName('title')[0]?.innerText || 'Laravel'

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => {
        // Handle domain-prefixed page names by removing the domain prefix
        // Convert 'Auth/Error' -> 'Error', 'Auth/Login' -> 'Login', etc.
        const pageName = name.startsWith('Auth/') ? name.substring(5) : name;
        const targetPath = `./Pages/${pageName}.vue`;
        
        return resolvePageComponent(targetPath, import.meta.glob('./Pages/**/*.vue'));
    },
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el)
    },
    progress: {
        color: '#4B5563',
    },
})
