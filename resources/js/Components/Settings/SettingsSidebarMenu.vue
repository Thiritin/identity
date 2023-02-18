<template>
    <aside class='py-6 lg:col-span-3'>
        <nav class='space-y-1'>
            <inertia-link v-for='item in menuItems' :key='item.name' :href='item.route'
                          :aria-current="pathActive(item) ? 'page' : undefined"
                          :class="[pathActive(item) ? 'bg-primary-200 text-primary-700 hover:bg-primary-50 hover:text-primary-700' : 'text-gray-900 hover:bg-gray-100 hover:text-gray-900', 'group px-3 py-2 flex items-center text-sm font-medium']">
                <component :is='item.icon'
                           :class="[pathActive(item) ? 'text-primary-500 group-hover:text-primary-500' : 'text-gray-400 group-hover:text-gray-500', 'flex-shrink-0 -ml-1 mr-3 h-6 w-6']"
                           aria-hidden='true'/>
                <span class='truncate'>{{ $trans(item.name) }}</span>
            </inertia-link>
        </nav>
    </aside>
</template>

<script>
export default {
    name: 'SettingsSidebarMenu',
    props: {
        menuItems: Object,
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
