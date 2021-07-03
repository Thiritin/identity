require('./bootstrap');

// Import modules...
import {createApp, h} from 'vue';
import {App as InertiaApp, plugin as InertiaPlugin} from '@inertiajs/inertia-vue3';
import {InertiaProgress} from '@inertiajs/progress';
import {__, getLocale, locales, setLocale, trans, transChoice} from "matice"

const el = document.getElementById('app');

createApp({
    render: () =>
        h(InertiaApp, {
            initialPage: JSON.parse(el.dataset.page),
            resolveComponent: (name) => require(`./Pages/${name}`).default,
        }),
})
    .mixin({ methods: { route } })
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


InertiaProgress.init({ color: '#4B5563' });
