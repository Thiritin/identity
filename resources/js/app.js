import './bootstrap'
import '../css/app.css'
import 'primeicons/primeicons.css'
import 'github-markdown-css/github-markdown-light.css'

// Import modules...
import {createApp, h} from 'vue'
import {createInertiaApp} from '@inertiajs/vue3'
import {ZiggyVue} from '../../vendor/tightenco/ziggy/dist/vue.m'
import {__, getLocale, locales, setLocale, trans, transChoice} from 'matice'
import {resolvePageComponent} from "laravel-vite-plugin/inertia-helpers";
import VueCookies from 'vue-cookies'
import PrimeVue from 'primevue/config';
import Aura from "./presets/aura";
import ToastService from 'primevue/toastservice';


import.meta.glob([
    '../assets/**',
])

const appName = window.document.getElementsByTagName('title')[0]?.innerText || 'Identity'

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    progress: {
        color: '#4B5563',
    },
    setup({el, App, props, plugin}) {
        return createApp({render: () => h(App, props)})
            .use(plugin)
            .use(ZiggyVue)
            .use(PrimeVue, {
                unstyled: true,
                pt: Aura,
            })
            .use(VueCookies, {})
            .use(ToastService)
            .mixin({methods: {route}})
            .mixin({
                methods: {
                    $trans: trans,
                    $__: __,
                    $transChoice: transChoice,
                    $setLocale(locale) {
                        setLocale(locale)
                        this.$forceUpdate() // Refresh the vue instance(The whole app in case of SPA) after the locale changes.
                    },
                    // The current locale
                    $locale() {
                        return getLocale()
                    },
                    // A listing of the available locales
                    $locales() {
                        return locales()
                    },
                },
            })
            .mount(el)
    },
})
