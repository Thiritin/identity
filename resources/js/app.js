import './bootstrap';
import '../css/app.css';

// Import modules...
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/inertia-vue3';
import { InertiaProgress } from '@inertiajs/progress';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy/dist/vue.m';
import { __, getLocale, locales, setLocale, trans, transChoice } from 'matice'

const appName = window.document.getElementsByTagName('title')[0]?.innerText || 'Identity';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, app, props, plugin }) {
        return createApp({ render: () => h(app, props) })
            .use(plugin)
            .use(ZiggyVue, Ziggy)
            .mixin({methods: {route}})
            .mixin({
                methods: {
                    $trans: trans,
                    $__: __,
                    $transChoice: transChoice,
                    $setLocale(locale) {
                        setLocale(locale);
                        this.$forceUpdate() // Refresh the vue instance(The whole app in case of SPA) after the locale changes.
                    },
                    // The current locale
                    $locale() {
                        return getLocale()
                    },
                    // A listing of the available locales
                    $locales() {
                        return locales()
                    }
                },
            })
            .mount(el);
    },
});

/*

*/
InertiaProgress.init({color: '#4B5563'});

