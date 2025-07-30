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
    resolve: (name) => {
        // Handle domain-prefixed page names by routing to the correct domain
        if (name.startsWith('Auth/')) {
            const pageName = name.substring(5); // Remove 'Auth/' prefix
            return resolvePageComponent(`./pages/${pageName}.vue`, import.meta.glob('./pages/**/*.vue'));
        } else if (name.startsWith('User/')) {
            const pageName = name.substring(5); // Remove 'User/' prefix
            return resolvePageComponent(`./pages/${pageName}.vue`, import.meta.glob('./pages/**/*.vue'));
        } else if (name.startsWith('Staff/')) {
            const pageName = name.substring(6); // Remove 'Staff/' prefix
            return resolvePageComponent(`./pages/${pageName}.vue`, import.meta.glob('./pages/**/*.vue'));
        } else if (name.startsWith('Admin/')) {
            const pageName = name.substring(6); // Remove 'Admin/' prefix
            return resolvePageComponent(`../Admin/js/Pages/${pageName}.vue`, import.meta.glob('../Admin/js/Pages/**/*.vue'));
        } else {
            // Default to User domain for pages without prefix
            return resolvePageComponent(`./pages/${name}.vue`, import.meta.glob('./pages/**/*.vue'));
        }
    },
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
