import './bootstrap'
import '../css/app.css'

// Import modules...
import {createApp, h} from 'vue'
import {createInertiaApp} from '@inertiajs/vue3'
import {ZiggyVue} from '../../vendor/tightenco/ziggy/dist/vue.m'
import {__, getLocale, locales, setLocale, trans, transChoice} from 'matice'
import VueCookies from 'vue-cookies'
import AppLayout from "./Layouts/AppLayout.vue";
import PrimeVue from "primevue/config";
import Lara from '../assets/presets/lara'

import.meta.glob([
    '../assets/**',
])

const appName = window.document.getElementsByTagName('title')[0]?.innerText || 'Identity'

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: name => {
        const pages = import.meta.glob('./Pages/**/*.vue', {eager: true})
        let page = pages[`./Pages/${name}.vue`]
        page.default.layout = page.default.layout || AppLayout
        return page
    },
    progress: {
        color: '#4B5563',
    },
    setup({el, App, props, plugin}) {
        return createApp({render: () => h(App, props)})
            .use(plugin)
            .use(ZiggyVue)
            .use(VueCookies, {})
            .use(PrimeVue, {
                unstyled: true,
                pt: Lara
            })
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
