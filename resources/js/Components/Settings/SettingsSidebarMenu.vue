<template>
    <aside class='py-6 lg:col-span-3'>
        <nav class='space-y-1'>
            <Link v-for='item in menuItems' :key='item.name' :href='item.route'
                  :aria-current="pathActive(item) ? 'page' : undefined"
                  :class="[pathActive(item) ? 'bg-primary-200 dark:bg-primary-700 text-primary-700 dark:text-primary-200 hover:bg-primary-50 dark:hover:text-primary-100 hover:text-primary-700' : 'text-gray-900 dark:text-primary-200 dark:hover:text-primary-100 hover:bg-gray-100 dark:hover:bg-primary-700 hover:text-gray-900', 'group px-3 py-2 flex items-center text-sm font-medium']">
                <span class='truncate'>{{ $trans(item.name) }}</span>
            </Link>
        </nav>
    </aside>
</template>

<script>
import {Link} from "@inertiajs/vue3";

export default {
    name: 'SettingsSidebarMenu',
    props: {
        menuItems: Object,
    },
    components: {
        Link,
    },
    methods: {
        pathActive(item) {
            return window.location.href === route("settings." + item.route);
        }
    }
}
</script>

<style scoped>

</style>
