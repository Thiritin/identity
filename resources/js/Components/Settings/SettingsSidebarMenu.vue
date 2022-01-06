<template>
    <aside class='py-6 lg:col-span-3'>
        <nav class='space-y-1'>
            <a v-for='item in menuItems' :key='item.name' :href='item.route'
               :class="[item.current ? 'bg-primary-50 border-primary-500 text-primary-700 hover:bg-primary-50 hover:text-primary-700' : 'border-transparent text-gray-900 hover:bg-gray-50 hover:text-gray-900', 'group border-l-4 px-3 py-2 flex items-center text-sm font-medium']"
               :aria-current="item.current ? 'page' : undefined">
                <component :is='item.icon'
                           :class="[item.current ? 'text-primary-500 group-hover:text-primary-500' : 'text-gray-400 group-hover:text-gray-500', 'flex-shrink-0 -ml-1 mr-3 h-6 w-6']"
                           aria-hidden='true' />
                <span class='truncate'>
                    {{ item.name }}
                  </span>
            </a>
        </nav>
    </aside>
</template>

<script>
    export default {
        name: 'SettingsSidebarMenu',
        props: {
            menuItems: Object,
        }
    }
</script>

<style scoped>

</style>
