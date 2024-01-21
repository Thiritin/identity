<script setup>
import ToogleDarkMode from "../../Layouts/ToogleDarkMode.vue";
import {Link} from "@inertiajs/vue3";

const props = defineProps({
    navigation: Object,
    darkMode: Boolean,
    toggleDarkMode: Function,
});
</script>

<template>
    <nav aria-label="Footer" class="-mx-5 -my-2 flex flex-wrap items-center justify-center">
        <div v-for="item in navigation.main" :key="item.name" class="px-5 py-2">
            <Link
v-if="item.href == null" :href="item.link"
                  :target="[item.newTab ? '_blank' : '_top']"
                  class="text-base text-gray-500 hover:text-gray-900 dark:hover:text-gray-400">
                {{ item.name }}
            </Link>
            <a
v-else :href="item.href" :target="[item.newTab ? '_blank' : '_top']"
               class="text-base text-gray-500 hover:text-gray-900 dark:hover:text-gray-400">
                {{ item.name }} </a>
        </div>
        <toogleDarkMode :dark-mode='darkMode' :toggle-dark-mode='toggleDarkMode'/>
    </nav>
</template>

<style scoped>

</style>
