import './bootstrap'
import '../css/app.css'
import 'github-markdown-css/github-markdown-light.css'

// Import modules...
import {createApp, h} from 'vue'
import {createInertiaApp, router} from '@inertiajs/vue3'
import {ZiggyVue} from '../../vendor/tightenco/ziggy/dist/vue.m'
import {i18nVue} from 'laravel-vue-i18n'
import {resolvePageComponent} from "laravel-vite-plugin/inertia-helpers";
import VueCookies from 'vue-cookies'
import {toast} from 'vue-sonner'
import 'vue-sonner/style.css'


import.meta.glob([
    '../assets/**',
])

const appName = window.document.getElementsByTagName('title')[0]?.innerText || 'Identity'

router.on('flash', (event) => {
    const t = event.detail.flash.toast
    if (t) {
        t.type === 'error' ? toast.error(t.message) : toast.success(t.message)
    }
})

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue', { eager: true })),
    progress: {
        color: '#4B5563',
    },
    setup({el, App, props, plugin}) {
        return createApp({render: () => h(App, props)})
            .use(plugin)
            .use(ZiggyVue)
            .use(VueCookies, {})
            .use(i18nVue, {
                lang: props.initialPage.props?.locale ?? 'en',
                resolve: async (lang) => {
                    const langs = import.meta.glob('../lang/*.json')
                    return await langs[`../lang/${lang}.json`]()
                },
            })
            .mixin({methods: {route}})
            .mount(el)
    },
})
