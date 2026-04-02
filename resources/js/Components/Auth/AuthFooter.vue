<script setup>
import ToogleDarkMode from '../ToogleDarkMode.vue'
import {Link} from '@inertiajs/vue3'
import {computed} from "vue";

const props = defineProps({
    navigation: Object,
    darkMode: Boolean,
    toggleDarkMode: Function,
})

const visibleNavigation = computed(() => {
    return props.navigation.main.filter(item => item.visible())
})
</script>

<template>
    <nav
        aria-label="Footer"
        class="flex flex-wrap items-center justify-center gap-x-4 gap-y-2"
    >
        <template v-for="item in visibleNavigation" :key="item.name">
            <Link
                v-if="item.href == null"
                :href="item.link"
                :target="[item.newTab ? '_blank' : '_top']"
                class="text-sm text-gray-500 hover:text-gray-900 dark:hover:text-gray-400"
            >
                {{ item.name }}
            </Link>
            <a
                v-else
                :href="item.href"
                :target="[item.newTab ? '_blank' : '_top']"
                class="text-sm text-gray-500 hover:text-gray-900 dark:hover:text-gray-400"
            >
                {{ item.name }}
            </a>
        </template>
        <toogleDarkMode
            :dark-mode="darkMode"
            :toggle-dark-mode="toggleDarkMode"
        />
    </nav>
</template>

<style scoped></style>
