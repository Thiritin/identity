<template>
    <div>
        <Sun @click='toggleDarkMode' v-if='darkMode' class='w-5 text-gray-600 fill-current cursor-pointer'
             title='Disable Dark Mode'></Sun>
        <MoonStars @click='toggleDarkMode' v-else class='w-5 text-gray-600 fill-current cursor-pointer'
                   title='Enable Dark Mode'></MoonStars>
    </div>
</template>
<script>
    import MoonStars from '@/Components/Icons/MoonStars.vue'
    import Sun from '@/Components/Icons/Sun.vue'

    export default {
        name: 'toogleDarkMode',
        components: { MoonStars, Sun },
        props: {
            darkMode: {},
            toggleDarkMode: {},
        },
    }
</script>
