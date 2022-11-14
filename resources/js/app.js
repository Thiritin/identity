require('./bootstrap');
import '../css/app.css';

// Import modules...
import {createApp, h} from 'vue';
import {App as InertiaApp, plugin as InertiaPlugin} from '@inertiajs/inertia-vue3';
import {InertiaProgress} from '@inertiajs/progress';
import {__, getLocale, locales, setLocale, trans, transChoice} from "matice"
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

const el = document.getElementById('app');

createInertiaApp({
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el)
    },
})
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
    .use(InertiaPlugin)
    .mount(el);


InertiaProgress.init({color: '#4B5563'});

