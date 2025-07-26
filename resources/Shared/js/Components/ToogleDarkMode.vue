<template>
    <div>
        <Sun
v-if='darkMode' class='w-5 text-gray-600 fill-current cursor-pointer' title='Disable Dark Mode'
             @click='toggleDarkMode'></Sun>
        <MoonStars
v-else class='w-5 text-gray-600 fill-current cursor-pointer' title='Enable Dark Mode'
                   @click='toggleDarkMode'></MoonStars>
    </div>
</template>
<script>
    import MoonStars from './Icons/MoonStars.vue'
    import Sun from './Icons/Sun.vue'

    export default {
        name: 'ToogleDarkMode',
        components: { MoonStars, Sun },
        props: {
            darkMode: {},
            toggleDarkMode: {},
        },
    }
</script>
