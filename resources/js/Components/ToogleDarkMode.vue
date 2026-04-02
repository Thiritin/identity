<template>
    <div>
        <Sun
v-if='darkMode' class='w-5 text-gray-500 hover:text-gray-900 dark:hover:text-gray-400 fill-current cursor-pointer' title='Disable Dark Mode'
             @click='toggleDarkMode'></Sun>
        <MoonStars
v-else class='w-5 text-gray-500 hover:text-gray-900 dark:hover:text-gray-400 fill-current cursor-pointer' title='Enable Dark Mode'
                   @click='toggleDarkMode'></MoonStars>
    </div>
</template>
<script>
    import MoonStars from '@/Components/Icons/MoonStars.vue'
    import Sun from '@/Components/Icons/Sun.vue'

    export default {
        name: 'ToogleDarkMode',
        components: { MoonStars, Sun },
        props: {
            darkMode: {},
            toggleDarkMode: {},
        },
    }
</script>
